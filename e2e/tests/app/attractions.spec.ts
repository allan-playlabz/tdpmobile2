import { test, expect } from '@playwright/test';
import { AppShell } from '../../pages/AppShell';
import { AttractionPage } from '../../pages/BookingPages';
import { expectHealthyPage, trackPageErrors } from '../../utils/checks';
import { env } from '../../utils/env';

test.describe('Attractions', () => {
  test('listing shows attraction cards or an empty notice', async ({ page }) => {
    const res = await page.goto('/attractions');
    await expectHealthyPage(page, res);
    const cards = page.locator('a[href*="/attractions/view/"], a[href*="/attractions/two-way/"]');
    const empty = page.getByText('No attractions found.');
    await expect(cards.first().or(empty)).toBeVisible();
  });

  test('search for nonsense finds nothing, Show All resets', async ({ page }) => {
    await page.goto('/attractions');
    await page.locator('input[name="search"]').fill(`zzz-e2e-${Date.now()}`);
    await page.getByRole('button', { name: 'Search' }).click();
    await expect(page).toHaveURL(/search=zzz-e2e-/);
    await expect(page.getByText('No attractions found.')).toBeVisible();

    await page.getByRole('link', { name: 'Show All' }).click();
    await expect(page).not.toHaveURL(/search=/);
  });

  test('search keeps the term in the box', async ({ page }) => {
    await page.goto('/attractions?search=a');
    await expect(page.locator('input[name="search"]')).toHaveValue('a');
  });

  test('pagination counter is consistent', async ({ page }) => {
    await page.goto('/attractions');
    const counter = page.getByText(/Page \d+ of \d+, showing \d+ record\(s\) out of \d+ total/);
    test.skip((await counter.count()) === 0, 'no paginated results');
    const [, current, pages] = (await counter.textContent())!.match(/Page (\d+) of (\d+)/)!.map(Number);
    expect(current).toBeLessThanOrEqual(pages);
  });

  test('category filter checkbox narrows the list', async ({ page }) => {
    await page.goto('/attractions');
    const category = page.locator('input.headclass').first();
    test.skip((await category.count()) === 0, 'no categories');
    await category.check();
    await page.getByRole('button', { name: 'Search' }).click();
    await expect(page).toHaveURL(/categories/);
    await expectHealthyPage(page);
  });

  test('opening an attraction shows its detail page', async ({ page }) => {
    const errors = trackPageErrors(page);
    await page.goto('/attractions');
    const card = page.locator('a[href*="/attractions/view/"]').first();
    test.skip((await card.count()) === 0, 'no attractions listed');
    await card.click();

    await expect(page).toHaveURL(/\/attractions\/view\//);
    await expectHealthyPage(page);
    const attraction = new AttractionPage(page);
    await expect(attraction.title).not.toBeEmpty();
    await expect(page.getByText('Terms And Conditions')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Options' })).toBeVisible();
    // View Cart is only shown when the cart has something in it (templates/Attractions/view.php: $cart_count).
    const viewCart = page.getByRole('link', { name: 'View Cart' });
    if (await new AppShell(page).cartBadge.count()) await expect(viewCart).toBeVisible();
    else await expect(viewCart).toHaveCount(0);
    expect(errors).toEqual([]);
  });

  test('ticket list loads over AJAX for a dated attraction', async ({ page }) => {
    // Settlement attractions render their tickets with the page; only dated ones (fdate/fdatetime)
    // load them from /attractions/get-tickets/{id} once a date (and slot) is picked.
    const candidates = [env.attractions.fdate, env.attractions.fdatetime].filter(Boolean);
    test.skip(candidates.length === 0, 'set E2E_ATTR_FDATE or E2E_ATTR_FDATETIME (settlement attractions load no tickets over AJAX)');
    const attraction = new AttractionPage(page);
    // An attraction the logged-in agent isn't assigned to answers 404 ("Attraction not found.").
    let id = '';
    for (const candidate of candidates) {
      const res = await page.goto(`/attractions/view/${candidate}`);
      if (res?.status() === 404) continue;
      await expectHealthyPage(page, res);
      await expect(attraction.form).toBeVisible();
      id = candidate;
      break;
    }
    test.skip(!id, `none of E2E_ATTR_FDATE / E2E_ATTR_FDATETIME is available to ${env.user.email} (404)`);
    const tickets = page.waitForResponse((r) => r.url().includes(`/attractions/get-tickets/${id}`) && r.ok(), { timeout: 60_000 });
    const when = await attraction.chooseDateAndTime(0, 0);
    test.skip(!when, 'no bookable date/slot');
    const body = await (await tickets).json();
    expect(body).toHaveProperty('data');
    await expect(attraction.quantities.first().or(attraction.noTickets).first()).toBeVisible();
  });

  test('settlement attraction lists its tickets without picking a date', async ({ page }) => {
    test.skip(!env.attractions.settlement, 'set E2E_ATTR_SETTLEMENT');
    const attraction = new AttractionPage(page);
    await expectHealthyPage(page, await attraction.goto(env.attractions.settlement));
    await expect(attraction.date).toBeHidden();
    await expect(attraction.quantities.first()).toBeVisible();
  });

  test('favourite toggle round-trips', async ({ page }) => {
    test.skip(env.user.role === 'viewer', 'viewers have no favourites');
    test.skip(!env.attractionId, 'set E2E_ATTRACTION_ID');
    await new AttractionPage(page).goto(env.attractionId);
    const star = page.locator('.btn-favorite');
    const wasFavourite = !(await star.evaluate((el) => el.classList.contains('fa-bookmark-o')));
    const off = /fa-bookmark-o/;

    const toggle = () => Promise.all([
      page.waitForResponse((r) => r.url().includes('/attractions/toggle-favorite')),
      star.click(),
    ]);
    const [res] = await toggle();
    expect(res.status()).toBeLessThan(400);
    if (wasFavourite) await expect(star).toHaveClass(off);
    else await expect(star).not.toHaveClass(off);

    await toggle(); // restore
    if (wasFavourite) await expect(star).not.toHaveClass(off);
    else await expect(star).toHaveClass(off);
  });

  test('unknown attraction id does not crash', async ({ page }) => {
    const res = await page.goto('/attractions/view/00000000-0000-0000-0000-000000000000');
    expect(res?.status()).toBeLessThan(500);
  });
});
