<?php

namespace Drupal\language_cookie;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\StreamWrapper\PublicStream;

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
   * The Language Negotiator.
   *
   * @var \Drupal\language\LanguageNegotiatorInterface
   */
  protected $languageNegotiator;

  /**
   * Callback helper.
   *
   * @see \Drupal\language_cookie\LanguageCookieSubscriber::setLanguageCookie()
   *
   * @return string|bool
   *   An string with the language code or FALSE.
   */
  protected function getLanguage() {
    $config = \Drupal::config('language_cookie.negotiation');
    // In the install hook for this module, we assume the interface language
    // will be used to set the cookie. If you want to use another language
    // negotiation type instead (ie. content/URL), you can use "language_type"
    // config key.
    $type = $config->get('language_type');
    // Get all methods available for this language type.
    $methods = $this->languageNegotiator->getNegotiationMethods($type);
    unset($methods['language-selected']);
    uasort($methods, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    foreach ($methods as $method_id => $method_definition) {
      // Do not consider language providers with a lower priority than the
      // cookie language provider, nor the cookie provider itself.
      if ($method_id == 'language_cookie') {
        return FALSE;
      }
      $lang = $this->languageNegotiator->getNegotiationMethodInstance($method_id)->getLangcode($this->event->getRequest());
      if ($lang) {
        return $lang;
      }
    }

    // If no other language was found, use the default one.
    return \Drupal::languageManager()->getDefaultLanguage()->getId();
  }

  /**
   * Event callback.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event.
   *
   * @return bool
   *   True or False.
   */
  public function setLanguageCookie(FilterResponseEvent $event) {
    $this->event = $event;
    $config = \Drupal::config('language_cookie.negotiation');

    /** @var LanguageCookieConditionManager $manager */
    $manager = \Drupal::service('plugin.manager.language_cookie_condition');

    foreach ($manager->getDefinitions() as $def) {
      $condition_plugin = $manager->createInstance($def['id'], $config->get());
      if (!$manager->execute($condition_plugin)) {
        return FALSE;
      }
    }

    // @todo move these conditions to plugins
    // Get the current request path.
    $request_path = $_SERVER["REQUEST_URI"];

    // Don't run this code if we are accessing anything in the files path.
    $public_files_path = PublicStream::basePath();
    if (strpos($request_path, $public_files_path) === 0) {
      return FALSE;
    }

    if (strpos($request_path, 'cdn/farfuture') === 0) {
      return FALSE;
    }

    if (strpos($request_path, 'httprl_async_function_callback') === 0) {
      return FALSE;
    }

    // Do not set cookie on language selection page.
    $language_selection_page_config = \Drupal::config('language_selection_page.negotiation');
    $language_selection_page_path = $language_selection_page_config->get('path');
    if ($request_path == $language_selection_page_path) {
      return FALSE;
    }

    $this->languageNegotiator = \Drupal::getContainer()->get('language_negotiator');
    $request = $event->getRequest();

    // Get current language.
    if ($lang = $this->getLanguage()) {
      $param = $config->get('param');

      if ((!$request->cookies->has($param) || ($request->cookies->get($param) != $lang)) || $config->get('set_on_every_pageload')) {
        $cookie = new Cookie($param, $lang, REQUEST_TIME + $config->get('time'), $config->get('path'), $config->get('domain'));
        // Allow other modules to change the $cookie.
        \Drupal::moduleHandler()->alter('language_cookie', $cookie);
        $this->event->getResponse()->headers->setCookie($cookie);
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // @todo Describe why we are setting this to 20. What does it need to run before or after?
    $events[KernelEvents::RESPONSE][] = array('setLanguageCookie', 20);
    return $events;
  }

}
