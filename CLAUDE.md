# CLAUDE.md

> **Read and follow ALL rules in `AGENTS.md` first.** This file contains Claude-specific instructions only.
> Do NOT treat this file as standalone — `AGENTS.md` is the source of truth for all rules.

---

## Context Loading Priority

When starting a new session, read files in this order:

1. `AGENTS.md` — mandatory rules (text domain, architecture, code style, git workflow)
2. `owlstack.php` — plugin header (verify Text Domain is `owlstack-wp`)
3. The specific files related to the current task

---

## Critical Reminder

**Text domain = `'owlstack-wp'`** (with `-wp`). The slug `'owlstack'` is ONLY for admin menus and settings pages.

If you see `'owlstack'` as a second argument in `__()`, `_e()`, `esc_html__()`, `esc_html_e()`, `_n()`, or similar translation functions — **that is a bug**. Fix it to `'owlstack-wp'`.

---

## Tool Usage

- **Prefer grep and find** to locate code — never guess file locations or class names
- **Run PHPCS after every change**: `php -d xdebug.mode=off vendor/bin/phpcs --standard=WordPress src/ owlstack.php`
- **Run I18n check specifically**: `php -d xdebug.mode=off vendor/bin/phpcs --standard=WordPress --sniffs=WordPress.WP.I18n src/ owlstack.php`
- **Use `git diff`** to verify changes before committing
