<?php

namespace Drupal\language_cookie\Plugin\LanguageCookieCondition;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\language_cookie\LanguageCookieConditionBase;
use Drupal\language_cookie\LanguageCookieConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Class for the Hardcoded blacklisted paths condition plugin.
 *
 * @LanguageCookieCondition(
 *   id = "hardcoded_blacklisted_paths",
 *   weight = -50,
 *   name = @Translation("Hardcoded blacklisted paths"),
 *   description = @Translation("Ignore paths that are in the hardcoded blacklist.")
 * )
 */
class LanguageCookieConditionHardcodedBlacklistedPaths extends LanguageCookieConditionBase implements LanguageCookieConditionInterface {

  /**
   * An alias manager to find the alias for the current system path.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a RequestPath condition plugin.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   An alias manager to find the alias for the current system path.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(AliasManagerInterface $alias_manager, PathMatcherInterface $path_matcher, RequestStack $request_stack, CurrentPathStack $current_path, ConfigFactoryInterface $config_factory, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
    $this->requestStack = $request_stack;
    $this->currentPath = $current_path;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('path.alias_manager'),
      $container->get('path.matcher'),
      $container->get('request_stack'),
      $container->get('path.current'),
      $container->get('config.factory'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $hardcoded_blacklist = [
      '/cdn/farfuture',
      '/httprl_async_function_callback',
      '/' . PublicStream::basePath() . '*',
    ];
    // Do not set a cookie on the Language Selection Page.
    // See https://www.drupal.org/project/language_selection_page.
    $language_selection_page_config = $this->configFactory->get('language_selection_page.negotiation');
    if ($language_selection_page_path = $language_selection_page_config->get('path')) {
      $hardcoded_blacklist[] = '/' . $language_selection_page_path;
    }

    foreach ($hardcoded_blacklist as $blacklisted_path) {
      $request = $this->requestStack->getCurrentRequest();
      // Compare the lowercase path alias (if any) and internal path.
      $path = $this->currentPath->getPath($request);
      // Do not trim a trailing slash if that is the complete path.
      $path = $path === '/' ? $path : rtrim($path, '/');
      $path_alias = Unicode::strtolower($this->aliasManager->getAliasByPath($path));

      $is_on_blacklisted_path = $this->pathMatcher->matchPath($path_alias, $blacklisted_path) || (($path != $path_alias) && $this->pathMatcher->matchPath($path, $blacklisted_path));

      if ($is_on_blacklisted_path) {
        return $this->block();
      }
    }

    return $this->pass();
  }

}
