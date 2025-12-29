import { FullConfig } from '@playwright/test';
import { resetDatabase } from './helpers/database.helpers';

async function globalSetup(config: FullConfig) {
  console.log('Running global setup - resetting database once before all tests');
  
  // Reset the database once before all tests
  await resetDatabase();
}

export default globalSetup;
