<?php

/**
 * @file
 * Contains the settings for administrating the Test Form
 */


namespace Drupal\testform\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class TestSettingForm extends ConfigFormBase {
  
/**
 * {@inheritdoc}
 */
  public function getFormId() {
    return 'testform_admin_settings';
  }


/**
 * {@inheritdoc}
 */
  protected function getEditableConfigNames() {
    return [
       'testform.settings',
    ];
  }


/**
 * {@inheritdoc}
 */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $types = node_type_get_names();
    $config = $this->config('testform.settings');
    $form['testform_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('The content types to enable Tset Form collection for.'),
      '#default_value' => $config->get('allowed_types'),
      '#options' => $types,
      '#description' => $this->t('In the specifed node types, an RSVO option will be available and can be enabled while the node is being edited'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_allowed_types = array_filter($form_state->getValue(
      'testform_types' ));
    sort($selected_allowed_types);

    $this->config('testform.settings')
      ->set('allowed_types', $selected_allowed_types)
      ->save();

   parent::submitForm($form, $form_state);

  }

}
