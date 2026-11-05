import { test, expect } from '@playwright/test';
import { MyTicketsPage, OrderDetailPage } from '../../pages/OrderPages';
import { expectHealthyPage, flashError, flashSuccess, trackPageErrors } from '../../utils/checks';
import { env } from '../../utils/env';
import type { PlacedOrder } from '../../utils/orders';
import { clearCart, isPdf, latestCreditsOrder, readFileHead, withUserPage } from '../../utils/ordering';

/**
 * After an order: detail page, invoice, vouchers, merged PDF, My Tickets, cancelling.
 * Uses the credits order pay-credits.spec.ts placed earlier in this run (placedOrders()); without
 * one (E2E_ALLOW_PURCHASE off) everything here is skipped.
 *
 * Named post-order (not after-order) so it sorts after pay-credits.spec.ts: spec files of a
 * project run in alphabetical order, and test-results/orders.jsonl only lives for one run.
 */
test.describe.configure({ mode: 'serial' });

let order: PlacedOrder | undefined;
let codes: { code: string; href: string }[] = [];

test.describe('After an order', () => {
  test.skip(env.user.role === 'viewer', 'viewers have no orders');

  test.beforeAll(async ({ browser }) => withUserPage(browser, (page) => clearCart(page)));

  test.beforeEach(() => {
    order = latestCreditsOrder();
    test.skip(!order, 'no credits order placed in this run (pay-credits.spec.ts needs E2E_ALLOW_PURCHASE=true)');
  });

  test('order detail lists the items and totals', async ({ page }) => {
    test.setTimeout(150_000);
    const errors = trackPageErrors(page);
    const detail = new OrderDetailPage(page);
    // Vouchers are issued asynchronously; wait for them (the API's details are re-read on each load).
    await expect
      .poll(
        async () => {
          await detail.goto(order!.ref);
          return detail.voucherLinks.count();
        },
        { timeout: 120_000, intervals: [3_000, 5_000, 10_000], message: `vouchers issued for ${order!.ref}` },
      )
      .toBeGreaterThan(0);
    await expectHealthyPage(page);
    await expect(detail.info('Order Ref')).toHaveText(order!.ref);
    await expect(detail.info('Payment Method')).toHaveText(/Credits/i);

    const summary = await detail.summary();
    expect(summary.lines.length).toBeGreaterThan(0);
    for (const line of summary.lines) {
      for (const row of line.rows) {
        expect(row.qty).toBeGreaterThan(0);
        expect(row.total, `${line.name} line total = unit price × qty`).toBeCloseTo(line.unitPrice * row.qty, 2);
      }
    }
    const itemsTotal = summary.lines.flatMap((l) => l.rows).reduce((sum, r) => sum + r.total, 0);
    expect(summary.subtotal).toBeCloseTo(itemsTotal, 2);
    expect(summary.grandTotal).toBeCloseTo(summary.subtotal + summary.fee, 2);
    if (order!.total) expect(summary.grandTotal, 'Grand Total = amount paid').toBeCloseTo(Number(order!.total), 2);
    codes = await detail.vouchers();
    expect(errors).toEqual([]);
  });

  test('invoice downloads as INVOICE_<ref>.pdf', async ({ page }) => {
    const detail = new OrderDetailPage(page);
    await expectHealthyPage(page, await detail.goto(order!.ref));
    test.skip((await detail.invoiceLink.count()) === 0, 'no Download Invoice link (Features.invoice is off)');
    const download = await detail.download(detail.invoiceLink.first());
    expect(download.suggestedFilename()).toBe(`INVOICE_${order!.ref}.pdf`);
    expect(isPdf(readFileHead(await download.path())), 'invoice body starts with %PDF').toBe(true);
  });

  test('each voucher link is a PDF', async ({ page }) => {
    test.skip(codes.length === 0, 'no voucher links on the order');
    await new OrderDetailPage(page).goto(order!.ref);
    for (const { code, href } of codes) {
      const response = await page.request.get(href);
      expect(response.status(), `voucher ${code}`).toBe(200);
      expect(isPdf(await response.body()), `voucher ${code} is a PDF`).toBe(true);
    }
  });

  test('Download as Single PDF starts a merge or returns the merged file', async ({ page }) => {
    const errors = trackPageErrors(page);
    const detail = new OrderDetailPage(page);
    await expectHealthyPage(page, await detail.goto(order!.ref));
    if (await detail.downloadAll.isVisible()) {
      const href = (await detail.downloadAll.getAttribute('href')) ?? '';
      const response = await page.request.get(href);
      expect(response.status()).toBe(200);
      expect(isPdf(await response.body()), 'merged file is a PDF').toBe(true);
    } else if (await detail.mergeAll.isVisible()) {
      // VouchersController::downloadAll() queues the merge (QueueSender /merged-pdf) and goes back.
      const [response] = await Promise.all([page.waitForResponse((r) => r.url().includes('/vouchers/download-all/')), detail.mergeAll.click()]);
      expect(response.status(), 'download-all answered (404 = queue server refused the merge)').toBeLessThan(400);
      await page.waitForURL(new RegExp(`/orders/view/${order!.ref}`));
      await expectHealthyPage(page);
      await expect(page.locator('.alert-info')).toContainText('Your request has been queued. Please wait for merge to complete.');
      await expect(detail.mergingMessage.or(detail.downloadAll).first()).toBeVisible();
    } else if (await detail.mergingMessage.isVisible()) {
      await expect(detail.mergingMessage).toContainText('Download as Single PDF (Processing)');
    } else {
      test.skip(true, `order status is "${await detail.info('Payment Status').textContent()}": no single-PDF download offered`);
    }
    expect(errors).toEqual([]);
  });

  test("My Tickets lists the order's voucher codes", async ({ page }) => {
    test.skip(codes.length === 0, 'no voucher codes on the order');
    const errors = trackPageErrors(page);
    const myTickets = new MyTicketsPage(page);
    for (const { code } of codes.slice(0, 5)) {
      await expectHealthyPage(page, await myTickets.gotoCode(code));
      await expect(myTickets.row(code), `voucher ${code} listed`).toHaveCount(1);
      await expect(myTickets.orderRef(code)).toHaveText(order!.ref);
    }
    expect(errors).toEqual([]);
  });

  test('cancel a voucher', async ({ page }) => {
    test.skip(!env.allowCancel, 'set E2E_ALLOW_CANCEL=true to cancel a voucher of the order placed by this run');
    test.skip(codes.length === 0, 'no voucher codes on the order');
    test.setTimeout(120_000);
    const errors = trackPageErrors(page);
    const { code } = codes[0];
    const myTickets = new MyTicketsPage(page);
    await expectHealthyPage(page, await myTickets.gotoCode(code));
    test.skip((await myTickets.cancelButton(code).count()) === 0, `voucher ${code} has no Cancel action (agent not allowed_api_cancel, or not cancellable)`);

    const message = await myTickets.cancel(code);
    expect(message).toBe('Are you sure you want to cancel this voucher?');
    await expectHealthyPage(page);
    await expect(flashError(page)).toHaveCount(0);
    await expect(flashSuccess(page)).toContainText(`Successfully cancelled voucher ${code}`);

    await myTickets.gotoCode(code);
    await expect(myTickets.cancelButton(code)).toHaveCount(0);
    if (await myTickets.statusHeader.count()) await expect(myTickets.status(code)).toHaveText('Cancelled');
    expect(errors).toEqual([]);
  });

  test('a voucher that is not cancellable has no Cancel action', async ({ page }) => {
    const myTickets = new MyTicketsPage(page);
    await expectHealthyPage(page, await myTickets.goto());
    if ((await myTickets.actionsHeader.count()) === 0) {
      // allowed_api_cancel is off for this agent: no voucher may offer Cancel.
      await expect(page.locator('a.btn-outline-danger', { hasText: 'Cancel' })).toHaveCount(0);
      return;
    }
    const closed = myTickets.rows.filter({ has: page.locator('.badge', { hasText: /Cancelled|Redeemed|Expired|Partially/ }) });
    const count = await closed.count();
    test.skip(count === 0, 'no cancelled/redeemed/expired voucher on the first My Tickets page');
    for (let i = 0; i < Math.min(count, 10); i++) {
      await expect(closed.nth(i).locator('a.btn-outline-danger', { hasText: 'Cancel' })).toHaveCount(0);
    }
  });
});

test.describe('My Tickets filters', () => {
  test.skip(env.user.role === 'viewer', 'viewers have no tickets');

  test('an invalid From date is handled', async ({ page }) => {
    const response = await page.goto('/orders/my-tickets?from=not-a-date');
    await expectHealthyPage(page, response);
    await expect(new MyTicketsPage(page).heading).toBeVisible();
  });
});
