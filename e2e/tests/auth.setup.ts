import fs from 'node:fs';
import { test as setup, expect } from '@playwright/test';
import { AUTH_DIR, USER_STATE, assertAllowedHost, env, hasUser } from '../utils/env';
import { LoginPage } from '../pages/LoginPage';

/** Logs the main account in once; "app", "session" and "mobile" reuse the saved session. */
setup('log in main test account', async ({ page }) => {
  assertAllowedHost();
  setup.skip(!hasUser(), 'Set E2E_USER_EMAIL and E2E_USER_PASSWORD in .env');

  await new LoginPage(page).login({
    email: env.user.email,
    password: env.user.password,
    totpSecret: env.user.totpSecret || undefined,
  });
  await expect(page.locator('nav.navbar')).toBeVisible();

  fs.mkdirSync(AUTH_DIR, { recursive: true });
  await page.context().storageState({ path: USER_STATE });
});
