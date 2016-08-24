<?php

namespace Drupal\phantomjs_capture\Controller;

use Drupal\Core\Controller\ControllerBase;

class PhantomJSCaptureInfoController extends ControllerBase {

  public function index() {
    $config = $this->config('phantomjs_capture.settings');

    // If the binary is not given try the default path.
    if (is_null($config->get('binary') || !file_exists($config->get('binary')))) {
      return ['#markup' => 'PhantomJS binary was not found. Please install PhantomJS on the system.'];
    }

    // Execute PhantomJS to get its version, if PhantomJS was found.
    $output = array();
    exec($config->get('binary') . ' -v', $output);

    return ['#markup' => $this->t('PhantomJS binary detected, version is :version', [':version' => $output[0]])];
  }

}