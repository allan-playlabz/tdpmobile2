import { test, expect } from '@playwright/test';
import { AppShell } from '../../pages/AppShell';
import { AttractionPage, CartPage } from '../../pages/BookingPages';
import { LoginPage } from '../../pages/LoginPage';
import { expectHealthyPage, flashError, trackPageErrors } from '../../utils/checks';
import { env, USER_STATE } from '../../utils/env';
import { addTicket, cartBadgeCount, clearCart, withUserPage, type AddedTicket } from '../../utils/ordering';

/**
 * Cart rules (OrdersController::cart(), ShoppingCartComponent, AttractionsController::view()).
 * The cart lives in the session as ShoppingCart[ticket_id][date] = qty.
 */
test.describe.configure({ mode: 'serial' });

test.describe('Cart rules', () => {
  test.skip(env.user.role === 'viewer', 'viewers have no cart');
  test.skip(!env.attractions.settlement, 'set E2E_ATTR_SETTLEMENT to a settlement attraction with bookable tickets');

  test.beforeAll(async ({ browser }) => withUserPage(browser, (page) => clearCart(page)));
  test.afterAll(async ({ browser }) => withUserPage(browser, (page) => clearCart(page)));

  test('two tickets / two dates add up in the badge and the total', async ({ page }) => {
    test.skip(!env.attractions.fdate && !env.attractions.second, 'needs E2E_ATTR_FDATE (two dates) or E2E_ATTR_SECOND (second attraction)');
    const errors = trackPageErrors(page);
    await clearCart(page);
    const added: AddedTicket[] = [await addTicket(page, env.attractions.settlement)];
    if (env.attractions.fdate) {
      added.push(await addTicket(page, env.attractions.fdate, { dateIndex: 0 }));
      added.push(await addTicket(page, env.attractions.fdate, { dateIndex: 1 }));
    } else {
      added.push(await addTicket(page, env.attractions.second));
    }

    const cart = new CartPage(page);
    await expectHealthyPage(page, await cart.goto());
    const lines = await cart.lines();
    expect(lines).toHaveLength(added.length);
    const expectedTotal = added.reduce((sum, a) => sum + a.price * a.qty, 0);
    expect(lines.reduce((sum, l) => sum + l.rowTotal, 0)).toBeCloseTo(expectedTotal, 2);
    expect(await cart.totalValue()).toBeCloseTo(expectedTotal, 2);
    // The badge is count(ShoppingCart): distinct tickets, not lines or quantities (layout/default.php).
    expect(await cartBadgeCount(page)).toBe(new Set(lines.map((l) => l.ticketId)).size);
    expect(errors).toEqual([]);
  });

  test('more than the max order quantity is refused and the cart is unchanged', async ({ page }) => {
    const errors = trackPageErrors(page);
    await clearCart(page);
    const cart = new CartPage(page);

    // Attraction page, with an empty cart: once a ticket of this attraction is in the cart, an attraction
    // that also has external (supplier_api_id) tickets hides the add form (not_allowed_to_add in
    // AttractionsController::view()). The + button stops at the max, so the value is set without a change
    // event (which would clamp it) to post an oversized quantity. The server checks Default.max_order_quantity.
    const attraction = new AttractionPage(page);
    await attraction.goto(env.attractions.settlement);
    const selected = await attraction.selectTicket(1);
    test.skip(!selected, 'no ticket can be selected');
    await attraction.form.locator(`input[name="quantity[${selected!.ticketId}]"]`).evaluate((el) => ((el as HTMLInputElement).value = '999'));
    const response = await attraction.submit('add');
    await expectHealthyPage(page, response);
    await expect(flashError(page)).toContainText(/Cannot exceed max allowed quantity \(\d+\)/);
    await expect(page).toHaveURL(new RegExp(`/attractions/view/${env.attractions.settlement}`));
    await cart.goto();
    expect(await cart.lines(), 'cart still empty after the refused add').toEqual([]);

    const added = await addTicket(page, env.attractions.settlement);
    await cart.goto();
    const before = await cart.lines();

    // Cart page: posting 999 for the line is refused (stock / max checks in OrdersController::cart()).
    await cart.quantity(added.ticketId, before[0].cartKey).evaluate((el) => ((el as HTMLInputElement).value = '999'));
    await cart.submit();
    await expectHealthyPage(page);
    await expect(page).toHaveURL(/\/orders\/cart/);
    await expect(flashError(page)).toContainText(/Sorry you have exceeded available|Cannot exceed max allowed quantity|exceeded max quantity/);
    await cart.goto();
    expect(await cart.lines(), 'cart unchanged after the refused update').toEqual(before);
    expect(errors).toEqual([]);
  });

  test('Buy Now goes straight to the cart', async ({ page }) => {
    const errors = trackPageErrors(page);
    await clearCart(page);
    const added = await addTicket(page, env.attractions.settlement, { via: 'buy' });
    await expect(page).toHaveURL(/\/orders\/cart/);
    const cart = new CartPage(page);
    await expect(cart.heading).toBeVisible();
    const lines = (await cart.lines()).filter((l) => l.ticketId === added.ticketId);
    expect(lines.map((l) => l.qty)).toEqual([added.qty]);
    expect(errors).toEqual([]);
  });

  test('quantity survives a reload; +/- on the cart is only saved by Checkout', async ({ page }) => {
    const errors = trackPageErrors(page);
    await clearCart(page);
    const added = await addTicket(page, env.attractions.settlement, { qty: 2 });
    const cart = new CartPage(page);
    await cart.goto();
    const [line] = await cart.lines();
    expect(line.qty).toBe(added.qty);
    await page.reload();
    expect((await cart.lines())[0].qty, 'quantity kept after reload').toBe(added.qty);

    const qty = cart.quantity(line.ticketId, line.cartKey);
    const max = await qty.getAttribute('max');
    test.skip(!!max && Number(max) <= added.qty, `ticket max is ${max}; cannot raise the quantity`);
    // The cart's +/- only change the page (no AJAX)…
    await cart.plus(line.ticketId, line.cartKey).click();
    await expect(qty).toHaveValue(String(added.qty + 1));
    expect(await cart.totalValue()).toBeCloseTo(line.price * (added.qty + 1), 2);
    await page.reload();
    await expect(cart.quantity(line.ticketId, line.cartKey)).toHaveValue(String(added.qty));
    // …and Checkout posts them (OrdersController::cart() → ShoppingCart::updateQuantity()).
    await cart.plus(line.ticketId, line.cartKey).click();
    await cart.submit();
    await expect(page).toHaveURL(/\/orders\/checkout/);
    await cart.goto();
    await expect(cart.quantity(line.ticketId, line.cartKey)).toHaveValue(String(added.qty + 1));
    expect(errors).toEqual([]);
  });

  test('removing one date keeps the other', async ({ page }) => {
    test.skip(!env.attractions.fdate, 'set E2E_ATTR_FDATE (fixed-date attraction with at least two dates)');
    const errors = trackPageErrors(page);
    await clearCart(page);
    const first = await addTicket(page, env.attractions.fdate, { dateIndex: 0 });
    const second = await addTicket(page, env.attractions.fdate, { dateIndex: 1 });
    const cart = new CartPage(page);
    await cart.goto();
    expect(await cart.lines()).toHaveLength(2);

    await cart.remove(first.ticketId, first.cartKey);
    await cart.goto();
    const left = await cart.lines();
    expect(left.map((l) => [l.ticketId, l.cartKey])).toEqual([[second.ticketId, second.cartKey]]);
    expect(await cart.totalValue()).toBeCloseTo(left[0].rowTotal, 2);
    expect(errors).toEqual([]);
  });

  // Last in this file: it logs the main account out and back in, then saves the new session to
  // .auth/user.json so the tests after it keep using a live session.
  test('cart after logout and login', async ({ page }) => {
    test.setTimeout(150_000);
    const errors = trackPageErrors(page);
    await clearCart(page);
    await addTicket(page, env.attractions.settlement);
    expect(await cartBadgeCount(page)).toBeGreaterThan(0);

    await page.goto('/agent-users/logout');
    await expect(page).toHaveURL(/\/agent-users\/login/);
    await new LoginPage(page).login({ email: env.user.email, password: env.user.password, totpSecret: env.user.totpSecret || undefined });
    await page.context().storageState({ path: USER_STATE });

    // Observed behaviour by design of the code: AgentUsersController::logout() destroys the session
    // and the cart only lives in the session (ShoppingCartComponent), so it is empty after logging in again.
    const cart = new CartPage(page);
    await expectHealthyPage(page, await cart.goto());
    await expect(cart.emptyMessage).toBeVisible();
    await expect(new AppShell(page).cartBadge).toHaveCount(0);
    expect(errors).toEqual([]);
  });
});
