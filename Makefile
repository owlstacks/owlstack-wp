# ────────────────────────────────────────────────────────────
# Owlstack WordPress Plugin — Makefile
# ────────────────────────────────────────────────────────────

PLUGIN_SLUG  := owlstack
VERSION      ?= $(shell grep -i 'Version:' owlstack.php | head -1 | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')
DIST_DIR     := dist
BUILD_DIR    := $(DIST_DIR)/$(PLUGIN_SLUG)
ZIP_FILE     := $(DIST_DIR)/$(PLUGIN_SLUG)-$(VERSION).zip

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

# ── Help ─────────────────────────────────────────────────────

.PHONY: help
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'
