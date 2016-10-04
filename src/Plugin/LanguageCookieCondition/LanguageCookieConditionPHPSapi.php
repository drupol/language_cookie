<?php

namespace Drupal\language_cookie\Plugin\LanguageCookieCondition;

use Drupal\language_cookie\LanguageCookieConditionBase;
use Drupal\language_cookie\LanguageCookieConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for the PHP Sapi condition plugin.
 *
 * @LanguageCookieCondition(
 *   id = "php_sapi",
 *   weight = -120,
 *   name = @Translation("PHP SAPI"),
 *   description = @Translation("Bails out when running on command line."),
 * )
 */
class LanguageCookieConditionPHPSapi extends LanguageCookieConditionBase implements LanguageCookieConditionInterface {

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
    if (PHP_SAPI === 'cli') {
      return $this->block();
    }

    return $this->pass();
  }

}
