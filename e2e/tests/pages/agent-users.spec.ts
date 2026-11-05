import { test, expect, isMaster } from './fixtures';
import { expectHealthyPage, flashError, flashSuccess } from '../../utils/checks';
import { env } from '../../utils/env';
import type { Page } from '@playwright/test';
import {
  AgentUserFormPage, AgentUsersPage, loginInFreshContext, openHealthy, postLikeForm,
} from '../../pages/MenuPages';

const NOT_AUTHORIZED = 'You are not authorized to access that location.';
const ZERO_ID = '00000000-0000-0000-0000-000000000000';

/** templates/AgentUsers/{index,add,edit}.php + AgentUsersController (master only). */
test.describe('Agent Users (master)', () => {
  test.skip(!isMaster, 'needs E2E_USER_ROLE=master');

  test('lists only this agent\'s sub-users with their actions', async ({ page }) => {
    const users = new AgentUsersPage(page);
    await users.goto();
    await expect(users.addUser).toHaveAttribute('href', /\/agent-users\/add$/);
    expect(await users.emails()).not.toContain(env.user.email);
    for (const row of await users.rows.all()) {
      await expect(row.locator('td').nth(2)).toHaveText(/^(Normal|Viewer)$/);
      await expect(row.locator('td').nth(3)).toHaveText(/^(Active|Inactive)$/);
      await expect(row.locator('a[href*="/agent-users/edit/"]')).toHaveCount(1);
      await expect(row.getByRole('link', { name: 'Reset Password' })).toHaveCount(1);
      // "Reset Authenticator" only for users with an authenticator set up.
      expect(await row.getByRole('link', { name: 'Reset Authenticator' }).count()).toBeLessThanOrEqual(1);
    }
  });

  test('sort links render and keep the same users', async ({ page }) => {
    const users = new AgentUsersPage(page);
    await users.goto();
    const all = [...(await users.emails())].sort();
    for (const [label, key] of [['Name', 'name'], ['Email', 'email'], ['Role', 'role'], ['Status', 'status']]) {
      await test.step(label, async () => {
        await users.goto();
        const link = users.sortLink(label);
        await expect(link).toHaveAttribute('href', new RegExp(`sort=${key}&(amp;)?direction=asc`));
        await Promise.all([page.waitForURL(new RegExp(`sort=${key}`)), link.click()]);
        await expectHealthyPage(page);
        expect([...(await users.emails())].sort()).toEqual(all);
      });
    }
  });

  test('sorting by name orders the list both ways', async ({ page }) => {
    const users = new AgentUsersPage(page);
    await users.goto();
    const names = await users.names();
    const sorted = [...names].sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' }));
    test.skip(new Set(names.map((n) => n.toLowerCase())).size < 2, 'needs at least two sub-users with different names');
    await users.goto({ sort: 'name', direction: 'asc' });
    expect(await users.names()).toEqual(sorted);
    // The column sorted ascending now links to descending.
    await expect(users.sortLink('Name')).toHaveAttribute('href', /sort=name&(amp;)?direction=desc/);
    await users.goto({ sort: 'name', direction: 'desc' });
    expect(await users.names()).toEqual([...sorted].reverse());
  });

  test('add form fields; temporary password is emailed (no password fields)', async ({ page }) => {
    await openHealthy(page, '/agent-users/add');
    const form = new AgentUserFormPage(page);
    await expect(form.name).toBeVisible();
    await expect(form.email).toHaveAttribute('type', 'email');
    await expect(form.role.locator('option')).toHaveText(['Normal', 'Viewer']);
    await expect(form.status.locator('option')).toHaveText(['Active', 'Inactive']);
    await expect(page.locator('[name="password"], [name="confirm_password"]')).toHaveCount(0);
    await expect(page.getByText('Note: Account temporary password will be sent to users email.')).toBeVisible();
  });

  test('add form: browser blocks empty name / invalid email', async ({ page }) => {
    await openHealthy(page, '/agent-users/add');
    const form = new AgentUserFormPage(page);
    await form.save.click();
    await expect(page).toHaveURL(/\/agent-users\/add$/);
    expect(await form.name.evaluate((el) => (el as HTMLInputElement).validity.valueMissing)).toBe(true);
    await form.name.fill('E2E nobody');
    await form.email.fill('not-an-email');
    await form.save.click();
    await expect(page).toHaveURL(/\/agent-users\/add$/);
    expect(await form.email.evaluate((el) => (el as HTMLInputElement).validity.typeMismatch)).toBe(true);
    await expect(flashSuccess(page)).toHaveCount(0);
  });

  test('add form: server rejects an invalid email', async ({ page }) => {
    await openHealthy(page, '/agent-users/add');
    const form = new AgentUserFormPage(page);
    await page.locator('form').first().evaluate((f) => { (f as HTMLFormElement).noValidate = true; });
    await form.name.fill('E2E invalid email');
    await form.email.evaluate((el) => { (el as HTMLInputElement).type = 'text'; });
    await form.email.fill('not-an-email');
    await form.status.selectOption('inactive');
    await Promise.all([page.waitForEvent('load'), form.save.click()]);
    await expectHealthyPage(page);
    await expect(flashError(page)).toContainText('The agent user could not be saved. Please, try again.');
    await expect(form.errors.first()).toBeVisible();
    await expect(form.email).toHaveValue('not-an-email');
  });

  test('add form: an email already in use is rejected', async ({ page }) => {
    test.skip(!env.user.email, 'needs E2E_USER_EMAIL');
    await openHealthy(page, '/agent-users/add');
    const form = new AgentUserFormPage(page);
    await form.name.fill('E2E duplicate email');
    await form.email.fill(env.user.email);
    await form.role.selectOption('viewer');
    await form.status.selectOption('inactive');
    await Promise.all([page.waitForEvent('load'), form.save.click()]);
    await expectHealthyPage(page);
    await expect(flashError(page)).toContainText('The agent user could not be saved. Please, try again.');
    await expect(form.errors.first()).toBeVisible();
  });

  test('Reset Password asks for confirmation; dismissing sends nothing', async ({ page }) => {
    const users = new AgentUsersPage(page);
    await users.goto();
    test.skip((await users.rows.count()) === 0, 'no sub-users');
    const row = users.rows.first();
    const name = ((await row.locator('td').first().textContent()) ?? '').trim();
    const dialog = page.waitForEvent('dialog');
    await row.getByRole('link', { name: 'Reset Password' }).click();
    const d = await dialog;
    expect(d.message()).toBe(`Are you sure you want to reset password of ${name}`);
    await d.dismiss();
    await expect(page).toHaveURL(/\/agent-users$/);
    await expect(flashSuccess(page)).toHaveCount(0);
  });

  test('actions on a user that is not this agent\'s sub-user are refused', async ({ page }) => {
    await page.goto('/agent-users');
    for (const action of ['suspend', 'agent-reset-password', 'reset-two-factor']) {
      await test.step(action, async () => {
        const res = await postLikeForm(page, `/agent-users/${action}/${ZERO_ID}`);
        expect(res.status()).toBe(302);
        await openHealthy(page, '/agent-users');
        await expect(flashError(page)).toContainText(NOT_AUTHORIZED);
      });
    }
    let res = await page.goto(`/agent-users/edit/${ZERO_ID}`);
    await expectHealthyPage(page, res);
    await expect(flashError(page)).toContainText(NOT_AUTHORIZED);
    // State-changing actions are POST only.
    res = await page.goto(`/agent-users/suspend/${ZERO_ID}`);
    expect(res?.status()).toBe(405);
  });
});

/** Add → list → edit → reset password → reset two-factor → suspend, on a throwaway user. */
test.describe('Agent Users lifecycle (master, E2E_ALLOW_USER_ADMIN)', () => {
  test.describe.configure({ mode: 'serial' });
  test.skip(!isMaster, 'needs E2E_USER_ROLE=master');
  test.skip(!env.allowUserAdmin, 'set E2E_ALLOW_USER_ADMIN=true to create and suspend a throwaway sub-user');

  const email = `e2e-${Date.now()}@example.invalid`;
  const name = `E2E User ${Date.now()}`;
  let id = '';

  const users = (page: Page) => new AgentUsersPage(page);

  test('add a throwaway user → listed as active', async ({ page }) => {
    await openHealthy(page, '/agent-users/add');
    const form = new AgentUserFormPage(page);
    await form.name.fill(name);
    await form.email.fill(email);
    await form.role.selectOption('normal');
    await form.status.selectOption('active');
    await Promise.all([page.waitForURL(/\/agent-users$/), form.save.click()]);
    await expectHealthyPage(page);
    test.info().annotations.push({ type: 'agent-user', description: email });
    await expect(flashSuccess(page)).toContainText('The agent user has been saved.');
    const row = users(page).row(email);
    await expect(row).toHaveCount(1);
    await expect(row.locator('td').nth(0)).toHaveText(name);
    await expect(row.locator('td').nth(2)).toHaveText('Normal');
    await expect(row.locator('td').nth(3)).toHaveText('Active');
    id = await users(page).idOf(email);
  });

  test('edit the name', async ({ page }) => {
    test.skip(!id, 'user was not created');
    await openHealthy(page, `/agent-users/edit/${id}`);
    const form = new AgentUserFormPage(page);
    await expect(page.getByText('Edit Account Details')).toBeVisible();
    await expect(form.name).toHaveValue(name);
    await form.name.fill(`${name} edited`);
    await Promise.all([page.waitForURL(/\/agent-users\/view/), form.update.click()]);
    await expectHealthyPage(page);
    await expect(flashSuccess(page)).toContainText('The agent user has been saved.');
    await users(page).goto();
    await expect(users(page).row(email).locator('td').nth(0)).toHaveText(`${name} edited`);
  });

  test('reset password after confirming', async ({ page }) => {
    test.skip(!id, 'user was not created');
    await users(page).goto();
    page.once('dialog', (d) => d.accept());
    await Promise.all([
      page.waitForEvent('load'),
      users(page).row(email).getByRole('link', { name: 'Reset Password' }).click(),
    ]);
    await expect(page).toHaveURL(/\/agent-users$/);
    await expectHealthyPage(page);
    await expect(flashSuccess(page)).toContainText('The password of this user has been reset.');
  });

  test('reset two-factor', async ({ page }) => {
    test.skip(!id, 'user was not created');
    await users(page).goto();
    // No "Reset Authenticator" button: this user never set one up. Post like the button would.
    await expect(users(page).row(email).getByRole('link', { name: 'Reset Authenticator' })).toHaveCount(0);
    const res = await postLikeForm(page, `/agent-users/reset-two-factor/${id}`);
    expect(res.status()).toBe(302);
    await users(page).goto();
    await expect(flashSuccess(page)).toContainText(`The authenticator of ${email} was reset.`);
  });

  test('suspend → inactive', async ({ page }) => {
    test.skip(!id, 'user was not created');
    await users(page).goto();
    // No Suspend button in templates/AgentUsers/index.php; POST /agent-users/suspend/<id> like a postLink.
    const res = await postLikeForm(page, `/agent-users/suspend/${id}`);
    expect(res.status()).toBe(302);
    await users(page).goto();
    await expect(flashSuccess(page)).toContainText('The agent user has been successfully suspended.');
    await expect(users(page).row(email).locator('td').nth(3)).toHaveText('Inactive');
  });
});

test.describe('Agent Users refused for non-masters', () => {
  const paths = ['/agent-users', '/agent-users/add', `/agent-users/edit/${ZERO_ID}`];

  async function expectRefused(page: Page, path: string) {
    const res = await page.goto(path);
    await expectHealthyPage(page, res);
    await expect(page, path).not.toHaveURL(/\/agent-users(\/add|\/edit)?($|\/|\?)/);
    await expect(flashError(page)).toContainText(NOT_AUTHORIZED);
  }

  test('main account (non-master) is refused', async ({ page }) => {
    test.skip(isMaster, 'main account is a master; see the E2E_NORMAL_* test');
    for (const p of paths) await test.step(p, () => expectRefused(page, p));
  });

  test('normal user (fresh login) is refused', async ({ browser }) => {
    test.skip(!(env.normalUser.email && env.normalUser.password), 'set E2E_NORMAL_EMAIL / E2E_NORMAL_PASSWORD');
    let session: Awaited<ReturnType<typeof loginInFreshContext>>;
    try {
      session = await loginInFreshContext(browser, {
        email: env.normalUser.email,
        password: env.normalUser.password,
        totpSecret: env.normalUser.totpSecret || undefined,
      });
    } catch (e) {
      test.skip(true, `normal user can't log in automatically: ${(e as Error).message}`);
      return;
    }
    try {
      for (const p of paths) await test.step(p, () => expectRefused(session.page, p));
      // Not offered in the menu either.
      await openHealthy(session.page, '/agent-users/view');
      await expect(session.page.locator('a.dropdown-item[href$="/agent-users"]')).toHaveCount(0);
      expect(session.errors).toEqual([]);
    } finally {
      await session.context.close();
    }
  });
});

