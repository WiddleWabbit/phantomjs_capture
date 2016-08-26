<?php

namespace Drupal\phantomjs_capture\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\phantomjs_capture\PhantomJSCaptureHelper;

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
   * @param PhantomJSCaptureHelper $phantomjs_capture_helper
   */
  public function __construct(PhantomJSCaptureHelper $phantomjs_capture_helper) {
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
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#description' => t('Absolute URL to the page that you want to capture (it must to be a complete URL with http://).'),
      '#default_value' => 'http://www.google.com',
    );

    $form['format'] = array(
      '#type' => 'select',
      '#title' => 'File format',
      '#options' => array(
        '.png' => 'png',
        '.jpg' => 'jpg',
        '.pdf' => 'pdf',
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
    $config = $this->config('phantomjs_capture.settings');
    $values = $form_state->getValues();

    // Build urls and destination.
    $url = $values['url'];
    $file = 'capture_test' . $values['format'];
    $destination = \Drupal::config('system.file')->get('default_scheme') . '://' . $config->get('destination') . '/test/' . REQUEST_TIME;
    $file_url = file_create_url($destination . '/' . $file);

    if ($this->captureHelper->capture($url, $destination, $file)) {
      drupal_set_message($this->t('The file has been generated! You can view it <a href=":url">here</a>', array(':url' => $file_url)));
    } else {
      drupal_set_message('The address entered could not be retrieved, directory was not writeable, or phantomjs could not perform the action requested.', 'error');
    }
  }
}