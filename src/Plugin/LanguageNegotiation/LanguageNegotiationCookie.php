<?php

namespace Drupal\language_cookie\Plugin\LanguageNegotiation;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from a language cookie.
 *
 * The recommended order is URL > Cookie > Language Selection Page, so weight
 * is set to -5 by default so that it is lower than Language Selection Page
 * (see https://www.drupal.org/project/language_selection_page), which has a
 * weight of -4, and so that it higher than URL, which has a weight of -8.
 *
 * @LanguageNegotiation(
 *   weight = -5,
 *   name = @Translation("Cookie"),
 *   description = @Translation("Determine the language from a cookie"),
 *   id = Drupal\language_cookie\Plugin\LanguageNegotiation\LanguageNegotiationCookie::METHOD_ID,
 *   config_route_name = "language_cookie.negotiation_cookie"
 * )
 */
class LanguageNegotiationCookie extends LanguageNegotiationMethodBase implements ContainerFactoryPluginInterface {

  /**
   * The language negotiation method ID.
   */
  const METHOD_ID = 'language-cookie';

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new LanguageNegotiationCookie instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $config = $this->configFactory->get('language_cookie.negotiation');
    $param = $config->get('param');

    return ($request->cookies->has($param) && in_array($request->cookies->get($param), array_keys($this->languageManager->getLanguages())))
      ? $request->cookies->get($param) : FALSE;
  }

}
