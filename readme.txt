=== Owlstack ===
Contributors: alihesari
Donate link: https://owlstack.dev
Tags: social media, auto publish, telegram, twitter, facebook
Requires at least: 6.4
Tested up to: 6.9
Stable tag: 1.1.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Publish content to Telegram, X (Twitter), Facebook, Instagram, LinkedIn, Discord, and more — directly from WordPress.

== Description ==

Owlstack lets you automatically share your WordPress posts to social media platforms with a single click or on publish. Powered by the [Owlstack Core](https://owlstack.dev) engine, it provides a seamless publishing experience.

**Supported Platforms:**

* **Telegram** — Send posts to channels and groups via Bot API
* **X (Twitter)** — Tweet your posts with media support
* **Facebook** — Share posts to Facebook Pages
* **Instagram** — Publish to Instagram via the Graph API
* **LinkedIn** — Share posts to LinkedIn profiles and company pages
* **Discord** — Send posts to Discord channels via webhooks
* **Pinterest** — Pin content to Pinterest boards
* **Reddit** — Submit posts to subreddits
* **Slack** — Post to Slack channels via webhooks
* **Tumblr** — Share posts to Tumblr blogs
* **WhatsApp** — Send messages via WhatsApp Business API

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

    // Publish to a specific platform by name
    owlstack()->publish($post, 'linkedin');

    // Publish to all configured platforms
    owlstack()->toAll($post);

== Installation ==

1. Upload the `owlstack` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Owlstack > Settings** to configure your platform credentials.

== Frequently Asked Questions ==

= What platforms are supported? =

Telegram, X (Twitter), Facebook, Instagram, LinkedIn, Discord, Pinterest, Reddit, Slack, Tumblr, and WhatsApp are all supported.

= Does this plugin require Composer? =

No. All dependencies are bundled with the plugin. Simply install and activate it from your WordPress admin panel.

= How do I get API credentials for each platform? =

* **Telegram:** Create a bot via [@BotFather](https://t.me/botfather) and get your Bot API Token.
* **Twitter/X:** Create an app at [developer.twitter.com](https://developer.twitter.com) and generate your API keys.
* **Facebook:** Create an app at [developers.facebook.com](https://developers.facebook.com), create a Page, and generate a Page Access Token.
* **Instagram:** Use the same Facebook app and connect your Instagram Business account.
* **LinkedIn:** Create an app at [linkedin.com/developers](https://www.linkedin.com/developers/) and request the necessary permissions.
* **Discord:** Create a webhook in your Discord server's channel settings.
* **Pinterest:** Create an app at [developers.pinterest.com](https://developers.pinterest.com).
* **Reddit:** Create an app at [reddit.com/prefs/apps](https://www.reddit.com/prefs/apps).
* **Slack:** Create an incoming webhook or Slack app at [api.slack.com](https://api.slack.com).
* **Tumblr:** Register an app at [tumblr.com/oauth/apps](https://www.tumblr.com/oauth/apps).
* **WhatsApp:** Set up WhatsApp Business API via [Meta for Developers](https://developers.facebook.com).

= Can I customize which post types are supported? =

Yes. Use the `owlstack_supported_post_types` filter:

    add_filter('owlstack_supported_post_types', function ($types) {
        return ['post', 'page', 'product'];
    });

= Is there proxy support? =

Yes. Configure proxy settings in **Owlstack > Settings** for servers that cannot access social media APIs directly.

= Where are delivery logs stored? =

Delivery logs are stored in a custom database table (`wp_owlstack_delivery_logs`). You can view them under **Owlstack > Delivery Logs**.

= What is the OwlStack Cloud connection? =

If you use the OwlStack Cloud dashboard, you can connect it to your site without sharing a WordPress username or Application Password. Go to **Owlstack > Cloud**, generate a site token, and paste it into the OwlStack dashboard together with your site URL. The token is scoped to a minimal set of plugin endpoints: OwlStack Cloud can create posts (as the author you choose, as a draft or published based on your setting), upload images for featured media, and remove posts it created — nothing else. Only a hash of the token is stored, you can revoke it at any time, and the plugin never makes outbound calls to OwlStack Cloud. The feature is entirely optional and inactive until you generate a token.

== Third-Party Services ==

This plugin connects to external third-party services to publish your content. Data such as post title, excerpt, URL, and featured image may be sent to the platforms you configure. **No data is sent unless you explicitly configure and enable a platform.**

**Telegram**

* Service: [Telegram Bot API](https://core.telegram.org/bots/api)
* Terms of Service: [https://telegram.org/tos](https://telegram.org/tos)
* Privacy Policy: [https://telegram.org/privacy](https://telegram.org/privacy)

**X (Twitter)**

* Service: [X API](https://developer.x.com/en/docs)
* Terms of Service: [https://x.com/en/tos](https://x.com/en/tos)
* Privacy Policy: [https://x.com/en/privacy](https://x.com/en/privacy)

**Facebook**

* Service: [Facebook Graph API](https://developers.facebook.com/docs/graph-api/)
* Terms of Service: [https://www.facebook.com/terms.php](https://www.facebook.com/terms.php)
* Privacy Policy: [https://www.facebook.com/privacy/policy/](https://www.facebook.com/privacy/policy/)

**Instagram**

* Service: [Instagram Graph API](https://developers.facebook.com/docs/instagram-api/)
* Terms of Service: [https://help.instagram.com/581066165581870](https://help.instagram.com/581066165581870)
* Privacy Policy: [https://privacycenter.instagram.com/policy](https://privacycenter.instagram.com/policy)

**LinkedIn**

* Service: [LinkedIn API](https://learn.microsoft.com/en-us/linkedin/)
* Terms of Service: [https://www.linkedin.com/legal/user-agreement](https://www.linkedin.com/legal/user-agreement)
* Privacy Policy: [https://www.linkedin.com/legal/privacy-policy](https://www.linkedin.com/legal/privacy-policy)

**Discord**

* Service: [Discord Webhooks](https://discord.com/developers/docs/resources/webhook)
* Terms of Service: [https://discord.com/terms](https://discord.com/terms)
* Privacy Policy: [https://discord.com/privacy](https://discord.com/privacy)

**Pinterest**

* Service: [Pinterest API](https://developers.pinterest.com/docs/api/v5/)
* Terms of Service: [https://policy.pinterest.com/en/terms-of-service](https://policy.pinterest.com/en/terms-of-service)
* Privacy Policy: [https://policy.pinterest.com/en/privacy-policy](https://policy.pinterest.com/en/privacy-policy)

**Reddit**

* Service: [Reddit API](https://www.reddit.com/dev/api/)
* Terms of Service: [https://www.redditinc.com/policies/user-agreement](https://www.redditinc.com/policies/user-agreement)
* Privacy Policy: [https://www.reddit.com/policies/privacy-policy](https://www.reddit.com/policies/privacy-policy)

**Slack**

* Service: [Slack Incoming Webhooks](https://api.slack.com/messaging/webhooks)
* Terms of Service: [https://slack.com/terms-of-service](https://slack.com/terms-of-service)
* Privacy Policy: [https://slack.com/privacy-policy](https://slack.com/privacy-policy)

**Tumblr**

* Service: [Tumblr API](https://www.tumblr.com/docs/en/api/v2)
* Terms of Service: [https://www.tumblr.com/policy/en/terms-of-service](https://www.tumblr.com/policy/en/terms-of-service)
* Privacy Policy: [https://www.tumblr.com/privacy/en](https://www.tumblr.com/privacy/en)

**WhatsApp**

* Service: [WhatsApp Business API](https://developers.facebook.com/docs/whatsapp/)
* Terms of Service: [https://www.whatsapp.com/legal/terms-of-service](https://www.whatsapp.com/legal/terms-of-service)
* Privacy Policy: [https://www.whatsapp.com/legal/privacy-policy](https://www.whatsapp.com/legal/privacy-policy)

== Screenshots ==

1. Settings page with platform credential configuration.
2. Publish meta box on the post editor.
3. Delivery logs page showing publishing activity.

== Changelog ==

= 1.1.0 =
* New: OwlStack Cloud connection (**Owlstack > Cloud**). Generate a revocable site token so the OwlStack Cloud dashboard can publish posts to this site without a WordPress username or Application Password.
* New: Incoming content controls — always draft, always publish, or honor the requested status; configurable post author and post type.
* New: REST endpoints under `owlstack/v1/cloud` for site info, post creation, image upload, and removal of Cloud-created posts. Token-authenticated; only a SHA-256 hash of the token is stored.
* The plugin makes no outbound calls to OwlStack Cloud; the feature is inactive until a token is generated.
* The Cloud page now sits directly after Settings in the Owlstack menu, instead of below every platform.
* Settings gains a short, dismissible explanation of what OwlStack Cloud is and how it differs from publishing directly from this site.

= 1.0.1 =
* Improved settings update feedback on the platform settings pages.
* Hardened input sanitization in options handling and settings registration.
* Added a third-party services section listing the terms and privacy policies of every supported platform.
* Added platform documentation links and clearer setup guidance in settings.
* Post body handling now falls back to the excerpt or trimmed content.
* Telegram: platform-specific post transformations, plus placeholders and hints on the username fields.
* Corrected author information in the plugin header.

= 1.0.0 =
* Initial public release.
* Support for 11 platforms: Telegram, X (Twitter), Facebook, Instagram, LinkedIn, Discord, Pinterest, Reddit, Slack, Tumblr, and WhatsApp.
* Admin settings page with platform credential management.
* Post editor meta box for platform selection and auto-publish.
* Delivery logs page with filtering and pagination.
* REST API endpoints for connection testing, publishing, and log management.
* WordPress HTTP API integration.
* Encrypted OAuth token storage.
* Event system with WordPress action hooks.
* Full developer API via `owlstack()` helper function.

== Upgrade Notice ==

= 1.1.0 =
Adds an optional OwlStack Cloud connection. Generate a revocable site token under Owlstack > Cloud to let the Cloud dashboard publish to this site without a WordPress username or Application Password. Existing setups are unaffected and the feature stays inactive until you generate a token.

= 1.0.1 =
Maintenance release. Stronger input sanitization, clearer setup guidance, and Telegram formatting improvements.

= 1.0.0 =
Initial release. Configure your platform credentials under Owlstack > Settings after activation.
