<?php

namespace Drupal\language_cookie\Plugin\LanguageNegotiation;

use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from a language selection page.
 *
 * @LanguageNegotiation(
 *   weight = -4,
 *   name = @Translation("Cookie"),
 *   description = @Translation("Determine the language from a cookie"),
 *   id = Drupal\language_cookie\Plugin\LanguageNegotiation\LanguageNegotiationCookie::METHOD_ID,
 *   config_route_name = "language.negotiation_cookie"
 * )
 */
class LanguageNegotiationCookie extends LanguageNegotiationMethodBase {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-cookie';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $config = \Drupal::config('language_cookie.negotiation');
    $languages = $this->languageManager->getLanguages();

    $param = $config->get('param');

    return (isset($_COOKIE[$param]) && in_array($_COOKIE[$param], array_keys($languages)))
      ? $_COOKIE[$param] : FALSE;
  }

}
