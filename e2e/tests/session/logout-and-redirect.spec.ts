import { test, expect } from '@playwright/test';
import { AppShell } from '../../pages/AppShell';
import { LoginPage } from '../../pages/LoginPage';
import { env, hasUser } from '../../utils/env';

/** Runs after "app": logging out / in again replaces the shared session. */
test.describe.configure({ mode: 'serial' });

test.describe('Session end and post-login redirects', () => {
  test.skip(!hasUser(), 'needs the main account');

  const creds = () => ({
    email: env.user.email,
    password: env.user.password,
    totpSecret: env.user.totpSecret || undefined,
  });

  test('logout from the menu ends the session', async ({ page }) => {
    await page.goto('/attractions');
    const shell = new AppShell(page);
    await shell.openUserMenu();
    await shell.menuItem('/agent-users/logout').click();
    await expect(page).toHaveURL(/\/agent-users\/login/);

    await page.goto('/orders');
    await expect(page).toHaveURL(/\/agent-users\/login\?redirect=%2Forders/);
  });

  test('login returns to the page that asked for it', async ({ page }) => {
    await page.context().clearCookies();
    await page.goto('/orders/my-tickets');
    await expect(page).toHaveURL(/redirect=/);
    await new LoginPage(page).login(creds(), '/orders/my-tickets');
    await expect(page).toHaveURL(/\/orders\/my-tickets/);
  });

  test('login ignores an off-site redirect target', async ({ page }) => {
    await page.context().clearCookies();
    for (const evil of ['https://evil.example/phish', '//evil.example/phish', '/\\evil.example']) {
      await test.step(evil, async () => {
        await page.context().clearCookies();
        await new LoginPage(page).login(creds(), evil);
        expect(new URL(page.url()).host).toBe(new URL(env.baseUrl).host);
      });
    }
  });

  test('a second login of the same user ends the first session', async ({ browser }) => {
    const opts = { baseURL: env.baseUrl };
    const first = await browser.newContext(opts);
    const second = await browser.newContext(opts);
    try {
      const p1 = await first.newPage();
      await new LoginPage(p1).login(creds());
      const p2 = await second.newPage();
      await new LoginPage(p2).login(creds());

      await p2.goto('/orders');
      await expect(p2).toHaveURL(/\/orders$/);
      await p1.goto('/orders');
      await expect(p1).toHaveURL(/\/agent-users\/login/);
    } finally {
      await first.close();
      await second.close();
    }
  });
});
