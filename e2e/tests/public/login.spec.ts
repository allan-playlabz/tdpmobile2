import { test, expect } from '@playwright/test';
import { LoginPage } from '../../pages/LoginPage';
import { expectHealthyPage, flashError, trackPageErrors } from '../../utils/checks';
import { env, hasUser, throwawayEmail } from '../../utils/env';

test.describe('Login page', () => {
  test('renders the sign-in form', async ({ page }) => {
    const errors = trackPageErrors(page);
    const login = new LoginPage(page);
    const res = await page.goto('/agent-users/login');
    await expectHealthyPage(page, res);

    await expect(login.heading).toHaveText('Agent Login');
    await expect(login.email).toBeVisible();
    await expect(login.password).toBeVisible();
    await expect(login.password).toHaveAttribute('type', 'password');
    await expect(login.submit).toHaveText('Sign in');
    await expect(login.forgotPasswordLink).toBeVisible();
    await expect(login.signupLink).toBeVisible();
    await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', /noindex/);
    await expect(page.locator('input[name="_csrfToken"]')).toHaveCount(1);
    expect(errors).toEqual([]);
  });

  test('unknown email shows a generic error and stays on login', async ({ page }) => {
    const login = new LoginPage(page);
    await login.goto();
    await login.submitPassword(throwawayEmail(), 'not-the-password');

    await expect(page).toHaveURL(/\/agent-users\/login/);
    await expect(flashError(page)).toContainText('Wrong login information.');
  });

  test('wrong password gives the same message as an unknown email (no account enumeration)', async ({ page }) => {
    test.skip(!hasUser(), 'needs E2E_USER_EMAIL');
    const login = new LoginPage(page);
    await login.goto();
    // One failed attempt; the app allows 5 per IP+email per 3 minutes and a later success clears them.
    await login.submitPassword(env.user.email, `wrong-${Date.now()}`);

    await expect(page).toHaveURL(/\/agent-users\/login/);
    await expect(flashError(page)).toHaveText('Wrong login information.');
  });

  test('forgot password link opens the reset form', async ({ page }) => {
    const login = new LoginPage(page);
    await login.goto();
    await login.forgotPasswordLink.click();

    await expect(page).toHaveURL(/\/agent-users\/forgot-password/);
    await expectHealthyPage(page);
    await expect(page.getByRole('heading', { name: 'Forgot Password' })).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Send me the link' })).toBeVisible();
  });

  test('signup link opens the agent signup form', async ({ page }) => {
    const login = new LoginPage(page);
    await login.goto();
    await login.signupLink.click();

    await expect(page).toHaveURL(/\/signup$/);
    await expectHealthyPage(page);
    for (const field of ['company_name', 'first_name', 'last_name', 'email', 'contact_no']) {
      await expect(page.locator(`[name="${field}"]`), field).toBeVisible();
    }
  });

  test('switches language with ?l=zh_CN and back with ?l=en_US', async ({ page }) => {
    const login = new LoginPage(page);
    await login.goto(undefined, 'zh_CN');
    test.skip((await login.heading.textContent())?.trim() === 'Agent Login', 'zh_CN not enabled in Translation.languages');
    await expect(login.heading).toHaveText('代理登录');
    await expect(login.submit).toHaveText('登录');

    await login.goto(undefined, 'en_US');
    await expect(login.heading).toHaveText('Agent Login');
  });
});
