import { test, expect, isMaster, isViewer } from './fixtures';
import { expectHealthyPage, flashError, flashSuccess } from '../../utils/checks';
import { env } from '../../utils/env';
import { AccountPage, ChangePasswordPage, ContactPage, HeaderBar, openHealthy } from '../../pages/MenuPages';

const WRONG_OLD = 'Please enter your old password correctly.';
const CHANGED = 'You have successfully changed your password.';

/** templates/AgentUsers/view.php + AgentUsersController::view. */
test.describe('My Account', () => {
  test('shows the logged-in user\'s company, email, credit and actions', async ({ page }) => {
    const account = new AccountPage(page);
    await account.goto();
    await expect(account.company).not.toBeEmpty();
    await expect(account.card).toContainText(env.user.email);
    await expect(account.card.locator('img.card-img-top')).toBeVisible();
    await expect(account.credit).toHaveText(/Current Credit: .*\d/);
    // Edit is for normal users only; Change Password for everyone but viewers.
    await expect(account.editLink).toHaveCount(!isMaster && !isViewer ? 1 : 0);
    await expect(account.changePasswordLink).toHaveCount(isViewer ? 0 : 1);
    // The user menu shows this user's name.
    await expect(page.locator('nav.navbar .nav-item.dropdown > a.dropdown-toggle').last()).not.toBeEmpty();
  });

  test('Change Password link opens the form', async ({ page }) => {
    test.skip(isViewer, 'viewers cannot change their password');
    const account = new AccountPage(page);
    await account.goto();
    await Promise.all([page.waitForURL(/\/agent-users\/change-password$/), account.changePasswordLink.click()]);
    await expectHealthyPage(page);
    await expect(page.getByRole('heading', { name: 'Change Password' })).toBeVisible();
    const form = new ChangePasswordPage(page);
    await expect(form.oldPassword).toHaveAttribute('type', 'password');
    await expect(form.password).toHaveAttribute('type', 'password');
    await expect(form.confirm).toHaveAttribute('type', 'password');
  });
});

test.describe('Change password', () => {
  test.skip(isViewer, 'viewers cannot change their password');

  test('a wrong old password is refused', async ({ page }) => {
    const form = new ChangePasswordPage(page);
    const flash = await form.change(`wrong-${Date.now()}`, 'E2e-NewPassw0rd!');
    expect(flash.error).toContain(WRONG_OLD);
    expect(flash.success).toBe('');
    await expect(page).toHaveURL(/\/agent-users\/change-password/);
    // Nothing is echoed back into the password fields.
    await expect(form.oldPassword).toHaveValue('');
  });

  test.describe('with E2E_ALLOW_PASSWORD_CHANGE', () => {
    test.describe.configure({ mode: 'serial' });
    test.skip(!env.allowPasswordChange, 'set E2E_ALLOW_PASSWORD_CHANGE=true to change the main password and change it back');
    test.skip(!env.user.password, 'needs E2E_USER_PASSWORD');

    /** Puts env.user.password back if `current` is what the account has now. */
    async function restore(form: ChangePasswordPage, current: string) {
      if (current === env.user.password) return;
      const flash = await form.change(current, env.user.password);
      if (!flash.success.includes(CHANGED)) {
        // Logged and annotated as well as thrown, so a lost password is hard to miss in the report.
        const msg = `Could not restore the main password (${flash.error}). It is now "${current}"; reset it by hand.`;
        console.error(msg);
        test.info().annotations.push({ type: 'warning', description: msg });
        throw new Error(msg);
      }
    }

    test('mismatched new passwords are refused', async ({ page }) => {
      const form = new ChangePasswordPage(page);
      const typed = `E2e-Mismatch-${Date.now()}!`;
      let current = env.user.password;
      try {
        const flash = await form.change(env.user.password, typed, `${typed}-other`);
        if (flash.success.includes(CHANGED)) current = typed;
        expect(flash.success, 'no success flash').toBe('');
        expect(flash.error).not.toBe('');
      } finally {
        await restore(form, current);
      }
    });

    test('change the password, then change it back', async ({ page }) => {
      const form = new ChangePasswordPage(page);
      const temp = `E2e-Temp-${Date.now()}!`;
      let current = env.user.password;
      try {
        let flash = await form.change(env.user.password, temp);
        if (flash.success.includes(CHANGED)) current = temp;
        expect(flash.success).toContain(CHANGED);
        // Master → Agent Users list; others are sent on to the home page.
        if (isMaster) await expect(page).toHaveURL(/\/agent-users$/);

        // The old password no longer works as "old password".
        flash = await form.change(env.user.password, `${temp}x`);
        expect(flash.error).toContain(WRONG_OLD);

        flash = await form.change(temp, env.user.password);
        if (flash.success.includes(CHANGED)) current = env.user.password;
        expect(flash.success).toContain(CHANGED);
      } finally {
        await restore(form, current);
      }
    });
  });
});

/** templates/AgentUsers/contact.php + AgentUsersController::contact. */
test.describe('Contact Us', () => {
  test.skip(isViewer, 'viewers have no contact form');

  test('form fields', async ({ page }) => {
    const contact = new ContactPage(page);
    await contact.goto();
    await expect(page.locator('h1', { hasText: 'Contact Us' })).toBeVisible();
    await expect(contact.subject).toBeVisible();
    await expect(contact.subject).toHaveAttribute('required', '');
    await expect(contact.message).toHaveAttribute('required', '');
    await expect(contact.message).toHaveAttribute('rows', '10');
    await expect(contact.send).toBeVisible();
  });

  test('nothing is sent without both subject and message', async ({ page }) => {
    const contact = new ContactPage(page);
    for (const [subject, message] of [['', ''], ['E2E subject only', ''], ['', 'E2E message only']]) {
      await test.step(`subject="${subject}" message="${message}"`, async () => {
        await contact.goto();
        // Drop the browser `required` check so the server-side check (mail only when both are set) is what is tested.
        await page.locator('#contact-form [required]').evaluateAll((els) => els.forEach((el) => el.removeAttribute('required')));
        await contact.submit(subject, message);
        await expect(page).toHaveURL(/\/agent-users\/contact$/);
        await expect(flashSuccess(page)).toHaveCount(0);
        await expect(contact.subject).toHaveValue(subject);
        await expect(contact.message).toHaveValue(message);
      });
    }
  });

  test('sends a message and clears the form', async ({ page }) => {
    test.skip(!env.allowEmail, 'set E2E_ALLOW_EMAIL=true to send a real email');
    const contact = new ContactPage(page);
    await contact.goto();
    await contact.submit(`E2E test ${new Date().toISOString()}`, 'Automated Playwright test message, please ignore.');
    await expect(flashSuccess(page)).toContainText('Message successfully sent.');
    await expect(contact.subject).toHaveValue('');
    await expect(contact.message).toHaveValue('');
  });

  test('viewers are refused', async ({ page }) => {
    test.skip(!isViewer, 'only for viewer accounts');
    await page.goto('/agent-users/contact');
    await expect(page).not.toHaveURL(/\/agent-users\/contact/);
    await expect(flashError(page)).toContainText('You are not authorized to access that location.');
  });
});

/** templates/AgentUsers/two_factor.php + AgentUsersController::twoFactor. */
test.describe('Two-factor settings page', () => {
  const STATES = ['Two-factor sign-in is off', 'Email codes', 'Authenticator app is set up', 'No authenticator app yet'];

  test('shows one state and only the forms that state allows', async ({ page }) => {
    await openHealthy(page, '/agent-users/two-factor');
    await expect(page.getByRole('heading', { name: 'Two-factor sign-in' })).toBeVisible();
    const state = page.locator('.card-body .fw-bold').first();
    await expect(state).toHaveText(new RegExp(`^(${STATES.join('|')})$`));
    const text = ((await state.textContent()) ?? '').trim();
    const buttons = page.locator('button[name="do"]');
    switch (text) {
      case 'Two-factor sign-in is off':
      case 'Email codes':
        await expect(buttons).toHaveCount(0);
        break;
      case 'No authenticator app yet':
        await expect(buttons).toHaveCount(1);
        await expect(page.getByRole('button', { name: 'Set up' })).toBeVisible();
        await expect(page.locator('input[name="code"]')).toHaveCount(0);
        break;
      case 'Authenticator app is set up':
        await expect(buttons).toHaveCount(2);
        await expect(page.getByText(/\d+ backup codes? left/)).toBeVisible();
        break;
    }
    // Header and menu still there (the page uses the default layout).
    await expect(new HeaderBar(page).navbar).toBeVisible();
  });

  test('authenticator users see backup-code and new-phone actions', async ({ page }) => {
    await openHealthy(page, '/agent-users/two-factor');
    test.skip(!(await page.getByText('Authenticator app is set up').isVisible()), 'main account has no authenticator');
    await expect(page.getByRole('button', { name: 'Create new codes' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Continue' })).toBeVisible();
    const codes = page.locator('input[name="code"]');
    await expect(codes).toHaveCount(2);
    await expect(codes.first()).toHaveAttribute('maxlength', '6');
    await expect(codes.first()).toHaveAttribute('autocomplete', 'one-time-code');
  });

  test('regenerating backup codes with a wrong app code is refused', async ({ page }) => {
    await openHealthy(page, '/agent-users/two-factor');
    test.skip(!(await page.getByText('Authenticator app is set up').isVisible()), 'main account has no authenticator');
    const form = page.locator('form', { has: page.getByRole('button', { name: 'Create new codes' }) });
    await form.locator('input[name="code"]').fill('000000');
    await Promise.all([page.waitForEvent('load'), form.getByRole('button', { name: 'Create new codes' }).click()]);
    await expect(page).toHaveURL(/\/agent-users\/two-factor$/);
    await expect(flashError(page)).toContainText('Incorrect code');
    await expect(page.locator('#tf-codes')).toHaveCount(0);
  });

  test('"Set up a new phone" with a wrong app code is refused', async ({ page }) => {
    await openHealthy(page, '/agent-users/two-factor');
    test.skip(!(await page.getByText('Authenticator app is set up').isVisible()), 'main account has no authenticator');
    const form = page.locator('form', { has: page.getByRole('button', { name: 'Continue' }) });
    await form.locator('input[name="code"]').fill('000000');
    await Promise.all([page.waitForEvent('load'), form.getByRole('button', { name: 'Continue' }).click()]);
    await expect(page).toHaveURL(/\/agent-users\/two-factor$/);
    await expect(flashError(page)).toContainText('Incorrect code');
  });

  test('backup codes page without a fresh regeneration redirects to the settings', async ({ page }) => {
    const res = await page.goto('/agent-users/two-factor/backup-codes');
    await expectHealthyPage(page, res);
    await expect(page).toHaveURL(/\/agent-users\/two-factor$/);
    await expect(page.locator('#tf-codes')).toHaveCount(0);
  });
});
