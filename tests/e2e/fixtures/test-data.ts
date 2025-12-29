/**
 * Test Data for E2E Tests
 * Contains test users, workout sessions, and exercise data
 */

// ============================================
// Test Users
// ============================================

export const TEST_USERS = {
  // Valid user that should exist in test database
  valid: {
    email: 'test@test.com',
    password: 'test123',
  },
  // For registration tests - generate unique email each time
  new: () => ({
    email: `test-${Date.now()}@example.com`,
    password: 'test123',
  }),
  // Invalid credentials for negative tests
  invalid: {
    email: 'nonexistent@example.com',
    password: 'WrongPassword123',
  },
} as const;

// ============================================
// Test Workout Sessions
// ============================================

export const TEST_WORKOUT = {
  name: 'Trening siłowy E2E',
  date: new Date().toISOString().split('T')[0],
  notes: 'Test workout session created by Playwright',
} as const;

// ============================================
// Test Exercises
// ============================================
// Note: These exercise names should match what exists in your database
// You may need to adjust based on your seed data

export const TEST_EXERCISES = {
  benchPress: {
    name: 'Wyciskanie sztangi na ławce poziomej',
    // Common variations to search for
    searchTerms: ['wyciskanie', 'bench press', 'ławka'],
  },
  squat: {
    name: 'Przysiad ze sztangą',
    searchTerms: ['przysiad', 'squat'],
  },
  deadlift: {
    name: 'Martwy ciąg',
    searchTerms: ['martwy', 'deadlift'],
  },
} as const;

// ============================================
// Test Set Data
// ============================================

export const TEST_SETS = {
  light: {
    reps: 12,
    weightKg: 40,
  },
  medium: {
    reps: 10,
    weightKg: 60,
  },
  heavy: {
    reps: 8,
    weightKg: 80,
  },
} as const;

// ============================================
// Helper Functions
// ============================================

/**
 * Generate unique workout name for testing
 */
export function generateWorkoutName(prefix: string = 'Test Workout'): string {
  return `${prefix} ${Date.now()}`;
}

/**
 * Generate today's date in YYYY-MM-DD format
 */
export function getTodayDate(): string {
  return new Date().toISOString().split('T')[0];
}

/**
 * Generate date offset from today
 */
export function getDateOffset(daysOffset: number): string {
  const date = new Date();
  date.setDate(date.getDate() + daysOffset);
  return date.toISOString().split('T')[0];
}


