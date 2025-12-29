/**
 * E2E Tests for User Authentication
 * Tests login, registration, logout, and protected routes
 */

import { test, expect } from '@playwright/test';
import {
  loginUser,
  logoutUser,
  clearAuthState,
  expectLoginPage,
  expectDashboard,
  getAuthToken,
  verifyJWTFormat,
} from './helpers/auth.helpers';
import { TEST_USERS } from './fixtures/test-data';
import { resetDatabase } from './helpers/database.helpers';

test.describe('Autentykacja użytkownika', () => {
  
  // Clear state before each test
  test.beforeEach(async ({ page }) => {
      await resetDatabase();
      await clearAuthState(page);
  });

  // ============================================
  // Successful Login Tests
  // ============================================

  test('Użytkownik może zalogować się z poprawnymi danymi', async ({ page }) => {
    // Arrange
    const { email, password } = TEST_USERS.valid;
    
    // Act
    await page.goto('/login');
    
    // Verify login page loaded
    await expect(page.locator('h2')).toContainText('Zaloguj się');
    
    // Fill login form
    await page.fill('input[type="email"]', email);
    await page.fill('input[type="password"]', password);
    
    // Submit form
    await page.click('button[type="submit"]');
    
    // Assert
    // Should redirect to dashboard
    await expectDashboard(page);
    
    // Token should be stored in localStorage
    const token = await getAuthToken(page);
    expect(token).toBeTruthy();
    expect(verifyJWTFormat(token!)).toBe(true);
  });


//   // ============================================
//   // Logout Tests
//   // ============================================

  test('Użytkownik może się wylogować', async ({ page }) => {
    // Login first
    await loginUser(page);
    await expectDashboard(page);
    
    // Logout by clearing localStorage and navigating
    await logoutUser(page);
    
    // Should be on login page
    await expectLoginPage(page);
    
    // Token should be cleared
    const token = await getAuthToken(page);
    expect(token).toBeNull();
  });

  test('Po wylogowaniu nie można dostać się do chronionych stron', async ({ page }) => {
    // Login first
    await loginUser(page);
    
    // Logout
    await logoutUser(page);
    
    // Try to access protected route
    await page.goto('/dashboard');
    
    // Should redirect to login
    await expect(page).toHaveURL(/\/login/, { timeout: 10000 });
  });
});

