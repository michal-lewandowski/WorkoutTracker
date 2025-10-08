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

1. **Clone the repository**
    ```bash
    git clone https://github.com/your-username/WorkoutTracker.git
    cd WorkoutTracker
    ```


2. **Backend Setup**
   - Ensure you have PHP (>=8.4) installed.
   - Install PHP dependencies using Composer:
    ```bash
    composer install
     ```
   - Configure your environment variables as needed (e.g., database settings).

3. **Frontend Setup**
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
- Clear the cache:
  ```bash
  php bin/console cache:clear
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
