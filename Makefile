# ────────────────────────────────────────────────────────────
# Owlstack WordPress Plugin — Makefile
# ────────────────────────────────────────────────────────────

PLUGIN_SLUG  := owlstack
VERSION      ?= $(shell grep -i 'Version:' owlstack.php | head -1 | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')
DIST_DIR     := dist
BUILD_DIR    := $(DIST_DIR)/$(PLUGIN_SLUG)
ZIP_FILE     := $(DIST_DIR)/$(PLUGIN_SLUG)-$(VERSION).zip

# SVN settings
SVN_URL      := https://plugins.svn.wordpress.org/$(PLUGIN_SLUG)
SVN_DIR      := .svn-wp
SVN_USER     := alihesari

.DEFAULT_GOAL := help

# ── Development ──────────────────────────────────────────────

.PHONY: install
install: ## Install all Composer dependencies (dev + prod)
	composer install --prefer-dist

.PHONY: lint
lint: ## Run PHPCS (WordPress coding standards)
	php -d xdebug.mode=off vendor/bin/phpcs --standard=WordPress src/ owlstack.php

.PHONY: lint-fix
lint-fix: ## Auto-fix PHPCS violations where possible
	php -d xdebug.mode=off vendor/bin/phpcbf --standard=WordPress src/ owlstack.php

.PHONY: test
test: ## Run PHPUnit tests
	vendor/bin/phpunit

# ── Build / Release ─────────────────────────────────────────

.PHONY: build
build: clean ## Build distribution zip for WordPress marketplace
	@echo "==> Building $(PLUGIN_SLUG) v$(VERSION)"

	@# Install production-only dependencies.
	composer install --no-dev --prefer-dist --optimize-autoloader --quiet

	@# Create build directory and sync files.
	@mkdir -p $(BUILD_DIR)
	rsync -rc --exclude-from=".distignore" ./ $(BUILD_DIR)/

	@# Remove dev vendor packages (safety net).
	rm -rf $(BUILD_DIR)/vendor/phpunit \
	       $(BUILD_DIR)/vendor/phpcsstandards \
	       $(BUILD_DIR)/vendor/squizlabs \
	       $(BUILD_DIR)/vendor/wp-coding-standards \
	       $(BUILD_DIR)/vendor/dealerdirect \
	       $(BUILD_DIR)/vendor/staabm \
	       $(BUILD_DIR)/vendor/phar-io \
	       $(BUILD_DIR)/vendor/sebastian \
	       $(BUILD_DIR)/vendor/theseer \
	       $(BUILD_DIR)/vendor/nikic \
	       $(BUILD_DIR)/vendor/myclabs \
	       $(BUILD_DIR)/vendor/bin

	@# Create the zip.
	@echo "==> Creating zip…"
	cd $(DIST_DIR) && zip -rq ../$(ZIP_FILE) $(PLUGIN_SLUG)/

	@# Clean up build directory.
	rm -rf $(BUILD_DIR)

	@# Restore dev dependencies.
	@echo "==> Restoring dev dependencies…"
	composer install --quiet

	@echo "==> Done! $(ZIP_FILE)"
	@echo "    Size: $$(du -h $(ZIP_FILE) | cut -f1)"

.PHONY: clean
clean: ## Remove previous build artifacts
	rm -rf $(DIST_DIR)

# ── SVN / WordPress.org Release ─────────────────────────────

.PHONY: svn-checkout
svn-checkout: ## One-time: checkout WordPress.org SVN repo
	@if [ -d "$(SVN_DIR)/trunk" ]; then \
		echo "==> SVN working copy already exists at $(SVN_DIR)"; \
		echo "    Run 'make svn-update' to pull latest changes."; \
	else \
		echo "==> Checking out $(SVN_URL)…"; \
		svn checkout $(SVN_URL) $(SVN_DIR) --username $(SVN_USER); \
		echo "==> Done! SVN working copy at $(SVN_DIR)"; \
	fi

.PHONY: svn-update
svn-update: ## Pull latest changes from WordPress.org SVN
	@if [ ! -d "$(SVN_DIR)/trunk" ]; then \
		echo "Error: No SVN working copy. Run 'make svn-checkout' first."; \
		exit 1; \
	fi
	svn update $(SVN_DIR)

.PHONY: svn-sync
svn-sync: ## Sync plugin files to SVN trunk (runs build first)
	@if [ ! -d "$(SVN_DIR)/trunk" ]; then \
		echo "Error: No SVN working copy. Run 'make svn-checkout' first."; \
		exit 1; \
	fi
	@echo "==> Preparing production build for SVN…"

	@# Install production-only dependencies.
	composer install --no-dev --prefer-dist --optimize-autoloader --quiet

	@# Sync to SVN trunk, respecting .distignore.
	@echo "==> Syncing to SVN trunk…"
	rsync -rc --delete --exclude-from=".distignore" ./ $(SVN_DIR)/trunk/

	@# Remove dev vendor packages (safety net).
	rm -rf $(SVN_DIR)/trunk/vendor/phpunit \
	       $(SVN_DIR)/trunk/vendor/phpcsstandards \
	       $(SVN_DIR)/trunk/vendor/squizlabs \
	       $(SVN_DIR)/trunk/vendor/wp-coding-standards \
	       $(SVN_DIR)/trunk/vendor/dealerdirect \
	       $(SVN_DIR)/trunk/vendor/staabm \
	       $(SVN_DIR)/trunk/vendor/phar-io \
	       $(SVN_DIR)/trunk/vendor/sebastian \
	       $(SVN_DIR)/trunk/vendor/theseer \
	       $(SVN_DIR)/trunk/vendor/nikic \
	       $(SVN_DIR)/trunk/vendor/myclabs \
	       $(SVN_DIR)/trunk/vendor/bin

	@# Track new and removed files in SVN.
	cd $(SVN_DIR) && svn add --force trunk
	cd $(SVN_DIR) && svn status trunk | awk '/^!/ {print $$2}' | xargs -I {} svn rm "{}"

	@# Restore dev dependencies.
	composer install --quiet

	@echo "==> Trunk synced. Run 'make svn-diff' to review or 'make svn-push' to commit."

.PHONY: svn-diff
svn-diff: ## Preview SVN changes before committing
	@if [ ! -d "$(SVN_DIR)/trunk" ]; then \
		echo "Error: No SVN working copy. Run 'make svn-checkout' first."; \
		exit 1; \
	fi
	@echo "==> SVN status:"
	cd $(SVN_DIR) && svn status
	@echo ""
	@echo "==> SVN diff (summary):"
	cd $(SVN_DIR) && svn diff --summarize

.PHONY: svn-push
svn-push: ## Commit SVN trunk to WordPress.org
	@if [ ! -d "$(SVN_DIR)/trunk" ]; then \
		echo "Error: No SVN working copy. Run 'make svn-checkout' first."; \
		exit 1; \
	fi
	@echo "==> Committing trunk v$(VERSION) to WordPress.org…"
	cd $(SVN_DIR) && svn commit --username $(SVN_USER) -m "Release $(VERSION)"
	@echo "==> Trunk committed."

.PHONY: svn-tag
svn-tag: ## Create SVN tag from trunk (copies remote, fast)
	@echo "==> Tagging v$(VERSION) on WordPress.org…"
	svn copy $(SVN_URL)/trunk $(SVN_URL)/tags/$(VERSION) \
		--username $(SVN_USER) \
		-m "Tagging version $(VERSION)"
	@echo "==> Tag $(VERSION) created. Plugin update will be live shortly."

.PHONY: svn-assets
svn-assets: ## Sync assets/ to SVN assets branch (banners, icons, screenshots)
	@if [ ! -d "$(SVN_DIR)/assets" ]; then \
		echo "Error: No SVN working copy. Run 'make svn-checkout' first."; \
		exit 1; \
	fi
	@if [ ! -d "wp-assets" ]; then \
		echo "Error: No wp-assets/ directory found."; \
		echo "  Create wp-assets/ with your banner/icon/screenshot files:"; \
		echo "    banner-1544x500.png   banner-772x250.png"; \
		echo "    icon-128x128.png      icon-256x256.png"; \
		echo "    screenshot-1.png      screenshot-2.png …"; \
		exit 1; \
	fi
	@echo "==> Syncing wp-assets/ to SVN assets/…"
	rsync -rc --delete wp-assets/ $(SVN_DIR)/assets/
	cd $(SVN_DIR) && svn add --force assets
	cd $(SVN_DIR) && svn status assets | awk '/^!/ {print $$2}' | xargs -I {} svn rm "{}"
	cd $(SVN_DIR) && svn commit --username $(SVN_USER) assets -m "Update plugin assets"
	@echo "==> Assets updated on WordPress.org."

.PHONY: release
release: lint test svn-sync svn-push svn-tag ## Full release: lint → test → sync → push → tag
	@echo ""
	@echo "==> 🎉 $(PLUGIN_SLUG) v$(VERSION) released to WordPress.org!"
	@echo "    https://wordpress.org/plugins/$(PLUGIN_SLUG)/"

.PHONY: version-check
version-check: ## Verify version is consistent across files
	@PHP_VER=$$(grep -i 'Version:' owlstack.php | head -1 | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]'); \
	README_VER=$$(grep -i 'Stable tag:' readme.txt | head -1 | sed 's/.*Stable tag:[[:space:]]*//' | tr -d '[:space:]'); \
	CONST_VER=$$(grep "OWLSTACK_VERSION" owlstack.php | head -1 | sed "s/.*'\\(.*\\)'.*/\\1/"); \
	echo "  owlstack.php header : $$PHP_VER"; \
	echo "  readme.txt Stable tag: $$README_VER"; \
	echo "  OWLSTACK_VERSION     : $$CONST_VER"; \
	if [ "$$PHP_VER" = "$$README_VER" ] && [ "$$PHP_VER" = "$$CONST_VER" ]; then \
		echo "  ✓ All versions match ($$PHP_VER)"; \
	else \
		echo "  ✗ VERSION MISMATCH — fix before releasing!"; \
		exit 1; \
	fi

.PHONY: version-bump
version-bump: ## Bump version: make version-bump V=1.2.0
	@if [ -z "$(V)" ]; then \
		echo "Usage: make version-bump V=1.2.0"; \
		exit 1; \
	fi
	@echo "==> Bumping version to $(V)…"
	@sed -i '' "s/^ \* Version:.*/ * Version:           $(V)/" owlstack.php
	@sed -i '' "s/^define('OWLSTACK_VERSION', '.*');/define('OWLSTACK_VERSION', '$(V)');/" owlstack.php
	@sed -i '' "s/^Stable tag:.*/Stable tag: $(V)/" readme.txt
	@echo "==> Version updated to $(V) in owlstack.php and readme.txt"
	@$(MAKE) --no-print-directory version-check

# ── Help ─────────────────────────────────────────────────────

.PHONY: help
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'
