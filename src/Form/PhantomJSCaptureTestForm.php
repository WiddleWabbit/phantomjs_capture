<?php

namespace Drupal\phantomjs_capture\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\phantomjs_capture\PhantomJSCaptureHelperInterface;
use Drupal\Core\Url;

/**
 * Class PhantomJSCaptureTestForm
 *
 * Provide a form to test the output of PhantomJS Capture.
 *
 * @package Drupal\phantomjs_capture\Form
 */
class PhantomJSCaptureTestForm extends FormBase {

  /**
   * @var PhantomJSCaptureHelper
   */
  private $captureHelper;

  /**
   * PhantomJSCaptureTestForm constructor.
   * @param PhantomJSCaptureHelperInterface $phantomjs_capture_helper
   */
  public function __construct(PhantomJSCaptureHelperInterface $phantomjs_capture_helper) {
    $this->captureHelper = $phantomjs_capture_helper;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('phantomjs_capture.helper'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'phantomjs_capture_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['url'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('URL'),
      '#required' => TRUE,
      '#description' => $this->t('Absolute URL to the page that you want to capture (it must to be a complete URL with http://). Certain kinds of URLs, such as ones that begin with a # symbol (SPAs or some search engine queries) may not work.'),
      '#default_value' => 'https://www.drupal.org',
    );

    $form['format'] = array(
      '#type' => 'select',
      '#title' => 'File format',
      '#options' => array(
        '.png' => 'PNG',
        '.jpg' => 'JPEG',
        '.pdf' => 'PDF',
      ),
    );

    $form['result'] = array(
      '#prefix' => '<div id="capture-result">',
      '#suffix' => '</div>',
      '#markup' => '',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Capture'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // empty
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the session of the current user
    $session_manager = \Drupal::service('session_manager');
    $session_id = $session_manager->getId();
    $session_name = $session_manager->getName();

    // Write the session information to a file where its not accessible on the web but the script can read it
    $filepath = $_SERVER["DOCUMENT_ROOT"] . "/../private_tmp/" . $session_name;
    // Delete any preexisting file
    if (file_exists($filepath)) {
        try {
            unlink($filepath);
            \Drupal::logger('phantomjs_capture')->notice("Deleted old session file at " . $filepath);
            file_put_contents($filepath, $session_id);
        } catch (Exception $e) {
            \Drupal::logger('phantomjs_capture')->error("Unable to delete previous session file at " . $filepath);
        }
    // Write the session file
    } else {
        file_put_contents($filepath, $session_id);
    }

    // Get the hostname of the site
    $host = \Drupal::request()->getHost();
    $host = '.' . $host;

    $config = $this->config('phantomjs_capture.settings');
    $values = $form_state->getValues();
    $url = Url::fromUri($values['url']);

    $file = 'capture_test' . $values['format'];
    $destination = \Drupal::config('system.file')->get('default_scheme') . '://' . $config->get('destination') . '/test/' . REQUEST_TIME;
    $file_url = file_create_url($destination . '/' . $file);

    if ($this->captureHelper->capture($url, $destination, $file, $session_name, $host)) {
      drupal_set_message($this->t('The file has been generated! You can view it <a href=":url">here</a>', array(':url' => $file_url)));
    } else {
      drupal_set_message('The address entered could not be retrieved, directory was not writeable, or phantomjs could not perform the action requested.', 'error');
    }

    // Delete the session information now that the page has been captured
    if (file_exists($filepath)) {
        try {
            unlink($filepath);
        } catch (Exception $e) {
            \Drupal::logger('phantomjs_capture')->error("Unable to delete previous session file at " . $filepath);
        }
    } else {
        \Drupal::logger('phantomjs_capture')->error("Unable to find session file to delete (" . $filepath . ")");
    }

  }
}
