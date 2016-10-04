<?php

namespace Drupal\language_cookie\Plugin\LanguageCookieCondition;

use Drupal\language_cookie\LanguageCookieConditionBase;
use Drupal\language_cookie\LanguageCookieConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for the Server Addr condition plugin.
 *
 * @LanguageCookieCondition(
 *   id = "server_addr",
 *   weight = -70,
 *   name = @Translation("Server Address condition check"),
 *   description = @Translation("Bails out if the server address is not set."),
 * )
 */
class LanguageCookieConditionServerAddr extends LanguageCookieConditionBase implements LanguageCookieConditionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (isset($_SERVER['SERVER_ADDR'])) {
      return $this->pass();
    }

    return $this->block();
  }

}
