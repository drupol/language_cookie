<?php

namespace Drupal\language_cookie\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Controller for legacy route (Drupal 7 path to Drupal 8 path).
 */
class LegacyDrupal7Redirect extends ControllerBase {

  /**
   * Callback for the language_cookie.negotiation_cookie_legacy_redirect route.
   */
  public function doRedirect() {
    return new RedirectResponse(Url::fromRoute('language_cookie.negotiation_cookie')->setAbsolute()->toString());
  }

}
