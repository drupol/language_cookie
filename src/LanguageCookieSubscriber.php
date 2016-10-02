<?php

namespace Drupal\language_cookie;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a MyModuleSubscriber.
 */
class LanguageCookieSubscriber implements EventSubscriberInterface {

  public function language_cookie_set(GetResponseEvent $event) {
    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $config = \Drupal::config('language_cookie.negotiation');
    $param = $config->get('param');

    if ((!isset($_COOKIE[$param]) || (isset($_COOKIE[$param]) && $_COOKIE[$param] != $lang)) || $config->get('set_on_every_pageload')) {
      $this->_language_cookie_set($lang);
    }
  }

  /**
   * Set cookie for current language.
   * If no parameter is passed the current language is used.
   *
   * @param string $lang
   */
  function _language_cookie_set($lang = NULL) {
    $config = \Drupal::config('language_cookie.negotiation');

    $cookie = new \stdClass();
    $cookie->name = $config->get('param');
    $cookie->value = $lang;
    $cookie->expire =  $config->get('time');
    $cookie->path = $config->get('path');
    $cookie->domain = $config->get('domain');
    $cookie->secure = FALSE;
    $cookie->httponly = FALSE;

    setrawcookie(
      $cookie->name,
      rawurlencode($cookie->value),
      REQUEST_TIME + $cookie->expire,
      $cookie->path,
      $cookie->domain,
      $cookie->secure,
      $cookie->httponly
    );
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('language_cookie_set', 20);
    return $events;
  }
}