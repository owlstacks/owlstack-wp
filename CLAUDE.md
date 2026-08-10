# CLAUDE.md

> **Read and follow ALL rules in `AGENTS.md` first.** This file contains Claude-specific instructions only.
> Do NOT treat this file as standalone — `AGENTS.md` is the source of truth for all rules.

---

## Context Loading Priority

When starting a new session, read files in this order:

1. `AGENTS.md` — mandatory rules (text domain, architecture, code style, git workflow)
2. `owlstack.php` — plugin header (verify Text Domain is `owlstack`)
3. The specific files related to the current task

---

## Critical Reminder

**Text domain = `'owlstack'`** — matching the plugin folder name inside `wp-content/plugins/`. The slug `'owlstack'` is used for both admin menus/settings pages AND the text domain.

If you see `'owlstack-wp'` as a second argument in `__()`, `_e()`, `esc_html__()`, `esc_html_e()`, `_n()`, or similar translation functions — **that is a bug**. Fix it to `'owlstack'`.

---

## Tool Usage

- **Prefer grep and find** to locate code — never guess file locations or class names
- **Run PHPCS after every change**: `php -d xdebug.mode=off vendor/bin/phpcs` (uses `phpcs.xml.dist` — WordPress security/I18n/DB/PHP sniffs on the repo's PSR-style code; do NOT run the raw `--standard=WordPress` ruleset, the codebase intentionally doesn't follow its formatting rules)
- **Run I18n check specifically**: `php -d xdebug.mode=off vendor/bin/phpcs --sniffs=WordPress.WP.I18n`
- **Use `git diff`** to verify changes before committing
