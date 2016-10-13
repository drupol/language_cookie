<?php

namespace Drupal\language_cookie\Form;

use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the Language cookie negotiation method for this site.
 */
class NegotiationLanguageCookieForm extends ConfigFormBase {

  /**
   * The configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The Language Cookie condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $languageCookieConditionManager;

  /**
   * NegotiationLanguageCookieForm constructor.
   *
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $plugin_manager
   *   The plugin manager.
   */
  public function __construct(ExecutableManagerInterface $plugin_manager) {
    parent::__construct($this->configFactory());
    $this->languageCookieConditionManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.language_cookie_condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_cookie_admin_form';
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
    $this->config = $this->config('language_cookie.negotiation');

    $form['param'] = array(
      '#title' => $this->t('Cookie parameter'),
      '#type' => 'textfield',
      '#default_value' => $this->config->get('param'),
      '#description' => $this->t('Name of the cookie parameter used to determine the desired language.'),
    );

    $form['time'] = array(
      '#title' => $this->t('Cookie duration'),
      '#type' => 'textfield',
      '#default_value' => $this->config->get('time'),
      '#description' => $this->t('The time the cookie expires. This is the number of seconds from the current time.'),
    );

    $form['path'] = array(
      '#title' => $this->t('Cookie path'),
      '#type' => 'textfield',
      '#default_value' => $this->config->get('path'),
      '#description' => $this->t('The cookie available server path'),
    );

    $form['domain'] = array(
      '#title' => $this->t('Cookie domain scope'),
      '#type' => 'textfield',
      '#default_value' => $this->config->get('domain'),
      '#description' => $this->t('The cookie domain scope'),
    );

    $form['set_on_every_pageload'] = array(
      '#title' => $this->t('Re-send cookie on every page load'),
      '#type' => 'checkbox',
      '#description' => $this->t('This will re-send a cookie on every page load, even if a cookie has already been set. This may be useful if you use a page cache such as Varnish and you plan to cache the language cookie. This prevents a user who already has a cookie visiting an uncached page and the cached version not setting a cookie.'),
      '#default_value' => $this->config->get('set_on_every_pageload'),
    );

    $manager = $this->languageCookieConditionManager;

    foreach ($manager->getDefinitions() as $def) {
      $condition_plugin = $manager->createInstance($def['id']);
      $form_state->set(['conditions', $condition_plugin->getPluginId()], $condition_plugin);

      $condition_plugin->setConfiguration($condition_plugin->getConfiguration() + (array) $this->config->get());

      $condition_form = [];
      $condition_form['#markup'] = $condition_plugin->getDescription();
      $condition_form += $condition_plugin->buildConfigurationForm([], $form_state);

      if (!empty($condition_form[$condition_plugin->getPluginId()])) {
        $condition_form['#type'] = 'details';
        $condition_form['#open'] = TRUE;
        $condition_form['#title'] = $condition_plugin->getName();
        $condition_form['#weight'] = $condition_plugin->getWeight();
        $form['conditions'][$condition_plugin->getPluginId()] = $condition_form;
      }
    }

    return parent::buildForm($form, $form_state);
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

    // Redirect to the language negotiation page on submit (previous Drupal 7
    // behavior, and intended behavior for other language negotiation settings
    // forms in Drupal 8 core).
    $form_state->setRedirect('language.negotiation');
  }

}
