<?php

namespace Drupal\rareimagery_x_activity\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for RareImagery X Activity routes.
 */
class XActivityController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
