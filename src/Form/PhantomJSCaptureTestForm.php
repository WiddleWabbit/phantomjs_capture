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
      '#type' => 'button',
      '#value' => t('Capture'),
      '#ajax' => array(
        'callback' => array($this, 'capture'),
        'wrapper' => 'capture-result',
        'method' => 'replace',
        'effect' => 'fade',
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

  public function capture(array &$form, FormStateInterface $form_state) {
    $config = $this->config('phantomjs_capture.settings');
    $values = $form_state->getValues();

    // Build urls and destination.
    $url = $values['url'];
    $file = 'capture_test' . $values['format'];
    $destination = \Drupal::config('system.file')->get('default_scheme') . '://' . $config->get('destination');

    // Get the screen shot and create success/error message.
    $output = '<div class="messages status"><ul><li>' . $this->t('The file has been generated. You can view it <a href=":url">here</a>', array(':url' => file_create_url($destination . '/' . $file))) . '.</ul></li></div>';

    if (!phantomjs_capture_screen($url, $file)) {
      $output = '<div class="messages error"><ul><li>' . $this->t('The address entered could not be retrieved, or phantomjs could not perform the action.') . '</ul></li></div>';
    }

    return array(
      'phantomjs_capture_test' => array(
        'result' => array(
          '#prefix' => '<div id="capture-result">',
          '#suffix' => '</div>',
          '#markup' => $output,
        ),
      ),
    );
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // empty
  }
}