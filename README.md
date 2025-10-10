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
- **Deployment:** Web application (online only)

## Getting Started Locally
Follow these steps to set up the project on your local machine:

### Option 1: Docker Setup (Recommended)

1. **Clone the repository**
    ```bash
    git clone https://github.com/your-username/WorkoutTracker.git
    cd WorkoutTracker
    ```

2. **Configure environment variables (optional)**
   - Copy `.env` and create `.env.local` for local overrides:
     ```bash
     cp .env .env.local
     ```
   - Modify PostgreSQL credentials in `.env.local` if needed:
     ```env
     POSTGRES_DB=workout_tracker
     POSTGRES_USER=workout_user
     POSTGRES_PASSWORD=your_secure_password
     ```

3. **Build and start Docker containers**
    ```bash
    docker compose build
    docker compose up -d
    ```

4. **Install dependencies inside containers**
    ```bash
    # Backend dependencies
    docker compose exec php composer install
    
    # Frontend dependencies
    docker compose exec frontend npm install
    ```

5. **Run database migrations**
    ```bash
    docker compose exec php bin/console doctrine:migrations:migrate
    ```

6. **Access the application**
   - Frontend: http://localhost (or https://localhost:443)
   - Backend API: http://localhost/api
   - Swagger UI: http://localhost:8081
   - PostgreSQL: localhost:5432

### Option 2: Local Setup (Without Docker)

1. **Clone the repository**
    ```bash
    git clone https://github.com/your-username/WorkoutTracker.git
    cd WorkoutTracker
    ```

2. **Install PostgreSQL**
   - Ensure PostgreSQL 16 is installed and running
   - Create database:
     ```bash
     createdb workout_tracker
     ```

3. **Backend Setup**
   - Ensure you have PHP (>=8.4) with PostgreSQL extensions installed:
     ```bash
     # Check PHP version
     php -v
     
     # Check PostgreSQL extensions
     php -m | grep pgsql
     ```
   - Install PHP dependencies using Composer:
    ```bash
    composer install
    ```
   - Configure `.env.local` with your database settings:
     ```env
     DATABASE_URL="postgresql://username:password@127.0.0.1:5432/workout_tracker?serverVersion=16&charset=utf8"
     ```
   - Run migrations:
     ```bash
     php bin/console doctrine:migrations:migrate
     ```

4. **Frontend Setup**
   - Navigate to the `frontend` folder:
     ```bash
     cd frontend
     ```
   - Install Node.js dependencies:
     ```bash
     npm install
     ```
   - Create a `.env.local` file if needed and specify relevant environment variables.


## Available Scripts
### Frontend (in the `frontend` directory)
- **Development:** `npm run dev` – Starts the Next.js development server.
- **Build:** `npm run build` – Builds the production-ready application.
- **Start:** `npm run start` – Starts the Next.js production server.
- **Lint:** `npm run lint` – Runs ESLint on the codebase.

### Backend
- Common Symfony console commands can be run via:
  ```bash
  php bin/console <command>
  ```
  Or with Docker:
  ```bash
  docker compose exec php bin/console <command>
  ```

- Clear the cache:
  ```bash
  php bin/console cache:clear
  ```

### Database Commands (Docker)
- Create a new migration:
  ```bash
  docker compose exec php bin/console make:migration
  ```
- Run migrations:
  ```bash
  docker compose exec php bin/console doctrine:migrations:migrate
  ```
- Check database schema:
  ```bash
  docker compose exec php bin/console doctrine:schema:validate
  ```
- Access PostgreSQL CLI:
  ```bash
  docker compose exec database psql -U app -d app
  ```
- View database logs:
  ```bash
  docker compose logs database
  ```

## Project Scope
The MVP of WorkoutTracker includes:
- **User Authentication:** Registration, login, and logout functionalities with secure password hashing.
- **Session Management:** Create, edit, and delete workout sessions with metadata (date, optional session name, and notes).
- **Exercise and Series Tracking:** Add up to 15 exercises per session with dynamic form entries for up to 20 series per exercise.
- **Visualizations:** Display workout statistics via charts that show the maximum weight lifted per session.
- **Mobile-First Design:** Optimized for use on mobile devices with an intuitive and responsive UI.
  
Areas not covered in the MVP include advanced analytics (e.g. 1RM calculations), social sharing features, and offline capabilities.

## Project Status
The project is currently in the MVP development phase. Core functionalities are being implemented and refined based on user feedback and testing. Continuous improvements and additional features are planned for future releases.

## License
This project is licensed under the **MIT License**.
