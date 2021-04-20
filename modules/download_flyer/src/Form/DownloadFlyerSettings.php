<?php

/**  
 * @file  
 * Contains Drupal\download_flyer\Form\DownloadFlyerSettings.  
 */

namespace Drupal\download_flyer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DownloadFlyerSettings extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'download_flyer.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'download_flyer_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        
        $config = $this->config('download_flyer.settings');

        $form['save_dir'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('File Save Directory'),
            '#description' => $this->t('The path to save the flyers to, relative to the public directory. Do not include a trailing slash.'),
            '#default_value' => $config->get('save_dir'),
        );

        return parent::buildForm($form, $form_state);

    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

        // Get the base path to the site
//        $base_path = $_SERVER["DOCUMENT_ROOT"];

        // Validate the directory
        if (is_writeable(\Drupal::config('system.file')->get('default_scheme') . '://' . $form_state->getValue('save_dir')) != TRUE) {
            $form_state->setErrorByName('Save Directory', $this->t('Please enter directory that is writable for the save directory.'));
        }

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        parent::submitForm($form, $form_state);

        $this->config('download_flyer.settings')
            ->set('save_dir', $form_state->getValue('save_dir'))
            ->save();

    }

}
