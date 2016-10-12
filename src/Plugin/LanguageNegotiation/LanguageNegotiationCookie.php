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
 * @todo explain why weight is -5 (or -4)
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
   *
   * Uses an underscore instead of a dash as this this what was used in 7.x.
   */
  const METHOD_ID = 'language_cookie';

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
