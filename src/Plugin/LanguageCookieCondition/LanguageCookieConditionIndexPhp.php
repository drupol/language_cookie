<?php

namespace Drupal\language_cookie\Plugin\LanguageCookieCondition;

use Drupal\language_cookie\LanguageCookieConditionBase;
use Drupal\language_cookie\LanguageCookieConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for the Index.php condition plugin.
 *
 * @LanguageCookieCondition(
 *   id = "index",
 *   weight = -60,
 *   name = "Index.php",
 *   description = @Translation("Bails out when running the script on another php file than index.php."),
 * )
 */
class LanguageCookieConditionIndexPhp extends LanguageCookieConditionBase implements LanguageCookieConditionInterface {

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
    if ($_SERVER['SCRIPT_NAME'] !== $GLOBALS['base_path'] . 'index.php') {
      return $this->block();
    }

    return $this->pass();
  }

}
