// ============================================
// TypeScript Types & Interfaces
// Based on Swagger API Documentation
// ============================================

// ============================================
// Authentication
// ============================================

export interface User {
  id: string;
  email: string;
  createdAt: string;
}

export interface LoginRequest {
  username: string;
  password: string;
}

export interface RegisterRequest {
  email: string;
  password: string;
  passwordConfirmation: string;
}

export interface AuthResponse {
  user: User;
  token: string;
}

// ============================================
// Muscle Categories & Exercises
// ============================================

export interface MuscleCategory {
  id: string;
  namePl: string;
  nameEn: string;
  createdAt: string;
}

export interface Exercise {
  id: string;
  name: string;
  nameEn: string | null;
  muscleCategoryId: string;
  muscleCategory: MuscleCategory;
  createdAt: string;
}

// ============================================
// Workout Sessions
// ============================================

export interface CreateWorkoutSessionRequest {
  date: string;
  name?: string | null;
  notes?: string | null;
}

export interface UpdateWorkoutSessionRequest {
  date: string;
  name: string | null;
  notes: string | null;
}

export interface WorkoutSessionSummary {
  id: string;
  userId: string;
  date: string;
  name: string | null;
  notes: string | null;
  exerciseCount: number;
  createdAt: string;
  updatedAt: string;
}

export interface WorkoutSessionDetail {
  id: string;
  userId: string;
  date: string;
  name: string | null;
  notes: string | null;
  workoutExercises: WorkoutExercise[];
  createdAt: string;
  updatedAt: string;
}

export interface PaginationMeta {
  total: number;
  limit: number;
  offset: number;
}

export interface WorkoutSessionList {
  items: WorkoutSessionSummary[];
  total: number;
  limit: number;
  offset: number;
}

// ============================================
// Workout Exercises
// ============================================

export interface SetInput {
  setsCount: number;
  reps: number;
  weightKg: number;
}

export interface CreateWorkoutExerciseRequest {
  workoutSessionId: string;
  exerciseId: string;
  sets?: SetInput[];
}

export interface UpdateWorkoutExerciseRequest {
  sets: SetInput[];
}

export interface WorkoutExercise {
  id: string;
  workoutSessionId: string;
  exerciseId: string;
  exercise: {
    id: string;
    name: string;
    nameEn: string;
    muscleCategoryId: string;
  };
  exerciseSets: ExerciseSet[];
  createdAt: string;
}

// ============================================
// Exercise Sets
// ============================================

export interface CreateExerciseSetRequest {
  workoutExerciseId: string;
  setsCount: number;
  reps: number;
  weightKg: number;
}

export interface UpdateExerciseSetRequest {
  setsCount: number;
  reps: number;
  weightKg: number;
}

export interface ExerciseSet {
  id: string;
  workoutExerciseId: string;
  setsCount: number;
  reps: number;
  weightKg: number;
  createdAt: string;
}

// ============================================
// Statistics
// ============================================

export interface ExerciseStatistics {
  exerciseId: string;
  exercise: {
    id: string;
    name: string;
    nameEn: string;
  };
  dataPoints: {
    date: string;
    sessionId: string;
    maxWeightKg: number;
  }[];
  summary: {
    totalSessions: number;
    personalRecord: number;
    prDate: string;
    firstWeight: number;
    latestWeight: number;
    progressPercentage: number;
  } | null;
}

export interface DashboardStatistics {
  totalSessions: number;
  totalExercises: number;
  totalSets: number;
  recentActivity: {
    last7Days: number;
    last30Days: number;
  };
  topExercises: {
    exerciseId: string;
    exerciseName: string;
    timesPerformed: number;
    personalRecord: number;
  }[];
}

// ============================================
// Error Responses
// ============================================

export interface ErrorResponse {
  message: string;
}

export interface ValidationErrorResponse {
  message: string;
  errors: Record<string, string[]>;
}

// ============================================
// Form Data Types
// ============================================

export interface WorkoutSessionFormData {
  date: string;
  name?: string;
  notes?: string;
  exercises: {
    exerciseId: string;
    sets: {
      setsCount: number;
      reps: number;
      weightKg: number;
    }[];
  }[];
}

export interface LoginFormData {
  username: string;
  password: string;
}

export interface RegisterFormData {
  email: string;
  password: string;
  passwordConfirmation: string;
}

