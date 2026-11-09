import { test, expect, isMaster, creditOrders } from './fixtures';
import { expectHealthyPage, flashError } from '../../utils/checks';
import { AppShell } from '../../pages/AppShell';
import { env } from '../../utils/env';
import { credsOf, loginInNewContext } from '../../utils/ordering';
import {
  BalanceReportPage, cellDay, csvDataRows, dayNumber, dayOffset, downloadFrom, downloadText, openHealthy, parseMoney,
} from '../../pages/MenuPages';

/** templates/CreditLogs/index.php + CreditLogsController::index (menu link only for masters with enable_credit_logs_view). */
test.describe('Balance Report', () => {
  test.skip(!isMaster, 'the Balance Report menu link is only shown to masters');

  test.beforeEach(async ({ page }) => {
    await openHealthy(page, '/agent-users/view');
    const shell = new AppShell(page);
    await shell.openUserMenu();
    const link = shell.userMenu.locator('a.dropdown-item[href*="/credit-logs"]');
    test.skip((await link.count()) === 0, 'Balance Report is not enabled for this agent (enable_credit_logs_view off)');
    await Promise.all([page.waitForURL(/\/credit-logs/), link.click()]);
    await expectHealthyPage(page);
  });

  test('lists the credits debit of each order paid with credits', async ({ page }) => {
    const placed = creditOrders();
    test.skip(placed.length === 0, 'no credits orders placed in this run');
    const report = new BalanceReportPage(page);
    await expect(report.heading).toBeVisible();
    await report.goto({ from: dayOffset(-1), to: dayOffset(1) });
    for (const order of placed) {
      await test.step(order.ref, async () => {
        const row = report.rowByRef(order.ref);
        await expect(row).toHaveCount(1);
        // A purchase is a "credit" log, shown in brackets: (12.34).
        const amount = ((await row.locator('td').nth(2).textContent()) ?? '').trim();
        expect(amount).toMatch(/^\([\d,]+\.\d{2}\)$/);
        const expected = parseMoney(order.total);
        if (expected !== null) expect(parseMoney(amount)).toBeCloseTo(expected, 2);
        await expect(row.locator('td').nth(3)).toHaveText(/^\(?-?[\d,]+\.\d{2}\)?$/);
      });
    }
  });

  test('date filter keeps rows inside the range', async ({ page }) => {
    const report = new BalanceReportPage(page);
    await report.filter(dayOffset(-14), dayOffset(0));
    await expect(report.from).toHaveValue(dayOffset(-14));
    await expect(report.to).toHaveValue(dayOffset(0));
    for (const d of await report.rows.locator('td:nth-child(1)').allTextContents()) {
      const n = cellDay(d);
      expect(n, d).not.toBeNull();
      expect(n!).toBeGreaterThanOrEqual(dayNumber(-14));
      expect(n!).toBeLessThanOrEqual(dayNumber(0));
    }
    for (const t of await report.rows.locator('td:nth-child(2)').allTextContents()) {
      expect(t.trim()).toMatch(/^(Top Up|\S+( \((Rebate|Cancel|Cancel Rebate)\))?)$/);
    }
  });

  test('a future range is empty', async ({ page }) => {
    const report = new BalanceReportPage(page);
    await report.goto({ from: dayOffset(2), to: dayOffset(9) });
    await expect(report.rows).toHaveCount(0);
    await expect(report.paginator.counter).toHaveText(/out of 0 total/);
  });

  test('download without a date range is refused', async ({ page }) => {
    const report = new BalanceReportPage(page);
    await report.goto();
    await Promise.all([page.waitForEvent('load'), report.download.click()]);
    await expectHealthyPage(page);
    await expect(flashError(page)).toContainText('Select a date and click filter to download!');
  });

  test('download of more than 31 days is refused', async ({ page }) => {
    // Kept inside CreditLogs.days_to_show (50 in the example config) so the start date is not clamped.
    const report = new BalanceReportPage(page);
    await report.goto({ from: dayOffset(-45), to: dayOffset(0) });
    await Promise.all([page.waitForEvent('load'), report.download.click()]);
    await expectHealthyPage(page);
    await expect(flashError(page)).toContainText('Date range cannot be more than 30 days!');
    // Redirects back with the same filter.
    await expect(page).toHaveURL(/[?&]from=/);
    await expect(page).toHaveURL(/[?&]to=/);
  });

  test('CSV export has the same rows as the table', async ({ page }) => {
    const report = new BalanceReportPage(page);
    await report.goto({ from: dayOffset(-7), to: dayOffset(0) });
    const info = await report.paginator.info();
    if (info.count === 0) {
      await Promise.all([page.waitForEvent('load'), report.download.click()]);
      await expect(flashError(page)).toContainText('No records found!');
      return;
    }
    const tableTypes = (await report.rows.locator('td:nth-child(2)').allTextContents()).map((s) => s.trim());
    const tableAmounts = (await report.rows.locator('td:nth-child(3)').allTextContents()).map((s) => s.trim());

    const download = await downloadFrom(page, report.download);
    expect(download.suggestedFilename()).toMatch(/Balance Report \d{12}\.csv$/);
    const csv = await downloadText(download);
    expect(csv).toContain('Balance Report');
    const rows = csvDataRows(csv, 'Date');
    expect(rows.length, 'CSV rows = table total').toBe(info.count);
    // Same order (log_no desc): the first page of the table matches the first CSV rows.
    expect(rows.slice(0, tableTypes.length).map((r) => r[1])).toEqual(tableTypes);
    expect(rows.slice(0, tableAmounts.length).map((r) => r[2])).toEqual(tableAmounts);
    for (const order of creditOrders()) expect(csv).toContain(order.ref);
  });
});

test.describe('Balance Report for non-masters', () => {
  test('a normal user is refused /credit-logs', async ({ browser }) => {
    test.skip(!env.normalUser.email || !env.normalUser.password, 'set E2E_NORMAL_EMAIL/PASSWORD to a normal (non-master) user');
    const normal = await loginInNewContext(browser, credsOf(env.normalUser));
    try {
      await normal.page.goto('/credit-logs');
      await expect(normal.page).not.toHaveURL(/\/credit-logs/);
      await expect(flashError(normal.page)).toContainText('You are not authorized to access that location.');
    } finally {
      await normal.context.close();
    }
  });
});
