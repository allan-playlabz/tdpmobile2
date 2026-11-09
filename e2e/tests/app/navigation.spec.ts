import { test, expect } from '@playwright/test';
import { AppShell } from '../../pages/AppShell';
import { expectHealthyPage, trackPageErrors } from '../../utils/checks';
import { env } from '../../utils/env';

const role = env.user.role;

test.describe('Logged-in shell and navigation', () => {
  test('home lands on attractions with the navbar', async ({ page }) => {
    const errors = trackPageErrors(page);
    const res = await page.goto('/');
    await expectHealthyPage(page, res);

    const shell = new AppShell(page);
    await expect(shell.brand).toBeVisible();
    await expect(page.locator('input[name="search"]')).toBeVisible();
    if (role !== 'viewer') {
      await expect(shell.credits).toContainText(/Credits:\s*\S+/);
      await expect(shell.cartLink).toHaveCount(1);
    }
    expect(errors).toEqual([]);
  });

  test('user menu shows the links for this role', async ({ page }) => {
    await page.goto('/attractions');
    const shell = new AppShell(page);
    await shell.openUserMenu();

    const always = ['/agent-users/view', '/agent-users/two-factor', '/agent-users/logout'];
    const nonViewer = ['/orders', '/orders/my-tickets', '/reports', '/agent-users/contact'];
    const masterOnly = ['/agent-topups', '/agent-users'];

    for (const p of always) await expect(shell.menuItem(p), p).toBeVisible();
    for (const p of nonViewer) await expect(shell.menuItem(p), p).toHaveCount(role === 'viewer' ? 0 : 1);
    for (const p of masterOnly) await expect(shell.menuItem(p), p).toHaveCount(role === 'master' ? 1 : 0);
  });

  test('every menu page opens without errors', async ({ page }) => {
    const errors = trackPageErrors(page);
    const shell = new AppShell(page);
    await page.goto('/attractions');
    await shell.openUserMenu();
    const hrefs = (await shell.userMenu.locator('a.dropdown-item').evaluateAll((as) =>
      as.map((a) => new URL((a as HTMLAnchorElement).href).pathname),
    )).filter((p) => !p.endsWith('/logout'));
    expect(hrefs.length).toBeGreaterThan(1);

    for (const path of hrefs) {
      await test.step(path, async () => {
        const res = await page.goto(path);
        await expect(page, `${path} should not bounce to login`).not.toHaveURL(/\/agent-users\/login/);
        await expectHealthyPage(page, res);
      });
    }
    expect(errors).toEqual([]);
  });

  test('menu navigation works by clicking', async ({ page }) => {
    await page.goto('/attractions');
    const shell = new AppShell(page);
    await shell.goViaMenu('/agent-users/view');
    await expect(page.locator('.card-title')).toBeVisible();
    await expect(page.getByText(env.user.email, { exact: false })).toBeVisible();
  });

  test('language switch persists in the session', async ({ page }) => {
    await page.goto('/attractions?l=zh_CN');
    const switched = await page.locator('input[name="search"]').getAttribute('placeholder');
    test.skip(switched === 'Name of Attractions', 'zh_CN not enabled');
    await page.goto('/attractions');
    await expect(page.locator('input[name="search"]')).toHaveAttribute('placeholder', switched ?? '');
    await page.goto('/attractions?l=en_US');
    await expect(page.locator('input[name="search"]')).toHaveAttribute('placeholder', 'Name of Attractions');
  });
});
