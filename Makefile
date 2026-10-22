SHELL := /bin/sh
APP_CONTAINER := app-apptemplate
VITE_CONTAINER := app-apptemplate-vite
# APP_DIR is the path to the Laravel app inside the container
APP_DIR := /workspaces/app-apptemplate
# WORKING_DIR is the path to the Laravel app on the host machine
WORKING_DIR := $(shell pwd)
COMPOSE_FILE := $(WORKING_DIR)/docker-compose.yml
# Mirror path used when Docker cannot read WORKING_DIR directly (e.g., fuse mounts).
MIRROR_HOST_PATH ?= $(HOME)/workspace/app-template-linux
PROBE_IMAGE ?= alpine:3.20
USER_UID := $(shell id -u)
USER_GID := $(shell id -g)
CHAT_PROJECT ?= $(notdir $(CURDIR))
CHAT_NOTES_DIR ?= $(HOME)/session-notes/$(CHAT_PROJECT)

define sync_to_mirror
mkdir -p "$(MIRROR_HOST_PATH)"; \
if ! rsync -a --delete --exclude 'node_modules/.vite/' "$(WORKING_DIR)/" "$(MIRROR_HOST_PATH)/"; then \
	echo "Mirror sync hit permission issues. Repairing mirror ownership and retrying..."; \
	docker run --rm -v "$(MIRROR_HOST_PATH):/mnt" alpine sh -lc 'chown -R $(USER_UID):$(USER_GID) /mnt' >/dev/null; \
	rsync -a --delete --exclude 'node_modules/.vite/' "$(WORKING_DIR)/" "$(MIRROR_HOST_PATH)/"; \
fi
endef

# If APP_HOST_PATH is set, it wins. Otherwise probe direct mount and fall back to mirror.
define resolve_host_path
HOST_PATH="$(APP_HOST_PATH)"; \
if [ -n "$$HOST_PATH" ]; then \
	echo "$$HOST_PATH"; \
elif docker run --rm -v "$(WORKING_DIR):/mnt" $(PROBE_IMAGE) sh -lc 'test -f /mnt/artisan && test -f /mnt/package.json' >/dev/null 2>&1; then \
	echo "$(WORKING_DIR)"; \
else \
	echo "$(MIRROR_HOST_PATH)"; \
fi
endef


.PHONY: help up down restart ps logs logs-app logs-vite migrate fresh seed shell app-shell vite-shell composer-install npm-install phpunit assets-fix clear-hot health-check aliases alias-artisan alias-php sync probe-host-path save-chat save-chat-quick save-chat-debug

help:
	@echo "Targets:"
	@echo "  make up              - start containers"
	@echo "  make down            - stop containers"
	@echo "  make restart         - restart containers"
	@echo "  make ps              - show container status"
	@echo "  make logs            - follow all compose logs"
	@echo "  make logs-app        - follow Laravel app logs"
	@echo "  make logs-vite       - follow Vite logs"
	@echo "  make migrate         - run Laravel migrations"
	@echo "  make fresh           - fresh migrations (drops all tables)"
	@echo "  make seed            - run Laravel seeders"
	@echo "  make shell           - shell into app container"
	@echo "  make composer-install- run composer install in app container"
	@echo "  make npm-install     - run npm install in vite container"
	@echo "  make aliases         - print shell wrappers for artisan, php, and ph"
	@echo "  make clear-hot       - remove stale Vite hot file if present"
	@echo "  make assets-fix      - clear stale Vite hot file and rebuild assets"
	@echo "  make health-check    - show active Docker mount source and Vite hot URL"
	@echo "  make sync            - sync repo to mirror path for Docker fallback"
	@echo "  make probe-host-path - print the host path Docker compose will use"
	@echo "  make save-chat       - append latest chat/session log to session notes file"
	@echo "                         optional: TITLE=\"...\" LATEST_SOURCE=\"...\" TAIL_LINES=200 CHAT_PROJECT=\"...\""
	@echo "  make save-chat-quick - append NOTE=\"...\" as a chat note"
	@echo "  make save-chat-debug - run save-chat with verbose trace and log output"
	@echo "                         optional: DEBUG_LOG=\"/tmp/save-chat-debug.log\""

build:
	docker compose build apptemplate && docker compose up -d

probe-host-path:
	@set -eu; \
	HOST_PATH="$$($(resolve_host_path))"; \
	echo "$$HOST_PATH"

sync:
	@set -eu; \
	$(sync_to_mirror); \
	echo "Synced $(WORKING_DIR) -> $(MIRROR_HOST_PATH)"

up:
	@set -eu; \
	HOST_PATH="$$($(resolve_host_path))"; \
	if [ "$$HOST_PATH" = "$(MIRROR_HOST_PATH)" ] && [ -z "$(APP_HOST_PATH)" ]; then \
		echo "Direct bind mount is not readable by Docker. Syncing mirror..."; \
		$(sync_to_mirror); \
	fi; \
	echo "Using APP_HOST_PATH=$$HOST_PATH"; \
	APP_HOST_PATH="$$HOST_PATH" docker compose -f "$(COMPOSE_FILE)" up -d --force-recreate

down:
	docker compose -f "$(COMPOSE_FILE)" down

restart:
	@$(MAKE) --no-print-directory down
	@$(MAKE) --no-print-directory up

ps:
	docker compose -f "$(COMPOSE_FILE)" ps

logs:
	docker compose -f "$(COMPOSE_FILE)" logs -f

logs-app:
	docker compose -f "$(COMPOSE_FILE)" logs -f app

logs-vite:
	docker compose -f "$(COMPOSE_FILE)" logs -f vite

migrate:
	docker exec -it $(APP_CONTAINER) sh -lc "cd $(APP_DIR) && php artisan migrate --force"

fresh:
	docker exec -it $(APP_CONTAINER) sh -lc "cd $(APP_DIR) && php artisan migrate:fresh --force"

seed:
	docker exec -it $(APP_CONTAINER) sh -lc "cd $(APP_DIR) && php artisan db:seed --force"

fresh-seed:
	docker exec -it $(APP_CONTAINER) sh -lc "cd $(APP_DIR) && php artisan migrate:fresh --seed --force"

shell:
	docker exec -it $(APP_CONTAINER) sh

app-shell: shell

vite-shell:
	docker exec -it $(VITE_CONTAINER) sh

composer-install:
	docker exec -it $(APP_CONTAINER) sh -lc "cd $(APP_DIR) && composer install"

phpunit:
	docker exec -it $(APP_CONTAINER) sh -lc "cd $(APP_DIR) && ./vendor/bin/phpunit"

npm-install:
	docker exec -it $(VITE_CONTAINER) sh -lc "cd $(APP_DIR) && npm install"

aliases:
	@$(MAKE) --no-print-directory alias-artisan
	@$(MAKE) --no-print-directory alias-php

alias-artisan:
	@printf '%s\n' 'artisan() { docker exec -it -w $(APP_DIR) $(APP_CONTAINER) php artisan "$$@"; }'

alias-php:
	@printf '%s\n' 'php() { docker exec -it -w $(APP_DIR) $(APP_CONTAINER) php "$$@"; }'
	@printf '%s\n' 'ph() { docker exec -it -w $(APP_DIR) $(APP_CONTAINER) php "$$@"; }'


clear-hot:
	@if [ -f public/hot ]; then \
		rm -f public/hot; \
		echo "Removed stale public/hot"; \
	else \
		echo "No public/hot found"; \
	fi

assets-fix: clear-hot
	docker exec -it $(VITE_CONTAINER) sh -lc "cd $(APP_DIR) && npm run build"

health-check:
	@echo "Container mount source -> $(APP_DIR):"
	@docker inspect $(APP_CONTAINER) --format '{{range .Mounts}}{{if eq .Destination "$(APP_DIR)"}}{{println .Source}}{{end}}{{end}}' 2>/dev/null || echo "app container not found"
	@echo "Vite hot URL (public/hot):"
	@docker exec $(APP_CONTAINER) sh -lc "if [ ! -f $(APP_DIR)/public/hot ]; then echo 'missing'; elif [ ! -s $(APP_DIR)/public/hot ]; then echo 'empty'; else cat $(APP_DIR)/public/hot; echo; fi" 2>/dev/null || echo "app container not running"

save-chat:
	@bash ./.scripts/save-session-notes.sh --project-name "$(CHAT_PROJECT)" --dir "$(CHAT_NOTES_DIR)" --latest-chat --title "$(if $(TITLE),$(TITLE),Latest Chat Session)" $(if $(LATEST_SOURCE),--latest-source "$(LATEST_SOURCE)",) $(if $(TAIL_LINES),--tail-lines "$(TAIL_LINES)",)

save-chat-quick:
	@if [ -z "$(NOTE)" ]; then \
		echo "Usage: make save-chat-quick NOTE=\"your summary here\" [TITLE=\"Section Title\"]"; \
		exit 1; \
	fi
	@bash ./.scripts/save-session-notes.sh --project-name "$(CHAT_PROJECT)" --dir "$(CHAT_NOTES_DIR)" --title "$(if $(TITLE),$(TITLE),Session Update)" --message "$(NOTE)"

save-chat-debug:
	@LOG_FILE="$(if $(DEBUG_LOG),$(DEBUG_LOG),/tmp/save-chat-debug.log)"; \
	echo "Running save-chat in debug mode..."; \
	echo "Debug log: $$LOG_FILE"; \
	bash -x ./.scripts/save-session-notes.sh --project-name "$(CHAT_PROJECT)" --dir "$(CHAT_NOTES_DIR)" --latest-chat --title "$(if $(TITLE),$(TITLE),Latest Chat Session)" $(if $(LATEST_SOURCE),--latest-source "$(LATEST_SOURCE)",) $(if $(TAIL_LINES),--tail-lines "$(TAIL_LINES)",) >"$$LOG_FILE" 2>&1 || { \
		echo "save-chat failed. See debug log: $$LOG_FILE"; \
		tail -n 40 "$$LOG_FILE"; \
		exit 1; \
	}; \
	echo "save-chat succeeded."; \
	echo "See debug log: $$LOG_FILE"; \
	tail -n 20 "$$LOG_FILE"