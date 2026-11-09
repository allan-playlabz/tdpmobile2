import { test, expect, isMaster } from './fixtures';
import { expectHealthyPage, flashError, flashSuccess } from '../../utils/checks';
import { env } from '../../utils/env';
import {
  TopupAddPage, TopupsPage, csvDataRows, downloadFrom, downloadText, fmtDate, openHealthy, tinyPng,
} from '../../pages/MenuPages';

const NOT_AUTHORIZED = 'You are not authorized to access that location.';

/** templates/AgentTopups/* + AgentTopupsController (master only). */
test.describe('Top Up (master)', () => {
  test.skip(!isMaster, 'needs E2E_USER_ROLE=master');

  test('list renders with columns, actions and the express top-up bank details', async ({ page }) => {
    const topups = new TopupsPage(page);
    await topups.goto();
    await expect(topups.heading).toBeVisible();
    await expect(page.getByText('*Only the last 6 months records will be shown')).toBeVisible();
    for (const h of ['Top Up Date', 'Amount(SGD)', 'Status', 'Actions']) {
      await expect(page.locator('.agentTopups thead th', { hasText: h })).toBeVisible();
    }
    for (const row of await topups.rows.all()) {
      await expect(row.locator('td').nth(0)).toHaveText(/^\d{2} [A-Z][a-z]{2} \d{4}$/);
      await expect(row.locator('td').nth(1)).toHaveText(/^-?[\d,]+\.\d{2}$/);
      await expect(row.locator('td').nth(2)).toHaveText(/^[A-Z]\S*( \S+)*$/); // ucwords(status)
      await expect(row.locator('a[href*="/agent-topups/view/"]')).toHaveText('View');
    }
    await expect(topups.addLink).toHaveAttribute('href', /\/agent-topups\/add$/);

    await topups.expressButton.click();
    await expect(topups.expressModal).toBeVisible();
    await expect(topups.expressModal.getByText('Your Bank Reference Code')).toBeVisible();
    const code = topups.expressModal.locator('#bankReferenceCode');
    if (await code.count()) {
      await expect(code).not.toHaveValue('');
      await expect(code).toHaveAttribute('readonly', '');
      await expect(topups.expressModal.locator('#copyReferenceBtn')).toBeVisible();
    } else {
      await expect(topups.expressModal.locator('.alert-warning')).toContainText('No bank reference code is assigned');
    }
    const bank = topups.expressModal.getByText('Bank Account Details');
    if (await bank.count()) {
      await expect(topups.expressModal.locator('table tbody tr').first()).toBeVisible();
    }
    await topups.expressModal.getByRole('button', { name: 'Close' }).click();
    await expect(topups.expressModal).toBeHidden();
  });

  test('a top-up view shows its amount, date and image', async ({ page }) => {
    const topups = new TopupsPage(page);
    await topups.goto();
    test.skip((await topups.viewLinks.count()) === 0, 'no top-ups in the last 6 months');
    const row = topups.rows.first();
    const date = ((await row.locator('td').nth(0).textContent()) ?? '').trim();
    await Promise.all([page.waitForURL(/\/agent-topups\/view\//), topups.viewLinks.first().click()]);
    await expectHealthyPage(page);
    await expect(page.getByRole('heading', { name: 'Top Up' })).toBeVisible();
    const value = (label: string) => page.locator('table tr', { has: page.locator('th', { hasText: label }) }).locator('td');
    await expect(value('Amount')).toHaveText(/\d+\.\d{2}/);
    await expect(value('Top Up Date')).toHaveText(date);
    const img = value('Image').locator('img');
    if (await img.count()) {
      const src = await img.getAttribute('src');
      expect(src).toContain('/agent-topups/topup-images/agenttopups/');
      const res = await page.request.get(src!);
      expect(res.status(), src!).toBe(200);
      expect(res.headers()['content-type']).toMatch(/^image\//);
    }
  });

  test('an unknown top-up id is not found', async ({ page }) => {
    const res = await page.goto('/agent-topups/view/00000000-0000-0000-0000-000000000000');
    expect(res?.status()).toBe(404);
  });

  test('top-up images of other agents / other paths are refused', async ({ page }) => {
    await page.goto('/agent-topups');
    for (const key of ['agenttopups/someone-else/x.png', 'agenttopups/../../etc/passwd', 'reports/x.csv']) {
      const res = await page.request.get(`/agent-topups/topup-images/${key}`);
      expect(res.status(), key).toBe(404);
    }
  });

  test('add form rejects a missing image', async ({ page }) => {
    const add = new TopupAddPage(page);
    await add.goto();
    await expect(page.getByText('All fields are required')).toBeVisible();
    await expect(add.image).toHaveAttribute('accept', 'image/*');
    await expect(add.remarks).toHaveAttribute('maxlength', '255');
    await add.amount.fill('1');
    await add.remarks.fill('E2E: no image, must be refused');
    await add.send();
    await expect(page).toHaveURL(/\/agent-topups\/add/);
    await expect(flashError(page)).toContainText(/Invalid file\. Please, try again\.|Please provide an image and try again\./);
    await expect(flashError(page)).toContainText('The agent top up could not be saved. Please, try again.');
    await expect(flashSuccess(page)).toHaveCount(0);
  });

  test('add form rejects a file that is not an image', async ({ page }) => {
    const add = new TopupAddPage(page);
    await add.goto();
    await add.amount.fill('1');
    // Named and typed like a PNG; the server checks the content with getimagesize().
    await add.image.setInputFiles({ name: 'proof.png', mimeType: 'image/png', buffer: Buffer.from('this is not an image\n') });
    await add.send();
    await expect(page).toHaveURL(/\/agent-topups\/add/);
    await expect(flashError(page)).toContainText('Invalid image.');
    await expect(flashSuccess(page)).toHaveCount(0);
  });

  test('add form needs an amount (browser validation)', async ({ page }) => {
    const add = new TopupAddPage(page);
    await add.goto();
    await add.submit.click();
    await expect(page).toHaveURL(/\/agent-topups\/add$/);
    expect(await add.amount.evaluate((el) => (el as HTMLInputElement).validity.valid)).toBe(false);
  });

  test('submit a top-up with a small PNG → listed as pending', async ({ page }) => {
    test.skip(!env.allowTopup, 'set E2E_ALLOW_TOPUP=true to submit a real top-up request');
    const add = new TopupAddPage(page);
    await add.goto();
    const remarks = `E2E automated test ${new Date().toISOString()} - please reject`;
    await add.amount.fill('1.00');
    await add.image.setInputFiles({ name: 'e2e-proof.png', mimeType: 'image/png', buffer: tinyPng() });
    await add.remarks.fill(remarks);
    await add.send();
    test.info().annotations.push({ type: 'topup', description: `1.00 SGD, remarks "${remarks}"` });
    await expect(page).toHaveURL(/\/agent-topups$/);
    await expect(flashSuccess(page)).toContainText('The agent top up has been saved.');

    const topups = new TopupsPage(page);
    const row = topups.rows.first(); // newest first
    await expect(row.locator('td').nth(0)).toHaveText(fmtDate(new Date()).replace(/^(\d) /, '0$1 '));
    await expect(row.locator('td').nth(1)).toHaveText('1.00');
    await expect(row.locator('td').nth(2)).toHaveText('Pending');

    await Promise.all([page.waitForURL(/\/agent-topups\/view\//), row.getByRole('link', { name: 'View' }).click()]);
    await expectHealthyPage(page);
    await expect(page.getByText(remarks)).toBeVisible();
  });

  test('CSV export matches the list', async ({ page }) => {
    const topups = new TopupsPage(page);
    await topups.goto();
    const info = await topups.paginator.info();
    if (info.count === 0) {
      await Promise.all([page.waitForEvent('load'), topups.download.click()]);
      await expect(flashError(page)).toContainText('No records found!');
      return;
    }
    const tableAmounts = (await topups.rows.locator('td:nth-child(2)').allTextContents()).map((s) => s.trim());
    const tableStatus = (await topups.rows.locator('td:nth-child(3)').allTextContents()).map((s) => s.trim());
    const download = await downloadFrom(page, topups.download);
    expect(download.suggestedFilename()).toMatch(/Top Up Report \d{12}\.csv$/);
    const csv = await downloadText(download);
    expect(csv).toContain('Top Up Report');
    const rows = csvDataRows(csv, 'Top Up Date');
    expect(rows.length).toBe(info.count);
    expect(rows.slice(0, tableAmounts.length).map((r) => r[1])).toEqual(tableAmounts);
    expect(rows.slice(0, tableStatus.length).map((r) => r[2])).toEqual(tableStatus);
  });
});

test.describe('Top Up (non-master)', () => {
  test.skip(isMaster, 'only for non-master accounts');

  for (const path of ['/agent-topups', '/agent-topups/add', '/agent-topups/view/00000000-0000-0000-0000-000000000000']) {
    test(`${path} is refused`, async ({ page }) => {
      const res = await page.goto(path);
      await expectHealthyPage(page, res);
      await expect(page).not.toHaveURL(/\/agent-topups/);
      await expect(flashError(page)).toContainText(NOT_AUTHORIZED);
      await openHealthy(page, '/agent-users/view');
    });
  }
});
