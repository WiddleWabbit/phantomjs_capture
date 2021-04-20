<?php

/**
 * Contains \Drupal\download_flyer\Controller\DownloadFlyerController.
 */

namespace Drupal\download_flyer\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class DownloadFlyerController
 *
 * Performs actions for the flyer download route.
 *
 * @package Drupal\download_flyer\Controller
 */
class DownloadFlyerController extends ControllerBase {

    /**
     * {@inheritdoc}
     */
    public function getTitle() {
        $nid = \Drupal::routeMatch()->getParameter('node');
        $node = \Drupal\node\Entity\Node::load($nid);
        return $node->getTitle() . ' Download';
    }

    /**
     *
     *
     *
     */
    public function downloadFile($file) {
        
    }

}
