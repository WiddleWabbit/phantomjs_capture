<?php

namespace Drupal\phantomjs_capture\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

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
    return ['phantomjs_capture.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['phantomjs_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('PhantomJS settings'),
      '#collapsible' => FALSE,
    );

    $form['phantomjs_settings']['phantomjs_capture_binary'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => t('Path to phantomJS'),
      '#description' => t('This module requries that you install PhantomJS on your server and enter the path to the executable. The program is not include in the module due to linces and operation system constrains. See !url for information about download.', array(
        '!url' => l('PhantomJs.org', 'http://phantomjs.org/'),
      )),
      '#default_value' => variable_get('phantomjs_capture_binary', _phantomjs_capture_get_binray()),
    );

    $form['phantomjs_settings']['phantomjs_capture_dest'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => t('Default destination'),
      '#description' => t('The default destination for screenshots captures with PhantomJS'),
      '#default_value' => variable_get('phantomjs_capture_dest', 'phantomjs'),
    );

    $form['phantomjs_settings']['phantomjs_capture_script'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => t('PhantomJS capture script'),
      '#description' => t('The script used by PhantomJS to capture the screen. It captures full HD images (1920 x 1080).'),
      '#default_value' => variable_get('phantomjs_capture_script', drupal_get_path('module', 'phantomjs_capture') . '/js/phantomjs_capture.js'),
    );

    $form['phantomjs_capture_test'] = array(
      '#type' => 'fieldset',
      '#title' => t('Phantom JS test'),
      '#description' => t('You can use the form in this section to test your installation of PhantomJS.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#tree' => TRUE,
    );

    $form['phantomjs_capture_test']['url'] = array(
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#description' => t('Absolute URL to the homepage that should be capture (it has to be a complet URL with http://).'),
      '#default_value' => 'http://www.google.com',
    );

    $form['phantomjs_capture_test']['format'] = array(
      '#type' => 'select',
      '#title' => 'File format',
      '#options' => array(
        '.png' => 'png',
        '.jpg' => 'jpg',
        '.pdf' => 'pdf',
      ),
    );

    $form['phantomjs_capture_test']['result'] = array(
      '#prefix' => '<div id="phantomjs-capture-test-result">',
      '#suffix' => '</div>',
      '#markup' => '',
    );

    $form['phantomjs_capture_test']['button'] = array(
      '#type' => 'button',
      '#value' => t('Capture'),
      "#ajax" => array(
        "callback" => "phantomjs_capture_test_submit",
        "wrapper" => "phantomjs-capture-test-result",
        "method" => 'replace',
        "effect" => "fade",
      ),
    );

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

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