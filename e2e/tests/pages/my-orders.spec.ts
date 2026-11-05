import { test, expect, isViewer, orders } from './fixtures';
import { expectHealthyPage, flashError } from '../../utils/checks';
import {
  MyOrdersPage, OrderDetailPage, ORDER_STATUS_LABELS, cellDay, dayNumber, dayOffset, parseMoney,
} from '../../pages/MenuPages';

/** templates/Orders/index.php + OrdersController::index / view. */
test.describe('My Orders', () => {
  test.skip(isViewer, 'viewers are refused My Orders (covered by the viewer redirect in OrdersController::beforeFilter)');

  test('placed orders are listed with date, status and total', async ({ page }) => {
    const placed = orders();
    test.skip(placed.length === 0, 'no orders placed by the ordering project in this run');
    const list = new MyOrdersPage(page);

    // Newest first, so the latest order of this run is on page 1 of the unfiltered list.
    await list.goto();
    await expect(list.row(placed[placed.length - 1].ref)).toHaveCount(1);

    for (const order of placed) {
      await test.step(order.ref, async () => {
        await list.goto({ order_ref_id: order.ref });
        const row = list.row(order.ref);
        await expect(row).toHaveCount(1);
        const data = await list.rowData(row);
        expect(cellDay(data.date), `date cell "${data.date}"`).not.toBeNull();
        expect(cellDay(data.date)!).toBeGreaterThanOrEqual(dayNumber(-1));
        expect(data.status).toMatch(/^(Completed|Waiting|Cancelled|Refunded|Failed)$/);
        expect(data.items.length, 'items cell lists the tickets').toBeGreaterThan(0);
        const expected = parseMoney(order.total);
        if (expected !== null) expect(parseMoney(data.total)).toBeCloseTo(expected, 2);
        else expect(parseMoney(data.total)).not.toBeNull();
      });
    }
  });

  test('filter by order ref is an exact match and is kept in the form', async ({ page }) => {
    const list = new MyOrdersPage(page);
    await list.goto();
    const first = list.rows.first();
    test.skip((await list.rows.count()) === 0, 'no orders in the last 6 months');
    const ref = (await list.rowData(first)).ref;

    await list.filter({ ref });
    await expect(page).toHaveURL(new RegExp(`order_ref_id=${encodeURIComponent(ref)}`));
    await expect(list.orderRef).toHaveValue(ref);
    expect(await list.columnTexts(1)).toEqual([ref]);

    // `order_ref_id like <value>` without wildcards: a prefix finds nothing.
    await list.goto({ order_ref_id: ref.slice(0, -1) });
    await expect(list.row(ref)).toHaveCount(0);
  });

  test('unknown ref shows an empty list with a zero counter', async ({ page }) => {
    const list = new MyOrdersPage(page);
    await list.goto();
    await list.filter({ ref: `NOPE-${Date.now()}` });
    await expect(list.rows).toHaveCount(0);
    await expect(list.paginator.counter).toHaveText(/showing 0 record\(s\) out of 0 total/);
  });

  test('booking date range keeps rows inside the range', async ({ page }) => {
    const list = new MyOrdersPage(page);
    await list.goto();
    await list.filter({ dateType: 'created', from: dayOffset(-30), to: dayOffset(0) });
    await expect(list.from).toHaveValue(dayOffset(-30));
    await expect(list.to).toHaveValue(dayOffset(0));
    for (const d of await list.columnTexts(0)) {
      const n = cellDay(d);
      expect(n, d).not.toBeNull();
      expect(n!).toBeGreaterThanOrEqual(dayNumber(-30));
      expect(n!).toBeLessThanOrEqual(dayNumber(0));
    }
    for (const order of orders()) {
      await expect(list.row(order.ref), `${order.ref} was booked today`).toHaveCount(1);
    }
  });

  test('a future booking date range is empty', async ({ page }) => {
    const list = new MyOrdersPage(page);
    await list.goto({ date_type: 'created', from: dayOffset(2), to: dayOffset(9) });
    await expect(list.rows).toHaveCount(0);
    await expect(list.paginator.counter).toHaveText(/out of 0 total/);
  });

  test('visit date filter uses both From and To', async ({ page }) => {
    const list = new MyOrdersPage(page);
    await list.goto({ date_type: 'visit_date', from: dayOffset(-7), to: dayOffset(30) });
    await expect(list.dateType).toHaveValue('visit_date');
    const placed = orders();
    test.skip(placed.length === 0, 'no orders placed by the ordering project in this run');

    // This run's tickets are for today or later: a range ending yesterday leaves them out...
    await list.goto({ date_type: 'visit_date', from: dayOffset(-7), to: dayOffset(-1) });
    for (const order of placed) await expect(list.row(order.ref), order.ref).toHaveCount(0);
    // ...and a range ending well ahead keeps them (newest first, so the latest is on page 1).
    await list.goto({ date_type: 'visit_date', from: dayOffset(-7), to: dayOffset(400) });
    await expect(list.row(placed[placed.length - 1].ref)).toHaveCount(1);
  });

  test('status filter only returns that status', async ({ page }) => {
    const list = new MyOrdersPage(page);
    await list.goto();
    const options = await list.status.locator('option').evaluateAll((os) => os.map((o) => (o as HTMLOptionElement).value));
    expect(options).toEqual(['all', 'done', 'cancelled', 'waiting', 'refunded', 'rejected']);
    for (const status of Object.keys(ORDER_STATUS_LABELS)) {
      await test.step(status, async () => {
        await list.goto({ status });
        await expect(list.status).toHaveValue(status);
        for (const s of await list.columnTexts(4)) expect(s).toMatch(ORDER_STATUS_LABELS[status]);
      });
    }
    await list.goto({ status: 'all' });
    for (const s of await list.columnTexts(4)) expect(s).toMatch(/^(Completed|Waiting|Cancelled|Refunded|Failed)$/);
  });

  test('Show All clears the filters', async ({ page }) => {
    const list = new MyOrdersPage(page);
    await list.goto({ order_ref_id: 'NOPE', status: 'done' });
    await Promise.all([page.waitForURL((u) => u.pathname === '/orders' && u.search === ''), list.showAll.click()]);
    await expectHealthyPage(page);
    await expect(list.orderRef).toHaveValue('');
  });

  test('newest orders first, pagination moves to the next page', async ({ page }) => {
    const list = new MyOrdersPage(page);
    await list.goto();
    const days = (await list.columnTexts(0)).map((d) => cellDay(d) ?? 0);
    expect([...days].sort((a, b) => b - a), 'rows sorted by booking date, newest first').toEqual(days);

    const info = await list.paginator.info();
    expect(info.current).toBe(await list.rows.count());
    test.skip(info.pages < 2, 'only one page of orders');
    const firstRef = (await list.rowData(list.rows.first())).ref;
    await Promise.all([page.waitForURL(/[?&]page=2/), list.paginator.next.first().click()]);
    await expectHealthyPage(page);
    expect((await list.paginator.info()).page).toBe(2);
    await expect(list.row(firstRef)).toHaveCount(0);
  });

  test('a row opens its order detail', async ({ page }) => {
    const list = new MyOrdersPage(page);
    const placed = orders();
    await list.goto(placed.length ? { order_ref_id: placed[0].ref } : {});
    test.skip((await list.rows.count()) === 0, 'no orders to open');
    const data = await list.rowData(list.rows.first());

    await Promise.all([
      page.waitForURL(new RegExp(`/orders/view/${encodeURIComponent(data.ref)}$`)),
      list.row(data.ref).getByRole('link', { name: data.ref, exact: true }).click(),
    ]);
    await expectHealthyPage(page);
    const detail = new OrderDetailPage(page);
    await expect(detail.infoValue('Order Ref')).toHaveText(data.ref);
    await expect(detail.infoValue('Payment Method')).not.toBeEmpty();
    await expect(detail.infoValue('Payment Status')).not.toBeEmpty();
    await expect(detail.infoValue('Purchase Date')).toHaveText(/\d{2} [A-Z][a-z]{2} \d{4}/);
    expect(parseMoney(await detail.grandTotal.textContent())).toBeCloseTo(parseMoney(data.total)!, 2);
  });

  test('unknown order ref is refused with a flash on My Orders', async ({ page }) => {
    const detail = new OrderDetailPage(page);
    const res = await detail.goto(`NOPE-${Date.now()}`);
    await expectHealthyPage(page, res);
    await expect(page).toHaveURL(/\/orders$/);
    await expect(flashError(page)).toContainText('Order not found.');
    await expect(page.getByRole('heading', { name: 'Order Information' })).toHaveCount(0);
  });
});
