<?php

namespace Drupal\phantomjs_capture\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PhantomJSCaptureSettingsForm
 *
 * Provide a settings form for global settings of PhantomJS Capture.
 *
 * @package Drupal\phantomjs_capture\Form
 */
class PhantomJSCaptureSettingsForm extends ConfigFormBase {

  /**
   * The config factory service.
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity manager service.
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * PhantomCaptureSettingsForm constructor.
   * @param ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'phantomjs_capture_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['phantomjs_capture'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('phantomjs_capture.settings');
    $url = 'http://phantomjs.org';

    $form['settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('PhantomJS settings'),
      '#open' => TRUE,
    );

    $form['settings']['binary'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Path to PhantomJS binary'),
      '#description' => $this->t('This module requires that you install PhantomJS on your server and enter the path to the executable. The program is not include in the module due to licensing and operation system constraints. See <a href=":url">:url</a> for more information about downloading.', array(
        ':url' => $url,
      )),
      '#default_value' => $config->get('binary'),
    );

    $form['settings']['destination'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Default destination'),
      '#description' => $this->t('The default destination for screenshots captures with PhantomJS.'),
      '#default_value' => $config->get('destination'),
    );

    $form['settings']['script'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('PhantomJS capture script'),
      '#description' => $this->t('The script used by PhantomJS to capture the screen. It captures full HD images (1920 x 1080).'),
      '#default_value' => $config->get('script'),
    );

//    $form['phantomjs_capture_test'] = array(
//      '#type' => 'details',
//      '#title' => $this->t('Phantom JS test'),
//      '#description' => $this->t('You can use the form in this section to test your installation of PhantomJS.'),
//      '#collapsible' => TRUE,
//      '#collapsed' => TRUE,
//      '#tree' => TRUE,
//    );
//
//    $form['phantomjs_capture_test']['url'] = array(
//      '#type' => 'textfield',
//      '#title' => $this->t('URL'),
//      '#description' => $this->t('Absolute URL to the homepage that should be capture (it has to be a complet URL with http://).'),
//      '#default_value' => 'http://www.google.com',
//    );
//
//    $form['phantomjs_capture_test']['format'] = array(
//      '#type' => 'select',
//      '#title' => 'File format',
//      '#options' => array(
//        '.png' => 'png',
//        '.jpg' => 'jpg',
//        '.pdf' => 'pdf',
//      ),
//    );
//
//    $form['phantomjs_capture_test']['result'] = array(
//      '#prefix' => '<div id="phantomjs-capture-test-result">',
//      '#suffix' => '</div>',
//      '#markup' => '',
//    );
//
//    $form['phantomjs_capture_test']['button'] = array(
//      '#type' => 'button',
//      '#value' => $this->t('Capture'),
//      "#ajax" => array(
//        "callback" => "phantomjs_capture_test_submit",
//        "wrapper" => "phantomjs-capture-test-result",
//        "method" => 'replace',
//        "effect" => "fade",
//      ),
//    );

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Check that PhantomJS exists.
    if (!file_exists($values['binary'])) {
      $form_state->setError($form['settings']['binary'], $this->t('The PhantomJS binary was not found at the location given.'));
    }
//
    // Check that destination can be created.
    $destination = \Drupal::config('system.file')->get('default_scheme') . '://' . $values['destination'];
    if (!file_prepare_directory($destination, FILE_CREATE_DIRECTORY)) {
      $form_state->setError($form['settings']['destination'], t('The path was not writeable or could not be created.'));
    }

    // Check that capture script exists.
    if (!file_exists($values['script'])) {
      $form_state->setError($form['settings']['script'], $this->t('PhantomJS script was not found at the location given.'));
    }
//
//    // Remove test form.
//    unset($form_state['values']['phantomjs_capture_test']);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('phantomjs.settings')
      ->save();

    parent::submitForm($form, $form_state);
  }
}