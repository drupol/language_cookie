<?php

namespace Drupal\language_cookie;

use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Component\Utility\Unicode;

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
   * @return string|bool
   *   An string with the language or FALSE.
   */
  private function getLanguage() {
    $methods = $this->languageNegotiator->getNegotiationMethods(LanguageInterface::TYPE_INTERFACE);
    unset($methods['language-selected']);
    uasort($methods, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    foreach ($methods as $method_id => $method_definition) {
      $lang = $this->languageNegotiator->getNegotiationMethodInstance($method_id)->getLangcode($this->event->getRequest());
      if ($lang) {
        return $lang;
      }
    }

    return FALSE;
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

    $this->languageNegotiator = \Drupal::getContainer()->get('language_negotiator');
    $request = $event->getRequest();

    // Get current language
    if ($lang = $this->getLanguage()) {
      $param = $config->get('param');

      if ((!$request->cookies->has($param) || ($request->cookies->get($param) != $lang)) || $config->get('set_on_every_pageload')) {
        $cookie = new Cookie($param, $lang, REQUEST_TIME + $config->get('time'), $config->get('path'), $config->get('domain'));
        //Allow other modules to change the $cookie.
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
