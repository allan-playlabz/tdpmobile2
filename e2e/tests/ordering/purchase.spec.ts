import { test, expect, type Page } from '@playwright/test';
import { CartPage, ConfirmationPage, isoDate } from '../../pages/BookingPages';
import { OrderDetailPage } from '../../pages/OrderPages';
import { expectHealthyPage, trackPageErrors } from '../../utils/checks';
import { assertAllowedHost, env } from '../../utils/env';
import { addTicket, bookTwoWay, checkoutToConfirmation, clearCart, payWithCredits, withUserPage } from '../../utils/ordering';

/**
 * Full purchases paid with agent credits, one REAL order per scenario: every single pricing type,
 * the mixed carts, and a quantity of 2. Each goes add → cart → checkout → confirmation → Pay Now
 * (credits) → thank-you → order detail. Runs only with E2E_ALLOW_PURCHASE=true and spends credits.
 *
 * Attractions come from E2E_ATTR_SETTLEMENT / E2E_ATTR_FDATE / E2E_ATTR_FDATETIME / E2E_ATTR_TWO_WAY;
 * a scenario skips when one it needs is not set. Mixed carts also skip (with the app's message)
 * when the app refuses the mix, e.g. tickets of an external supplier with tickets of another merchant.
 *
 * Named purchase (sorts after post-order.spec.ts) so post-order keeps using pay-credits' order.
 */
// Not serial: each purchase stands alone, so one failing doesn't skip the others (one worker runs them in order).

type Kind = 'settlement' | 'fdate' | 'fdatetime';
type Item = { kind: Kind; qty?: number };

const attractionOf = (kind: Kind): string => env.attractions[kind];

/** First configured one-way pricing type, for "2 quantities of any". */
const anyKind: Kind = (['settlement', 'fdate', 'fdatetime'] as const).find((k) => attractionOf(k)) ?? 'settlement';

const scenarios: { name: string; items: Item[] }[] = [
  { name: '1 settlement', items: [{ kind: 'settlement' }] },
  { name: '1 fdate', items: [{ kind: 'fdate' }] },
  { name: '1 fdatetime', items: [{ kind: 'fdatetime' }] },
  { name: 'settlement + fdate', items: [{ kind: 'settlement' }, { kind: 'fdate' }] },
  { name: 'settlement + fdatetime', items: [{ kind: 'settlement' }, { kind: 'fdatetime' }] },
  { name: 'fdate + fdatetime', items: [{ kind: 'fdate' }, { kind: 'fdatetime' }] },
  { name: `2 × ${anyKind}`, items: [{ kind: anyKind, qty: 2 }] },
];

/** Order detail: paid with credits, the expected number of ticket rows and quantity, Grand Total = amount paid. */
async function expectOrderDetail(page: Page, ref: string, expected: { rows: number; qty: number; total: number }): Promise<void> {
  const detail = new OrderDetailPage(page);
  await expectHealthyPage(page, await detail.goto(ref));
  await expect(detail.info('Order Ref')).toHaveText(ref);
  await expect(detail.info('Payment Method')).toHaveText(/Credits/i);
  const summary = await detail.summary();
  const rows = summary.lines.flatMap((l) => l.rows);
  expect(rows, 'one row per ticket line in the order').toHaveLength(expected.rows);
  expect(rows.reduce((sum, r) => sum + r.qty, 0), 'total quantity in the order').toBe(expected.qty);
  expect(summary.grandTotal, 'Grand Total = amount paid').toBeCloseTo(expected.total, 2);
}

test.describe('Purchase with credits', () => {
  test.skip(env.user.role === 'viewer', 'viewers cannot book');
  test.skip(!env.allowPurchase, 'set E2E_ALLOW_PURCHASE=true to place real orders paid with agent credits');

  test.beforeAll(async ({ browser }) => {
    assertAllowedHost();
    await withUserPage(browser, (page) => clearCart(page));
  });
  test.afterAll(async ({ browser }) => withUserPage(browser, (page) => clearCart(page)));

  for (const scenario of scenarios) {
    test(`${scenario.name}: cart → checkout → pay with credits → order detail`, async ({ page }) => {
      const missing = scenario.items.map((i) => i.kind).filter((k) => !attractionOf(k));
      test.skip(missing.length > 0, `set ${[...new Set(missing)].map((k) => `E2E_ATTR_${k.toUpperCase()}`).join(', ')}`);
      test.setTimeout(300_000);
      const errors = trackPageErrors(page);
      await clearCart(page);

      const added = [];
      for (const item of scenario.items) {
        const ticket = await addTicket(page, attractionOf(item.kind), { qty: item.qty ?? 1 });
        expect(ticket.type, `E2E_ATTR_${item.kind.toUpperCase()} must be a ${item.kind} attraction`).toBe(item.kind);
        expect(ticket.qty).toBe(item.qty ?? 1);
        added.push(ticket);
      }

      const cart = new CartPage(page);
      await expectHealthyPage(page, await cart.goto());
      const lines = await cart.lines();
      expect(lines, 'one cart line per added ticket').toHaveLength(added.length);
      const expectedTotal = added.reduce((sum, a) => sum + a.price * a.qty, 0);
      const cartTotal = await cart.totalValue();
      expect(cartTotal, 'cart Total = Σ unit price × qty').toBeCloseTo(expectedTotal, 2);

      const attraction = [...new Set(added.map((a) => a.attractionId))].join(',');
      await checkoutToConfirmation(page, { attraction });
      expect(await new ConfirmationPage(page).grandTotalValue(), 'confirmation Grand Total = cart Total').toBeCloseTo(cartTotal, 2);

      const { ref, total } = await payWithCredits(page, { attraction });
      expect(total).toBeCloseTo(cartTotal, 2);

      await expectOrderDetail(page, ref, { rows: added.length, qty: added.reduce((sum, a) => sum + a.qty, 0), total });
      await cart.goto();
      await expect(cart.emptyMessage, 'the cart is emptied after paying').toBeVisible();
      expect(errors).toEqual([]);
    });
  }

  test('1 two-way (two_way_fdatetime): Buy Now → checkout → pay with credits → order detail', async ({ page }) => {
    test.skip(!env.attractions.twoWay, 'set E2E_ATTR_TWO_WAY');
    test.setTimeout(300_000);
    const errors = trackPageErrors(page);

    // viewTwoWay() clears the cart itself; Buy Now goes straight to checkout with both legs.
    // Return on a later day than the departure (2nd bookable return date), like a real trip: the
    // first return slot is often minutes after the departure on the same day. The ferry operator
    // (BRF) books the trip when it is paid and answers a refusal with "8001 Cannot Create Order"
    // without its reason (passenger names with digits were refused, see passengerAnswer()).
    const { when, depart, returnLeg, attraction } = await bookTwoWay(page, env.attractions.twoWay, { returnIndex: 1 });
    test.skip(isoDate(when.returnDate) <= isoDate(when.departDate), `no return date after the departure (${when.departDate}) to pick`);
    expect(returnLeg, 'the return leg follows the depart quantity').not.toBeNull();
    await attraction.submit('buy');
    await expect(page).toHaveURL(/\/orders\/checkout/);
    await expectHealthyPage(page);

    await checkoutToConfirmation(page, { viaCart: false, attraction: env.attractions.twoWay });
    const expectedTotal = depart.price * depart.qty + returnLeg!.price * returnLeg!.qty;
    expect(await new ConfirmationPage(page).grandTotalValue(), 'Grand Total = depart + return').toBeCloseTo(expectedTotal, 2);

    const { ref, total } = await payWithCredits(page, { attraction: env.attractions.twoWay });
    await expectOrderDetail(page, ref, { rows: 2, qty: depart.qty + returnLeg!.qty, total });
    expect(errors).toEqual([]);
  });
});
