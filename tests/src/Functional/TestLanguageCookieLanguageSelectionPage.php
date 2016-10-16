<?php

namespace Drupal\Tests\language_cookie\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\language_cookie\Plugin\LanguageNegotiation\LanguageNegotiationCookie;

/**
 * Test that the language_cookie module works well with language_selection_page.
 *
 * @group language_cookie
 */
class TestLanguageCookieLanguageSelectionPage extends BrowserTestBase {

  /**
   * Text to assert for to determine if we are on the Language Selection Page.
   */
  const LANGUAGE_SELECTION_PAGE_TEXT = 'This page is the default page of the module Language Selection Page';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'language_selection_page',
    'language_cookie',
    'locale',
    'content_translation',
  ];

  /**
   * Use the standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);
    // Create FR.
    $this->drupalPostForm('admin/config/regional/language/add', [
      'predefined_langcode' => 'fr',
    ], 'Add language');
    // Set prefixes to en and fr.
    $this->drupalPostForm('admin/config/regional/language/detection/url', [
      'prefix[en]' => 'en',
      'prefix[fr]' => 'fr',
    ], 'Save configuration');
    // Set up URL and language selection page methods.
    $this->drupalPostForm('admin/config/regional/language/detection', [
      'language_interface[enabled][language-selection-page]' => 1,
      'language_interface[enabled][language-url]' => 1,
    ], 'Save settings');
    // Turn on content translation for pages.
    $this->drupalPostform('admin/structure/types/manage/page', ['language_configuration[content_translation]' => 1], 'Save content type');
  }

  /**
   * Test that the language cookie and the language selection page work.
   */
  public function testLanguageCookieAndSelectionPage() {
    // Test that no cookie is set when the module is enabled but not configured.
    $node = $this->drupalCreateNode();
    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageLoaded();

    // Enable cookie.
    $this->drupalPostForm('admin/config/regional/language/detection', [
      'language_interface[enabled][language-url]' => 1,
      'language_interface[enabled][' . LanguageNegotiationCookie::METHOD_ID . ']' => 1,
      'language_interface[enabled][language-selection-page]' => 1,
      'language_interface[weight][language-url]' => -8,
      'language_interface[weight][' . LanguageNegotiationCookie::METHOD_ID . ']' => -5,
      'language_interface[weight][language-selection-page]' => -4,
    ], 'Save settings');
    $this->assertEquals($this->getSession()->getCookie('language'), 'en');
    // Remove cookie.
    $this->getSession()->setCookie('language', NULL);
    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageLoaded();
    $this->assertSession()->responseContains('en/node/' . $node->id());
    // Cookie should not yet be set.
    $this->assertEmpty($this->getSession()->getCookie('language'));
    $this->clickLink('English');
    // Cookie should be set at this point.
    $this->assertEquals($this->getSession()->getCookie('language'), 'en');
  }

  /**
   * Assert that the language selection page is loaded.
   */
  protected function assertLanguageSelectionPageLoaded() {
    $this->assertSession()->pageTextContains(self::LANGUAGE_SELECTION_PAGE_TEXT);
  }

  /**
   * Assert that the language selection page is not loaded.
   */
  protected function assertLanguageSelectionPageNotLoaded() {
    $this->assertSession()->pageTextNotContains(self::LANGUAGE_SELECTION_PAGE_TEXT);
  }

}
