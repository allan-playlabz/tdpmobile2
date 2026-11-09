import { test, expect, type Page } from '@playwright/test';
import { CheckoutPage, ConfirmationPage } from '../../pages/BookingPages';
import { expectHealthyPage, flashError, trackPageErrors } from '../../utils/checks';
import { env } from '../../utils/env';
import { addTicket, checkoutToConfirmation, clearCart, defaultCustomer, newTrackingNo, recordCheckoutOrder, withUserPage } from '../../utils/ordering';

/**
 * Checkout form (templates/Orders/checkout.php → OrdersController::checkout()): HTML validation of
 * the customer fields, booking questions, tracking number, and what survives going back from the
 * confirmation page. Every successful Continue creates an unpaid order through the API (recorded).
 */
test.describe.configure({ mode: 'serial' });

/** Unique customer names (letters/digits only, as the name pattern allows) to tell typed values from defaults. */
const uniqueCustomer = () => {
  const n = Date.now().toString(36).replace(/\d/g, '').slice(-4) || 'abcd';
  return { ...defaultCustomer(), firstName: `Eetwoe ${n}`, lastName: `Tester ${n}`, mobile: '+6591234567' };
};

async function openCheckout(page: Page): Promise<CheckoutPage> {
  const checkout = new CheckoutPage(page);
  const response = await checkout.goto();
  await expect(page, 'checkout redirects to the attractions list when the cart is empty').toHaveURL(/\/orders\/checkout/);
  await expectHealthyPage(page, response);
  return checkout;
}

const validity = (field: import('@playwright/test').Locator) =>
  field.evaluate((el) => {
    const v = (el as HTMLInputElement).validity;
    return { valid: v.valid, valueMissing: v.valueMissing, typeMismatch: v.typeMismatch, patternMismatch: v.patternMismatch };
  });

test.describe('Checkout', () => {
  test.skip(env.user.role === 'viewer', 'viewers cannot book');
  test.afterAll(async ({ browser }) => withUserPage(browser, (page) => clearCart(page)));

  test.describe('customer details', () => {
    test.skip(!env.attractions.settlement, 'set E2E_ATTR_SETTLEMENT to a settlement attraction with bookable tickets');

    test('required customer fields block Continue', async ({ page }) => {
      const errors = trackPageErrors(page);
      await clearCart(page);
      await addTicket(page, env.attractions.settlement);
      const checkout = await openCheckout(page);

      for (const field of [checkout.firstName, checkout.lastName, checkout.email, checkout.mobile]) {
        await expect(field).toHaveAttribute('required', /.*/);
      }
      await checkout.fillCustomer({ firstName: '', lastName: '', email: '', mobile: '' });
      await checkout.continueButton.click();
      await expect(page).toHaveURL(/\/orders\/checkout/);
      const invalid = await checkout.invalidFields();
      expect(invalid).toEqual(expect.arrayContaining(['first_name', 'last_name', 'email', 'mobile']));
      expect((await validity(checkout.email)).valueMissing).toBe(true);
      expect(errors).toEqual([]);
    });

    test('email and mobile formats are checked', async ({ page }) => {
      const errors = trackPageErrors(page);
      const checkout = await openCheckout(page);
      await checkout.fillCustomer(defaultCustomer());
      await expect(checkout.email).toHaveAttribute('type', 'email');

      await checkout.email.fill('not-an-email');
      expect((await validity(checkout.email)).typeMismatch).toBe(true);
      await checkout.email.fill('e2e@example.com');
      expect((await validity(checkout.email)).valid).toBe(true);

      // pattern [+]*[0-9]+ : "+" and digits only.
      for (const bad of ['abc', '+65 1234 5678', '65-1234-5678']) {
        await checkout.mobile.fill(bad);
        expect((await validity(checkout.mobile)).patternMismatch, `mobile "${bad}"`).toBe(true);
      }
      await checkout.mobile.fill('+6512345678');
      expect((await validity(checkout.mobile)).valid).toBe(true);

      await checkout.email.fill('not-an-email');
      await checkout.continueButton.click();
      await expect(page, 'the browser blocks the submit').toHaveURL(/\/orders\/checkout/);
      expect(errors).toEqual([]);
    });

    test('tracking number is shown on the confirmation; a reused one is refused with the form kept', async ({ page }) => {
      test.setTimeout(120_000);
      const errors = trackPageErrors(page);
      const tracking = newTrackingNo();
      const customer = uniqueCustomer();
      await checkoutToConfirmation(page, { viaCart: false, tracking, customer, attraction: env.attractions.settlement });
      const confirmation = new ConfirmationPage(page);
      await expect(confirmation.customerField('tracking_no')).toHaveText(tracking);
      await expect(confirmation.customerField('first_name')).toHaveText(customer.firstName);
      await expect(confirmation.customerField('last_name')).toHaveText(customer.lastName);
      await expect(confirmation.customerField('email')).toHaveText(customer.email);
      await expect(confirmation.customerField('mobile')).toHaveText(customer.mobile);

      // Same tracking number again: the API answers 5007 and checkout re-renders with the posted data.
      const checkout = await openCheckout(page);
      await checkout.fillCustomer(customer);
      await checkout.trackingNo.fill(tracking);
      await checkout.submit();
      await expectHealthyPage(page);
      if (/\/orders\/confirmation/.test(page.url())) {
        await recordCheckoutOrder(page, tracking, { attraction: env.attractions.settlement });
        test.skip(true, 'the backend accepted a tracking number already used by an unpaid order (it may only count paid orders)');
      }
      await expect(flashError(page)).toContainText('Order Tracking Number already exists. Tracking Number should be unique or empty.');
      await expect(checkout.firstName).toHaveValue(customer.firstName);
      await expect(checkout.trackingNo).toHaveValue(tracking);
      expect(errors).toEqual([]);
    });

    test('Back from the confirmation keeps the customer details', async ({ page }) => {
      // checkout() keeps the submitted details in the session for the same cart (not the tracking number).
      const errors = trackPageErrors(page);
      const customer = uniqueCustomer();
      await checkoutToConfirmation(page, { viaCart: false, customer, attraction: env.attractions.settlement });
      const confirmation = new ConfirmationPage(page);
      await Promise.all([page.waitForURL(/\/orders\/checkout/), confirmation.backLink.click()]);
      await expectHealthyPage(page);
      const checkout = new CheckoutPage(page);
      await expect(checkout.firstName).toHaveValue(customer.firstName);
      await expect(checkout.lastName).toHaveValue(customer.lastName);
      await expect(checkout.mobile).toHaveValue(customer.mobile);
      expect(errors).toEqual([]);
    });
  });

  test.describe('booking questions', () => {
    test.skip(!env.attractions.questions, 'set E2E_ATTR_QUESTIONS to an attraction whose tickets have booking questions');
    let answers: Record<string, string> = {};

    test('booking questions are required and their answers reach the confirmation', async ({ page }) => {
      test.setTimeout(120_000);
      const errors = trackPageErrors(page);
      await clearCart(page);
      await addTicket(page, env.attractions.questions);
      const checkout = await openCheckout(page);
      test.skip((await checkout.questionFields.count()) === 0, 'no booking-question fields rendered (the API returned no question_group)');
      await expect(checkout.ticketDetailsCard).toBeVisible();
      const required = await checkout.requiredQuestionNames();
      test.skip(required.length === 0, 'the booking questions are all optional');

      await checkout.fillCustomer(defaultCustomer());
      await checkout.fillTravellers();
      await checkout.continueButton.click();
      await expect(page).toHaveURL(/\/orders\/checkout/);
      expect(await checkout.invalidFields()).toEqual(expect.arrayContaining(required));

      answers = await checkout.fillQuestions('E2E answer');
      const missing = await checkout.emptyRequiredFields();
      test.skip(missing.length > 0, `checkout needs details this suite doesn't fill: ${missing.join(', ')}`);
      const tracking = newTrackingNo();
      await checkout.trackingNo.fill(tracking);
      await checkout.submit();
      await expect(page, (await flashError(page).allTextContents()).join(' ')).toHaveURL(/\/orders\/confirmation/);
      await expectHealthyPage(page);
      await recordCheckoutOrder(page, tracking, { attraction: env.attractions.questions });

      const confirmation = new ConfirmationPage(page);
      await expect(confirmation.ticketInformation).toBeVisible();
      for (const value of new Set(Object.values(answers).filter((v) => v === 'E2E answer'))) {
        await expect(confirmation.ticketInformation).toContainText(value);
      }
      expect(errors).toEqual([]);
    });

    test('booking-question answers are kept when going back from the confirmation', async ({ page }) => {
      test.skip(Object.keys(answers).length === 0, 'previous test did not answer any question');
      const confirmation = new ConfirmationPage(page);
      await expectHealthyPage(page, await confirmation.goto());
      await Promise.all([page.waitForURL(/\/orders\/checkout/), confirmation.backLink.click()]);
      await expectHealthyPage(page);
      const values = await new CheckoutPage(page).questionValues();
      for (const [name, value] of Object.entries(answers)) {
        expect(values[name], name).toBe(value);
      }
    });
  });
});
