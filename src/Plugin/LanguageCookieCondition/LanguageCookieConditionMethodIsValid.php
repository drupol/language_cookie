<?php

namespace Drupal\language_cookie\Plugin\LanguageCookieCondition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\language\LanguageNegotiatorInterface;
use Drupal\language_cookie\LanguageCookieConditionBase;
use Drupal\language_cookie\LanguageCookieConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class for LanguageCookieConditionPathIsValid.
 *
 * @LanguageCookieCondition(
 *   id = "method_is_valid",
 *   weight = -200,
 *   name = @Translation("Method is valid"),
 *   description = @Translation("Bails out if the method is not present."),
 * )
 */
class LanguageCookieConditionMethodIsValid extends LanguageCookieConditionBase implements LanguageCookieConditionInterface {

  /**
   * The current path.
   *
   * @var LanguageNegotiatorInterface
   */
  protected $languageNegotiator;

  /**
   * Constructs a LanguageCookieConditionPath plugin.
   *
   * @param LanguageNegotiatorInterface $language_negotiator
   *   The language negotiator.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(LanguageNegotiatorInterface $language_negotiator, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageNegotiator = $language_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('language_negotiator'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $user = \Drupal::currentUser();
    $this->languageNegotiator->setCurrentUser($user->getAccount());
    $methods = $this->languageNegotiator->getNegotiationMethods(LanguageInterface::TYPE_INTERFACE);

    // Do not set cookie if not configured in Language Negotiation.
    if (!isset($methods['language_cookie'])) {
      return $this->block();
    }

    return $this->pass();
  }

}
