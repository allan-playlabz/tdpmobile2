import { test, expect } from '@playwright/test';
import { CartPage, ConfirmationPage } from '../../pages/BookingPages';
import { MyOrdersPage, ThankYouPage } from '../../pages/OrderPages';
import { expectHealthyPage, flashError, trackPageErrors } from '../../utils/checks';
import { assertAllowedHost, env } from '../../utils/env';
import { recordOrder } from '../../utils/orders';
import {
  addTicket,
  checkoutToConfirmation,
  clearCart,
  credsOf,
  defaultCustomer,
  loginInNewContext,
  readCredits,
  withUserPage,
} from '../../utils/ordering';

/**
 * Paying with agent credits (OrdersController::confirmation() with pay_by=credits → API order/update
 * "pay" → thank-you). Places a REAL order and spends credits, so it only runs with
 * E2E_ALLOW_PURCHASE=true. The order is recorded (paidWith "credits") for post-order.spec.ts and
 * the pages project.
 */
test.describe.configure({ mode: 'serial' });

test.describe('Pay with credits', () => {
  test.skip(env.user.role === 'viewer', 'viewers cannot book');
  test.skip(!env.allowPurchase, 'set E2E_ALLOW_PURCHASE=true to place a real order paid with agent credits');
  test.skip(!env.attractions.settlement, 'set E2E_ATTR_SETTLEMENT to a settlement attraction with bookable tickets');

  test.beforeAll(async ({ browser }) => {
    assertAllowedHost();
    await withUserPage(browser, (page) => clearCart(page));
  });

  test('pay → thank-you shows the ref → credits drop by the total → order in My Orders', async ({ page }) => {
    test.setTimeout(300_000);
    const errors = trackPageErrors(page);
    await clearCart(page);
    await addTicket(page, env.attractions.settlement);
    const { tracking } = await checkoutToConfirmation(page, { attraction: env.attractions.settlement });

    const confirmation = new ConfirmationPage(page);
    test.skip((await confirmation.payByCredits.count()) === 0, 'credits are not enabled as a payment method for this agent');
    test.skip(await confirmation.payByCredits.isDisabled(), 'the main account has fewer credits than the order total');
    const creditsBefore = await readCredits(page);
    await confirmation.choose('credits');
    const total = await confirmation.grandTotalValue();
    expect(total, 'credits carry no convenience fee').toBeCloseTo(await confirmation.subtotalValue(), 2);

    // Read the ref from the thank-you response: while the order is processing, the page polls
    // /orders/get-status and may move on by itself.
    const final = await confirmation.payNowAndWait(180_000);
    if (!/\/orders\/thank-you/.test(final?.url() ?? page.url())) {
      const flash = (await flashError(page).allTextContents()).join(' ').trim();
      throw new Error(`paying with credits did not reach thank-you (${page.url()}): ${flash || 'no error message'}`);
    }
    const html = await final!.text().catch(() => page.content());
    expect(html).toContain('Thank you for your purchase!');
    expect(html).not.toContain('There is an error processing this order');
    const ref = ThankYouPage.refFromHtml(html);
    expect(ref, 'thank-you page shows the order ref').toBeTruthy();
    recordOrder({ ref: ref!, paidWith: 'credits', total: total.toFixed(2), attraction: env.attractions.settlement });
    await expectHealthyPage(page);

    // The navbar reloads the agent's credits on every request (AppController::beforeFilter).
    await expect
      .poll(
        async () => {
          await page.goto('/');
          return Math.round((creditsBefore - (await readCredits(page))) * 100) / 100;
        },
        { timeout: 60_000, message: 'credits dropped by the order total' },
      )
      .toBeCloseTo(total, 2);

    const myOrders = new MyOrdersPage(page);
    await expect
      .poll(
        async () => {
          await myOrders.goto({ order_ref_id: ref! });
          return myOrders.row(ref!).count();
        },
        { timeout: 60_000, message: `order ${ref} listed in My Orders` },
      )
      .toBe(1);
    await expectHealthyPage(page);
    await expect(myOrders.status(ref!)).toHaveText(/Completed|Waiting/);
    expect(Number((await myOrders.total(ref!).textContent())?.replace(/[^\d.]/g, ''))).toBeCloseTo(total, 2);
    await myOrders.goto({ tracking_no: tracking });
    await expect(myOrders.row(ref!)).toHaveCount(1);

    const cart = new CartPage(page);
    await cart.goto();
    await expect(cart.emptyMessage, 'the cart is emptied after paying').toBeVisible();
    expect(errors).toEqual([]);
  });
});

test.describe('Pay with credits: not enough credits', () => {
  test.skip(!env.lowCredit.email || !env.lowCredit.password, 'set E2E_LOW_CREDIT_EMAIL/PASSWORD to a user whose agent has fewer credits than one ticket');
  test.skip(!env.attractions.settlement, 'set E2E_ATTR_SETTLEMENT to a settlement attraction with bookable tickets');

  test('low-credit account cannot pay with credits and no order is placed', async ({ browser }) => {
    test.setTimeout(180_000);
    const { context, page } = await loginInNewContext(browser, credsOf(env.lowCredit));
    try {
      const errors = trackPageErrors(page);
      await clearCart(page, { viewer: false });
      await addTicket(page, env.attractions.settlement);
      const { tracking } = await checkoutToConfirmation(page, {
        customer: defaultCustomer(env.lowCredit.email),
        attraction: env.attractions.settlement,
      });
      const confirmation = new ConfirmationPage(page);
      test.skip((await confirmation.payByCredits.count()) === 0, 'credits are not a payment method for this agent');
      const creditsBefore = await readCredits(page);
      const total = await confirmation.grandTotalValue();
      test.skip(creditsBefore >= total, `the low-credit account has ${creditsBefore}, enough for ${total}`);

      // confirmation.php disables the credits option when credits < order total.
      await expect(confirmation.payByCredits).toBeDisabled();
      await expect(confirmation.payByCredits).not.toBeChecked();
      if (await confirmation.payNow.isEnabled()) {
        // alert() blocks click() until the dialog is handled, so dismiss it from a handler set up first.
        let message = '';
        page.once('dialog', async (d) => {
          message = d.message();
          await d.dismiss();
        });
        await confirmation.payNow.click();
        await expect.poll(() => message).toContain('Please choose a payment method!');
      } else {
        await expect(confirmation.payNow).toBeDisabled();
      }
      await expect(page).toHaveURL(/\/orders\/confirmation/);

      await page.goto('/');
      expect(await readCredits(page)).toBeCloseTo(creditsBefore, 2);
      // My Orders lists paid/processing orders only; the checkout's unpaid order must not show up.
      const myOrders = new MyOrdersPage(page);
      await expectHealthyPage(page, await myOrders.goto({ tracking_no: tracking }));
      await expect(myOrders.rows).toHaveCount(0);
      expect(errors).toEqual([]);
      await clearCart(page, { viewer: false });
    } finally {
      await context.close();
    }
  });
});
