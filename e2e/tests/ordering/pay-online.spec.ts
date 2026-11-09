import { test, expect, type Page } from '@playwright/test';
import { amountPattern, CartPage, ConfirmationPage } from '../../pages/BookingPages';
import { expectHealthyPage, trackPageErrors } from '../../utils/checks';
import { env } from '../../utils/env';
import {
  addTicket,
  appHost,
  checkoutToConfirmation,
  clearCart,
  GATEWAY_HOSTS,
  readCredits,
  sessionOrder,
  withUserPage,
} from '../../utils/ordering';

/**
 * Online payment methods (E2E_PAYMENT_METHODS, pay_by values). Each goes only as far as the
 * gateway's page: the fee + Grand Total on the confirmation, Pay Now lands on the provider's host
 * (2C2P *.2c2p.com, Stripe checkout.stripe.com, PayPal *.paypal.com), then the test comes back
 * and checks the order is still unpaid and the credits unchanged. Nothing is ever paid.
 */
test.describe.configure({ mode: 'serial' });

async function toConfirmation(page: Page): Promise<ConfirmationPage> {
  await clearCart(page);
  await addTicket(page, env.attractions.settlement);
  await checkoutToConfirmation(page, { attraction: env.attractions.settlement });
  const confirmation = new ConfirmationPage(page);
  await expect(confirmation.heading).toBeVisible();
  return confirmation;
}

/** Back to our app from the gateway: browser Back (up to 4 steps), else open the confirmation directly. */
async function comeBack(page: Page): Promise<void> {
  for (let i = 0; i < 4 && new URL(page.url()).hostname !== appHost(); i++) {
    await page.goBack({ waitUntil: 'load' }).catch(() => null);
  }
  if (new URL(page.url()).hostname !== appHost()) await page.goto('/orders/confirmation');
}

test.describe('Pay online (up to the gateway)', () => {
  test.skip(env.user.role === 'viewer', 'viewers cannot book');
  test.skip(!env.attractions.settlement, 'set E2E_ATTR_SETTLEMENT to a settlement attraction with bookable tickets');

  test.beforeAll(async ({ browser }) => withUserPage(browser, (page) => clearCart(page)));
  test.afterAll(async ({ browser }) => withUserPage(browser, (page) => clearCart(page)));

  test('Pay Now needs a payment method', async ({ page }) => {
    const errors = trackPageErrors(page);
    const confirmation = await toConfirmation(page);
    await expect(page.locator('input[name="pay_by"]:checked')).toHaveCount(0);
    // Pay Now starts disabled (order has no payment_method); if it is enabled, the page's JS asks for one.
    if (await confirmation.payNow.isEnabled()) {
      // alert() blocks click() until the dialog is handled, so dismiss it from a handler set up first.
      let message = '';
      page.once('dialog', async (d) => {
        message = d.message();
        await d.dismiss();
      });
      await confirmation.payNow.click();
      await expect.poll(() => message).toContain('Please choose a payment method!');
      await expect(page).toHaveURL(/\/orders\/confirmation/);
    } else {
      await expect(confirmation.payNow).toBeDisabled();
    }
    expect(errors).toEqual([]);
  });

  for (const method of env.paymentMethods) {
    test(`${method}: fee + Grand Total, Pay Now reaches the gateway, coming back leaves the order unpaid`, async ({ page }) => {
      test.setTimeout(240_000);
      const errors = trackPageErrors(page);
      const confirmation = await toConfirmation(page);
      test.skip((await confirmation.method(method).count()) === 0, `pay_by "${method}" is not offered (offered: ${(await confirmation.methodValues()).join(', ')})`);
      const provider = (await confirmation.providers())[method] ?? '';
      const subtotal = await confirmation.subtotalValue();
      const creditsBefore = await readCredits(page);

      await confirmation.choose(method);
      const fee = await confirmation.feeValue();
      const grandTotal = await confirmation.grandTotalValue();
      expect(fee).toBeGreaterThanOrEqual(0);
      expect(grandTotal, 'Grand Total = Subtotal + Convenience Fee').toBeCloseTo(subtotal + fee, 2);
      expect(errors, 'no JS errors on our pages').toEqual([]);

      await confirmation.payNow.click();
      await page.waitForURL((url) => url.hostname !== appHost(), { timeout: 90_000, waitUntil: 'domcontentloaded' });
      const gatewayHost = new URL(page.url()).hostname;
      const expected = GATEWAY_HOSTS[provider];
      if (expected) {
        expect(gatewayHost, `${provider} gateway host`).toMatch(expected);
      } else {
        test.info().annotations.push({ type: 'gateway', description: `unknown provider "${provider}" for ${method}: landed on ${gatewayHost}` });
      }
      // The gateway page should show what we're charging (soft: some gateways render it late or in an iframe).
      await expect.soft(page.locator('body'), `amount ${grandTotal.toFixed(2)} on the gateway page`).toContainText(amountPattern(grandTotal), {
        timeout: 20_000,
      });

      await comeBack(page);
      const response = await page.goto('/orders/confirmation');
      // confirmation() redirects to thank-you once the order is 'done'.
      await expect(page, 'order is not paid').toHaveURL(/\/orders\/confirmation/);
      await expectHealthyPage(page, response);
      const order = await sessionOrder(page);
      if (order) expect(order.status, `status of ${order.order_ref_id}`).not.toBe('done');
      expect(await readCredits(page), 'credits unchanged').toBeCloseTo(creditsBefore, 2);
      const cart = new CartPage(page);
      await cart.goto();
      await expect(cart.quantities.first(), 'cart still holds the ticket').toBeVisible();
    });
  }
});
