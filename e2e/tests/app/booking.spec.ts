import { test, expect } from '@playwright/test';
import { AttractionPage, CartPage, CheckoutPage, ConfirmationPage } from '../../pages/BookingPages';
import { AppShell } from '../../pages/AppShell';
import { clickWithDialog, expectHealthyPage, flashError, trackPageErrors } from '../../utils/checks';
import { env, USER_STATE } from '../../utils/env';

/**
 * Cart → checkout → confirmation for E2E_ATTRACTION_ID.
 * Stops at "Pay Now" unless E2E_ALLOW_PURCHASE=true (then it pays with agent credits).
 * The cart lives in the session, so these run in order and start from an empty cart.
 */
test.describe.configure({ mode: 'serial' });

test.describe('Booking flow', () => {
  test.skip(env.user.role === 'viewer', 'viewers cannot book');
  test.skip(!env.attractionId, 'set E2E_ATTRACTION_ID to an attraction with bookable tickets');

  async function addOneTicket(page: import('@playwright/test').Page): Promise<void> {
    const attraction = new AttractionPage(page);
    await attraction.goto(env.attractionId);
    await attraction.pickDate(env.ticketDate || undefined);
    await attraction.pickTimeIfNeeded();
    await attraction.addFirstAvailableTicket();
    await expect(attraction.addToCart).toBeVisible();
    await Promise.all([page.waitForLoadState('load'), attraction.addToCart.click()]);
    await expect(flashError(page)).toHaveCount(0);
  }

  test('empty cart shows a notice', async ({ page }) => {
    const cart = new CartPage(page);
    await cart.clear();
    await expectHealthyPage(page);
    await expect(cart.checkout).toHaveCount(0);
  });

  test('add a ticket to the cart', async ({ page }) => {
    const errors = trackPageErrors(page);
    await addOneTicket(page);

    const shell = new AppShell(page);
    await page.goto('/orders/cart');
    await expect(shell.cartBadge).toHaveText('1');

    const cart = new CartPage(page);
    await expect(cart.heading).toBeVisible();
    await expect(cart.quantities).toHaveCount(1);
    expect(Number(await cart.quantities.first().inputValue())).toBeGreaterThan(0);
    await expect(cart.total).toContainText(/Total/);
    expect(errors).toEqual([]);
  });

  test('cart quantity buttons update the total', async ({ page }) => {
    const cart = new CartPage(page);
    await cart.goto();
    const qty = cart.quantities.first();
    const max = await qty.getAttribute('max');
    const before = Number(await qty.inputValue());
    test.skip(!!max && Number(max) <= before, 'ticket already at max quantity');

    const totalBefore = await cart.total.textContent();
    await page.locator('.btn-add').first().click();
    await expect(qty).toHaveValue(String(before + 1));
    await expect(cart.total).not.toHaveText(totalBefore ?? '');

    await page.locator('.btn-subtract').first().click();
    await expect(qty).toHaveValue(String(before));
  });

  test('remove a ticket empties the cart', async ({ page }) => {
    const cart = new CartPage(page);
    await cart.goto();
    page.once('dialog', (d) => {
      expect(d.message()).toMatch(/^Remove .* from cart\?$/);
      d.accept();
    });
    await Promise.all([page.waitForLoadState('load'), cart.removeButtons.first().click()]);
    await cart.goto();
    await expect(cart.emptyMessage).toBeVisible();
  });

  test('dismissing the remove prompt keeps the ticket', async ({ page }) => {
    await addOneTicket(page);
    const cart = new CartPage(page);
    await cart.goto();
    page.once('dialog', (d) => d.dismiss());
    await cart.removeButtons.first().click();
    await cart.goto();
    await expect(cart.quantities).toHaveCount(1);
  });

  test('checkout validates customer details', async ({ page }) => {
    const cart = new CartPage(page);
    await cart.goto();
    await Promise.all([page.waitForURL(/\/orders\/checkout/), cart.checkout.click()]);
    await expectHealthyPage(page);

    const checkout = new CheckoutPage(page);
    await checkout.firstName.fill('');
    await checkout.continueButton.click();
    await expect(page).toHaveURL(/\/orders\/checkout/);
    expect(await checkout.firstName.evaluate((el) => (el as HTMLInputElement).validity.valueMissing)).toBe(true);

    // Pattern: English letters, digits, - _ and spaces only.
    await checkout.firstName.fill('名字');
    expect(await checkout.firstName.evaluate((el) => (el as HTMLInputElement).validity.patternMismatch)).toBe(true);
    await checkout.mobile.fill('abc');
    expect(await checkout.mobile.evaluate((el) => (el as HTMLInputElement).validity.patternMismatch)).toBe(true);
  });

  test('checkout → confirmation', async ({ page }) => {
    await page.goto('/orders/checkout');
    const checkout = new CheckoutPage(page);
    await checkout.fillCustomer({
      firstName: 'E2E',
      lastName: 'Playwright',
      email: env.user.email,
      mobile: '+6512345678',
    });
    await checkout.trackingNo.fill(`E2E-${Date.now()}`);

    const missing = await checkout.emptyRequiredFields();
    test.skip(missing.length > 0, `attraction needs traveller/transfer info this test doesn't fill: ${missing.join(', ')}`);

    await Promise.all([page.waitForURL(/\/orders\/confirmation/), checkout.continueButton.click()]);
    await expectHealthyPage(page);

    const confirmation = new ConfirmationPage(page);
    await expect(confirmation.heading).toBeVisible();
    await expect(page.locator('input[name="first_name"]')).toHaveValue('E2E');
    await expect(page.locator('input[name="last_name"]')).toHaveValue('Playwright');
    await expect(confirmation.grandTotal).toBeVisible();
    await expect(confirmation.payNow).toBeVisible();
  });

  test('Pay Now without a payment method asks for one', async ({ page }) => {
    await page.goto('/orders/confirmation');
    const confirmation = new ConfirmationPage(page);
    test.skip(await confirmation.payNow.isDisabled(), 'Pay Now is disabled until a method is chosen');
    test.skip((await page.locator('input[name="pay_by"]:checked').count()) > 0, 'a method is preselected');
    expect(await clickWithDialog(page, confirmation.payNow)).toContain('Please choose a payment method!');
    await expect(page).toHaveURL(/\/orders\/confirmation/);
  });

  test('pay with credits and reach thank-you', async ({ page }) => {
    test.skip(!env.allowPurchase, 'set E2E_ALLOW_PURCHASE=true to place a real order with credits');
    test.setTimeout(180_000);
    await page.goto('/orders/confirmation');
    const confirmation = new ConfirmationPage(page);
    test.skip((await confirmation.payByCredits.count()) === 0, 'credits not enabled for this agent');

    await confirmation.payByCredits.check();
    await expect(confirmation.payNow).toBeEnabled();
    await confirmation.payNow.click();
    await page.waitForURL(/\/orders\/thank-you/, { timeout: 150_000 });
    await expectHealthyPage(page);
    await expect(page.getByRole('heading', { name: 'Thank you for your purchase!' })).toBeVisible();

    await page.goto('/orders/cart');
    await expect(new CartPage(page).emptyMessage).toBeVisible();
  });

  test.afterAll(async ({ browser }) => {
    // Leave the shared session with an empty cart.
    const context = await browser.newContext({ baseURL: env.baseUrl, storageState: USER_STATE });
    const page = await context.newPage();
    await new CartPage(page).clear().catch(() => undefined);
    await context.close();
  });
});
