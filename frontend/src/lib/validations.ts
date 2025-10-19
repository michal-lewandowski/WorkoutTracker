import { z } from 'zod';

// ============================================
// Zod Validation Schemas
// ============================================

// ============================================
// Authentication Schemas
// ============================================

/**
 * Login form validation schema
 */
export const loginSchema = z.object({
  username: z
    .string()
    .min(1, 'Email jest wymagany')
    .email('Nieprawidłowy format email'),
  password: z
    .string()
    .min(1, 'Hasło jest wymagane'),
});

/**
 * Register form validation schema
 * Password requirements:
 * - Min 8 characters
 * - At least 1 uppercase letter
 * - At least 1 digit
 */
export const registerSchema = z
  .object({
    email: z
      .string()
      .min(1, 'Email jest wymagany')
      .email('Nieprawidłowy format email')
      .max(255, 'Email może mieć maksymalnie 255 znaków'),
    password: z
      .string()
      .min(8, 'Hasło musi mieć minimum 8 znaków')
      .regex(/[A-Z]/, 'Hasło musi zawierać co najmniej jedną wielką literę')
      .regex(/\d/, 'Hasło musi zawierać co najmniej jedną cyfrę'),
    passwordConfirmation: z
      .string()
      .min(1, 'Potwierdzenie hasła jest wymagane'),
  })
  .refine((data) => data.password === data.passwordConfirmation, {
    message: 'Hasła muszą być identyczne',
    path: ['passwordConfirmation'],
  });

// ============================================
// Workout Session Schemas
// ============================================

/**
 * Exercise set validation schema
 */
export const exerciseSetSchema = z.object({
  setsCount: z
    .number({
      required_error: 'Liczba serii jest wymagana',
      invalid_type_error: 'Liczba serii musi być liczbą',
    })
    .int('Liczba serii musi być liczbą całkowitą')
    .min(1, 'Minimalna liczba serii to 1'),
  reps: z
    .number({
      required_error: 'Liczba powtórzeń jest wymagana',
      invalid_type_error: 'Liczba powtórzeń musi być liczbą',
    })
    .int('Liczba powtórzeń musi być liczbą całkowitą')
    .min(1, 'Minimalna liczba powtórzeń to 1')
    .max(100, 'Maksymalna liczba powtórzeń to 100'),
  weightKg: z
    .number({
      required_error: 'Ciężar jest wymagany',
      invalid_type_error: 'Ciężar musi być liczbą',
    })
    .min(0, 'Ciężar nie może być ujemny')
    .max(500, 'Ciężar nie może przekraczać 500kg'),
});

/**
 * Workout exercise validation schema
 */
export const workoutExerciseSchema = z.object({
  exerciseId: z
    .string()
    .min(1, 'Wybierz ćwiczenie')
    .uuid('Nieprawidłowy identyfikator ćwiczenia'),
  sets: z
    .array(exerciseSetSchema)
    .min(1, 'Dodaj co najmniej jedną serię')
    .max(20, 'Maksymalnie 20 serii na ćwiczenie'),
});

/**
 * Workout session form validation schema
 */
export const workoutSessionSchema = z.object({
  date: z
    .string()
    .min(1, 'Data jest wymagana')
    .regex(/^\d{4}-\d{2}-\d{2}$/, 'Nieprawidłowy format daty (YYYY-MM-DD)')
    .refine((date) => {
      const sessionDate = new Date(date);
      const today = new Date();
      today.setHours(23, 59, 59, 999);
      return sessionDate <= today;
    }, 'Data sesji nie może być z przyszłości'),
  name: z
    .string()
    .max(255, 'Nazwa może mieć maksymalnie 255 znaków')
    .optional(),
  notes: z
    .string()
    .optional(),
  exercises: z
    .array(workoutExerciseSchema)
    .max(15, 'Maksymalnie 15 ćwiczeń na sesję'),
});

/**
 * Update workout session metadata schema
 */
export const updateWorkoutSessionSchema = z.object({
  date: z
    .string()
    .min(1, 'Data jest wymagana')
    .regex(/^\d{4}-\d{2}-\d{2}$/, 'Nieprawidłowy format daty (YYYY-MM-DD)'),
  name: z
    .string()
    .max(255, 'Nazwa może mieć maksymalnie 255 znaków')
    .nullable(),
  notes: z
    .string()
    .nullable(),
});

// ============================================
// Helper Functions
// ============================================

/**
 * Check password strength
 * Returns object with validation results
 */
export function checkPasswordStrength(password: string) {
  return {
    minLength: password.length >= 8,
    hasUppercase: /[A-Z]/.test(password),
    hasDigit: /\d/.test(password),
    isValid: password.length >= 8 && /[A-Z]/.test(password) && /\d/.test(password),
  };
}

/**
 * Format validation errors for display
 */
export function formatValidationErrors(
  errors: Record<string, string[]>
): Record<string, string> {
  const formatted: Record<string, string> = {};
  Object.entries(errors).forEach(([field, messages]) => {
    formatted[field] = messages[0]; // Take first error message
  });
  return formatted;
}

