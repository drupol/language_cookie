<?php

namespace Drupal\language_cookie;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\LanguageNegotiatorInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a LanguageCookieSubscriber.
 */
class LanguageCookieSubscriber implements EventSubscriberInterface {

  /**
   * The event
   *
   * @var FilterResponseEvent
   */
  protected $event;

  /**
   * The Language Negotiator
   *
   * @var LanguageNegotiatorInterface
   */
  protected $languageNegotiator;

  /**
   * Callback helper.
   *
   * @return array|bool
   */
  public function _get_language() {
    $methods = $this->languageNegotiator->getNegotiationMethods(LanguageInterface::TYPE_INTERFACE);
    uasort($methods, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    unset($methods['language-cookie'], $methods['language-selected']);

    foreach ($methods as $method_id => $method_definition) {
      $lang = $this->languageNegotiator->getNegotiationMethodInstance($method_id)->getLangcode($this->event->getRequest());
      if ($lang) {
        return [$lang, $method_id];
      }
    }

    return FALSE;
  }

  /**
   * Event callback
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   */
  public function language_cookie_set(FilterResponseEvent $event) {
    $this->event = $event;
    $this->languageNegotiator = \Drupal::getContainer()->get('language_negotiator');
    $request = $event->getRequest();

    $user = \Drupal::currentUser();
    $this->languageNegotiator->setCurrentUser($user->getAccount());
    $methods = $this->languageNegotiator->getNegotiationMethods(LanguageInterface::TYPE_INTERFACE);

    // Do not set cookie if not configured in Language Negotiation.
    if (!isset($methods['language-cookie'])) {
      return;
    }

    if ($lang = $this->_get_language()) {
      $config = \Drupal::config('language_cookie.negotiation');
      $param = $config->get('param');

      list($lang, $method) = $lang;
      if ((!$request->cookies->has($param) || ($request->cookies->get($param) != $lang)) || $config->get('set_on_every_pageload')) {
        $cookie = new Cookie($param, $lang, REQUEST_TIME + $config->get('time'), $config->get('path'), $config->get('domain'));
        $this->event->getResponse()->headers->setCookie($cookie);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('language_cookie_set', 20);
    return $events;
  }
}