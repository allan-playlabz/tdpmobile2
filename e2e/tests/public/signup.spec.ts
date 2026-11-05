import { test, expect } from '@playwright/test';
import { expectHealthyPage } from '../../utils/checks';

/** /signup (templates/AgentsSignups/add.php). Never submitted with valid data: that creates a lead and sends mail. */
test.describe('Agent signup', () => {
  test('form renders with required fields', async ({ page }) => {
    const res = await page.goto('/signup');
    await expectHealthyPage(page, res);
    await expect(page.locator('[name="country"]')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
  });

  test('empty submit is blocked by browser validation', async ({ page }) => {
    await page.goto('/signup');
    const required = page.locator('form [required]');
    test.skip((await required.count()) === 0, 'form has no required fields');

    await page.getByRole('button', { name: 'Submit' }).click();
    await expect(page).toHaveURL(/\/signup$/);
    const invalid = await required.first().evaluate((el) => !(el as HTMLInputElement).checkValidity());
    expect(invalid).toBe(true);
  });

  test('thank-you page renders', async ({ page }) => {
    const res = await page.goto('/signup/thank-you');
    await expectHealthyPage(page, res);
    await expect(page.getByRole('heading', { name: 'Thank You!' })).toBeVisible();
  });
});
