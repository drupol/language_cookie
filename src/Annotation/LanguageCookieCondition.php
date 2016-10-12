<?php

namespace Drupal\language_cookie\Annotation;

use Drupal\Core\Condition\Annotation\Condition;

/**
 * Defines a language cookie condition annotation object.
 *
 * Plugin Namespace: Plugin\LanguageCookieCondition.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class LanguageCookieCondition extends Condition {

  /**
   * The language cookie condition plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The default weight of the language cookie condition plugin.
   *
   * @var int
   */
  public $weight;

  /**
   * The human-readable name of the language cookie condition plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The description of the language cookie condition plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
