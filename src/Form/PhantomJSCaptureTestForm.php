<?php

namespace Drupal\phantomjs_capture\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PhantomJSCaptureTestForm
 *
 * Provide a form to test the output of PhantomJS Capture.
 *
 * @package Drupal\phantomjs_capture\Form
 */
class PhantomJSCaptureTestForm extends FormBase {

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
      '#description' => t('Absolute URL to the homepage that should be capture (it has to be a complete URL with http://).'),
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
      '#prefix' => '<div id="phantomjs-capture-test-result">',
      '#suffix' => '</div>',
      '#markup' => '',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Capture'),
      "#ajax" => array(
        "callback" => "phantomjs_capture_test_submit",
        "wrapper" => "phantomjs-capture-test-result",
        "method" => 'replace',
        "effect" => "fade",
      ),
    );

    return $form;
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
    $config = $this->config('phantomjs_capture.settings');
    $values = $form_state->getValues();

    // Build urls and destination.
    $url = $values['url'];
    $file = 'test' . $values['format'];
    $dest = \Drupal::config('system.file')->get('default_scheme') . '://' . $config->get('destination');

    // Get the screen shot and create success/error message.
    $output = '<div class="messages status"><ul><li>' . t('File have been generated. You can get it !url', array('!url' => l(t('here'), file_create_url($dest . '/' . $file)))) . '.</ul></li></div>';
    if (!phantomjs_capture_screen($url, $dest, $file)) {
      $output = '<div class="messages error"><ul><li>' . t('The address entered could not be retrieved.') . '</ul></li></div>';
    }

    // Return.
    return array(
      'phantomjs_capture_test' => array(
        'result' => array(
          '#prefix' => '<div id="phantomjs-capture-test-result">',
          '#suffix' => '</div>',
          '#markup' => $output,
        ),
      ),
    );
  }
}