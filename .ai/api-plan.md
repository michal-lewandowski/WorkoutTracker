# REST API Plan - WorkoutTracker MVP

## Overview

This document defines the REST API architecture for the WorkoutTracker application, a workout tracking system built with Symfony backend and Next.js frontend. The API follows REST principles, uses JSON for data exchange, and implements token-based authentication.

**Base URL**: `/api/v1`  
**Content-Type**: `application/json`  
**Character Encoding**: UTF-8

---

## 1. Resources

| Resource | Database Table | Description | Key Endpoints |
|----------|----------------|-------------|---------------|
| Auth | users | User authentication and authorization | POST /auth/register, /login, GET /auth/me |
| Muscle Categories | muscle_categories | Dictionary of 6 muscle categories | GET /muscle-categories |
| Exercises | exercises | Dictionary of 50-70 predefined exercises | GET /exercises, /exercises/{id} |
| Workout Sessions | workout_sessions | User workout sessions with metadata | GET /workout-sessions, POST (empty session), PUT/PATCH/DELETE |
| Workout Exercises | workout_exercises | Exercises within a workout session | POST /workout-exercises, PUT/DELETE /{id} |
| Exercise Sets | exercise_sets | Sets of repetitions for exercises | POST /exercise-sets, PUT/DELETE /{id} |
| Statistics | Multiple tables | Aggregated workout progress data | GET /statistics/exercise/{id}, /dashboard |

---

## 2. Authentication & Authorization

### 2.1 Authentication Mechanism

**Method**: JWT (JSON Web Token) Bearer Authentication

**Flow**:
1. User registers or logs in with email/password
2. Backend validates credentials and issues JWT token
3. Frontend stores token (localStorage/httpOnly cookie)
4. Token sent in Authorization header for protected endpoints
5. Token expires after 24 hours (configurable)

**Header Format**:
```
Authorization: Bearer <JWT_TOKEN>
```

### 2.2 Endpoints

#### POST /auth/register
Register a new user account.

**Request Body**:
```json
{
  "email": "user@example.com",
  "password": "SecurePass123",
  "passwordConfirmation": "SecurePass123"
}
```

**Validation**:
- `email`: required, valid email format, unique (case-insensitive)
- `password`: required, min 8 characters, at least 1 uppercase letter, 1 digit
- `passwordConfirmation`: required, must match password

**Success Response** (201 Created):
```json
{
  "user": {
    "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
    "email": "user@example.com",
    "createdAt": "2025-10-11T10:30:00Z"
  },
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Error Responses**:
- `400 Bad Request`: Validation errors
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["Email is already registered"],
    "password": ["Password must contain at least 1 uppercase letter"]
  }
}
```

---

#### POST /auth/login
Authenticate user and receive access token.

**Request Body**:
```json
{
  "email": "user@example.com",
  "password": "SecurePass123"
}
```

**Success Response** (200 OK):
```json
{
  "user": {
    "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
    "email": "user@example.com",
    "createdAt": "2025-10-11T10:30:00Z"
  },
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Error Responses**:
- `401 Unauthorized`: Invalid credentials
```json
{
  "message": "Invalid email or password"
}
```

---

#### GET /auth/me
Get current authenticated user profile.

**Headers**: `Authorization: Bearer <token>`

**Success Response** (200 OK):
```json
{
  "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "email": "user@example.com",
  "createdAt": "2025-10-11T10:30:00Z"
}
```

**Error Responses**:
- `401 Unauthorized`: Invalid or expired token

---

## 3. Muscle Categories

### GET /muscle-categories
Retrieve all muscle categories (public or authenticated).

**Query Parameters**: None

**Success Response** (200 OK):
```json
[
  {
    "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
    "namePl": "Klatka piersiowa",
    "nameEn": "Chest",
    "createdAt": "2025-10-10T00:00:00Z"
  },
  {
    "id": "01ARZ3NDEKTSV4RRFFQ69G5FB0",
    "namePl": "Plecy",
    "nameEn": "Back",
    "createdAt": "2025-10-10T00:00:00Z"
  }
  // ... 4 more categories
]
```

---

## 4. Exercises

### GET /exercises
Retrieve all exercises with optional filtering.

**Query Parameters**:
- `muscleCategoryId` (optional): Filter by muscle category ID
- `search` (optional): Search by exercise name (partial match, case-insensitive)
- `lang` (optional): Language for exercise names (`pl` or `en`, default: `pl`)

**Example**: `/exercises?muscleCategoryId=01ARZ3NDEKTSV4RRFFQ69G5FAV&search=wyciskanie`

**Success Response** (200 OK):
```json
[
  {
    "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
    "name": "Wyciskanie sztangi leżąc",
    "nameEn": "Barbell Bench Press",
    "muscleCategoryId": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
    "muscleCategory": {
      "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
      "namePl": "Klatka piersiowa",
      "nameEn": "Chest"
    },
    "createdAt": "2025-10-10T00:00:00Z"
  }
  // ... more exercises
]
```

---

### GET /exercises/{id}
Retrieve a single exercise by ID.

**Path Parameters**:
- `id`: Exercise uuid4

**Success Response** (200 OK):
```json
{
  "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "name": "Wyciskanie sztangi leżąc",
  "nameEn": "Barbell Bench Press",
  "muscleCategoryId": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "muscleCategory": {
    "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
    "namePl": "Klatka piersiowa",
    "nameEn": "Chest"
  },
  "createdAt": "2025-10-10T00:00:00Z"
}
```

**Error Responses**:
- `404 Not Found`: Exercise not found
```json
{
  "message": "Exercise not found"
}
```

---

## 5. Workout Sessions

### GET /workout-sessions
Retrieve all workout sessions for authenticated user.

**Headers**: `Authorization: Bearer <token>`

**Query Parameters**:
- `limit` (optional, default: 50): Number of results per page
- `offset` (optional, default: 0): Pagination offset
- `dateFrom` (optional): Filter sessions from date (YYYY-MM-DD)
- `dateTo` (optional): Filter sessions to date (YYYY-MM-DD)
- `sortBy` (optional, default: `date`): Sort field (`date`, `createdAt`)
- `sortOrder` (optional, default: `desc`): Sort order (`asc`, `desc`)

**Example**: `/workout-sessions?dateFrom=2025-09-01&dateTo=2025-09-30&limit=10`

**Success Response** (200 OK):
```json
{
  "data": [
    {
      "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
      "userId": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
      "date": "2025-10-11",
      "name": "Trening A - FBW",
      "notes": "Świetny trening, nowe rekordy!",
      "exerciseCount": 8,
      "createdAt": "2025-10-11T10:30:00Z",
      "updatedAt": "2025-10-11T10:30:00Z"
    }
    // ... more sessions
  ],
  "meta": {
    "total": 45,
    "limit": 50,
    "offset": 0
  }
}
```

**Note**: For dashboard (last 5 sessions), use: `GET /workout-sessions?limit=5&sortBy=date&sortOrder=desc`

---

### GET /workout-sessions/{id}
Retrieve detailed workout session with all exercises and sets.

**Headers**: `Authorization: Bearer <token>`

**Path Parameters**:
- `id`: Workout session uuid4

**Success Response** (200 OK):
```json
{
  "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "userId": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "date": "2025-10-11",
  "name": "Trening A - FBW",
  "notes": "Świetny trening!",
  "workoutExercises": [
    {
      "id": "01ARZ3NDEKTSV4RRFFQ69G5FB0",
      "exerciseId": "01ARZ3NDEKTSV4RRFFQ69G5FB1",
      "exercise": {
        "id": "01ARZ3NDEKTSV4RRFFQ69G5FB1",
        "name": "Wyciskanie sztangi leżąc",
        "nameEn": "Barbell Bench Press",
        "muscleCategoryId": "01ARZ3NDEKTSV4RRFFQ69G5FAV"
      },
      "sets": [
        {
          "id": "01ARZ3NDEKTSV4RRFFQ69G5FB2",
          "setsCount": 3,
          "reps": 10,
          "weightKg": 70.0,
          "createdAt": "2025-10-11T10:35:00Z"
        },
        {
          "id": "01ARZ3NDEKTSV4RRFFQ69G5FB3",
          "setsCount": 2,
          "reps": 8,
          "weightKg": 80.0,
          "createdAt": "2025-10-11T10:40:00Z"
        }
      ],
      "createdAt": "2025-10-11T10:35:00Z"
    }
    // ... more exercises
  ],
  "createdAt": "2025-10-11T10:30:00Z",
  "updatedAt": "2025-10-11T10:45:00Z"
}
```

**Error Responses**:
- `404 Not Found`: Session not found
```json
{
  "message": "Workout session not found"
}
```
- `403 Forbidden`: User doesn't own this session
```json
{
  "message": "Access denied"
}
```

**Note**: Weight is converted from grams to kg in the response (application layer).

---

### POST /workout-sessions
Create a new workout session (can be empty initially, exercises added later).

**Headers**: `Authorization: Bearer <token>`

**Request Body**:
```json
{
  "date": "2025-10-11",
  "name": "Trening A - FBW",
  "notes": "Świetny trening!"
}
```

**Validation**:
- `date`: required, valid date format (YYYY-MM-DD), not future date
- `name`: optional, max 255 characters
- `notes`: optional, text

**Success Response** (201 Created):
```json
{
  "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "userId": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "date": "2025-10-11",
  "name": "Trening A - FBW",
  "notes": "Świetny trening!",
  "workoutExercises": [],
  "createdAt": "2025-10-11T10:30:00Z",
  "updatedAt": "2025-10-11T10:30:00Z"
}
```

**Error Responses**:
- `400 Bad Request`: Validation errors
```json
{
  "message": "Validation failed",
  "errors": {
    "date": ["Date is required"],
    "name": ["Name cannot exceed 255 characters"]
  }
}
```

**Note**: After creating a session, use `POST /workout-exercises` to add exercises incrementally.

---

### PUT /workout-sessions/{id}
Update workout session metadata (full replacement of metadata fields only).

**Headers**: `Authorization: Bearer <token>`

**Path Parameters**:
- `id`: Workout session uuid4

**Request Body**:
```json
{
  "date": "2025-10-12",
  "name": "Updated name",
  "notes": "Updated notes"
}
```

**Validation**: Same as POST /workout-sessions

**Success Response** (200 OK):
```json
{
  "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "userId": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "date": "2025-10-12",
  "name": "Updated name",
  "notes": "Updated notes",
  "workoutExercises": [
    // ... existing exercises remain unchanged
  ],
  "createdAt": "2025-10-11T10:30:00Z",
  "updatedAt": "2025-10-11T11:00:00Z"
}
```

**Error Responses**:
- `400 Bad Request`: Validation errors
```json
{
  "message": "Validation failed",
  "errors": {
    "date": ["Invalid date format"]
  }
}
```
- `404 Not Found`: Session not found
- `403 Forbidden`: User doesn't own this session

**Note**: This endpoint only updates session metadata. Use `POST/PUT/DELETE /workout-exercises` to manage exercises.

---

### DELETE /workout-sessions/{id}
Soft delete a workout session.

**Headers**: `Authorization: Bearer <token>`

**Path Parameters**:
- `id`: Workout session uuid4

**Success Response** (204 No Content):
```
No response body
```

**Error Responses**:
- `404 Not Found`: Session not found
```json
{
  "message": "Workout session not found"
}
```
- `403 Forbidden`: User doesn't own this session
```json
{
  "message": "Access denied"
}
```

**Note**: This is a soft delete - sets `deleted_at` timestamp and `deleted_by` user ID. Related workout_exercises and exercise_sets remain in database but are hidden from queries.

---

## 6. Workout Exercises

### POST /workout-exercises
Add an exercise to an existing workout session.

**Headers**: `Authorization: Bearer <token>`

**Request Body**:
```json
{
  "workoutSessionId": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "exerciseId": "01ARZ3NDEKTSV4RRFFQ69G5FB1",
  "sets": [
    {
      "setsCount": 3,
      "reps": 10,
      "weightKg": 70.0
    },
    {
      "setsCount": 2,
      "reps": 8,
      "weightKg": 80.0
    }
  ]
}
```

**Validation**:
- `workoutSessionId`: required, valid uuid4, session must belong to authenticated user
- `exerciseId`: required, valid uuid4, must exist in exercises table
- `sets`: optional, array, max 20 items
- `sets[].setsCount`: required, integer, min 1
- `sets[].reps`: required, integer, 1-100
- `sets[].weightKg`: required, number, 0-500
- Session cannot have more than 15 exercises total

**Success Response** (201 Created):
```json
{
  "id": "01ARZ3NDEKTSV4RRFFQ69G5FB0",
  "workoutSessionId": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "exerciseId": "01ARZ3NDEKTSV4RRFFQ69G5FB1",
  "exercise": {
    "id": "01ARZ3NDEKTSV4RRFFQ69G5FB1",
    "name": "Wyciskanie sztangi leżąc",
    "nameEn": "Barbell Bench Press",
    "muscleCategoryId": "01ARZ3NDEKTSV4RRFFQ69G5FAV"
  },
  "sets": [
    {
      "id": "01ARZ3NDEKTSV4RRFFQ69G5FB2",
      "setsCount": 3,
      "reps": 10,
      "weightKg": 70.0,
      "createdAt": "2025-10-11T10:35:00Z"
    },
    {
      "id": "01ARZ3NDEKTSV4RRFFQ69G5FB3",
      "setsCount": 2,
      "reps": 8,
      "weightKg": 80.0,
      "createdAt": "2025-10-11T10:40:00Z"
    }
  ],
  "createdAt": "2025-10-11T10:35:00Z"
}
```

**Error Responses**:
- `400 Bad Request`: Validation errors
```json
{
  "message": "Validation failed",
  "errors": {
    "workoutSessionId": ["Session cannot have more than 15 exercises"],
    "sets": ["Maximum 20 sets per exercise"],
    "sets[0].reps": ["Repetitions must be between 1 and 100"]
  }
}
```
- `404 Not Found`: Session or exercise not found
- `403 Forbidden`: User doesn't own the workout session

**Note**: Sets can be added later using `POST /exercise-sets` if omitted during exercise creation.

---

### PUT /workout-exercises/{id}
Update an exercise's sets (full replacement of sets).

**Headers**: `Authorization: Bearer <token>`

**Path Parameters**:
- `id`: Workout exercise uuid4

**Request Body**:
```json
{
  "sets": [
    {
      "setsCount": 4,
      "reps": 12,
      "weightKg": 75.0
    }
  ]
}
```

**Validation**:
- `sets`: required, array, min 1, max 20 items
- Same validation rules as POST for set fields

**Success Response** (200 OK):
```json
{
  "id": "01ARZ3NDEKTSV4RRFFQ69G5FB0",
  "workoutSessionId": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "exerciseId": "01ARZ3NDEKTSV4RRFFQ69G5FB1",
  "exercise": {
    "id": "01ARZ3NDEKTSV4RRFFQ69G5FB1",
    "name": "Wyciskanie sztangi leżąc",
    "nameEn": "Barbell Bench Press"
  },
  "sets": [
    {
      "id": "01ARZ3NDEKTSV4RRFFQ69G5FB5",
      "setsCount": 4,
      "reps": 12,
      "weightKg": 75.0,
      "createdAt": "2025-10-11T11:00:00Z"
    }
  ],
  "createdAt": "2025-10-11T10:35:00Z"
}
```

**Error Responses**:
- `400 Bad Request`: Validation errors
- `404 Not Found`: Workout exercise not found
- `403 Forbidden`: User doesn't own this workout exercise

**Note**: This replaces ALL existing sets for this exercise. Old sets are deleted, new sets are created.

---

### DELETE /workout-exercises/{id}
Remove an exercise from a workout session.

**Headers**: `Authorization: Bearer <token>`

**Path Parameters**:
- `id`: Workout exercise uuid4

**Success Response** (204 No Content):
```
No response body
```

**Error Responses**:
- `404 Not Found`: Workout exercise not found
```json
{
  "message": "Workout exercise not found"
}
```
- `403 Forbidden`: User doesn't own this workout exercise
```json
{
  "message": "Access denied"
}
```

**Note**: This permanently deletes the workout exercise and all associated sets (CASCADE delete).

---

## 7. Exercise Sets

### POST /exercise-sets
Add a new set group to an existing workout exercise.

**Headers**: `Authorization: Bearer <token>`

**Request Body**:
```json
{
  "workoutExerciseId": "01ARZ3NDEKTSV4RRFFQ69G5FB0",
  "setsCount": 3,
  "reps": 10,
  "weightKg": 70.0
}
```

**Validation**:
- `workoutExerciseId`: required, valid uuid4, must belong to user's session
- `setsCount`: required, integer, min 1
- `reps`: required, integer, 1-100
- `weightKg`: required, number, 0-500
- Total sets for exercise cannot exceed 20

**Success Response** (201 Created):
```json
{
  "id": "01ARZ3NDEKTSV4RRFFQ69G5FB2",
  "workoutExerciseId": "01ARZ3NDEKTSV4RRFFQ69G5FB0",
  "setsCount": 3,
  "reps": 10,
  "weightKg": 70.0,
  "createdAt": "2025-10-11T10:35:00Z"
}
```

**Error Responses**:
- `400 Bad Request`: Validation errors
```json
{
  "message": "Validation failed",
  "errors": {
    "workoutExerciseId": ["Exercise cannot have more than 20 sets total"],
    "reps": ["Repetitions must be between 1 and 100"],
    "weightKg": ["Weight must be between 0 and 500 kg"]
  }
}
```
- `404 Not Found`: Workout exercise not found
- `403 Forbidden`: User doesn't own this workout exercise

---

### PUT /exercise-sets/{id}
Update an existing set group.

**Headers**: `Authorization: Bearer <token>`

**Path Parameters**:
- `id`: Exercise set uuid4

**Request Body**:
```json
{
  "setsCount": 4,
  "reps": 12,
  "weightKg": 75.0
}
```

**Validation**: Same as POST

**Success Response** (200 OK):
```json
{
  "id": "01ARZ3NDEKTSV4RRFFQ69G5FB2",
  "workoutExerciseId": "01ARZ3NDEKTSV4RRFFQ69G5FB0",
  "setsCount": 4,
  "reps": 12,
  "weightKg": 75.0,
  "createdAt": "2025-10-11T10:35:00Z"
}
```

**Error Responses**:
- `400 Bad Request`: Validation errors
- `404 Not Found`: Exercise set not found
- `403 Forbidden`: User doesn't own this set

---

### DELETE /exercise-sets/{id}
Delete a set group from an exercise.

**Headers**: `Authorization: Bearer <token>`

**Path Parameters**:
- `id`: Exercise set uuid4

**Success Response** (204 No Content):
```
No response body
```

**Error Responses**:
- `404 Not Found`: Exercise set not found
- `403 Forbidden`: User doesn't own this set

---

## 8. Statistics

### GET /statistics/exercise/{exerciseId}
Get progress statistics for a specific exercise (max weight per session over time).

**Headers**: `Authorization: Bearer <token>`

**Path Parameters**:
- `exerciseId`: Exercise uuid4

**Query Parameters**:
- `dateFrom` (optional): Start date for statistics (YYYY-MM-DD)
- `dateTo` (optional): End date for statistics (YYYY-MM-DD)
- `limit` (optional, default: 100): Max number of data points

**Success Response** (200 OK):
```json
{
  "exerciseId": "01ARZ3NDEKTSV4RRFFQ69G5FB1",
  "exercise": {
    "id": "01ARZ3NDEKTSV4RRFFQ69G5FB1",
    "name": "Wyciskanie sztangi leżąc",
    "nameEn": "Barbell Bench Press"
  },
  "dataPoints": [
    {
      "date": "2025-09-15",
      "sessionId": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
      "maxWeightKg": 70.0
    },
    {
      "date": "2025-09-18",
      "sessionId": "01ARZ3NDEKTSV4RRFFQ69G5FB0",
      "maxWeightKg": 72.5
    },
    {
      "date": "2025-09-22",
      "sessionId": "01ARZ3NDEKTSV4RRFFQ69G5FB5",
      "maxWeightKg": 75.0
    }
  ],
  "summary": {
    "totalSessions": 3,
    "personalRecord": 75.0,
    "prDate": "2025-09-22",
    "firstWeight": 70.0,
    "latestWeight": 75.0,
    "progressPercentage": 7.14
  }
}
```

**Error Responses**:
- `404 Not Found`: Exercise not found
```json
{
  "message": "Exercise not found"
}
```
- `200 OK with empty dataPoints`: User hasn't performed this exercise yet
```json
{
  "exerciseId": "01ARZ3NDEKTSV4RRFFQ69G5FB1",
  "exercise": { /* ... */ },
  "dataPoints": [],
  "summary": null
}
```

**Business Logic**:
- Query all workout_sessions for user where deleted_at IS NULL
- Find workout_exercises matching exerciseId
- Calculate MAX(weight_grams) from exercise_sets for each session
- Group by session date
- Sort by date ascending
- Convert grams to kg in response

---

### GET /statistics/dashboard
Get aggregated statistics for dashboard display.

**Headers**: `Authorization: Bearer <token>`

**Query Parameters**: None

**Success Response** (200 OK):
```json
{
  "totalSessions": 45,
  "totalExercises": 360,
  "totalSets": 1440,
  "recentActivity": {
    "last7Days": 3,
    "last30Days": 12
  },
  "topExercises": [
    {
      "exerciseId": "01ARZ3NDEKTSV4RRFFQ69G5FB1",
      "exerciseName": "Wyciskanie sztangi leżąc",
      "timesPerformed": 15,
      "personalRecord": 80.0
    }
    // ... top 5 exercises by frequency
  ]
}
```

---

## 9. Error Handling

### Standard Error Response Format

All error responses follow this structure:

```json
{
  "message": "Human-readable error message in Polish",
  "errors": {
    "field": ["Specific validation error"]
  }
}
```

**Note**: Error type is determined by HTTP status code only. No `status` or `code` fields in response body.

### HTTP Status Codes

| Code | Description | Usage |
|------|-------------|-------|
| 200 | OK | Successful GET, PUT, PATCH, DELETE |
| 201 | Created | Successful POST (resource created) |
| 400 | Bad Request | Validation errors, malformed request |
| 401 | Unauthorized | Missing or invalid authentication token |
| 403 | Forbidden | Authenticated but not authorized |
| 404 | Not Found | Resource doesn't exist |
| 409 | Conflict | Duplicate resource (e.g., email already exists) |
| 422 | Unprocessable Entity | Semantic validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Unexpected server error |

---

## 10. Validation Rules Summary

### Users
- `email`: required, valid email format, unique (case-insensitive), max 255 characters
- `password`: required, min 8 characters, at least 1 uppercase letter, at least 1 digit

### Workout Sessions
- `date`: required, valid date (YYYY-MM-DD), not future date
- `name`: optional, max 255 characters
- `notes`: optional, text (no length limit in MVP)
- Sessions can be created empty (no exercises required initially)
- Max 15 exercises per session

### Workout Exercises
- `workoutSessionId`: required, session must belong to authenticated user
- `exerciseId`: required, must exist in exercises table
- `sets`: optional during exercise creation, can be added later
- Can have duplicate exerciseId in same session
- Max 15 exercises per session

### Exercise Sets
- `workoutExerciseId`: required, must belong to user's session
- `setsCount`: required, integer, min 1
- `reps`: required, integer, min 1, max 100
- `weightKg`: required, number (float), min 0, max 500
- Stored as weight_grams (INTEGER) in database: weightKg * 1000
- Max 20 set groups per exercise

---

## 11. Business Logic Implementation

### 11.1 Incremental Workout Building
- Workout sessions can be created empty (no exercises required)
- Exercises added one-by-one via `POST /workout-exercises`
- Sets can be added during exercise creation or later via `POST /exercise-sets`
- Frontend can implement "live workout tracking" during gym session

### 11.2 Soft Delete for Workout Sessions
- DELETE endpoint sets `deleted_at = CURRENT_TIMESTAMP` and `deleted_by = current_user_id`
- All queries filter by `WHERE deleted_at IS NULL`
- Related workout_exercises and exercise_sets remain in database
- No restore endpoint in MVP (can be added later)

### 11.3 Weight Conversion
- Frontend sends weight in kilograms (float)
- Backend converts to grams (integer) before database storage: `weight_grams = weightKg * 1000`
- Backend converts back to kg in responses: `weightKg = weight_grams / 1000`
- Eliminates floating-point arithmetic errors

### 11.4 Exercise Set Grouping
- Frontend can send multiple individual sets or grouped sets
- Database stores grouped format: `setsCount=3, reps=10, weightKg=70` represents "3x10@70kg"
- Frontend decides presentation format (e.g., 3 rows vs 1 row with "3x10@70kg")

### 11.5 Statistics Calculation
- For each session with target exercise, find MAX(weight_grams) across all sets
- Group by session date
- Return time series data for chart rendering
- Frontend handles chart library integration

### 11.6 Authorization Rules
- Users can only access their own workout sessions
- Users can only create/edit/delete their own sessions
- Exercises and muscle categories are read-only (seeded data)
- Middleware checks `workout_session.user_id === authenticated_user.id`
- Authorization cascades: owning session grants access to its exercises and sets

---

## 12. Performance Considerations

### 12.1 Database Query Optimization
- Use partial index on `workout_sessions (user_id, date DESC) WHERE deleted_at IS NULL` for dashboard/history queries
- Use index on `workout_exercises.exercise_id` for statistics queries
- Eager load related entities (workout_exercises, exercise_sets) when fetching session details
- Use Doctrine Query Builder with proper JOINs to avoid N+1 queries

### 12.2 Pagination
- Default limit: 50 items
- Max limit: 100 items
- Use offset-based pagination for MVP (cursor-based can be added later for better performance)

### 12.3 Caching Strategy (Future Enhancement)
- Cache muscle categories (rarely change, seeded data)
- Cache exercises dictionary (read-heavy, rarely updated)
- Invalidate user statistics cache on workout session create/update/delete

---

## 13. Security Measures

### 13.1 Authentication Security
- Passwords hashed with bcrypt (cost factor 12) or argon2id
- JWT tokens signed with HS256 or RS256
- Token expiration: 24 hours
- Refresh tokens not implemented in MVP (can be added later)
- Store tokens securely in frontend (httpOnly cookies preferred over localStorage)

### 13.2 Input Validation
- All inputs validated on backend (Symfony Validator)
- Sanitize all user inputs to prevent XSS
- Parameterized queries via Doctrine ORM (prevents SQL injection)
- CSRF protection for web forms (if using Symfony forms)

### 13.3 Rate Limiting (Recommended)
- Login endpoint: 5 attempts per 15 minutes per IP
- Registration endpoint: 3 attempts per hour per IP
- General API: 100 requests per minute per user
- Use Symfony Rate Limiter component

### 13.4 CORS Configuration
- Allow frontend domain (Next.js app)
- Restrict allowed origins in production
- Configure via NelmioCorsBundle

### 13.5 HTTPS
- Enforce HTTPS in production
- Set Secure flag on cookies
- HSTS header enabled

---

## 14. Internationalization (i18n)

### 14.1 Language Support
- Default language: Polish (`pl`)
- Prepared for English (`en`) and future languages
- Query parameter `?lang=pl` or `Accept-Language` header

### 14.2 Response Localization
- Exercise names returned based on language preference
- API uses `nameEn` if available and requested, fallback to `name` (Polish)
- Error messages in Polish for MVP (can be localized later)
- Validation messages use Symfony Translation component

---

## 15. API Versioning

### 15.1 Version Strategy
- URL path versioning: `/api/v1/...`
- Current version: v1
- Breaking changes require new version (v2, v3, etc.)
- Non-breaking changes added to current version

### 15.2 Deprecation Policy
- Deprecated endpoints return `Deprecated` header
- Minimum 6 months support for old versions
- Clear migration documentation for breaking changes

---

## 16. Testing Strategy

### 16.1 Backend Testing
- Unit tests for business logic (Doctrine entities, services)
- Integration tests for repositories
- Functional tests for API endpoints (Symfony WebTestCase)
- Test coverage target: >80%

### 16.2 Test Data
- Use Doctrine Fixtures for seeding test data
- Separate test database
- Reset database between test runs

---

## 17. API Response Format Standards

### 17.1 Success Response Format
- **Collections**: Return array directly or object with `data` array + `meta` for pagination
- **Single resources**: Return object directly
- **No wrapper**: No `status: "success"` field - use HTTP status code

**Example - Collection with pagination**:
```json
{
  "data": [ /* array of resources */ ],
  "meta": {
    "total": 45,
    "limit": 50,
    "offset": 0
  }
}
```

**Example - Single resource**:
```json
{
  "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "name": "Workout A",
  /* ... other fields */
}
```

### 17.2 Date/Time Format
- Dates: ISO 8601 format `YYYY-MM-DD`
- DateTimes: ISO 8601 with timezone `YYYY-MM-DDTHH:MM:SSZ`
- Timezone: UTC in API, convert in frontend

### 17.3 Numeric Formats
- Weight: float with 1 decimal precision (e.g., 70.5)
- Integers: standard JSON integer format
- IDs: uuid4 string (26 characters)

### 17.4 Null Handling
- Optional fields can be `null`
- Empty arrays preferred over `null` for collections
- Omit fields instead of sending `null` when appropriate

---

## 18. Future API Enhancements (Post-MVP)

### 18.1 Planned Endpoints
- `POST /workout-sessions/{id}/restore` - Restore soft-deleted session
- `GET /workout-sessions/templates` - Workout templates
- `POST /workout-sessions/duplicate/{id}` - Duplicate session
- `GET /statistics/volume` - Volume analytics (sets × reps × weight)
- `GET /statistics/comparison` - Compare time periods
- `PATCH /users/password` - Change password
- `DELETE /users/account` - Delete account

### 18.2 Advanced Features
- Real-time updates via WebSockets
- Export data (JSON, CSV)
- Import workouts from other apps
- Social features (share workouts, follow users)
- OAuth integration (Google, Facebook)

---

## Appendix A: Example Incremental Workout Creation Flow

This example demonstrates the recommended workflow for creating a workout session during a live gym session.

### Step 1: User logs in
```http
POST /api/v1/auth/login
{
  "email": "user@example.com",
  "password": "SecurePass123"
}
```

**Response** (200 OK): Receives JWT token
```json
{
  "user": { "id": "...", "email": "..." },
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

### Step 2: Create empty workout session
```http
POST /api/v1/workout-sessions
Authorization: Bearer <token>
{
  "date": "2025-10-11",
  "name": "FBW"
}
```

**Response** (201 Created):
```json
{
  "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "date": "2025-10-11",
  "name": "FBW",
  "workoutExercises": [],
  "createdAt": "2025-10-11T10:30:00Z"
}
```

### Step 3: Add first exercise (Bench Press)
```http
POST /api/v1/workout-exercises
Authorization: Bearer <token>
{
  "workoutSessionId": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "exerciseId": "01ARZ3NDEKTSV4RRFFQ69G5FB1",
  "sets": [
    { "setsCount": 1, "reps": 10, "weightKg": 60 }
  ]
}
```

**Response** (201 Created): Exercise added with first set

### Step 4: Add more sets to Bench Press
```http
POST /api/v1/exercise-sets
Authorization: Bearer <token>
{
  "workoutExerciseId": "01ARZ3NDEKTSV4RRFFQ69G5FB0",
  "setsCount": 3,
  "reps": 10,
  "weightKg": 70
}
```

**Response** (201 Created): Additional set group added

### Step 5: Add second exercise (Squats)
```http
POST /api/v1/workout-exercises
Authorization: Bearer <token>
{
  "workoutSessionId": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "exerciseId": "01ARZ3NDEKTSV4RRFFQ69G5FB5",
  "sets": [
    { "setsCount": 4, "reps": 12, "weightKg": 100 }
  ]
}
```

**Response** (201 Created): Second exercise added

### Step 6: View complete session
```http
GET /api/v1/workout-sessions/01ARZ3NDEKTSV4RRFFQ69G5FAV
Authorization: Bearer <token>
```

**Response** (200 OK): Full session with all exercises and sets

### Step 7: View progress statistics
```http
GET /api/v1/statistics/exercise/01ARZ3NDEKTSV4RRFFQ69G5FB1
Authorization: Bearer <token>
```

**Response** (200 OK): Progress chart data for Bench Press

**Benefits of this approach**:
- User can start session before knowing all exercises
- Sets can be added in real-time during workout
- No data loss if user exits app mid-workout
- Natural flow matching actual gym behavior

---

## Appendix B: Database-to-API Field Mapping

| Database Field | API Field | Notes |
|---------------|-----------|-------|
| id | id | uuid4 (26 chars) |
| user_id | userId | uuid4 |
| created_at | createdAt | ISO 8601 DateTime |
| updated_at | updatedAt | ISO 8601 DateTime |
| deleted_at | (hidden) | Not exposed in API responses |
| deleted_by | (hidden) | Not exposed in API responses |
| weight_grams | weightKg | Converted: grams / 1000 |
| sets_count | setsCount | camelCase in API |
| muscle_category_id | muscleCategoryId | Foreign key as uuid4 |
| name_pl | namePl | camelCase in API |
| name_en | nameEn | camelCase in API |
| exercise_id | exerciseId | Foreign key as uuid4 |
| workout_session_id | workoutSessionId | Foreign key as uuid4 (usually in nested context) |
| workout_exercise_id | workoutExerciseId | Foreign key as uuid4 (usually in nested context) |

---

**Document Version**: 1.1  
**Last Updated**: 2025-10-11  
**API Version**: v1  
**Status**: MVP Specification  

**Changelog**:
- v1.1 (2025-10-11):
  - Removed `status` field from all responses (rely on HTTP status codes)
  - Removed `GET /workout-sessions/recent` endpoint (use query params on main endpoint)
  - Changed `POST /workout-sessions` to allow empty sessions (no exercises required)
  - Added new endpoints: `POST/PUT/DELETE /workout-exercises` for incremental exercise management
  - Added new endpoints: `POST/PUT/DELETE /exercise-sets` for granular set management
  - Updated workflow example to demonstrate incremental workout building
- v1.0 (2025-10-11): Initial API specification

