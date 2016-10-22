<?php

namespace Drupal\language_cookie\Plugin\LanguageSelectionPageCondition;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language_selection_page\LanguageSelectionPageConditionBase;
use Drupal\language_selection_page\LanguageSelectionPageConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for LanguageSelectionPageConditionJSCookieRedirect.
 *
 * @LanguageSelectionPageCondition(
 *   id = "javascript_cookie_redirect",
 *   weight = 100,
 *   name = @Translation("Javascript Cookie redirect"),
 *   description = @Translation("TODO"),
 *   runInBlock = FALSE,
 * )
 */
class LanguageSelectionPageConditionJSCookieRedirect extends LanguageSelectionPageConditionBase implements LanguageSelectionPageConditionInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * LanguageSelectionPageConditionIgnoreNeutral constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(ConfigFactoryInterface $config_factory, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('config.factory'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return $this->pass();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[$this->getPluginId()] = [
      '#title' => $this->t('Enable Javascript-based language cookie redirect.'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration[$this->getPluginId()],
      '#description' => $this->t('Redirect requests to the language selection page using the language saved in the language cookie of the visitor. This may be useful if you use page caching systems such as Boost or Varnish.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $form_state->set($this->getPluginId(), (bool) $form_state->get($this->getPluginId()));
  }

  /**
   * {@inheritdoc}
   */
  public function alterPageContent(array &$content = array()) {
    parent::alterPageContent($content);
    $config = $this->configFactory->get('language_cookie.negotiation');

    $content['#attached']['library'][] = 'language_cookie/language_cookie_js_redirect';
    $content['#attached']['drupalSettings']['language_cookie']['param'] = $config->get('param');
  }

}
