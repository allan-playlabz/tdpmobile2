import { test, expect, type Page } from '@playwright/test';
import { AttractionPage, CartPage, ConfirmationPage, isoDate, readSidebar } from '../../pages/BookingPages';
import { expectHealthyPage, trackPageErrors } from '../../utils/checks';
import { env } from '../../utils/env';
import { addTicket, bookTwoWay, checkoutToConfirmation, clearCart, withUserPage } from '../../utils/ordering';

/**
 * One block per pricing type (src/Lib/PricingType.php): add a ticket, check the cart line
 * (date / time / return leg and unit price), go through checkout and check the confirmation
 * total equals the cart total. Checkout creates an unpaid order through the API; nothing is paid.
 *
 * Each block is serial on its own so one pricing type failing doesn't skip the others.
 */

/** Cart lines of one ticket, after opening the cart. */
async function cartLines(page: Page, ticketId: string) {
  const cart = new CartPage(page);
  await expectHealthyPage(page, await cart.goto());
  await expect(cart.heading).toBeVisible();
  return { cart, lines: (await cart.lines()).filter((l) => l.ticketId === ticketId) };
}

/** Cart → checkout → confirmation; the confirmation Grand Total must equal the cart Total. */
async function expectConfirmationTotalEqualsCart(page: Page, attraction: string): Promise<ConfirmationPage> {
  const cart = new CartPage(page);
  await cart.goto();
  const cartTotal = await cart.totalValue();
  await checkoutToConfirmation(page, { attraction });
  const confirmation = new ConfirmationPage(page);
  await expect(confirmation.heading).toBeVisible();
  expect(await confirmation.grandTotalValue(), 'confirmation Grand Total = cart Total').toBeCloseTo(cartTotal, 2);
  return confirmation;
}

test.describe('Pricing types', () => {
  test.skip(env.user.role === 'viewer', 'viewers cannot book');

  test.beforeAll(async ({ browser }) => withUserPage(browser, (page) => clearCart(page)));
  test.afterAll(async ({ browser }) => withUserPage(browser, (page) => clearCart(page)));

  test.describe('settlement', () => {
    test.describe.configure({ mode: 'serial' });
    test.skip(!env.attractions.settlement, 'set E2E_ATTR_SETTLEMENT to an open-dated (settlement) attraction');

    test('add → cart line without a date, with the unit price → confirmation total = cart total', async ({ page }) => {
      const errors = trackPageErrors(page);
      await clearCart(page);
      const added = await addTicket(page, env.attractions.settlement, { qty: 2 });
      expect(added.type, 'E2E_ATTR_SETTLEMENT must be a settlement attraction').toBe('settlement');

      const { lines } = await cartLines(page, added.ticketId);
      expect(lines).toHaveLength(1);
      // Settlement lines show no date; the session key is today's date (AttractionsController::view()).
      expect(lines[0].shownDate).toBe('');
      expect(isoDate(lines[0].cartKey), 'session key is a d M Y date').not.toBe('');
      expect(lines[0].price).toBeCloseTo(added.price, 2);
      expect(lines[0].qty).toBe(added.qty);
      expect(lines[0].rowTotal).toBeCloseTo(added.price * added.qty, 2);

      await expectConfirmationTotalEqualsCart(page, added.attractionId);
      expect(errors).toEqual([]);
    });
  });

  test.describe('fixed date (fdate)', () => {
    test.describe.configure({ mode: 'serial' });
    test.skip(!env.attractions.fdate, 'set E2E_ATTR_FDATE to a fixed-date attraction');

    test('add → cart line shows the picked date and unit price → confirmation shows the date and the same total', async ({ page }) => {
      const errors = trackPageErrors(page);
      await clearCart(page);
      const added = await addTicket(page, env.attractions.fdate);
      expect(added.type, 'E2E_ATTR_FDATE must be an fdate attraction').toBe('fdate');

      const { lines } = await cartLines(page, added.ticketId);
      expect(lines).toHaveLength(1);
      expect(lines[0].cartKey).toBe(added.date);
      expect(lines[0].shownDate).toBe(added.date);
      expect(lines[0].price).toBeCloseTo(added.price, 2);
      expect(lines[0].qty).toBe(added.qty);

      await expectConfirmationTotalEqualsCart(page, added.attractionId);
      // confirmation.php prints date_of_visit as "d M Y" for fdate tickets.
      const sidebar = await readSidebar(page);
      expect(sidebar.lines.flatMap((l) => l.rows.map((r) => isoDate(r.date)))).toContain(isoDate(added.date));
      expect(errors).toEqual([]);
    });

    test('a day without a fixed date cannot be picked', async ({ page }) => {
      const errors = trackPageErrors(page);
      const attraction = new AttractionPage(page);
      await expectHealthyPage(page, await attraction.goto(env.attractions.fdate));
      const label = await attraction.clickUnavailableFutureDay(attraction.date);
      test.skip(!label, 'every day of the next 3 months is bookable; no unavailable day to try');
      await expect(attraction.date, `clicking unavailable ${label} must not fill the date`).toHaveValue('');
      await expect(attraction.quantities).toHaveCount(0);
      await expect(attraction.addToCart).toBeHidden();
      expect(errors).toEqual([]);
    });
  });

  test.describe('fixed date + time (fdatetime)', () => {
    test.describe.configure({ mode: 'serial' });
    test.skip(!env.attractions.fdatetime, 'set E2E_ATTR_FDATETIME to a fixed date+time attraction');

    test('add → cart line shows date and slot and unit price → confirmation shows the slot and the same total', async ({ page }) => {
      const errors = trackPageErrors(page);
      await clearCart(page);
      const added = await addTicket(page, env.attractions.fdatetime);
      expect(added.type, 'E2E_ATTR_FDATETIME must be an fdatetime attraction').toBe('fdatetime');
      expect(added.time).toMatch(/^\d{2}:\d{2}$/);

      const { lines } = await cartLines(page, added.ticketId);
      expect(lines).toHaveLength(1);
      expect(lines[0].cartKey).toBe(`${added.date} ${added.time}`);
      expect(lines[0].shownDate).toBe(`${added.date} ${added.time}`);
      expect(lines[0].price).toBeCloseTo(added.price, 2);

      await expectConfirmationTotalEqualsCart(page, added.attractionId);
      // confirmation.php prints only time_of_visit (H:i) for fdatetime tickets.
      const sidebar = await readSidebar(page);
      expect(sidebar.lines.flatMap((l) => l.rows.map((r) => r.date))).toContain(added.time);
      expect(errors).toEqual([]);
    });

    test('a time slot is required before tickets can be selected', async ({ page }) => {
      const errors = trackPageErrors(page);
      const attraction = new AttractionPage(page);
      await expectHealthyPage(page, await attraction.goto(env.attractions.fdatetime));
      const date = await attraction.pickAvailableDate(attraction.date, 0, '/attractions/get-fixeddate-time/');
      test.skip(!date, 'no bookable date');
      // Picking a date only loads the slots; the ticket list stays empty until a slot is chosen.
      await expect(attraction.time).toHaveValue('');
      await expect(attraction.quantities).toHaveCount(0);
      await expect(attraction.addToCart).toBeHidden();
      await expect(attraction.buyNow).toBeHidden();

      const time = await attraction.pickTime(attraction.time, 0, '/attractions/get-tickets/');
      test.skip(!time, `no slot on ${date}`);
      await expect(attraction.quantities.first().or(attraction.noTickets).first()).toBeVisible();
      expect(errors).toEqual([]);
    });
  });

  test.describe('two-way (two_way_fdatetime)', () => {
    test.describe.configure({ mode: 'serial' });
    test.skip(!env.attractions.twoWay, 'set E2E_ATTR_TWO_WAY to a two-way attraction');
    // A two-way ticket in the cart breaks /orders/cart (known bug), so the cart is cleaned via a settlement add.
    test.afterAll(async ({ browser }) => withUserPage(browser, (page) => clearCart(page)));

    test('Buy Now → checkout lists the depart and return legs with date/time and unit price → confirmation total = checkout total', async ({ page }) => {
      const errors = trackPageErrors(page);
      // viewTwoWay() clears the cart itself before adding the trip.
      const { when, depart, returnLeg, attraction } = await bookTwoWay(page, env.attractions.twoWay);
      expect(returnLeg, 'the return leg follows the depart quantity').not.toBeNull();
      expect(returnLeg!.qty).toBe(depart.qty);

      await attraction.submit('buy');
      await expect(page).toHaveURL(/\/orders\/checkout/);
      await expectHealthyPage(page);

      // Checkout's sidebar (element/selected_tickets.php) prints each leg's session key "d M yyyy H:i".
      const sidebar = await readSidebar(page);
      expect(sidebar.lines).toHaveLength(2);
      const byName = (name: string, fallback: number) => sidebar.lines.find((l) => l.name === name) ?? sidebar.lines[fallback];
      const departLine = byName(depart.name, 0);
      const returnLine = byName(returnLeg!.name, 1);
      expect(departLine.rows.map((r) => r.date)).toEqual([`${when.departDate} ${when.departTime}`]);
      expect(returnLine.rows.map((r) => r.date)).toEqual([`${when.returnDate} ${when.returnTime}`]);
      expect(departLine.rows[0].qty).toBe(depart.qty);
      expect(returnLine.rows[0].qty).toBe(depart.qty);
      // Checkout re-prices both legs through the API (_getTicketApiPrice); they should match the page.
      expect(departLine.unitPrice).toBeCloseTo(depart.price, 2);
      expect(returnLine.unitPrice).toBeCloseTo(returnLeg!.price, 2);

      await checkoutToConfirmation(page, { viaCart: false, attraction: env.attractions.twoWay });
      const confirmation = new ConfirmationPage(page);
      expect(await confirmation.grandTotalValue()).toBeCloseTo(sidebar.grandTotal, 2);
      expect(errors).toEqual([]);
    });

    test('cart page sends the two-way trip on to checkout', async ({ page }) => {
      // A two-way pair is booked as a whole, so /orders/cart forwards to the checkout (as viewTwoWay does).
      const cart = new CartPage(page);
      const response = await cart.goto();
      await expect(page).toHaveURL(/\/orders\/checkout/);
      await expectHealthyPage(page, response);
    });

    test('a return before the departure is refused', async ({ page }) => {
      const errors = trackPageErrors(page);
      const attraction = new AttractionPage(page);
      await expectHealthyPage(page, await attraction.gotoTwoWay(env.attractions.twoWay));
      // Departure on the 2nd bookable day, return on the 1st; else same day with an earlier return slot.
      let when = await attraction.chooseTwoWay({ departIndex: 1, returnIndex: 0 });
      if (!when) {
        await attraction.gotoTwoWay(env.attractions.twoWay);
        when = await attraction.chooseTwoWay({ departIndex: 0, departTimeIndex: 1, returnIndex: 0, returnTimeIndex: 0 });
      }
      test.skip(!when, 'needs two bookable days, or two slots on one day, to put the return before the departure');

      // There is no explicit check in viewTwoWay()/checkout(); the refusal comes from the
      // backend availability API (no tickets offered) or from checkout. Reaching checkout = accepted.
      const offered = await attraction.selectTicket(1, attraction.departTickets);
      if (!offered) {
        await expect(attraction.noTickets.first()).toBeVisible();
      } else {
        await attraction.submit('buy');
        await expectHealthyPage(page);
        await expect(page, `return ${when!.returnDate} ${when!.returnTime} before departure ${when!.departDate} ${when!.departTime} was accepted`).not.toHaveURL(
          /\/orders\/checkout/,
        );
      }
      expect(errors).toEqual([]);
    });
  });
});
