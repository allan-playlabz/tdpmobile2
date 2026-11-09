import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';
import { env, USER_STATE } from './utils/env';

/**
 * Projects
 *  - public      no login: login page, access control, CSRF, i18n, signup form
 *  - setup       logs the main test account in once and saves .auth/user.json
 *  - app         everything behind login, reusing that session
 *  - ordering    every pricing type, cart rules, checkout, credits + gateway payments, after-order (tests/ordering)
 *  - pages       My Orders, My Tickets, reports, balance, top-ups, agent users, account, header (tests/pages)
 *  - two-factor  authenticator login with a second account (skipped without E2E_2FA_*)
 *  - session     logout and post-login redirect; runs last because it ends the shared session
 *  - mobile-public / mobile   login page and navigation at phone size (this is a mobile front end)
 *
 * One worker: the app keeps one DB session per agent user and the cart lives in that
 * session, so parallel tests with the same account would trample each other.
 */
const chrome = { ...devices['Desktop Chrome'] };

export default defineConfig({
  testDir: './tests',
  fullyParallel: false,
  workers: 1,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  timeout: 60_000,
  expect: { timeout: 10_000 },
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: env.baseUrl,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    actionTimeout: 15_000,
    navigationTimeout: 30_000,
  },
  projects: [
    { name: 'public', testMatch: /public\/.*\.spec\.ts/, use: chrome },
    { name: 'setup', testMatch: /auth\.setup\.ts/, use: chrome },
    {
      name: 'app',
      testMatch: /app\/.*\.spec\.ts/,
      dependencies: ['setup'],
      use: { ...chrome, storageState: USER_STATE },
    },
    {
      name: 'ordering',
      testMatch: /ordering\/.*\.spec\.ts/,
      dependencies: ['setup'],
      use: { ...chrome, storageState: USER_STATE },
    },
    {
      // Menu pages; after ordering so My Orders / My Tickets / Balance Report have the new order.
      name: 'pages',
      testMatch: /pages\/.*\.spec\.ts/,
      dependencies: ['setup', 'ordering'],
      use: { ...chrome, storageState: USER_STATE },
    },
    { name: 'two-factor', testMatch: /two-factor\/.*\.spec\.ts/, use: chrome },
    {
      name: 'session',
      testMatch: /session\/.*\.spec\.ts/,
      dependencies: ['app', 'mobile', 'ordering', 'pages'],
      use: { ...chrome, storageState: USER_STATE },
    },
    { name: 'mobile-public', testMatch: /public\/login\.spec\.ts/, use: devices['Pixel 7'] },
    {
      name: 'mobile',
      testMatch: /app\/navigation\.spec\.ts/,
      dependencies: ['setup'],
      use: { ...devices['Pixel 7'], storageState: USER_STATE },
    },
  ],
  webServer: env.startServer
    ? {
        command: `"${env.phpBin}" -S 127.0.0.1:8765 -t webroot webroot/index.php`,
        cwd: path.resolve(__dirname, env.appDir),
        url: 'http://127.0.0.1:8765/agent-users/login',
        reuseExistingServer: true,
        timeout: 60_000,
      }
    : undefined,
});
