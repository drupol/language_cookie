<?php

namespace Drupal\Tests\language_cookie\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\language_cookie\Plugin\LanguageNegotiation\LanguageNegotiationCookie;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests that the condition plugins work.
 *
 * @group language_cookie
 */
class TestLanguageCookieCondition extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'language_cookie',
    'locale',
    'content_translation',
    'language_test'
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
      'language_interface[enabled][' . LanguageNegotiationCookie::METHOD_ID . ']' => 1,
      'language_interface[enabled][language-url]' => 1,
    ], 'Save settings');
    // Turn on content translation for pages.
    $this->drupalPostform('admin/structure/types/manage/page', ['language_configuration[content_translation]' => 1], 'Save content type');
  }


  /**
   * Test the "Blacklisted paths" condition.
   */
  public function testBlackListedPaths() {
    $node = $this->drupalCreateNode();

    // Remove cookie
    $this->getSession()->setCookie('language', NULL);
    $this->drupalGet('fr/node/' . $node->id());
    $this->assertEquals($this->getSession()->getCookie('language'), 'fr');
    $this->drupalGet('node/' . $node->id());
    $last = $this->container->get('state')->get('language_test.language_negotiation_last');
    $last_interface_language = $last[LanguageInterface::TYPE_INTERFACE];
    $this->assertEquals($last_interface_language, 'fr');

    // Add node to blacklisted paths.
    $this->drupalPostForm('admin/config/regional/language/detection/language_cookie', ['blacklisted_paths' =>  '/admin/*' . PHP_EOL . '/node/' . $node->id()], 'Save configuration');
    $this->getSession()->setCookie('language', NULL);
    $this->drupalGet('node/' . $node->id());
    $this->assertEmpty($this->getSession()->getCookie('language'));

    // Add node to blacklisted paths (in the middle).
    $this->drupalPostForm('admin/config/regional/language/detection/language_cookie', ['blacklisted_paths' => '/admin/*' . PHP_EOL . '/node/' . $node->id() .  PHP_EOL . '/bar'], 'Save configuration');
    $this->getSession()->setCookie('language', NULL);
    $this->drupalGet('node/' . $node->id());
    $this->assertEmpty($this->getSession()->getCookie('language'));

    // Add string that contains node, but not node itself.
    $this->drupalPostForm('admin/config/regional/language/detection/language_cookie', ['blacklisted_paths' => '/admin/*' . PHP_EOL . '/node/' . $node->id() . '/foobar' . PHP_EOL . '/bar'], 'Save configuration');
    $this->getSession()->setCookie('language', NULL);
    $this->drupalGet('node/' . $node->id());
    $this->assertEquals($this->getSession()->getCookie('language'), 'en');

    // Add string that starts with node, but not node itself.
    $this->drupalPostForm('admin/config/regional/language/detection/language_cookie', ['blacklisted_paths' => '/admin/*' . PHP_EOL . '/node/' . $node->id() . '/foobar'], 'Save configuration');
    $this->getSession()->setCookie('language', NULL);
    $this->drupalGet('node/' . $node->id());
    $this->assertEquals($this->getSession()->getCookie('language'), 'en');

    // Test front page.
    $this->drupalPostForm('admin/config/regional/language/detection/language_cookie', ['blacklisted_paths' => '/admin/*'], 'Save configuration');
    $this->getSession()->setCookie('language', NULL);
    $this->drupalGet('<front>');
    $this->assertEquals($this->getSession()->getCookie('language'), 'en');

    $this->drupalPostForm('en/admin/config/regional/language/detection/language_cookie', ['blacklisted_paths' => '/admin/*' . PHP_EOL . '<front>'], 'Save configuration');
    $this->getSession()->setCookie('language', NULL);
    $this->drupalGet('<front>');
    $this->assertEmpty($this->getSession()->getCookie('language'));

    // Test hardcoded blacklist.
    // @todo

    // Create file.

    // Get file from address.

    // Check that cookie is not set.
  }

  /**
   * Test the "xml_http_request" condition.
   */
  public function testAjax() {
    $node = $this->drupalCreateNode();
    $headers = [];
    $this->getSession()->setCookie('language', NULL);
    $this->drupalGet('fr/node/' . $node->id(), array(), $headers);
    $this->assertEquals($this->getSession()->getCookie('language'), 'fr');
    $headers['X-Requested-With'] = 'XMLHttpRequest';
    $this->getSession()->setCookie('language', NULL);
    $this->drupalGet('node/' . $node->id(), array(), $headers);
    $this->assertEmpty($this->getSession()->getCookie('language'));
  }

}
