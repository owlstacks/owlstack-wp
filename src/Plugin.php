<?php

declare(strict_types=1);

namespace Owlstack\WordPress;

use Owlstack\Core\Config\OwlstackConfig;
use Owlstack\Core\Events\Contracts\EventDispatcherInterface;
use Owlstack\Core\Formatting\CharacterTruncator;
use Owlstack\Core\Formatting\HashtagExtractor;
use Owlstack\Core\Http\Contracts\HttpClientInterface;
use Owlstack\Core\Platforms\Facebook\FacebookFormatter;
use Owlstack\Core\Platforms\Facebook\FacebookPlatform;
use Owlstack\Core\Platforms\PlatformRegistry;
use Owlstack\Core\Platforms\Telegram\TelegramFormatter;
use Owlstack\Core\Platforms\Telegram\TelegramPlatform;
use Owlstack\Core\Platforms\Twitter\TwitterFormatter;
use Owlstack\Core\Platforms\Twitter\TwitterPlatform;
use Owlstack\Core\Publishing\Publisher;
use Owlstack\WordPress\Admin\DeliveryLogsPage;
use Owlstack\WordPress\Admin\MetaBox;
use Owlstack\WordPress\Admin\OptionsManager;
use Owlstack\WordPress\Admin\SettingsPage;
use Owlstack\WordPress\Auth\WpTokenStore;
use Owlstack\WordPress\Events\WpEventDispatcher;
use Owlstack\WordPress\Http\WpHttpClient;
use Owlstack\WordPress\Publishing\PostPublisher;
use Owlstack\WordPress\Publishing\SendTo;
use Owlstack\WordPress\Rest\OwlstackRestController;

/**
 * Main plugin class — wires all services and registers WordPress hooks.
 */
class Plugin
{
    private static ?self $instance = null;

    private ?OwlstackConfig $config = null;
    private ?HttpClientInterface $httpClient = null;
    private ?EventDispatcherInterface $eventDispatcher = null;
    private ?PlatformRegistry $registry = null;
    private ?Publisher $publisher = null;
    private ?SendTo $sendTo = null;
    private ?OptionsManager $optionsManager = null;
    private ?WpTokenStore $tokenStore = null;

    private bool $booted = false;

    private function __construct()
    {
        // Singleton — use Plugin::instance().
    }

    /**
     * Get the singleton plugin instance.
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Boot the plugin — register all hooks and initialize services.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        // Build services.
        $this->buildServices();

        // Admin hooks.
        if (is_admin()) {
            $this->registerAdminHooks();
        }

        // REST API.
        add_action('rest_api_init', [$this, 'registerRestRoutes']);

        // Post publishing hook.
        add_action('transition_post_status', [$this->postPublisher(), 'onTransitionPostStatus'], 10, 3);
    }

    // ── Service accessors ────────────────────────────────────────────────

    public function config(): OwlstackConfig
    {
        if ($this->config === null) {
            $this->buildServices();
        }

        return $this->config;
    }

    public function httpClient(): HttpClientInterface
    {
        if ($this->httpClient === null) {
            $this->httpClient = new WpHttpClient();
        }

        return $this->httpClient;
    }

    public function eventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = new WpEventDispatcher();
        }

        return $this->eventDispatcher;
    }

    public function optionsManager(): OptionsManager
    {
        if ($this->optionsManager === null) {
            $this->optionsManager = new OptionsManager();
        }

        return $this->optionsManager;
    }

    public function tokenStore(): WpTokenStore
    {
        if ($this->tokenStore === null) {
            $this->tokenStore = new WpTokenStore();
        }

        return $this->tokenStore;
    }

    public function registry(): PlatformRegistry
    {
        if ($this->registry === null) {
            $this->buildPlatforms();
        }

        return $this->registry;
    }

    public function publisher(): Publisher
    {
        if ($this->publisher === null) {
            $this->publisher = new Publisher(
                platforms: $this->registry(),
                eventDispatcher: $this->eventDispatcher(),
            );
        }

        return $this->publisher;
    }

    public function sendTo(): SendTo
    {
        if ($this->sendTo === null) {
            $this->sendTo = new SendTo(
                publisher: $this->publisher(),
                config: $this->config(),
                registry: $this->registry(),
            );
        }

        return $this->sendTo;
    }

    // ── Internal setup ───────────────────────────────────────────────────

    private function buildServices(): void
    {
        $manager = $this->optionsManager();
        $this->config = $manager->buildConfig();
    }

    private function buildPlatforms(): void
    {
        $this->registry = new PlatformRegistry();
        $config = $this->config();
        $httpClient = $this->httpClient();

        $hashtagExtractor = new HashtagExtractor();
        $truncator = new CharacterTruncator();

        if ($config->hasPlatform('telegram')) {
            $formatter = new TelegramFormatter($hashtagExtractor, $truncator);
            $platform = new TelegramPlatform(
                credentials: $config->credentials('telegram'),
                httpClient: $httpClient,
                formatter: $formatter,
            );
            $this->registry->register($platform);
        }

        if ($config->hasPlatform('twitter')) {
            $formatter = new TwitterFormatter($hashtagExtractor, $truncator);
            $platform = new TwitterPlatform(
                credentials: $config->credentials('twitter'),
                httpClient: $httpClient,
                formatter: $formatter,
            );
            $this->registry->register($platform);
        }

        if ($config->hasPlatform('facebook')) {
            $formatter = new FacebookFormatter($hashtagExtractor, $truncator);
            $graphVersion = $config->credentials('facebook')?->get('default_graph_version', 'v21.0') ?? 'v21.0';
            $platform = new FacebookPlatform(
                credentials: $config->credentials('facebook'),
                httpClient: $httpClient,
                formatter: $formatter,
                graphVersion: $graphVersion,
            );
            $this->registry->register($platform);
        }
    }

    private function registerAdminHooks(): void
    {
        $settingsPage = new SettingsPage($this->optionsManager());
        add_action('admin_menu', [$settingsPage, 'register']);
        add_action('admin_init', [$settingsPage, 'registerSettings']);

        $metaBox = new MetaBox();
        add_action('add_meta_boxes', [$metaBox, 'register']);
        add_action('save_post', [$metaBox, 'save'], 10, 2);

        $logsPage = new DeliveryLogsPage();
        add_action('admin_menu', [$logsPage, 'register']);

        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    /**
     * Register REST API routes.
     */
    public function registerRestRoutes(): void
    {
        $controller = new OwlstackRestController($this);
        $controller->registerRoutes();
    }

    /**
     * Enqueue admin CSS and JS on Owlstack admin pages.
     */
    public function enqueueAdminAssets(string $hook): void
    {
        $owlstackPages = [
            'toplevel_page_owlstack',
            'owlstack_page_owlstack-logs',
        ];

        // Also load on post edit screens for the meta box.
        $postPages = ['post.php', 'post-new.php'];

        if (! in_array($hook, array_merge($owlstackPages, $postPages), true)) {
            return;
        }

        wp_enqueue_style(
            'owlstack-admin',
            OWLSTACK_URL . 'assets/css/admin.css',
            [],
            OWLSTACK_VERSION,
        );

        wp_enqueue_script(
            'owlstack-admin',
            OWLSTACK_URL . 'assets/js/admin.js',
            [],
            OWLSTACK_VERSION,
            true,
        );

        wp_localize_script('owlstack-admin', 'owlstackAdmin', [
            'restUrl' => rest_url('owlstack/v1/'),
            'nonce'   => wp_create_nonce('wp_rest'),
        ]);
    }

    private function postPublisher(): PostPublisher
    {
        return new PostPublisher($this->sendTo());
    }

    /**
     * Prevent cloning.
     */
    private function __clone()
    {
    }
}
