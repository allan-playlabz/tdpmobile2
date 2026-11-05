import { test, expect } from '@playwright/test';
import { clickWithDialog, expectHealthyPage, flashError, trackPageErrors } from '../../utils/checks';
import { env } from '../../utils/env';

test.describe('Orders, tickets and reports', () => {
  test.skip(env.user.role === 'viewer', 'viewers have no orders or reports');

  test('My Orders renders with filters', async ({ page }) => {
    const errors = trackPageErrors(page);
    const res = await page.goto('/orders');
    await expectHealthyPage(page, res);
    await expect(page.getByRole('heading', { name: 'My Orders' })).toBeVisible();
    for (const f of ['order_ref_id', 'tracking_no', 'status', 'date_type', 'from', 'to']) {
      await expect(page.locator(`[name="${f}"]`), f).toBeAttached();
    }
    expect(errors).toEqual([]);
  });

  test('My Orders filter by unknown reference returns no rows', async ({ page }) => {
    await page.goto('/orders');
    await page.locator('[name="order_ref_id"]').fill(`NOPE-${Date.now()}`);
    await page.getByRole('button', { name: 'Filter' }).click();
    await expect(page).toHaveURL(/order_ref_id=NOPE-/);
    await expectHealthyPage(page);
    await expect(page.locator('a[href*="/orders/view/"]')).toHaveCount(0);
  });

  test('first order opens its detail page', async ({ page }) => {
    await page.goto('/orders');
    const link = page.locator('a[href*="/orders/view/"]').first();
    test.skip((await link.count()) === 0, 'no orders yet');
    await link.click();
    await expect(page).toHaveURL(/\/orders\/view\//);
    await expectHealthyPage(page);
  });

  test('another agent\'s / unknown order is not shown', async ({ page }) => {
    const res = await page.goto('/orders/view/00000000-0000-0000-0000-000000000000');
    expect(res?.status()).toBeLessThan(500);
    await expect(page.locator('body')).not.toContainText('Grand Total');
  });

  test('My Tickets renders with filters', async ({ page }) => {
    const res = await page.goto('/orders/my-tickets');
    await expectHealthyPage(page, res);
    await expect(page.getByRole('heading', { name: 'My Tickets' })).toBeVisible();
    // The status filter is commented out in templates/Orders/my_tickets.php.
    for (const f of ['code', 'from', 'to']) {
      await expect(page.locator(`[name="${f}"]`), f).toBeAttached();
    }
  });

  test('My Tickets filter by unknown code', async ({ page }) => {
    await page.goto('/orders/my-tickets');
    await page.locator('[name="code"]').fill(`NOPE${Date.now()}`);
    await page.getByRole('button', { name: 'Filter' }).click();
    await expect(page).toHaveURL(/code=NOPE/);
    await expectHealthyPage(page);
  });

  test('Sales Reports page renders', async ({ page }) => {
    const res = await page.goto('/reports');
    await expectHealthyPage(page, res);
    await expect(page.getByRole('heading', { name: 'Sales Reports' })).toBeVisible();
    await expect(page.locator('[name="issue_start_date"]')).toBeAttached();
    await expect(page.locator('[name="issue_end_date"]')).toBeAttached();
    await expect(page.getByRole('button', { name: 'Generate Report' })).toBeVisible();
  });

  test('report download rejects path traversal', async ({ page }) => {
    const res = await page.goto('/reports/download/..%2F..%2Fconfig%2Fapp_config.php', { waitUntil: 'commit' });
    const body = (await res?.text()) ?? '';
    expect(body).not.toContain('Datasources');
    expect(res?.headers()['content-type'] ?? '').not.toMatch(/octet-stream|php/);
  });
});

test.describe('Master-only pages', () => {
  test.skip(env.user.role !== 'master', 'needs E2E_USER_ROLE=master');

  test('Top Up list and form render (not submitted)', async ({ page }) => {
    let res = await page.goto('/agent-topups');
    await expectHealthyPage(page, res);
    await expect(page.getByRole('heading', { name: 'Top Up' })).toBeVisible();

    res = await page.goto('/agent-topups/add');
    await expectHealthyPage(page, res);
    await expect(page.locator('[name="amount"]')).toBeVisible();
    await expect(page.locator('input[type="file"][name="image"]')).toBeAttached();
    await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
  });

  test('Balance Report renders when enabled', async ({ page }) => {
    const res = await page.goto('/credit-logs');
    await expectHealthyPage(page, res);
    const heading = page.getByRole('heading', { name: 'Balance Report' });
    test.skip((await heading.count()) === 0, 'credit log view not enabled for this agent');
    await expect(page.locator('[name="from"]')).toBeAttached();
    await expect(page.locator('[name="to"]')).toBeAttached();
  });

  test('Agent Users lists sub-users only (not the master)', async ({ page }) => {
    const res = await page.goto('/agent-users');
    await expectHealthyPage(page, res);
    await expect(page.getByRole('link', { name: /Add User/ })).toBeVisible();
    for (const h of ['Name', 'Email', 'Role', 'Status', 'Actions']) {
      await expect(page.locator('thead th', { hasText: h })).toBeVisible();
    }
    await expect(page.locator('tbody tr', { hasText: env.user.email })).toHaveCount(0);
    const roles = await page.locator('tbody tr td:nth-child(3)').allTextContents();
    for (const r of roles) expect(r.trim()).toMatch(/^(Normal|Viewer)$/);
    const firstEdit = page.locator('tbody tr a[href*="/agent-users/edit/"]').first();
    if (await firstEdit.count()) await expect(firstEdit).toBeVisible();
  });

  test('Agent Users sorting links work', async ({ page }) => {
    for (const col of ['name', 'email', 'role', 'status']) {
      const res = await page.goto(`/agent-users?sort=${col}&direction=asc`);
      await expectHealthyPage(page, res);
    }
  });

  test('Add User form renders', async ({ page }) => {
    const res = await page.goto('/agent-users/add');
    await expectHealthyPage(page, res);
    // No password fields: the temporary password is emailed to the new user.
    for (const f of ['name', 'email', 'role', 'status']) {
      await expect(page.locator(`[name="${f}"]`), f).toBeVisible();
    }
  });

  test('Reset Password asks for confirmation (dismissed, nothing sent)', async ({ page }) => {
    await page.goto('/agent-users');
    const btn = page.getByRole('link', { name: 'Reset Password' }).first();
    test.skip((await btn.count()) === 0, 'no users');
    // Dismissed: the onclick only submits the reset form when confirm() returns true.
    expect(await clickWithDialog(page, btn, 'dismiss')).toContain('Are you sure you want to reset password');
    await expect(page).toHaveURL(/\/agent-users$/);
  });
});

test.describe('Non-master is kept out of master pages', () => {
  test.skip(env.user.role === 'master', 'only for non-master accounts');

  test('Agent Users is refused', async ({ page }) => {
    await page.goto('/agent-users');
    await expect(page).not.toHaveURL(/\/agent-users$/);
    await expect(flashError(page)).toContainText('You are not authorized to access that location.');
  });
});
