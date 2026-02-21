=== Owlstack ===
Contributors: alihesari
Donate link: https://owlstack.com
Tags: social media, telegram, twitter, facebook, auto publish
Requires at least: 6.4
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Publish content to Telegram, X (Twitter), and Facebook directly from WordPress.

== Description ==

Owlstack lets you automatically share your WordPress posts to social media platforms with a single click or on publish. Powered by the [owlstack-core](https://github.com/owlstack/owlstack-core) engine, it provides a seamless publishing experience.

**Supported Platforms:**

* **Telegram** — Send posts to channels and groups via Bot API
* **X (Twitter)** — Tweet your posts with media support
* **Facebook** — Share posts to Facebook Pages

**Key Features:**

* **Admin Settings Page** — Configure platform credentials and proxy settings from a clean admin UI
* **Publish Meta Box** — Select platforms and publish directly from the post editor
* **Auto-Publish** — Automatically share posts when they're published
* **Delivery Logs** — Track all publishing activity with status, errors, and external links
* **REST API** — AJAX endpoints for connection testing, manual publishing, and log management
* **WP HTTP API** — Uses native WordPress HTTP functions instead of cURL
* **WordPress Events** — Full hook support with `owlstack_post_published` and `owlstack_post_failed` actions
* **Token Storage** — Encrypted OAuth token storage via `wp_options`

**For Developers:**

Owlstack provides a simple PHP API for publishing from themes or other plugins:

    // Publish to Telegram
    owlstack()->telegram('Hello from WordPress!');

    // Publish to Twitter/X
    owlstack()->twitter('Hello from WordPress!');

    // Publish to all configured platforms
    owlstack()->toAll($post);

== Installation ==

1. Upload the `owlstack` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Owlstack > Settings** to configure your platform credentials.

== Frequently Asked Questions ==

= What platforms are supported? =

Currently Telegram, X (Twitter), and Facebook are supported. More platforms will be added in future releases.

= Does this plugin require Composer? =

No. All dependencies are bundled with the plugin. Simply install and activate it from your WordPress admin panel.

= How do I get API credentials for each platform? =

* **Telegram:** Create a bot via [@BotFather](https://t.me/botfather) and get your Bot API Token.
* **Twitter/X:** Create an app at [developer.twitter.com](https://developer.twitter.com) and generate your API keys.
* **Facebook:** Create an app at [developers.facebook.com](https://developers.facebook.com), create a Page, and generate a Page Access Token.

= Can I customize which post types are supported? =

Yes. Use the `owlstack_supported_post_types` filter:

    add_filter('owlstack_supported_post_types', function ($types) {
        return ['post', 'page', 'product'];
    });

= Is there proxy support? =

Yes. Configure proxy settings in **Owlstack > Settings** for servers that cannot access social media APIs directly.

= Where are delivery logs stored? =

Delivery logs are stored in a custom database table (`wp_owlstack_delivery_logs`). You can view them under **Owlstack > Delivery Logs**.

== Screenshots ==

1. Settings page with platform credential configuration.
2. Publish meta box on the post editor.
3. Delivery logs page showing publishing activity.

== Changelog ==

= 1.0.0 =
* Initial public release.
* Telegram, Twitter/X, and Facebook platform support.
* Admin settings page with platform credential management.
* Post editor meta box for platform selection and auto-publish.
* Delivery logs page with filtering and pagination.
* REST API endpoints for connection testing, publishing, and log management.
* WordPress HTTP API integration.
* Encrypted OAuth token storage.
* Event system with WordPress action hooks.
* Full developer API via `owlstack()` helper function.

== Upgrade Notice ==

= 1.0.0 =
Initial release. Configure your platform credentials under Owlstack > Settings after activation.
