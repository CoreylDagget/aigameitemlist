SHELL := /bin/sh

.DEFAULT_GOAL := help

help:
@echo "Available targets:"
@grep -E '^[a-zA-Z_-]+:.*?##' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

install: ## Install PHP dependencies via Composer
composer install

up: ## Start the Docker development stack
docker compose up -d --build

down: ## Stop the Docker development stack
docker compose down

logs: ## Tail application logs
docker compose logs -f

health: ## Curl the health endpoint
curl -s http://localhost:8080/health | jq
