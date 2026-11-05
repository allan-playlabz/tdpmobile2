import { expect, type Locator, type Page } from '@playwright/test';
import { freshTotp } from '../utils/totp';

export interface Credentials {
  email: string;
  password: string;
  /** Base32 authenticator key; needed when the account uses the authenticator method. */
  totpSecret?: string;
  /** Use this backup code instead of an app code. */
  backupCode?: string;
}

/** /agent-users/login (templates/AgentUsers/login.php, layout/login.php). */
export class LoginPage {
  readonly email: Locator;
  readonly password: Locator;
  readonly submit: Locator;
  readonly heading: Locator;
  readonly forgotPasswordLink: Locator;
  readonly signupLink: Locator;

  constructor(private readonly page: Page) {
    this.email = page.locator('input[name="email"]');
    this.password = page.locator('input[name="password"]');
    this.submit = page.locator('form.form-signin button[type="submit"]');
    this.heading = page.locator('form.form-signin h1');
    this.forgotPasswordLink = page.locator('a[href*="/agent-users/forgot-password"]');
    this.signupLink = page.locator('a[href$="/signup"]');
  }

  async goto(redirect?: string, lang?: string): Promise<void> {
    const params = new URLSearchParams();
    if (redirect) params.set('redirect', redirect);
    if (lang) params.set('l', lang);
    const qs = params.toString();
    await this.page.goto(`/agent-users/login${qs ? `?${qs}` : ''}`);
  }

  /** Submits email + password only; the caller decides what should happen next. */
  async submitPassword(email: string, password: string): Promise<void> {
    await this.email.fill(email);
    await this.password.fill(password);
    await Promise.all([this.page.waitForLoadState('load'), this.submit.click()]);
  }

  /**
   * Full login, including the authenticator step when the account has one.
   * Leaves the page on wherever the app redirects after login.
   */
  async login(creds: Credentials, redirect?: string): Promise<void> {
    await this.goto(redirect);
    await this.submitPassword(creds.email, creds.password);

    const url = this.page.url();
    if (/\/agent-users\/login/.test(url)) {
      const msg = (await this.page.locator('.alert-danger').allTextContents()).join(' ').trim();
      throw new Error(`Login failed for ${creds.email}: ${msg || 'still on the login page'}`);
    }
    if (/\/agent-users\/otp/.test(url)) {
      const otp = new OtpPage(this.page);
      if (!(await otp.isAuthenticator())) {
        throw new Error(
          `${creds.email} got an emailed OTP, which these tests can't read. Use an account with 2FA off, ` +
            'an authenticator already set up (and its key in the env), or log in once from this IP so it is trusted.',
        );
      }
      if (creds.backupCode) {
        await otp.submit(creds.backupCode);
      } else if (creds.totpSecret) {
        await otp.submit(await freshTotp(creds.totpSecret));
      } else {
        throw new Error(`${creds.email} uses an authenticator app; set its TOTP secret in .env.`);
      }
      if (/\/agent-users\/otp/.test(this.page.url())) {
        throw new Error(`Authenticator code was rejected for ${creds.email}. Check the secret and the PC clock.`);
      }
    }
    if (/\/two-factor\/setup/.test(this.page.url())) {
      throw new Error(`${creds.email} still has to set up an authenticator app. Do that once by hand first.`);
    }
    await expect(this.page).not.toHaveURL(/\/agent-users\/(login|otp)/);
  }
}

/** /agent-users/otp (templates/AgentUsers/otp.php): authenticator or emailed code. */
export class OtpPage {
  readonly code: Locator;
  readonly submitButton: Locator;
  readonly heading: Locator;
  readonly resendOtp: Locator;

  constructor(private readonly page: Page) {
    this.code = page.locator('input[name="otp"]');
    this.submitButton = page.locator('form button').first();
    this.heading = page.locator('h3.subtitle');
    this.resendOtp = page.getByText('Resend OTP');
  }

  async isAuthenticator(): Promise<boolean> {
    return (await this.heading.textContent())?.includes('Authenticator') ?? false;
  }

  async submit(code: string): Promise<void> {
    await this.code.fill(code);
    await Promise.all([this.page.waitForLoadState('load'), this.submitButton.click()]);
  }
}
