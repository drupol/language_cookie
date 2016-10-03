<?php

namespace Drupal\language_cookie;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\LanguageNegotiationMethodManager;
use Drupal\language\LanguageNegotiator;
use Drupal\language\LanguageNegotiatorInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a MyModuleSubscriber.
 */
class LanguageCookieSubscriber implements EventSubscriberInterface {

  /**
   * Callback helper.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *
   * @return array|bool
   */
  public function _get_language(GetResponseEvent $event) {
    $request = $event->getRequest();

    /** @var LanguageNegotiatorInterface $languageNegotiator */
    $languageNegotiator = \Drupal::getContainer()->get('language_negotiator');
    $user = \Drupal::currentUser();
    $languageNegotiator->setCurrentUser($user->getAccount());

    $methods = $languageNegotiator->getNegotiationMethods(LanguageInterface::TYPE_INTERFACE);
    uasort($methods, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    unset($methods['language-cookie'], $methods['language-selected']);

    foreach ($methods as $method_id => $method_definition) {
      $lang = $languageNegotiator->getNegotiationMethodInstance($method_id)->getLangcode($request);
      if ($lang) {
        return [$lang, $method_id];
      }
    }

    return FALSE;
  }

  /**
   * Event callback
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function language_cookie_set(GetResponseEvent $event) {
    // Todo: clean this.
    $languageNegotiator = \Drupal::getContainer()->get('language_negotiator');
    $user = \Drupal::currentUser();
    $languageNegotiator->setCurrentUser($user->getAccount());
    $methods = $languageNegotiator->getNegotiationMethods(LanguageInterface::TYPE_INTERFACE);

    // Do not set cookie if not configured in Language Negotiation.
    if (!isset($methods['language-cookie'])) {
      return;
    }

    if ($lang = $this->_get_language($event)) {
      $config = \Drupal::config('language_cookie.negotiation');
      $param = $config->get('param');

      list($lang, $method) = $lang;
      if ((! \Drupal::request()->cookies->has($param) || (\Drupal::request()->cookies->get($param) != $lang)) || $config->get('set_on_every_pageload')) {
        $this->_language_cookie_set($lang);
      }
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
