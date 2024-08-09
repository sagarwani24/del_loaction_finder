<?php

namespace Drupal\dhl_location_finder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implement form for location finder.
 */
class LocationFinderForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dhl_location_finder_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['container'] = [
      '#type' => 'details',
      '#title' => $this->t('Find Location'),
      '#attributes' => [
        'class' => [
          'container-inline',
        ],
        'open' => TRUE,
      ],
    ];
    $form['container']['country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country'),
      '#required' => TRUE,
      '#size' => 30,
    ];
    $form['container']['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#required' => TRUE,
      '#size' => 30,
    ];
    $form['container']['post_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Post Code'),
      '#required' => TRUE,
      '#size' => 30,
    ];
    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Find Location'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild('TRUE');
  }

}
