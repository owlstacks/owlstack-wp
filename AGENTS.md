# AGENTS.md

> **This is the single source of truth for all AI coding agents** working in this repository.
> All agents (GitHub Copilot, Claude, ChatGPT, Cursor, Windsurf, Codex, etc.) MUST follow these rules.
> Agent-specific files (`CLAUDE.md`, `.github/copilot-instructions.md`) extend this file — they do NOT replace it.

---

## Project Overview

**OwlStack WP** is a WordPress plugin that integrates [OwlStack Core](https://github.com/AliYmworworking/owlstack-core) with WordPress, enabling publishing of WordPress posts to social media platforms.

> **Brand name:** "OwlStack" (capital S). The domain is `owlstack.dev`.

| Property | Value |
|----------|-------|
| **Type** | WordPress plugin |
| **Plugin slug** | `owlstack-wp` |
| **Text Domain** | `owlstack-wp` |
| **Admin menu slug** | `owlstack` |
| **PHP Version** | 8.1+ (strict types required) |
| **Dependencies** | `owlstack/owlstack-core` (Composer) |
| **License** | GPL-2.0-or-later |

---

## CRITICAL: Text Domain Rules

The WordPress text domain **MUST** be `owlstack-wp` — matching the plugin folder name. This is a WordPress requirement for Plugin Check compatibility.

### What is `'owlstack-wp'`

The text domain used in ALL translation functions:

```php
// CORRECT — always use 'owlstack-wp' as text domain
__('Settings', 'owlstack-wp')
_e('Save', 'owlstack-wp')
esc_html__('Platform', 'owlstack-wp')
esc_html_e('Status', 'owlstack-wp')
_n('%s item', '%s items', $count, 'owlstack-wp')
```

### What is `'owlstack'` (without `-wp`)

The admin **menu slug** and **settings page slug** — NOT a text domain:

```php
// CORRECT — 'owlstack' is the menu/page slug
menu_slug: 'owlstack',
parent_slug: 'owlstack',
do_settings_sections('owlstack');
add_settings_section('section_id', $title, $callback, 'owlstack');
add_settings_field('field_id', $title, $callback, 'owlstack', 'section_id');
```

### Common mistake

**NEVER** use `'owlstack'` (without `-wp`) as a text domain:

```php
// WRONG — 'owlstack' is NOT the text domain
__('Settings', 'owlstack')    // ← WRONG
esc_html_e('Save', 'owlstack') // ← WRONG
```

The plugin header in `owlstack.php` MUST say:
```
Text Domain: owlstack-wp
```

---

## Directory Structure

```
owlstack.php              # Main plugin file (plugin header, bootstrap)
src/
├── Plugin.php             # Core plugin class (hooks, assets, localization)
├── Activator.php          # Plugin activation logic
├── Uninstaller.php        # Plugin uninstall logic
├── helpers.php            # Helper functions (owlstack() singleton)
├── Admin/                 # Admin UI (settings pages, meta box)
│   ├── SettingsPage.php
│   ├── DeliveryLogsPage.php
│   ├── MetaBox.php
│   └── views/             # PHP view templates
├── Auth/                  # WordPress token storage
├── Database/              # Custom DB tables (delivery logs)
├── Publishing/            # WordPress-specific publishing logic
└── Rest/                  # REST API endpoints
```

---

## Coding Standards

- Follow **WordPress Coding Standards** (PHPCS with `WordPress` ruleset)
- Use `phpcs:ignore` or `phpcs:disable` comments ONLY when a violation is intentional and unavoidable
- All PHP files must have `declare(strict_types=1);`
- All translatable strings must use the `'owlstack-wp'` text domain
- Option names and hook names use the `owlstack_` prefix (e.g., `owlstack_settings`, `owlstack_delivery_logs`)

---

## Git Workflow

1. Create a feature branch from `main`
2. Make changes and run PHPCS: `php -d xdebug.mode=off vendor/bin/phpcs --standard=WordPress src/ owlstack.php`
3. Commit with conventional commit messages (`fix:`, `feat:`, `docs:`, etc.)
4. Push and create a PR against `main`

---

## Testing Checklist

Before submitting a PR:

- [ ] PHPCS passes on `src/` and `owlstack.php`
- [ ] Text domain is `'owlstack-wp'` in ALL translation functions
- [ ] Plugin header `Text Domain:` matches `owlstack-wp`
- [ ] No `'owlstack'` used as text domain (only as menu/settings slug)
