// ============================================
// Application Constants
// ============================================

/**
 * API Base URL
 * Uses environment variable or defaults to localhost
 */
export const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/api/v1';

/**
 * App Configuration
 */
export const APP_CONFIG = {
  name: 'WorkoutTracker',
  version: '0.1.0',
  author: 'WorkoutTracker Team',
} as const;

/**
 * Pagination defaults
 */
export const PAGINATION = {
  defaultLimit: 20,
  maxLimit: 100,
} as const;

/**
 * Cache duration (milliseconds)
 */
export const CACHE_DURATION = {
  exercises: 24 * 60 * 60 * 1000, // 24 hours
  shortTerm: 5 * 60 * 1000, // 5 minutes
} as const;

/**
 * Local storage keys
 */
export const STORAGE_KEYS = {
  authToken: 'auth_token',
  exercisesCache: 'exercises_cache',
  workoutDraft: 'workout-draft',
} as const;

