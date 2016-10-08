<?php

namespace Drupal\language_cookie\Plugin\LanguageCookieCondition;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\language\LanguageNegotiatorInterface;
use Drupal\language_cookie\LanguageCookieConditionBase;
use Drupal\language_cookie\LanguageCookieConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\language_cookie\Plugin\LanguageNegotiation\LanguageNegotiationCookie;

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a LanguageCookieConditionPath plugin.
   *
   * @param LanguageNegotiatorInterface $language_negotiator
   *   The language negotiator.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(LanguageNegotiatorInterface $language_negotiator, AccountInterface $current_user, $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageNegotiator = $language_negotiator;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('language_negotiator'),
      $container->get('current_user'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $user = $this->currentUser;
    $this->languageNegotiator->setCurrentUser($user);
    $methods = $this->languageNegotiator->getNegotiationMethods(LanguageInterface::TYPE_INTERFACE);

    // Do not set cookie if not configured in Language Negotiation.
    if (!isset($methods[LanguageNegotiationCookie::METHOD_ID])) {
      return $this->block();
    }

    return $this->pass();
  }

}
