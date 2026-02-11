# Synglify for WordPress

WordPress plugin for [Synglify](https://synglify.com).

Integrates `synglify/synglify-core` into WordPress sites — admin settings, post meta boxes, delivery logs, REST API endpoints, and more.

## Requirements

- PHP 8.1+
- WordPress 6.4+
- Composer

## Installation

```bash
cd wp-content/plugins/synglify-wordpress
composer install
```

Activate the plugin from WP Admin → Plugins.

## Features

- **Admin Settings Page** — Configure platform credentials (Telegram, X/Twitter, Facebook) and proxy settings
- **Publish Meta Box** — Select platforms and publish directly from the post editor
- **Delivery Logs** — Track all publishing activity with status, errors, and external links
- **REST API** — AJAX endpoints for connection testing, manual publishing, and log management
- **WP HTTP API** — Uses native WordPress HTTP functions instead of cURL
- **WordPress Events** — Hooks into `do_action` for `synglify_post_published` and `synglify_post_failed`
- **Token Storage** — Encrypted OAuth token storage via `wp_options`

## Usage

### From PHP (theme or plugin code)

```php
// Publish to Telegram
synglify()->telegram('Hello from WordPress!');

// Publish to Twitter/X
synglify()->twitter('Hello from WordPress!');

// Publish to Facebook
synglify()->facebook('Hello from WordPress!', 'link', ['link' => 'https://example.com']);

// Publish to all configured platforms
$post = new \Synglify\Core\Content\Post(
    title: 'My Post',
    body: 'Hello world!',
    url: 'https://example.com/my-post',
);
synglify()->toAll($post);
```

### Auto-publish on post publish

Enable via the Synglify meta box in the post editor. Select platforms and the post will be published automatically when you hit "Publish".

## Hooks

### Actions

- `synglify_post_published` — Fired after successful publishing. Receives `PostPublished` event.
- `synglify_post_failed` — Fired after a publishing failure. Receives `PostFailed` event.
- `synglify_before_publish` — Fired before publishing starts. Receives `WP_Post` and platform names.
- `synglify_after_publish` — Fired after publishing completes. Receives `WP_Post` and results.

### Filters

- `synglify_supported_post_types` — Filter which post types show the meta box. Default: `['post']`.
- `synglify_post_data` — Filter the `Post` object before publishing.
- `synglify_publish_options` — Filter platform-specific options before publishing.

## Dependencies

- `synglify/synglify-core` (bundled via Composer)

## License

MIT
