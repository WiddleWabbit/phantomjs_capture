<?php

/**
 * Contains \Drupal\download_flyer\Controller\DownloadFlyerController.
 */

namespace Drupal\download_flyer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;

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
    public function getTitle($node) {
        $nodedata = \Drupal\node\Entity\Node::load($node);
        return $nodedata->getTitle() . ' Download';
    }

    /**
     * Access Handler for the Download Flyer Tab
     *
     * @return \Drupal\Core\Access\AccessResult
     */
    public function accessRestriction($node) {
        $nodedata = \Drupal\node\Entity\Node::load($node);
        if ($nodedata->bundle() == 'custom_design_flyer') {
            if (\Drupal\user\Entity\User::load(\Drupal::currentUser()->id())->hasPermission('edit any custom_design_flyer content')) {
                return AccessResult::allowed();
            }
        } else {
            return AccessResult::forbidden();
        }
    }

}
