import { test, expect } from '@playwright/test';
import { CartPage, ConfirmationPage } from '../../pages/BookingPages';
import { expectHealthyPage, trackPageErrors } from '../../utils/checks';
import { assertAllowedHost, env } from '../../utils/env';
import { combinations, MATRIX_FILE } from '../../utils/matrix';
import { addTicket, bookTwoWay, checkoutToConfirmation, clearCart, payWithCredits, withUserPage } from '../../utils/ordering';

/**
 * One REAL order per pricing_type × ticket type × voucher_type combination (32; ctc is left out),
 * paid with agent credits: add the combination's ticket → cart → checkout → confirmation →
 * Pay Now (credits) → thank-you. The voucher itself is not checked.
 *
 * Tickets come from matrix.json (scripts/build-matrix.php fills it from the target's database);
 * a combination without a ticket skips. Runs only with E2E_ALLOW_PURCHASE=true and spends credits;
 * barcode/pdf combinations use up a code/PDF from the pool and api ones book with the supplier.
 *
 * Named purchase-* (sorts after post-order.spec.ts) so post-order keeps using pay-credits' order.
 */
// Not serial: each combination stands alone, so one failing doesn't skip the others.

test.describe('Purchase matrix (pricing × type × voucher)', () => {
  test.skip(env.user.role === 'viewer', 'viewers cannot book');
  test.skip(!env.allowPurchase, 'set E2E_ALLOW_PURCHASE=true to place real orders paid with agent credits');

  test.beforeAll(async ({ browser }) => {
    assertAllowedHost();
    await withUserPage(browser, (page) => clearCart(page));
  });
  test.afterAll(async ({ browser }) => withUserPage(browser, (page) => clearCart(page)));

  for (const combo of combinations()) {
    test(`${combo.key}: pay with credits → thank-you`, async ({ page }) => {
      test.skip(!combo.cell, `no ${combo.key} ticket in ${MATRIX_FILE} (rebuild it with scripts/build-matrix.php or set one by hand)`);
      const { attraction, ticket, note } = combo.cell!;
      test.info().annotations.push({ type: 'ticket', description: `${note ?? ''} (${ticket})`.trim() });
      test.setTimeout(300_000);
      const errors = trackPageErrors(page);
      await clearCart(page);

      let expectedTotal: number;
      if (combo.pricing === 'two_way_fdatetime') {
        // viewTwoWay() clears the cart itself; Buy Now goes straight to checkout with both legs.
        // Return on the 2nd bookable date, like a real trip (see purchase.spec.ts).
        const trip = await bookTwoWay(page, attraction, { ticketId: ticket, returnIndex: 1 });
        expect(trip.returnLeg, 'the return leg follows the depart quantity').not.toBeNull();
        await trip.attraction.submit('buy');
        await expect(page).toHaveURL(/\/orders\/checkout/);
        await expectHealthyPage(page);
        expectedTotal = trip.depart.price * trip.depart.qty + trip.returnLeg!.price * trip.returnLeg!.qty;
        await checkoutToConfirmation(page, { viaCart: false, attraction });
      } else {
        const added = await addTicket(page, attraction, { ticketId: ticket });
        expect(added.ticketId, 'the matrix ticket was added').toBe(ticket);
        expect(added.type, `${combo.key}: the attraction's pricing type`).toBe(combo.pricing);

        const cart = new CartPage(page);
        await expectHealthyPage(page, await cart.goto());
        const lines = (await cart.lines()).filter((l) => l.ticketId === ticket);
        expect(lines, 'one cart line for the ticket').toHaveLength(1);
        expect(lines[0].price).toBeCloseTo(added.price, 2);
        expectedTotal = await cart.totalValue();
        await checkoutToConfirmation(page, { attraction });
      }
      expect(await new ConfirmationPage(page).grandTotalValue(), 'confirmation Grand Total').toBeCloseTo(expectedTotal, 2);

      // Asserts the thank-you page and records the order (test-results/orders.jsonl, placed-orders.jsonl).
      const { total } = await payWithCredits(page, { attraction });
      expect(total).toBeCloseTo(expectedTotal, 2);
      expect(errors).toEqual([]);
    });
  }
});
