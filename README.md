# WorkoutTracker

## Table of Contents
- [Project Name](#project-name)
- [Project Description](#project-description)
- [Tech Stack](#tech-stack)
- [Getting Started Locally](#getting-started-locally)
- [Available Scripts](#available-scripts)
- [Project Scope](#project-scope)
- [Project Status](#project-status)
- [License](#license)

## Project Name
**WorkoutTracker**

## Project Description
WorkoutTracker is a responsive web application designed for tracking resistance training sessions. The application aims to simplify the process of recording workouts by allowing users to create sessions on-the-go, log exercises with multiple series, and visualize progress through dynamic statistical charts. Whether you are a beginner looking for a simple tracking tool or an intermediate user needing detailed insights into your training routines, WorkoutTracker provides a fast, mobile-first solution for managing your workout history.

## Tech Stack
- **Backend:** Symfony 7.3 (PHP 8.4), Doctrine ORM, Nelmio CORS Bundle
- **Frontend:** Next.js, React, TypeScript
- **Database:** PostgreSQL
- **API Documentation:** Swagger

## Getting Started Locally
Follow these steps to set up the project on your local machine:

### Prerequisites
- Docker and Docker Compose installed on your system
- Make installed on your system

### Setting Up the Development Environment

1. Clone the repository
   ```bash
   git clone https://github.com/yourusername/WorkoutTracker.git
   cd WorkoutTracker
   ```

2. Clean any existing containers or images (if you had a previous installation)
   ```bash
   make clean
   ```

3. Build the application containers
   ```bash
   make build
   ```

4. Start the application in development mode
   ```bash
   make start-dev
   ```

5. Access the application
   - Frontend: http://localhost
   - Backend API: http://localhost/api/
   - Swagger UI: http://localhost:8081

### Available Make Commands

```bash
# Build all containers
make build

# Start all containers in production mode
make start

# Start all containers in development mode
make start-dev

# Stop all containers
make stop

# Restart all containers
make restart

# Remove all containers and images
make clean

# Show running containers
make ps

# Display logs from all containers
make logs

# Open shell in PHP container
make shell-php

# Open shell in frontend container
make shell-frontend

# Open shell in database container
make shell-db

# Run PHP linter
make lint

# Run tests
make test
```
