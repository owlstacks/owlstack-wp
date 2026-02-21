# Owlstack for WordPress

WordPress plugin for [Owlstack](https://owlstack.dev) — publish content to multiple social media platforms directly from WordPress.

Integrates `owlstack/owlstack-core` into WordPress sites — admin settings, post meta boxes, delivery logs, REST API endpoints, and more.

## Supported Platforms

| Platform | Status |
|----------|--------|
| Telegram | ✅ Supported |
| X (Twitter) | ✅ Supported |
| Facebook | ✅ Supported |
| Instagram | ✅ Supported |
| LinkedIn | ✅ Supported |
| Discord | ✅ Supported |
| Pinterest | ✅ Supported |
| Reddit | ✅ Supported |
| Slack | ✅ Supported |
| Tumblr | ✅ Supported |
| WhatsApp | ✅ Supported |

## Requirements

- PHP 8.1+
- WordPress 6.4+

## Installation

1. Download the plugin zip or clone this repository into `wp-content/plugins/owlstack`.
2. For development, run `composer install` in the plugin directory.
3. Activate the plugin from WP Admin → Plugins.
4. Go to **Owlstack > Settings** to configure your platform credentials.

## Features

- **Admin Settings Page** — Configure platform credentials and proxy settings from a clean admin UI
- **Publish Meta Box** — Select platforms and publish directly from the post editor
- **Auto-Publish** — Automatically share posts when they're published
- **Delivery Logs** — Track all publishing activity with status, errors, and external links
- **REST API** — AJAX endpoints for connection testing, manual publishing, and log management
- **WP HTTP API** — Uses native WordPress HTTP functions instead of cURL
- **WordPress Events** — Full hook support with `owlstack_post_published` and `owlstack_post_failed` actions
- **Token Storage** — Encrypted OAuth token storage via `wp_options`

## Usage

### From PHP (theme or plugin code)

```php
// Publish to Telegram
owlstack()->telegram('Hello from WordPress!');

// Publish to Twitter/X
owlstack()->twitter('Hello from WordPress!');
// or
owlstack()->x('Hello from WordPress!');

// Publish to Facebook
owlstack()->facebook('Hello from WordPress!', 'link', ['link' => 'https://example.com']);

// Publish to a specific platform by name
owlstack()->publish($post, 'linkedin');

// Publish to all configured platforms
$post = new \Owlstack\Core\Content\Post(
    title: 'My Post',
    body: 'Hello world!',
    url: 'https://example.com/my-post',
);
owlstack()->toAll($post);
```

### Auto-publish on post publish

Enable via the Owlstack meta box in the post editor. Select platforms and the post will be published automatically when you hit "Publish".

## Hooks

### Actions

- `owlstack_post_published` — Fired after successful publishing. Receives `PostPublished` event.
- `owlstack_post_failed` — Fired after a publishing failure. Receives `PostFailed` event.
- `owlstack_before_publish` — Fired before publishing starts. Receives `WP_Post` and platform names.
- `owlstack_after_publish` — Fired after publishing completes. Receives `WP_Post` and results.

### Filters

- `owlstack_supported_post_types` — Filter which post types show the meta box. Default: `['post']`.
- `owlstack_post_data` — Filter the `Post` object before publishing.
- `owlstack_publish_options` — Filter platform-specific options before publishing.

## Dependencies

- [`owlstack/owlstack-core`](https://github.com/AliKarimi79/owlstack-core) (bundled via Composer)

## License

GPL-2.0-or-later
