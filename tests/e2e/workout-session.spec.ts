/**
 * E2E Tests for Workout Session Management
 * Tests creating, viewing, editing, and deleting workout sessions
 */

import { test, expect } from '@playwright/test';
import { setupAuthenticatedState } from './helpers/auth.helpers';
import { addExerciseToSession, addSetToExercise } from './helpers/session.helpers';
import { TEST_WORKOUT, TEST_SETS, generateWorkoutName, getTodayDate } from './fixtures/test-data';
// Database reset moved to global-setup.ts

test.describe('Workout Session Management', () => {
  
  // Login before each test
  test.beforeEach(async ({ page }) => {
      await setupAuthenticatedState(page);
  });

  // ============================================
  // Navigation Tests
  // ============================================

  test('The "New session" button redirects to the form', async ({ page }) => {
    await page.goto('/dashboard');
    
    // Find and click "new session" button
    // Look for link or button with appropriate text
    const newSessionButton = page.locator('a[href="/dashboard/sessions/new"], button:has-text("Nowa sesja")').first();
    await newSessionButton.click();
    
    // Should navigate to new session form
    await expect(page).toHaveURL('/dashboard/sessions/new');
    await expect(page.locator('h1')).toContainText(/nowa sesja/i);
  });

  test('New session form is available', async ({ page }) => {
    await page.goto('/dashboard/sessions/new');
    
    // Verify form elements are present
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="date"]')).toBeVisible();
    await expect(page.locator('textarea[name="notes"]')).toBeVisible();
    await expect(page.locator('input[placeholder="Szukaj ćwiczenia..."]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  // ============================================
  // Create Workout Session Tests
  // ============================================

  test('User can create a simple workout session', async ({ page }) => {
    // Navigate to new session form
    await page.goto('/dashboard/sessions/new');
    
    // Fill basic metadata
    const workoutName = generateWorkoutName('Prosty trening');
    await page.fill('input[name="name"]', workoutName);
    await page.fill('input[name="date"]', getTodayDate());
    await page.fill('textarea[name="notes"]', 'Test notatka');
    
    // Search for and add an exercise
    const exerciseSearchInput = page.locator('input[placeholder="Szukaj ćwiczenia..."]');
    await exerciseSearchInput.fill('przysiad'); // Search for exercise
    
    // Wait for dropdown to appear and select first result
    await page.waitForTimeout(500);
    const firstExerciseOption = page.locator('button:has-text("przysiad"), li:has-text("przysiad")').first();
    await firstExerciseOption.click();
    
    // Wait for exercise to be added to form
    await page.waitForTimeout(500);
    
    // Add a set to the exercise
    await page.click('button:has-text("Dodaj serię")');
    await page.waitForTimeout(300);
    
    // Fill set data
    const repsInput = page.locator('input[name*="reps"]').last();
    const weightInput = page.locator('input[name*="weightKg"]').last();
    
    await repsInput.fill(String(TEST_SETS.medium.reps));
    await weightInput.fill(String(TEST_SETS.medium.weightKg));
    
    // Submit form
    await page.click('button[type="submit"]:has-text("Zapisz sesję")');
    
    // Should redirect to session detail page (UUID format)
    await expect(page).toHaveURL(/\/dashboard\/sessions\/[0-9a-f-]+/, { timeout: 10000 });
    
    // Verify workout name is displayed in h1 heading
    await expect(page.locator('h1').first()).toContainText(workoutName);
  });

  test('Multiple exercises can be added to a single session', async ({ page }) => {
    await page.goto('/dashboard/sessions/new');
    
    // Fill basic data
    await page.fill('input[name="name"]', 'Multi-exercise workout');
    await page.fill('input[name="date"]', getTodayDate());
    
    // Add first exercise
    await addExerciseToSession(page, 'przysiad');
    
    // Add second exercise
    await addExerciseToSession(page, 'wyciskanie');
    
    // Add third exercise
    await addExerciseToSession(page, 'martwy');
    
    // Count exercise sections by counting red trash icon buttons (delete exercise buttons)
    const removeButtons = page.locator('button.text-red-600[type="button"]');
    const count = await removeButtons.count();
    expect(count).toBeGreaterThanOrEqual(3);
  });

  test('Multiple sets can be added to a single exercise', async ({ page }) => {
    await page.goto('/dashboard/sessions/new');
    
    // Fill basic data
    await page.fill('input[name="name"]', 'Multi-set workout');
    
    // Add exercise
    await addExerciseToSession(page, 'przysiad');
    
    // Add multiple sets
    await addSetToExercise(page, 10, 60);
    await addSetToExercise(page, 10, 65);
    await addSetToExercise(page, 10, 70);
    
    // Count set inputs (reps inputs as proxy)
    const repsInputs = page.locator('input[name*="reps"]');
    const count = await repsInputs.count();
    expect(count).toBeGreaterThanOrEqual(3);
  });


  // ============================================
  // View Workout Session Tests
  // ============================================

  test('Created session is visible in the list', async ({ page }) => {
    // Create a workout first
    await page.goto('/dashboard/sessions/new');
    
    const workoutName = generateWorkoutName('Lista test');
    await page.fill('input[name="name"]', workoutName);
    await page.fill('input[name="date"]', getTodayDate());
    
    // Add exercise with set
    await addExerciseToSession(page, 'przysiad');
    await addSetToExercise(page, 10, 50);
    
    // Submit form
    await page.click('button[type="submit"]:has-text("Zapisz sesję")');
    
    // Wait for redirect (UUID format)
    await page.waitForURL(/\/dashboard\/sessions\/[0-9a-f-]+/, { timeout: 10000 });
    
    // Navigate to dashboard/history
    await page.goto('/dashboard');
    
    // Workout should be visible in the list
    await expect(page.locator(`text="${workoutName}"`)).toBeVisible({ timeout: 5000 });
  });
});

