<?php

namespace Drupal\language_cookie\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure the Language cookie negotiation method for this site.
 */
class NegotiationLanguageCookieForm extends ConfigFormBase {

  /**
   * The configuration.
   *
   * @var Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_negotiation_configure_language_cookie_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['language_cookie.negotiation'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $this->config = $this->config('language_cookie.negotiation');

    $form['param'] = array(
      '#title' => t('Cookie parameter'),
      '#type' => 'textfield',
      '#default_value' => $this->config->get('param'),
      '#description' => $this->t('Name of the cookie parameter used to determine the desired language.'),
    );

    $form['time'] = array(
      '#title' => t('Cookie duration'),
      '#type' => 'textfield',
      '#default_value' => $this->config->get('time'),
      '#description' => $this->t('The time the cookie expires. This is the number of seconds from the current time.'),
    );

    $form['path'] = array(
      '#title' => t('Cookie path'),
      '#type' => 'textfield',
      '#default_value' => $this->config->get('path'),
      '#description' => t('The cookie available server path'),
    );

    $form['domain'] = array(
      '#title' => t('Cookie domain scope'),
      '#type' => 'textfield',
      '#default_value' => $this->config->get('domain'),
      '#description' => t('The cookie domain scope'),
    );

    $form['set_on_every_pageload'] = array(
      '#title' => t('Re-send cookie on every page load'),
      '#type' => 'checkbox',
      '#description' => t('This will re-send a cookie on every page load, even if a cookie has already been set. This may be useful if you use a page cache such as Varnish and you plan to cache the language cookie. This prevents a user who already has a cookie visiting an uncached page and the cached version not setting a cookie.'),
      '#default_value' => $this->config->get('set_on_every_pageload'),
    );

    $form['blacklisted_paths'] = array(
      '#type' => 'textarea',
      '#title' => t('Paths blacklist'),
      '#default_value' => implode(PHP_EOL, (array) $this->config->get('blacklisted_paths')),
      '#size' => 10,
      '#description' => t('Specify on which paths the language selection pages should be circumvented.') . '<br />'
        . t("Specify pages by using their aliased paths. Enter one path per line. The '*' character is a wildcard."),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $form_state->setValue('blacklisted_paths', array_filter(array_map('trim', explode(PHP_EOL, $form_state->getValue('blacklisted_paths')))));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config
      ->set('param', $form_state->getValue('param'))
      ->set('time', $form_state->getValue('time'))
      ->set('path', $form_state->getValue('path'))
      ->set('domain', $form_state->getValue('domain'))
      ->set('set_on_every_pageload', $form_state->getValue('set_on_every_pageload'))
      ->set('blacklisted_paths', $form_state->getValue('blacklisted_paths'))
      ->save();
  }

}
