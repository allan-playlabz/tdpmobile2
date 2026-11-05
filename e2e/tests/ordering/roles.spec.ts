import { test, expect } from '@playwright/test';
import { AppShell } from '../../pages/AppShell';
import { AttractionPage } from '../../pages/BookingPages';
import { MyOrdersPage } from '../../pages/OrderPages';
import { expectHealthyPage, flashError, trackPageErrors } from '../../utils/checks';
import { env } from '../../utils/env';
import { clearCart, credsOf, latestCreditsOrder, loginInNewContext, withUserPage } from '../../utils/ordering';

/**
 * Who sees which orders (OrdersController::index(): a master sees every order of the agent's
 * users, everyone else only their own) and what a viewer may do (OrdersController::beforeFilter
 * blocks cart/checkout for viewers; Attractions view hides the buy buttons).
 */
test.describe.configure({ mode: 'serial' });

/** "showing X record(s) out of N total" → N. */
async function totalOrders(page: import('@playwright/test').Page): Promise<number> {
  const text = (await page.locator('.paginator p').textContent()) ?? '';
  return Number(text.match(/out of (\d+) total/)?.[1] ?? NaN);
}

test.describe('Roles', () => {
  test.beforeAll(async ({ browser }) => withUserPage(browser, (page) => clearCart(page)));

  test('a normal user sees only their own orders; the master sees the whole agent’s', async ({ page, browser }) => {
    test.skip(!env.normalUser.email || !env.normalUser.password, 'set E2E_NORMAL_EMAIL/PASSWORD to a normal (non-master) user of the same agent');
    test.skip(env.user.role !== 'master', 'the main account (E2E_USER_ROLE) must be the master');
    test.setTimeout(150_000);
    const errors = trackPageErrors(page);

    const normal = await loginInNewContext(browser, credsOf(env.normalUser));
    try {
      const normalOrders = new MyOrdersPage(normal.page);
      await expectHealthyPage(normal.page, await normalOrders.goto());
      await expect(normalOrders.heading).toBeVisible();
      const normalRefs = await normalOrders.refs();
      const normalTotal = await totalOrders(normal.page);

      const masterOrders = new MyOrdersPage(page);
      await expectHealthyPage(page, await masterOrders.goto());
      const masterTotal = await totalOrders(page);
      const masterOrder = latestCreditsOrder(); // placed by the main (master) account in this run
      test.skip(normalRefs.length === 0 && !masterOrder, 'neither account has an order to compare (run with E2E_ALLOW_PURCHASE=true)');

      expect(masterTotal, 'the master sees at least everything the normal user sees').toBeGreaterThanOrEqual(normalTotal);
      // Every order of the normal user is visible to the master…
      for (const ref of normalRefs.slice(0, 5)) {
        await masterOrders.goto({ order_ref_id: ref });
        await expect(masterOrders.row(ref), `master sees ${ref} of the normal user`).toHaveCount(1);
      }
      // …but the master's own order is not visible to the normal user.
      if (masterOrder) {
        await masterOrders.goto({ order_ref_id: masterOrder.ref });
        await expect(masterOrders.row(masterOrder.ref)).toHaveCount(1);
        await expectHealthyPage(normal.page, await normalOrders.goto({ order_ref_id: masterOrder.ref }));
        await expect(normalOrders.row(masterOrder.ref), `normal user must not see the master's ${masterOrder.ref}`).toHaveCount(0);
      }
    } finally {
      await normal.context.close();
    }
    expect(errors).toEqual([]);
  });

  test('a viewer cannot reach the cart or checkout', async ({ page }) => {
    test.skip(env.user.role !== 'viewer', 'only when the main account is a viewer (E2E_USER_ROLE=viewer)');
    const errors = trackPageErrors(page);
    for (const path of ['/orders/cart', '/orders/checkout']) {
      const response = await page.goto(path);
      await expectHealthyPage(page, response);
      await expect(page, `${path} sends a viewer home`).not.toHaveURL(new RegExp(path));
      await expect(flashError(page)).toContainText('You are not authorized to access that location.');
    }
    await expect(new AppShell(page).cartLink).toHaveCount(0);
    if (env.attractions.settlement) {
      const attraction = new AttractionPage(page);
      await expectHealthyPage(page, await attraction.goto(env.attractions.settlement));
      await expect(attraction.addToCart).toHaveCount(0);
      await expect(attraction.buyNow).toHaveCount(0);
      await expect(attraction.quantities).toHaveCount(0);
    }
    expect(errors).toEqual([]);
  });
});
