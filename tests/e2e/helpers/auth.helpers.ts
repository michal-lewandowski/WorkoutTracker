/**
 * Authentication Helper Functions for E2E Tests
 * Provides reusable functions for login, logout, and user management
 */

import { Page, expect } from '@playwright/test';
import { TEST_USERS } from '../fixtures/test-data';

/**
 * Login user through UI
 * Fills the login form and waits for successful authentication
 */
export async function loginUser(
  page: Page,
  email: string = TEST_USERS.valid.email,
  password: string = TEST_USERS.valid.password
): Promise<void> {
  await page.goto('/login');
  
  // Wait for page to load
  await expect(page.locator('h2:has-text("Zaloguj się")')).toBeVisible({ timeout: 10000 });
  
  // Fill login form
  await page.fill('input[type="email"]', email);
  await page.fill('input[type="password"]', password);
  
  // Submit form
  await page.click('button[type="submit"]');
  
  // Wait for redirect to dashboard
  await expect(page).toHaveURL(/\/dashboard/, { timeout: 15000 });
  
  // Verify token is stored
  const token = await page.evaluate(() => localStorage.getItem('auth_token'));
  expect(token).toBeTruthy();
}

/**
 * Register new user through UI
 * Fills the registration form and waits for successful registration
 */
export async function registerUser(
  page: Page,
  email: string,
  password: string
): Promise<void> {
  await page.goto('/register');
  
  // Wait for page to load
  await expect(page.locator('h2:has-text("Zarejestruj się")')).toBeVisible({ timeout: 10000 });
  
  // Fill registration form
  await page.fill('input[type="email"]', email);
  
  // Fill password fields (adjust selectors based on your form)
  const passwordFields = page.locator('input[type="password"]');
  const count = await passwordFields.count();
  
  if (count === 2) {
    // Password and confirm password
    await passwordFields.nth(0).fill(password);
    await passwordFields.nth(1).fill(password);
  } else {
    // Just password
    await passwordFields.first().fill(password);
  }
  
  // Submit form
  await page.click('button[type="submit"]');
  
  // After registration, user should be logged in and redirected
  await expect(page).toHaveURL(/\/dashboard/, { timeout: 15000 });
}

/**
 * Logout user
 * Clears localStorage and navigates to login page
 */
export async function logoutUser(page: Page): Promise<void> {
  // Navigate to any page first to access localStorage (if not already on a page)
  try {
    await page.evaluate(() => {
      localStorage.removeItem('auth_token');
      localStorage.clear();
    });
  } catch (error) {
    // If localStorage access fails, navigate first then clear
    await page.goto('/');
    await page.evaluate(() => {
      localStorage.removeItem('auth_token');
      localStorage.clear();
    });
  }
  
  // Navigate to login page
  await page.goto('/login');
  await expect(page).toHaveURL('/login');
}

/**
 * Check if user is logged in
 * Returns true if auth token exists in localStorage
 */
export async function isUserLoggedIn(page: Page): Promise<boolean> {
  try {
    const token = await page.evaluate(() => localStorage.getItem('auth_token'));
    return !!token;
  } catch (error) {
    // If localStorage is not accessible, user is not logged in
    return false;
  }
}

/**
 * Setup authenticated state
 * Use this in beforeEach to start tests with logged in user
 */
export async function setupAuthenticatedState(page: Page): Promise<void> {
  // Clear any existing state
  await clearAuthState(page);
  
  // Login user
  await loginUser(page);
}

/**
 * Clear authentication state
 * Use this in afterEach or beforeEach to ensure clean state
 */
export async function clearAuthState(page: Page): Promise<void> {
  // Must navigate to a page first before accessing localStorage
  await page.goto('/');
  await page.evaluate(() => {
    localStorage.clear();
    sessionStorage.clear();
  });
}

/**
 * Verify user is on login page
 */
export async function expectLoginPage(page: Page): Promise<void> {
  await expect(page).toHaveURL('/login');
  await expect(page.locator('h2:has-text("Zaloguj się")')).toBeVisible();
}

/**
 * Verify user is on dashboard
 */
export async function expectDashboard(page: Page): Promise<void> {
  await expect(page).toHaveURL(/\/dashboard/);
  // Could add more assertions here like checking for specific dashboard elements
}

/**
 * Get authentication token from localStorage
 */
export async function getAuthToken(page: Page): Promise<string | null> {
  try {
    return await page.evaluate(() => localStorage.getItem('auth_token'));
  } catch (error) {
    // If localStorage is not accessible, return null
    return null;
  }
}

/**
 * Verify token format (JWT)
 */
export function verifyJWTFormat(token: string): boolean {
  const jwtRegex = /^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+$/;
  return jwtRegex.test(token);
}

