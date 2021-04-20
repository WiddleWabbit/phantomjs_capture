<?php

namespace Drupal\download_flyer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\phantomjs_capture\PhantomJSCaptureHelperInterface;
use Drupal\dpi_converter\DpiConverterHelperInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DownloadFlyerForm
 *
 * Provide a form to download the current node as a flyer.
 *
 * @package Drupal\download_flyer\Form
 */
class DownloadFlyerForm extends FormBase {

    /**
     * @var PhantomJSCaptureHelper
     */
    private $captureHelper;

    /**
     * @var DpiConverterHelper
     */
    private $dpiConverter;

    /**
     * DownloadFlyerForm constructor
     * @param PhantomJSCaptureHelperInterface $phantomjs_capture_helper
     * @param DpiConverterHelperInterface $dpi_converter
     */
    public function __construct(PhantomJSCaptureHelperInterface $phantomjs_capture_helper, DpiConverterHelperInterface $dpi_converter_helper) {
        $this->captureHelper = $phantomjs_capture_helper;
        $this->dpiConverter = $dpi_converter_helper;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        // Method called to create a new instance of this class (form), thus this is where the arguments are supplied to the __construct
        return new static($container->get('phantomjs_capture.helper'), $container->get('dpi_converter.helper'));
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'download_flyer_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        // Get the node object
        $nid = \Drupal::routeMatch()->getParameter('node');
        $node = \Drupal\node\Entity\Node::load($nid);

        // Set template
        $form['#theme'] = 'download_flyer';

        // Advanced Collapsible Section
        $form['advanced'] = array(
            '#type' => 'details',
            '#title' => t('Advanced Settings'),
            '#description' => $this->t('Advanced configuration settings.'),
            '#open' => FALSE,
        );

        // URL Field
        $form['advanced']['url'] = array(
            '#type' => 'textarea',
            '#title' => t('URL'),
            '#required' => TRUE,
            '#description' => $this->t('Absolute URL to the page that you want to capture (it must to be a complete URL with https://).'),
            '#default_value' => $node->toUrl('canonical', array('absolute' => TRUE))->toString(),
        );

        // Whether or not to pass the session cookie for this user
        $form['advanced']['session_cookie'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Pass current user session?'),
            '#default_value' => TRUE,
            '#return_value' => TRUE,
            '#required' => FALSE,
        );

        // What CSS selection to restrict the render to on the page
        $form['advanced']['render_element'] = array(
            '#type' => 'textfield',
            '#title' => t('Element on page'),
            '#description' => $this->t('The specific element on page to restrict render to (CSS Selector).'),
            '#default_value' => '.' . str_replace('_', '-', $node->getType()),
            '#required' => TRUE,
        );

        // Horizontal DPI
        $form['advanced']['horizontal_dpi'] = array(
            '#type' => 'number',
            '#title' => $this->t('Horizontal DPI'),
            '#description' => $this->t('The horizontal DPI of the image to save'),
            '#min' => 72,
            '#max' => 300,
            '#step' => 1,
            '#size' => 3,
            '#default_value' => 150,
            '#required' => TRUE,
        );

        // Vertical DPI
        $form['advanced']['vertical_dpi'] = array(
            '#type' => 'number',
            '#title' => $this->t('Vertical DPI'),
            '#description' => $this->t('The vertical DPI of the image to save'),
            '#default_value' => 150,
            '#min' => 72,
            '#max' => 300,
            '#step' => 1,
            '#size' => 3,
            '#required' => TRUE,
        );

        // The name of the file to generate (not including extension)
        $form['advanced']['file_name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('File name'),
            '#description' => $this->t('The filename for the saved flyer (name only not extension)'),
            '#default_value' => 'FLYER_' . str_replace(" ", "_", $node->getTitle()),
            '#required' => TRUE,
        );

        $form['actions']['#type'] = 'actions';
        // The submit button
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Download'),
            '#button_type' => 'primary',
            '#attributes' => [
                'class' => ['cta-btn', 'cta-btn-bor', 'pull-left']
            ],
        );

        return $form;

    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

        // Fetch the admin configuration
        $config = $this->config('download_flyer.settings');

        // Validate URL is a URL
        if (filter_var($form_state->getValue('url'), FILTER_VALIDATE_URL) == false) {
            $form_state->setErrorByName('URL', $this->t('Please enter a valid URL.'));
        }

        // Validate this is an absolute URL
        $components = parse_url($form_state->getValue('url'));
        if (empty($components['host'])) {
            $form_state->setErrorByName('URL', $this->t('Please enter a complete URL.'));
        }

        // Validate URL is internal
        $host = \Drupal::request()->getHost();
        if (strcasecmp($components['host'], $host) != 0) {
            $form_state->SetErrorByName('URL', $this->t('Please enter a URL matching the current hostname.'));
        }

        // Get the filepath to save to and validate it exists
        $filepath = $config->get('save_dir');
        if (isset($filepath) == FALSE) {
            $form_state->SetErrorByName('Config', $this->t('No configured save directory for the rendered file.'));
        }
        
        // Validate there is a protected dir if one is required
        $protected_dir = \Drupal::config('phantomjs_capture.settings')->get('protected_dir');
        if (($form_state->getValue('session_cookie') == TRUE) && (isset($protected_dir) == FALSE)) {
            $form_state->SetErrorByName('Config', $this->t('No configured protected directory for user session tmp storage.'));
        }

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        // Fetch the admin configuration
        $config = $this->config('download_flyer.settings');

        // File path variables
        // Directory to save into using drupal's scheme i.e. public://
        $filepath = \Drupal::config('system.file')->get('default_scheme') . '://' . $config->get('save_dir');
        // The actual name of the file
        $filename = $form_state->getValue('file_name') . '.jpg';
        // URL Version i.e. domain/sites/default/files
        $savepath = file_create_url($filepath . '/' . $filename);
        // The actual file using drupal's scheme i.e. public://
        $redirectpath = \Drupal::config('system.file')->get('default_scheme') . '://' . $config->get('save_dir') . '/' . $filename;
        // Real server path to image
        $realpath = \Drupal::service('file_system')->realpath($redirectpath);

        $x_dpi = $form_state->getValue('horizontal_dpi');
        $y_dpi = $form_state->getValue('vertical_dpi');

        // Get the selector
        $element = $form_state->getValue('render_element');

        // Get the url
        $url = Url::fromUri($form_state->getValue('url'));

        // If pass user session is ticked
        $pass_session = $form_state->getValue('session_cookie');

        if ((isset($pass_session)) && ($pass_session == TRUE)) {

            // Get the session of the current user
            $session_manager = \Drupal::service('session_manager');
            $session_id = $session_manager->getId();
            $session_name = $session_manager->getName();

            // Write the session to a file
            $this->captureHelper->writeSession($session_name, $session_id);

            // Fetch the hostname so it can be passed for cookie creation
            $host = \Drupal::request()->getHost();
            $host = '.' . $host;

        // If the session is not required
        } else {

            $session_name = 'none';
            $host = 'none';

        }

        // Run the capture and return the file url in a message.
        if ($this->captureHelper->capture($url, $filepath, $filename, $session_name, $host, $element)) {
            if ($this->dpiConverter->convertImage($realpath, $x_dpi, $y_dpi)) {
                $headers = array(
                    'Content-Type' => 'image/' . pathinfo($realpath, PATHINFO_EXTENSION),
                    'Content-Disposition' => 'attachment;filename="' . $filename . '"'
                );
                $form_state->setResponse(new \Symfony\Component\HttpFoundation\BinaryFileResponse($redirectpath, 200, $headers, true));
            } else {
                drupal_set_message('Unable to convert image DPI correctly.', 'error');
            }

        } else {

            drupal_set_message('The address entered could not be retrieved, directory was not writeable, or phantomjs could not perform the action requested.', 'error');

        }

        // Delete the session file
        if (isset($session_id) == TRUE) {
            $this->captureHelper->deleteSession($session_name);
        }

    }

}
