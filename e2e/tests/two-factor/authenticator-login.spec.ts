import { test, expect } from '@playwright/test';
import { LoginPage, OtpPage } from '../../pages/LoginPage';
import { AppShell } from '../../pages/AppShell';
import { flashError } from '../../utils/checks';
import { env, hasTwoFactorUser } from '../../utils/env';
import { freshTotp, wrongTotp } from '../../utils/totp';

/**
 * Login with an authenticator-app account (E2E_2FA_*): agents.mobile_otp = 1,
 * agent_users.two_factor_enabled = 1, two_factor_method = 'authenticator', app already set up.
 * Each login ends that user's other sessions, so this account must not be the main one.
 */
test.describe.configure({ mode: 'serial' });

test.describe('Authenticator two-factor login', () => {
  test.skip(!hasTwoFactorUser(), 'set E2E_2FA_EMAIL, E2E_2FA_PASSWORD and E2E_2FA_TOTP_SECRET');
  test.skip(
    !!env.user.email && env.twoFactor.email.toLowerCase() === env.user.email.toLowerCase(),
    'E2E_2FA_EMAIL must be a different account from E2E_USER_EMAIL',
  );

  const { email, password, totpSecret } = env.twoFactor;

  test('password step asks for an authenticator code and does not log in yet', async ({ page }) => {
    const login = new LoginPage(page);
    await login.goto();
    await login.submitPassword(email, password);

    await expect(page).toHaveURL(/\/agent-users\/otp/);
    const otp = new OtpPage(page);
    await expect(otp.heading).toHaveText('Authenticator code');
    await expect(otp.code).toHaveAttribute('autocomplete', 'one-time-code');
    await expect(otp.resendOtp).toHaveCount(0);

    // Not logged in until the second factor passes.
    await page.goto('/orders');
    await expect(page).toHaveURL(/\/agent-users\/login/);
  });

  test('wrong code is rejected, right code logs in', async ({ page }) => {
    const login = new LoginPage(page);
    await login.goto();
    await login.submitPassword(email, password);
    const otp = new OtpPage(page);

    await otp.submit(wrongTotp(totpSecret));
    await expect(page).toHaveURL(/\/agent-users\/otp/);
    await expect(flashError(page)).toContainText('Incorrect code');

    await otp.submit(await freshTotp(totpSecret));
    await expect(page).not.toHaveURL(/\/agent-users\/(login|otp)/);
    await expect(new AppShell(page).navbar).toBeVisible();
  });

  test('a code cannot be reused (replay protection)', async ({ page }) => {
    const login = new LoginPage(page);
    const otp = new OtpPage(page);

    await login.goto();
    await login.submitPassword(email, password);
    const code = await freshTotp(totpSecret, 15);
    await otp.submit(code);
    await expect(page).not.toHaveURL(/\/agent-users\/otp/);

    await page.context().clearCookies();
    await login.goto();
    await login.submitPassword(email, password);
    await otp.submit(code);
    await expect(page).toHaveURL(/\/agent-users\/otp/);
    await expect(flashError(page)).toContainText('Incorrect code');
  });

  test('redirect target survives the second factor', async ({ page }) => {
    const login = new LoginPage(page);
    await login.goto('/orders/my-tickets');
    await login.submitPassword(email, password);
    await expect(page).toHaveURL(/\/agent-users\/otp\?redirect=/);
    await new OtpPage(page).submit(await freshTotp(totpSecret));
    await expect(page).toHaveURL(/\/orders\/my-tickets/);
  });

  test('two-factor page reports the authenticator as set up', async ({ page }) => {
    await new LoginPage(page).login({ email, password, totpSecret });
    await page.goto('/agent-users/two-factor');
    await expect(page.getByText('Authenticator app is set up')).toBeVisible();
    await expect(page.getByText(/backup codes? left/)).toBeVisible();
  });

  test('backup code logs in once', async ({ page }) => {
    test.skip(!env.twoFactor.backupCode, 'set E2E_2FA_BACKUP_CODE (it gets used up)');
    const login = new LoginPage(page);
    await login.goto();
    await login.submitPassword(email, password);
    await new OtpPage(page).submit(env.twoFactor.backupCode);
    await expect(page).not.toHaveURL(/\/agent-users\/(login|otp)/);

    // Same code again must fail.
    await page.context().clearCookies();
    await login.goto();
    await login.submitPassword(email, password);
    await new OtpPage(page).submit(env.twoFactor.backupCode);
    await expect(page).toHaveURL(/\/agent-users\/otp/);
  });
});
