import { test, expect, isViewer, orders } from './fixtures';
import { expectHealthyPage } from '../../utils/checks';
import {
  MyTicketsPage, OrderDetailPage, TICKET_STATUS, cellDay, dayNumber, dayOffset, openHealthy,
} from '../../pages/MenuPages';
import type { Page } from '@playwright/test';

/** Voucher codes (+ download links) of an order, read from its detail page. */
async function vouchersOf(page: Page, ref: string) {
  const detail = new OrderDetailPage(page);
  const res = await detail.goto(ref);
  await expectHealthyPage(page, res);
  await expect(detail.infoValue('Order Ref')).toHaveText(ref);
  return detail.vouchers();
}

/** templates/Orders/my_tickets.php + OrdersController::myTickets. */
test.describe('My Tickets', () => {
  test.skip(isViewer, 'viewers are refused My Tickets');

  test('codes of placed orders are listed with ticket name and status', async ({ page }) => {
    const placed = orders();
    test.skip(placed.length === 0, 'no orders placed by the ordering project in this run');
    const tickets = new MyTicketsPage(page);

    let checked = 0;
    for (const order of placed) {
      await test.step(order.ref, async () => {
        const vouchers = await vouchersOf(page, order.ref);
        checked += vouchers.length;
        for (const v of vouchers) {
          await tickets.goto({ code: v.code });
          const row = tickets.rowByCode(v.code);
          await expect(row).toHaveCount(1);
          await expect(row.getByRole('link', { name: order.ref, exact: true })).toBeVisible();
          await expect(row.locator('td').nth(2).locator('i'), 'ticket name in italics').not.toBeEmpty();
          const statusCol = await tickets.columnIndex('Status');
          if (statusCol >= 0) await expect(row.locator('td').nth(statusCol)).toHaveText(TICKET_STATUS);
        }
      });
    }
    test.skip(checked === 0, 'placed orders have no vouchers yet (still processing?)');
  });

  test('code filter is a prefix match and is kept in the form', async ({ page }) => {
    const tickets = new MyTicketsPage(page);
    await tickets.goto();
    test.skip((await tickets.rows.count()) === 0, 'no tickets in the last 6 months');
    const code = ((await tickets.rows.first().locator('td').nth(1).textContent()) ?? '').trim();
    const prefix = code.slice(0, Math.max(3, code.length - 2));

    await tickets.filter({ code: prefix });
    await expect(page).toHaveURL(new RegExp(`code=${encodeURIComponent(prefix)}`));
    await expect(tickets.code).toHaveValue(prefix);
    const codes = (await tickets.rows.locator('td:nth-child(2)').allTextContents()).map((s) => s.trim());
    expect(codes).toContain(code);
    for (const c of codes) expect(c.startsWith(prefix), c).toBe(true);
  });

  test('unknown code shows an empty list', async ({ page }) => {
    const tickets = new MyTicketsPage(page);
    await tickets.goto();
    await tickets.filter({ code: `NOPE${Date.now()}` });
    await expect(tickets.rows).toHaveCount(0);
    await expect(tickets.paginator.counter).toHaveText(/out of 0 total/);
  });

  test('date range keeps rows inside the range', async ({ page }) => {
    const tickets = new MyTicketsPage(page);
    await tickets.goto();
    await tickets.filter({ from: dayOffset(-30), to: dayOffset(0) });
    await expect(tickets.from).toHaveValue(dayOffset(-30));
    await expect(tickets.to).toHaveValue(dayOffset(0));
    for (const d of await tickets.rows.locator('td:nth-child(4)').allTextContents()) {
      const n = cellDay(d);
      expect(n, d).not.toBeNull();
      expect(n!).toBeGreaterThanOrEqual(dayNumber(-30));
      expect(n!).toBeLessThanOrEqual(dayNumber(0));
    }
  });

  test('a future date range is empty', async ({ page }) => {
    const tickets = new MyTicketsPage(page);
    await tickets.goto({ from: dayOffset(2), to: dayOffset(9) });
    await expect(tickets.rows).toHaveCount(0);
  });

  test('status filter is not offered (commented out in the template)', async ({ page }) => {
    const tickets = new MyTicketsPage(page);
    await tickets.goto();
    // my_tickets.php wraps the status <select> in an HTML comment, and the controller ignores ?status.
    await expect(tickets.status).toHaveCount(0);
    const statusCol = await tickets.columnIndex('Status');
    if (statusCol >= 0) {
      for (const s of await tickets.rows.locator(`td:nth-child(${statusCol + 1})`).allTextContents()) {
        if (s.trim()) expect(s.trim()).toMatch(TICKET_STATUS);
      }
    }
  });

  test('voucher download link returns a PDF', async ({ page }) => {
    const placed = orders();
    let href: string | undefined;
    for (const order of placed) {
      href = (await vouchersOf(page, order.ref))[0]?.href;
      if (href) break;
    }
    if (!href) {
      // No order this run: use the newest ticket in the list.
      const tickets = new MyTicketsPage(page);
      await tickets.goto();
      test.skip((await tickets.rows.count()) === 0, 'no tickets to download');
      const ref = ((await tickets.rows.first().locator('td').first().textContent()) ?? '').trim();
      href = (await vouchersOf(page, ref!))[0]?.href;
    }
    test.skip(!href, 'order has no voucher links');

    // page.request shares the logged-in cookies.
    const res = await page.request.get(href!);
    expect(res.status(), href).toBe(200);
    const body = await res.body();
    expect(body.subarray(0, 4).toString('latin1'), `voucher at ${href} should be a PDF`).toBe('%PDF');
  });

  test('Show All resets My Tickets', async ({ page }) => {
    const tickets = new MyTicketsPage(page);
    await tickets.goto({ code: 'NOPE' });
    await expect(tickets.showAll).toHaveAttribute('href', /\/orders\/my-tickets$/, { timeout: 2_000 });
  });

  test('a malformed from date is handled', async ({ page }) => {
    await openHealthy(page, '/orders/my-tickets?from=not-a-date');
  });
});
