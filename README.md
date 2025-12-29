# WorkoutTracker

## Project Description
WorkoutTracker is a responsive web application designed for tracking resistance training sessions. The application aims to simplify the process of recording workouts by allowing users to create sessions on-the-go, log exercises with multiple series, and visualize progress through dynamic statistical charts. Whether you are a beginner looking for a simple tracking tool or an intermediate user needing detailed insights into your training routines, WorkoutTracker provides a fast, mobile-first solution for managing your workout history.

## Tech Stack
- **Backend:** Symfony 7.3 (PHP 8.4), Doctrine ORM
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
WorkoutTracker Makefile Commands:
--------------------------------
make build         - Build all containers (backend, frontend, nginx)
make start         - Start all containers in detached mode
make start-dev     - Start all containers in development mode
make stop          - Stop all containers
make restart       - Restart all containers
make clean         - Remove all containers and images
make lint          - Run PHP linter
make test          - Run tests
make e2e           - Run e2e tests
```
