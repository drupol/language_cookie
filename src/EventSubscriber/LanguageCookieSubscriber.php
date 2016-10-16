<?php

namespace Drupal\language_cookie\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\language\LanguageNegotiatorInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSelected;
use Drupal\language_cookie\Plugin\LanguageNegotiation\LanguageNegotiationCookie;

/**
 * Provides a LanguageCookieSubscriber.
 */
class LanguageCookieSubscriber implements EventSubscriberInterface {

  /**
   * The event.
   *
   * @var FilterResponseEvent
   */
  protected $event;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language negotiator.
   *
   * @var \Drupal\language\LanguageNegotiatorInterface
   */
  protected $languageNegotiator;

  /**
   * The Language Cookie condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $languageCookieConditionManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new class object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\language\LanguageNegotiatorInterface $language_negotiator
   *   The language negotiator.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $plugin_manager
   *   The language cookie condition plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, LanguageNegotiatorInterface $language_negotiator, ExecutableManagerInterface $plugin_manager, ModuleHandlerInterface $module_handler) {
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->languageNegotiator = $language_negotiator;
    $this->languageCookieConditionManager = $plugin_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Helper method that gets the language code to set the cookie to.
   *
   * Loops through all available language negotiation methods with higher
   * priority than the Language Cookie method itself.
   *
   * @see \Drupal\language_cookie\LanguageCookieSubscriber::setLanguageCookie()
   *
   * @return string|bool
   *   An string with the language code or FALSE.
   */
  protected function getLanguage() {
    $config = $this->configFactory->get('language_cookie.negotiation');
    // In the install hook for this module, we assume the interface language
    // will be used to set the cookie. If you want to use another language
    // negotiation type instead (ie. content/URL), you can use "language_type"
    // config key.
    $type = $config->get('language_type');
    // Get all methods available for this language type.
    $methods = $this->languageNegotiator->getNegotiationMethods($type);
    // We ignore this language method or else it will always return a language.
    unset($methods[LanguageNegotiationSelected::METHOD_ID]);
    uasort($methods, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    foreach ($methods as $method_id => $method_definition) {
      // Do not consider language providers with a lower priority than the
      // cookie language provider, nor the cookie provider itself.
      if ($method_id == LanguageNegotiationCookie::METHOD_ID) {
        return FALSE;
      }
      $lang = $this->languageNegotiator->getNegotiationMethodInstance($method_id)->getLangcode($this->event->getRequest());
      if ($lang) {
        return $lang;
      }
    }

    // If no other language was found, use the default one.
    return $this->languageManager->getDefaultLanguage()->getId();
  }

  /**
   * Event callback for setting the language cookie.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event.
   *
   * @return bool
   *   - FALSE if a condition plugin prevented the cookie from being set.
   *   - TRUE if all conditions pass. If a language is available, the cookie
   *     will have been set.
   */
  public function setLanguageCookie(FilterResponseEvent $event) {
    $this->event = $event;
    $config = $this->configFactory->get('language_cookie.negotiation');

    $manager = $this->languageCookieConditionManager;

    // Run through the condition plugins that may prevent a cookie from being
    // set.
    foreach ($manager->getDefinitions() as $def) {
      /** @var ExecutableInterface $condition_plugin */
      $condition_plugin = $manager->createInstance($def['id'], $config->get());
      if (!$manager->execute($condition_plugin)) {
        return FALSE;
      }
    }

    // Get the current language to set in the cookie to by running through all
    // language negotiation methods with higher priority (in terms of weight)
    // than the Language Cookie method.
    if ($lang = $this->getLanguage()) {
      $request = $this->event->getRequest();

      // Get the name of the cookie parameter.
      $param = $config->get('param');

      if ((!$request->cookies->has($param) || ($request->cookies->get($param) != $lang)) || $config->get('set_on_every_pageload')) {
        $cookie = new Cookie($param, $lang, REQUEST_TIME + $config->get('time'), $config->get('path'), $config->get('domain'));
        // Allow other modules to change the $cookie.
        $this->moduleHandler->alter('language_cookie', $cookie);
        $this->event->getResponse()->headers->setCookie($cookie);
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // You can set the order of execution of this event callback in the array.
    // Find the order of execution by doing this in the Drupal Root:
    // grep "$events[KernelEvents::RESPONSE][]" . -R | grep -v 'Test'
    // The value is currently set to 20, feel free to adjust if needed.
    // @todo explain why it's 20? just a random number?
    $events[KernelEvents::RESPONSE][] = array('setLanguageCookie', 20);
    return $events;
  }

}
