<?php

/**
 * @file
 * Contains \Drupal\govdelivery_taxonomy\Form\GovDeliveryTaxonomySettingsForm.
 */

namespace Drupal\govdelivery_taxonomy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;

class GovDeliveryTaxonomySettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'govdelivery_taxonomy_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('govdelivery_taxonomy.settings');
    $values = $form_state->getValues()['govdelivery_taxonomy'];

    if (empty($values['password'])) {
      // there was no new password submitted, so don't overwrite the one we have
      unset($values['password']);
    }

    // process the categories so they are saved properly
    $categories = [];
    foreach ($values['category'] AS $key => $value) {
      if ($value) {
        $categories[] = $key;
      }
    }
    $config->set('category', $categories);
    unset($values['category']);
    
    // process the rest of the settings
    $this->configRecurse($config, $values);

    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*
    $values = $form_state->getValues();
    */
  }

  /**
   * recursively go through the set values to set the configuration
   */
  protected function configRecurse($config, $values, $base = '') {
    foreach ($values AS $var => $value) {
      if (!empty($base)) {
        $v = $base . '.' . $var;
      }
      else {
        $v = $var;
      }
      if (!is_array($value)) {
        $config->set($v, $value);
      }
      else {
        $this->configRecurse($config, $value, $v);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['govdelivery_taxonomy.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form = [], FormStateInterface $form_state) {
    $config = $this->config('govdelivery_taxonomy.settings');
    $accounts = $config->get('accounts');

    $form['govdelivery_taxonomy'] = array(
      '#tree' => TRUE,
    );
    $form['govdelivery_taxonomy']['username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('WebService Administrator Username'),
      '#default_value' => $config->get('username'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['govdelivery_taxonomy']['password'] = array(
      '#type' => 'password',
      '#title' => $this->t('WebService Administrator Password'),
      '#default_value' => $config->get('password'),
      '#maxlength' => 25,
      '#required' => !empty($config->get('password')) ? FALSE : TRUE,
    );
    $form['govdelivery_taxonomy']['clientcode'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GovDelivery DCM Client Account Code'),
      '#default_value' => $config->get('clientcode'),
      '#maxlength' => 20,
      '#required' => TRUE,
    );
    $form['govdelivery_taxonomy']['server'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GovDelivery API domain'),
      '#description' => $this->t('This is usually stage-api.govdelivery.com or api.govdelivery.com'),
      '#default_value' => $config->get('server'),
      '#maxlength' => 100,
      '#required' => TRUE,
    );
    $form['govdelivery_taxonomy']['drupalserver'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Drupal Instance Base URL (Without HTTPS://)'),
      '#description' => $this->t('This is the URL to this Drupal instance without any subdirectories'),
      '#default_value' => $config->get('drupalserver'),
      '#maxlength' => 100,
      '#required' => TRUE,
    );
    $categories = govdelivery_taxonomy_get_categories(NULL);

    $form['govdelivery_taxonomy']['category'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('GovDelivery Categories'),
      '#description' => $this->t('Topics/Terms will be able to be assigned to these categories. Only GovDelivery categories that "allow subscriptions" are available.'),
      //'#default_value' => $config->get('category'),
      //'#maxlength' => 25,
      //'#required' => TRUE,
    );
    foreach ($categories AS $category) {
      if ($category->{'allow-subscriptions'} == 'true') {
        $form['govdelivery_taxonomy']['category'][$category->code] = array(
          '#type' => 'checkbox',
          '#title' => $category->name,
          '#default_value' => array_search($category->code, $config->get('category')) !== FALSE ? TRUE : FALSE,
        );
        if (gettype($category->description) == 'string') {
          $form['govdelivery_taxonomy']['category'][$category->code]['#description'] = $category->description;
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }
}