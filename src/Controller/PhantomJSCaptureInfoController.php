<?php

namespace Drupal\phantomjs_capture\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\phantomjs_capture\PhantomJSCaptureHelper;

class PhantomJSCaptureInfoController extends ControllerBase {

  /**
   * @var PhantomJSCaptureHelper
   */
  private $captureHelper;

  /**
   * PhantomJSCaptureInfoController constructor.
   * @param PhantomJSCaptureHelper $capture_helper
   */
  public function __construct(PhantomJSCaptureHelper $capture_helper) {
    $this->captureHelper = $capture_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('phantomjs_capture.helper'));
  }

  /**
   * Return basic information about the phantomjs binary, if detected.
   * @return array
   */
  public function index() {
    $version = $this->captureHelper->getVersion();
    return ['#markup' => $this->t('PhantomJS binary detected, version is :version', [':version' => $version])];
  }

}