import { test, expect } from '@playwright/test';
import { expectHealthyPage } from '../../utils/checks';

/** Pages that need a login; each should bounce to /agent-users/login?redirect=<path>. */
const PROTECTED = [
  '/',
  '/attractions',
  '/orders',
  '/orders/cart',
  '/orders/checkout',
  '/orders/my-tickets',
  '/credit-logs',
  '/agent-topups',
  '/agent-topups/add',
  '/agent-users',
  '/agent-users/view',
  '/agent-users/add',
  '/agent-users/change-password',
  '/agent-users/contact',
  '/agent-users/two-factor',
  '/reports',
];

test.describe('Access control (logged out)', () => {
  for (const path of PROTECTED) {
    test(`${path} redirects to login`, async ({ page }) => {
      await page.goto(path);
      await expect(page).toHaveURL(/\/agent-users\/login/);
      const redirect = new URL(page.url()).searchParams.get('redirect');
      if (path !== '/') expect(redirect).toBe(path);
    });
  }

  test('public pages are reachable without login', async ({ page }) => {
    for (const path of ['/agent-users/login', '/agent-users/forgot-password', '/signup']) {
      const res = await page.goto(path);
      await expect(page, path).toHaveURL(new RegExp(`${path.replace(/\//g, '\\/')}`));
      await expectHealthyPage(page, res);
    }
  });

  test('OTP page without a pending login sends you back to login', async ({ page }) => {
    // AgentUsersController::otp(): a visit (GET) with no login waiting for its second factor goes
    // straight to the login page, without a flash message. (Posting a code in that state is still
    // refused with "Session has expired. Please log in again.")
    const res = await page.goto('/agent-users/otp');
    await expect(page).toHaveURL(/\/agent-users\/login/);
    await expectHealthyPage(page, res);
    await expect(page.locator('input[name="otp"]'), 'no OTP form is shown').toHaveCount(0);
  });

  test('authenticator setup without a pending login sends you back to login', async ({ page }) => {
    await page.goto('/agent-users/two-factor/setup');
    await expect(page).toHaveURL(/\/agent-users\/login/);
  });

  test('backup codes page without a pending login sends you back to login', async ({ page }) => {
    await page.goto('/agent-users/two-factor/backup-codes');
    await expect(page).toHaveURL(/\/agent-users\/login/);
  });

  test('removed /agents listing is not routable', async ({ page }) => {
    const res = await page.goto('/agents');
    expect(res?.status()).toBe(404);
  });

  test('unknown controller/action is not routable (fallbacks disabled)', async ({ page }) => {
    const res = await page.goto('/agents/index');
    expect(res?.status()).toBe(404);
  });

  test('voucher download with a bad signature does not return a file', async ({ page }) => {
    const res = await page.goto('/vouchers/download/00000000-0000-0000-0000-000000000000/not-a-valid-check', {
      waitUntil: 'commit',
    });
    expect(res?.headers()['content-type'] ?? '').not.toContain('application/pdf');
  });
});

test.describe('CSRF protection', () => {
  test('login POST without a CSRF token is rejected', async ({ page }) => {
    await page.goto('/agent-users/login');
    // Sent from the page (same origin, with its cookies) but without _csrfToken.
    const status = await page.evaluate(async () => {
      const body = new URLSearchParams({ email: 'e2e@example.invalid', password: 'x' });
      const r = await fetch('/agent-users/login', { method: 'POST', body, redirect: 'manual' });
      return r.status;
    });
    expect(status).toBe(403);
  });

  test('login POST with a forged CSRF token is rejected', async ({ page }) => {
    await page.goto('/agent-users/login');
    const status = await page.evaluate(async () => {
      const body = new URLSearchParams({ _csrfToken: 'forged', email: 'e2e@example.invalid', password: 'x' });
      const r = await fetch('/agent-users/login', { method: 'POST', body, redirect: 'manual' });
      return r.status;
    });
    expect(status).toBe(403);
  });
});
