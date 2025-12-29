/**
 * Workout Session Helper Functions for E2E Tests
 * Provides reusable functions for creating and managing workout sessions
 */

import { Page, expect } from '@playwright/test';

/**
 * Add exercise to workout session form using search
 * @param page - Playwright page object
 * @param exerciseName - Name or partial name of exercise to search for
 */
export async function addExerciseToSession(
  page: Page,
  exerciseName: string = 'przysiad'
): Promise<void> {
  // Find and fill exercise search input
  const exerciseSearchInput = page.locator('input[placeholder="Szukaj ćwiczenia..."]');
  await exerciseSearchInput.fill(exerciseName);
  
  // Wait for dropdown to appear
  await page.waitForTimeout(500);
  
  // Click first matching exercise from dropdown
  const firstExerciseOption = page.locator(
    `button:has-text("${exerciseName}"), li:has-text("${exerciseName}")`
  ).first();
  
  await firstExerciseOption.click();
  
  // Wait for exercise to be added to form
  await page.waitForTimeout(300);
}

/**
 * Add a set to an exercise in the workout form
 * @param page - Playwright page object
 * @param reps - Number of repetitions
 * @param weight - Weight in kg
 * @param exerciseIndex - Index of exercise to add set to (0-based)
 */
export async function addSetToExercise(
  page: Page,
  reps: number,
  weight: number,
  exerciseIndex: number = 0
): Promise<void> {
  // Find "Dodaj serię" button for specific exercise
  // If exerciseIndex is 0, use first, otherwise use nth
  const addSetButtons = page.locator('button:has-text("Dodaj serię")');
  const addSetButton = exerciseIndex === 0 ? addSetButtons.first() : addSetButtons.nth(exerciseIndex);
  
  await addSetButton.click();
  await page.waitForTimeout(300);
  
  // Fill the most recently added set (last inputs)
  const allRepsInputs = page.locator('input[name*="reps"]');
  const allWeightInputs = page.locator('input[name*="weightKg"]');
  
  const repsInput = allRepsInputs.last();
  const weightInput = allWeightInputs.last();
  
  await repsInput.fill(String(reps));
  await weightInput.fill(String(weight));
}

/**
 * Create a complete workout session with exercises and sets
 * @param page - Playwright page object
 * @param workoutName - Name of the workout
 * @param exercises - Array of exercises with sets
 */
export async function createCompleteWorkoutSession(
  page: Page,
  workoutName: string,
  exercises: Array<{
    name: string;
    sets: Array<{ reps: number; weight: number }>;
  }>
): Promise<void> {
  await page.goto('/dashboard/sessions/new');
  
  // Fill metadata
  await page.fill('input[name="name"]', workoutName);
  const today = new Date().toISOString().split('T')[0];
  await page.fill('input[name="date"]', today);
  
  // Add each exercise
  for (const exercise of exercises) {
    await addExerciseToSession(page, exercise.name);
    
    // Add sets to this exercise
    for (const set of exercise.sets) {
      await addSetToExercise(page, set.reps, set.weight);
    }
  }
  
  // Submit form
  await page.click('button[type="submit"]:has-text("Zapisz sesję")');
  
  // Wait for redirect (UUID format)
  await expect(page).toHaveURL(/\/dashboard\/sessions\/[0-9a-f-]+/, { timeout: 10000 });
}

/**
 * Navigate to new workout session form
 */
export async function goToNewSessionForm(page: Page): Promise<void> {
  await page.goto('/dashboard/sessions/new');
  await expect(page.locator('h1:has-text("Nowa Sesja Treningowa")')).toBeVisible();
}

/**
 * Check if session form is valid and ready to submit
 */
export async function isSessionFormValid(page: Page): Promise<boolean> {
  // Check if there's at least one exercise with one set
  const repsInputs = page.locator('input[name*="reps"]');
  const count = await repsInputs.count();
  return count > 0;
}

/**
 * Clear exercise search input
 */
export async function clearExerciseSearch(page: Page): Promise<void> {
  const exerciseSearchInput = page.locator('input[placeholder="Szukaj ćwiczenia..."]');
  await exerciseSearchInput.clear();
}

/**
 * Get number of exercises added to the form
 */
export async function getExerciseCount(page: Page): Promise<number> {
  // Count by red trash icon buttons in exercise cards
  // These buttons have text-red-600 class and contain SVG with trash icon
  const removeButtons = page.locator('button.text-red-600[type="button"]');
  return await removeButtons.count();
}

/**
 * Remove exercise from form by index
 */
export async function removeExerciseByIndex(page: Page, index: number): Promise<void> {
  // Find red trash icon buttons
  const removeButtons = page.locator('button.text-red-600[type="button"]');
  const button = removeButtons.nth(index);
  
  await button.click();
  
  // Confirm if there's a confirmation dialog
  page.on('dialog', dialog => dialog.accept());
}

