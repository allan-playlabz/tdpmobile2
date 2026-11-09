import { test, expect, isViewer, orders } from './fixtures';
import { expectHealthyPage, flashError, flashSuccess } from '../../utils/checks';
import { env } from '../../utils/env';
import {
  GeneratedReportsPage, ReportsPage, dayOffset, downloadFrom, downloadText, fmtDate,
} from '../../pages/MenuPages';

/** templates/Reports/index.php, reports.php + ReportsController. */
test.describe('Sales Reports', () => {
  test.skip(isViewer, 'viewers have no Sales Report menu');

  test('form defaults to the first of this month and links to generated reports', async ({ page }) => {
    const reports = new ReportsPage(page);
    await reports.goto();
    await expect(reports.heading).toBeVisible();
    const now = new Date();
    // date('01 M Y')
    const first = `01 ${fmtDate(now).split(' ').slice(1).join(' ')}`;
    await expect(reports.startDate).toHaveValue(first);
    await expect(reports.endDate).toHaveValue('');
    await expect(reports.startDate).toHaveAttribute('autocomplete', 'off');

    // Datepicker (endDate: today): future days are disabled.
    await reports.startDate.click();
    const picker = page.locator('.datepicker-dropdown');
    await expect(picker).toBeVisible();
    await expect(picker.locator('td.day.disabled').first()).toBeAttached();
    await reports.startDate.press('Escape');

    await Promise.all([page.waitForURL(/\/reports\/reports$/), reports.generatedLink.click()]);
    await expectHealthyPage(page);
    await expect(new GeneratedReportsPage(page).heading).toBeVisible();
  });

  test('an end date before the start date finds no transactions', async ({ page }) => {
    const reports = new ReportsPage(page);
    await reports.goto();
    await reports.submit(dayOffset(0), dayOffset(-3));
    await expect(flashError(page)).toContainText(/No transactions found\.|You have a report that is processing/);
    await expect(flashSuccess(page)).toHaveCount(0);
  });

  test('a range with no orders → "No transactions found."', async ({ page }) => {
    const reports = new ReportsPage(page);
    await reports.goto();
    await reports.submit(dayOffset(10), dayOffset(20));
    const error = (await flashError(page).allTextContents()).join(' ');
    test.skip(/report that is processing/.test(error), 'a report of this user is still processing; the range is not checked then');
    await expect(flashError(page)).toContainText('No transactions found.');
    await expect(flashSuccess(page)).toHaveCount(0);
  });

  test('generated reports page lists files or says none', async ({ page }) => {
    const generated = new GeneratedReportsPage(page);
    await generated.goto();
    await expect(page.getByRole('link', { name: 'Back' })).toHaveAttribute('href', /\/reports$/);
    const files = await generated.reportActions();
    const processing = await generated.processing.count();
    if (files.length + processing === 0) await expect(generated.none).toBeVisible();
    else await expect(generated.none).toHaveCount(0);
  });

  test('generate a report for today and download it', async ({ page }) => {
    test.skip(!env.allowReports, 'set E2E_ALLOW_REPORTS=true to generate a report on the server');
    test.setTimeout(240_000);
    const generated = new GeneratedReportsPage(page);
    await generated.goto();
    const before = new Set(await generated.reportActions());

    const reports = new ReportsPage(page);
    await reports.goto();
    // Yesterday..tomorrow so a timezone difference between this PC and the server can't drop today's orders.
    await reports.submit(dayOffset(-1), dayOffset(1));
    const error = (await flashError(page).allTextContents()).join(' ');
    test.skip(/No transactions found/.test(error) && orders().length === 0, 'no orders today to report on');
    await expect(flashSuccess(page)).toContainText('We are generating the report.');

    // Generation runs in the background (bin/cake generate_report); wait for a new downloadable file.
    let action = '';
    await expect
      .poll(
        async () => {
          await generated.goto();
          action = (await generated.reportActions()).find((a) => !before.has(a)) ?? '';
          return action;
        },
        { timeout: 180_000, intervals: [3_000, 5_000, 10_000] , message: 'new report in /reports/reports' },
      )
      .not.toBe('');

    const download = await downloadFrom(page, generated.linkFor(action));
    expect(download.suggestedFilename()).toMatch(/\.csv$/);
    const csv = await downloadText(download);
    expect(csv.length).toBeGreaterThan(0);
    for (const order of orders()) {
      expect(csv, `report contains ${order.ref}`).toContain(order.ref);
    }
  });

  test('report download refuses path traversal', async ({ page }) => {
    await page.goto('/reports');
    const names = [
      '..',
      '..%2F..%2F..%2Fconfig%2Fapp_config',
      '..%2F..%2Fconfig%2Fapp_config.php',
      '%2e%2e%2f%2e%2e%2f%2e%2e%2fcomposer',
      '..%5C..%5C..%5Cconfig%5Capp_config',
      'nope-' + Date.now(),
    ];
    for (const name of names) {
      await test.step(name, async () => {
        const res = await page.request.post(`/reports/download/${name}`, {
          form: { _csrfToken: (await page.locator('input[name="_csrfToken"]').first().getAttribute('value')) ?? '' },
          maxRedirects: 0,
        });
        const body = await res.text();
        expect(res.status(), `${name} → ${res.status()}`).toBeGreaterThanOrEqual(400);
        expect(res.status()).toBeLessThan(500);
        expect(res.headers()['content-disposition'] ?? '', 'no file attachment').not.toMatch(/attachment/i);
        expect(body).not.toMatch(/Datasources|SECURITY_SALT|"require"\s*:/);
      });
    }
  });
});
