import { test, expect, isViewer } from './fixtures';
import { expectHealthyPage } from '../../utils/checks';
import { env } from '../../utils/env';
import { AppShell } from '../../pages/AppShell';
import { AccountPage, HeaderBar, loginInFreshContext, openHealthy } from '../../pages/MenuPages';

/** Navbar of templates/layout/default.php. Logout is tested by the session project (it ends the shared session). */
test.describe('Header', () => {
  test('logo goes home', async ({ page }) => {
    await openHealthy(page, '/agent-users/view');
    const shell = new AppShell(page);
    await expect(shell.brand.locator('img')).toBeVisible();
    await Promise.all([page.waitForURL((u) => u.pathname === '/' || u.pathname === '/attractions'), shell.brand.click()]);
    await expectHealthyPage(page);
    await expect(page.locator('input[name="search"]')).toBeVisible();
  });

  test('cart badge equals the number of tickets in the cart', async ({ page }) => {
    test.skip(isViewer, 'viewers have no cart');
    // On the cart page itself the layout renders after the controller dropped unavailable tickets.
    await openHealthy(page, '/orders/cart');
    const shell = new AppShell(page);
    const cards = await page.locator('.ticket-card').count();
    if (cards === 0) {
      await expect(page.getByText('No items on cart.')).toBeVisible();
      if (await shell.cartBadge.count()) await expect(shell.cartBadge).toHaveText('0');
    } else {
      await expect(shell.cartBadge).toHaveText(String(cards));
    }
    await Promise.all([page.waitForURL(/\/orders\/cart$/), shell.cartLink.click()]);
    await expectHealthyPage(page);
  });

  test('header credits equal My Account credits', async ({ page }) => {
    test.skip(isViewer, 'viewers see no credits');
    const account = new AccountPage(page);
    await account.goto();
    const header = new HeaderBar(page);
    const headerCredit = await header.creditText();
    expect(headerCredit).toMatch(/\d/);
    expect(await account.creditText()).toBe(headerCredit);
  });

  test('viewers see no credits, bell or cart', async ({ page }) => {
    test.skip(!isViewer, 'only for viewer accounts');
    await openHealthy(page, '/agent-users/view');
    const header = new HeaderBar(page);
    await expect(header.credits).toHaveCount(0);
    await expect(header.bell).toHaveCount(0);
    await expect(new AppShell(page).cartLink).toHaveCount(0);
  });

  test('low-credit account shows the LOW badge', async ({ browser }) => {
    test.skip(!(env.lowCredit.email && env.lowCredit.password), 'set E2E_LOW_CREDIT_EMAIL / E2E_LOW_CREDIT_PASSWORD');
    let session: Awaited<ReturnType<typeof loginInFreshContext>>;
    try {
      session = await loginInFreshContext(browser, {
        email: env.lowCredit.email,
        password: env.lowCredit.password,
        totpSecret: env.lowCredit.totpSecret || undefined,
      });
    } catch (e) {
      test.skip(true, `low-credit user can't log in automatically: ${(e as Error).message}`);
      return;
    }
    try {
      await openHealthy(session.page, '/agent-users/view');
      const header = new HeaderBar(session.page);
      await expect(header.lowCredit).toBeVisible();
      await expect(header.lowCredit).toHaveText('LOW');
      await expect(header.lowCredit).toHaveClass(/text-bg-danger/);
      expect(session.errors).toEqual([]);
    } finally {
      await session.context.close();
    }
  });

  test('announcement popover opens and closes', async ({ page }) => {
    test.skip(isViewer, 'viewers have no announcements bell');
    await openHealthy(page, '/agent-users/view');
    const header = new HeaderBar(page);
    await header.expandIfCollapsed();
    // Don't mark announcements read on the server (read-only run): answer the AJAX call here.
    await page.route('**/agent-users/read/**', (route) =>
      route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ status: 200, data: [] }) }),
    );
    await header.bell.click();
    await expect(header.popover).toBeVisible();
    await expect(header.popover.locator('.popover-header')).toContainText('Announcement');
    await expect(header.popover.locator('#announcements_container')).toBeAttached();
    await header.popoverClose.click();
    await expect(header.popover).toBeHidden();
  });

  test('opening announcements clears the unread dot', async ({ page }) => {
    test.skip(isViewer, 'viewers have no announcements bell');
    await openHealthy(page, '/agent-users/view');
    const header = new HeaderBar(page);
    await header.expandIfCollapsed();
    test.skip((await header.unreadDot.count()) === 0, 'no unread announcement');
    await expect(header.popover).toHaveCount(0);
    // The real call would set last_read_time; answer it here so the run stays read-only.
    const read = page.waitForRequest((r) => r.url().includes('/agent-users/read/') && r.method() === 'POST');
    await page.route('**/agent-users/read/**', (route) =>
      route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ status: 200, data: [] }) }),
    );
    await header.bell.click();
    const req = await read;
    expect(req.headers()['x-csrf-token'], 'AJAX call carries the CSRF token').toBeTruthy();
    expect(req.headers()['x-requested-with']).toBe('XMLHttpRequest');
    await expect(header.popover.locator('#announcements_container > .row').first()).toBeVisible();
    await expect(header.unreadDot).toBeHidden();
  });

  test('mark-as-read endpoint ignores non-AJAX requests', async ({ page }) => {
    await page.goto('/agent-users/view');
    const href = await page.evaluate(() =>
      Array.from(document.querySelectorAll('script:not([src])'))
        .map((s) => s.textContent ?? '')
        .join('\n')
        .match(/\/agent-users\/read\/[^'"\s]+/)?.[0] ?? '');
    test.skip(!href, 'no read URL on the page (viewer?)');
    const res = await page.request.get(href);
    expect(res.status()).toBe(404);
  });

  test('language switch keeps the current path and query', async ({ page }) => {
    const path = isViewer ? '/agent-users/view' : '/orders';
    const query: Record<string, string> = isViewer ? { e2e: '1' } : { order_ref_id: 'NOPE-LANG', status: 'done' };
    await openHealthy(page, `${path}?${new URLSearchParams(query)}`);
    const header = new HeaderBar(page);
    test.skip((await header.languageMenu.count()) === 0, 'translation feature is off (no language menu)');

    const items = header.languageMenu.locator('a.dropdown-item');
    const hrefs = await items.evaluateAll((as) => as.map((a) => (a as HTMLAnchorElement).href));
    expect(hrefs.length).toBeGreaterThan(1);
    for (const href of hrefs) {
      const u = new URL(href);
      expect(u.pathname, href).toBe(path);
      for (const [k, v] of Object.entries(query)) expect(u.searchParams.get(k), `${k} in ${href}`).toBe(v);
      expect(u.searchParams.get('l'), href).toBeTruthy();
    }
    const other = hrefs.find((h) => new URL(h).searchParams.get('l') !== 'en_US');
    test.skip(!other, 'only English is offered');

    try {
      await header.expandIfCollapsed();
      await header.languageToggle.click();
      const lang = new URL(other!).searchParams.get('l')!;
      await Promise.all([
        page.waitForURL((u) => u.searchParams.get('l') === lang),
        header.languageMenu.locator(`a.dropdown-item[href$="l=${lang}"]`).click(),
      ]);
      await expectHealthyPage(page);
      const now = new URL(page.url());
      expect(now.pathname).toBe(path);
      for (const [k, v] of Object.entries(query)) expect(now.searchParams.get(k)).toBe(v);
      // The toggle shows the chosen language; the session keeps it without ?l.
      const chosen = ((await header.languageToggle.textContent()) ?? '').trim();
      expect(chosen).not.toBe('English');
      await openHealthy(page, path);
      await expect(header.languageToggle).toHaveText(chosen);
    } finally {
      await page.goto(`${path}?l=en_US`);
    }
    await expect(header.languageToggle).toHaveText('English');
  });
});
