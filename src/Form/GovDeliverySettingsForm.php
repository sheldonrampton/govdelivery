<?php

/**
 * @file
 * Contains \Drupal\saml_sp\Form\GovDeliverySettingsForm.
 */

namespace Drupal\govdelivery\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;

class GovDeliverySettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'govdelivery_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('govdelivery.tms_settings');
    $values = $form_state->getValues()['govdelivery_tms_settings'];
    $this->configRecurse($config, $values['accounts'], 'accounts');
    $config->set('server', $values['server']);
    $config->set('auth_token', $values['auth_token']);
    $config->set('enabled', $values['enabled']);
    $config->set('queue', $values['queue']);
    $config->set('cron_tms', $values['cron_tms']);
    $config->set('override_from', $values['override_from']);
    $config->set('max_bid', $values['max_bid']);
    $config->set('external_cron_interval', $values['external_cron_interval']);

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
    return ['govdelivery.tms_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form = [], FormStateInterface $form_state) {
    $config = $this->config('govdelivery.tms_settings');
    $accounts = $config->get('accounts');

    $account_fieldset = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Mail account'),
      '#tree' => TRUE,
    );
    // TODO figure out what this account stuff is about, why would we want
    // multiple account information and why are the fields hidden?
    if (!empty($accounts) && is_array($accounts)) {
      foreach ($govdelivery_tms_settings['accounts'] as $username => $account_settings) {
        $account_fieldset[$username . '%###%fromname'] = array(
          '#type' => "textfield",
          '#title' => t('From name'),
          '#default_value' => check_plain((!empty($account_settings['fromname']) ? $account_settings['fromname'] : '')),
          '#description' => t('The name displayed in the From field of the received email. E.G. John Smith.'),
        );
        $account_fieldset[$username . '%###%username'] = array(
          '#type' => "hidden",
          '#title' => t('Username'),
          // Hardcoded username
          '#default_value' => t('gd_drupal_tms'),
        );
        $account_fieldset[$username . '%###%password'] = array(
          '#type' => "hidden",
          '#title' => t('Password'),
          '#default_value' => t('gd_drupal_tms'),
        );
      }
    }
    else {
      $account_fieldset['fromname'] = array(
        '#type' => "textfield",
        '#title' => t('From name'),
        '#description' => t('The name displayed in the From field of the received email. E.G. John Smith.'),
      );
      $account_fieldset['username'] = array(
        '#type' => "hidden",
        '#title' => t('Username'),
        '#value' => t('gd_drupal_tms'),
      );
      $account_fieldset['password'] = array(
        '#type' => "hidden",
        '#title' => t('Password'),
        '#value' => t('gd_drupal_tms'),
      );
    }

    // Commenting out subscription form for now until usage is clearer.
    // $subscription_fieldset = array(
    //   '#type' => 'fieldset',
    //   '#title' => t('Subscription API'),
    //   '#tree' => TRUE,
    // );
    // $subscription_fieldset['service_url'] = array(
    //   '#type' => "textfield",
    //   '#title' => t('URL for subscription service'),
    //   '#default_value' => (!empty($govdelivery_subscription_settings['service_url']) ? check_url($govdelivery_subscription_settings['service_url']) : ''),
    // );
    // $subscription_fieldset['cron_subscriptions'] = array(
    //   '#type' => 'radios',
    //   '#default_value' => (isset($govdelivery_subscription_settings['cron_subscriptions']) ? $govdelivery_subscription_settings['cron_subscriptions'] : 1),
    //   '#title' => t('Subscriptions cron'),
    //   '#options' => array('Disabled', 'Enabled'),
    //   '#description' => t('Will automatically resend the subscriptions queue on cron runs.'),
    // );
    // $subscription_fieldset['subscriber_api_url_base'] = array(
    //   '#type' => 'textfield',
    //   '#title' => t('Base URL for Subscriber API'),
    //   '#default_value' => (!empty($govdelivery_subscription_settings['subscriber_api_url_base']) ? check_url($govdelivery_subscription_settings['subscriber_api_url_base']) : ''),
    // );
    // $subscription_fieldset['default_topic_category_id'] = array(
    //   '#type' => 'textfield',
    //   '#title' => t('Default category assigned to new topics'),
    //   '#default_value' => (!empty($govdelivery_subscription_settings['default_topic_category_id']) ? $govdelivery_subscription_settings['default_topic_category_id'] : ''),
    // );
    $boole_options = array(
      FALSE => 'Disabled',
      TRUE => 'Enabled'
    );

    $form['govdelivery_tms_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Mail server settings'),
      '#tree' => TRUE,
      'accounts' => $account_fieldset,
    );
    // 'default_username' => array(
    //   '#type' => "textfield",
    //   '#title' => 'Default mail username',
    //   '#default_value' => (!empty($govdelivery_tms_settings['default_username']) ? check_url($govdelivery_tms_settings['default_username']) : ''),
    //   '#description' => t('The username of the default mail account to use.'),
    // ),
    $form['govdelivery_tms_settings']['server'] = array(
      '#type' => "textfield",
      '#title' => 'Server',
      '#default_value' => $config->get('server'),
      '#description' => t('Enter the URL of your GovDelivery TMS instance. It must begin with "https." Example: https://yourserver.govdelivery.com'),
    );
    $form['govdelivery_tms_settings']['auth_token'] = array(
      '#type' => "textfield",
      '#title' => t('Auth token'),
      '#default_value' => $config->get('auth_token'),
      '#description' => t('Enter the authentication token needed to send emails through your GovDelivery TMS service. If you do not have an authentication token, please content your GovDelivery account representative.'),
    );
    $form['govdelivery_tms_settings']['enabled'] = array(
      '#type' => 'select',
      '#default_value' => (boolean) $config->get('enabled'),
      '#title' => t('Use TMS for Outbound Mail'),
      '#options' => $boole_options,
      '#description' => t('If this option is enabled, emails from your Drupal site will be sent using GovDelivery\'s Targeted Messaging System (TMS). If it is disabled, emails we be sent using Drupal\'s standard SMTP-based email system.'),
    );
    $form['govdelivery_tms_settings']['queue'] = array(
      '#type' => 'select',
      '#default_value' => (boolean) $config->get('queue'),
      '#title' => t('Queue Mail for High Volume'),
      '#options' => $boole_options,
      '#description' => t('If this option is enabled, messages will be placed in a queue for delivery rather than be sent immediately. (Messages that fail to send will be queued anyway for later delivery.)'),
    );
    $form['govdelivery_tms_settings']['cron_tms'] = array(
      '#type' => 'select',
      '#default_value' => (boolean) $config->get('cron_tms'),
      '#title' => t('Messages cron'),
      '#options' => $boole_options,
      '#description' => t('Will automatically resend the messages queue when cron runs.'),
    );
    $form['govdelivery_tms_settings']['override_from'] = array(
      '#type' => 'select',
      '#default_value' => (boolean) $config->get('override_from'),
      '#title' => t('Override the From address on outgoing messages and failback on the predefined From address in this module'),
      '#options' => $boole_options,
      '#description' => t('This will allow webforms and other modules to configure the From address.'),
    );
    $form['govdelivery_tms_settings']['max_bid'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum bins used with queue asynchronous processing'),
      '#default_value' => $config->get('max_bid'),
    );
    $form['govdelivery_tms_settings']['external_cron_interval'] = array(
      '#type' => 'textfield',
      '#title' => t('External cron interval (in seconds)'),
      '#default_value' => $config->get('external_cron_interval'),
    );

    return parent::buildForm($form, $form_state);
  }
}