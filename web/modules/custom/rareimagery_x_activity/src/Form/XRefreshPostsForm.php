<?php

namespace Drupal\rareimagery_x_activity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a RareImagery X Activity form.
 */
class XRefreshPostsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rareimagery_x_activity_refresh_posts';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh Posts'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('Posts refreshed.'));
  }

}
