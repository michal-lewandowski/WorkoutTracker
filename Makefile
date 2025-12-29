# WorkoutTracker Makefile
# Author: Claude
# Date: Dec 29, 2025

.PHONY: help build start stop restart clean ps logs shell lint test

# Default target
help:
	@echo "WorkoutTracker Makefile Commands:"
	@echo "--------------------------------"
	@echo "make build         - Build all containers (backend, frontend, nginx)"
	@echo "make start         - Start all containers in detached mode"
	@echo "make start-dev     - Start all containers in development mode"
	@echo "make stop          - Stop all containers"
	@echo "make restart       - Restart all containers"
	@echo "make clean         - Remove all containers and images"
	@echo "make lint          - Run PHP linter"
	@echo "make test          - Run tests"
	@echo "make e2e           - Run e2e tests"

# Build all containers
build:
	@echo "🏗️ Building all containers..."
	docker compose build --pull --no-cache php
	docker compose build --pull --no-cache frontend
	docker compose build --pull --no-cache nginx
	@echo "✅ Build completed successfully!"

# Start all containers in detached mode
start:
	@echo "🚀 Starting all containers..."
	docker compose up -d
	@echo "✅ Containers started successfully!"
	@echo "🌐 Access your application:"
	@echo "   Frontend: http://localhost"
	@echo "   Backend API: http://localhost/api/"
	@echo "   Swagger UI: http://localhost:8081"

# Start all containers in development mode
start-dev:
	@echo "🚀 Starting all containers in development mode..."
	docker compose -f compose.yaml -f compose.dev.yaml up -d
	@echo "✅ Development containers started successfully!"
	@echo "🌐 Access your application:"
	@echo "   Frontend: http://localhost"
	@echo "   Backend API: http://localhost/api/"
	@echo "   Swagger UI: http://localhost:8081"

# Stop all containers
stop:
	@echo "🛑 Stopping all containers..."
	docker compose down
	@echo "✅ Containers stopped successfully!"

# Restart all containers
restart: stop start
	@echo "🔄 Containers restarted successfully!"

# Remove all containers and images related to the project
clean:
	@echo "🧹 Removing all containers and images..."
	docker compose down --volumes --rmi all --remove-orphans
	@echo "✅ Cleanup completed successfully!"

# Run PHP linter
lint:
	docker exec workouttracker-php-1 vendor/bin/phpstan analyse src --level=5 --memory-limit 256M
	docker exec workouttracker-php-1 vendor/bin/php-cs-fixer fix --diff

# Run PHPUnit
test:
	docker exec workouttracker-php-1 vendor/bin/phpunit

# Run playwright e2e tests
e2e:
	npx playwright test
