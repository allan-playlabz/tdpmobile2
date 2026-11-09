import fs from 'node:fs';
import { expect, test, type Browser, type BrowserContext, type Page } from '@playwright/test';
import { AppShell } from '../pages/AppShell';
import {
  AttractionPage,
  CartPage,
  CheckoutPage,
  ConfirmationPage,
  parseMoney,
  TWO_WAY_IN_CART,
  type PricingType,
  type SelectedTicket,
} from '../pages/BookingPages';
import { LoginPage, type Credentials } from '../pages/LoginPage';
import { ThankYouPage } from '../pages/OrderPages';
import { expectHealthyPage, flashError, flashSuccess } from './checks';
import { env, USER_STATE } from './env';
import { placedOrders, recordOrder, type PlacedOrder } from './orders';

/** Shared steps for tests/ordering/*. */

export interface AddedTicket extends SelectedTicket {
  attractionId: string;
  type: PricingType;
  /** Picked date ("5 Nov 2026"), '' for settlement. */
  date: string;
  /** Picked slot (H:i), '' unless fdatetime. */
  time: string;
  /** Session cart key: date, "date time"; '' for settlement (the app keys it on today's date). */
  cartKey: string;
}

export interface Customer {
  firstName: string;
  lastName: string;
  email: string;
  mobile: string;
}

export const defaultCustomer = (email = env.user.email): Customer => ({
  firstName: 'E2E',
  lastName: 'Playwright',
  email: email || 'e2e@example.com',
  mobile: '+6512345678',
});

/** Unique "Order Tracking Number" so the orders a run creates can be found again. */
export const newTrackingNo = (): string => `E2E-${Date.now()}-${Math.floor(Math.random() * 1000)}`;

/** Hosts the online gateways redirect to, by provider name from Configure Payment.providers. */
export const GATEWAY_HOSTS: Record<string, RegExp> = {
  '2C2P': /(^|\.)2c2p\.com$/i,
  Stripe: /^checkout\.stripe\.com$/i,
  Paypal: /(^|\.)paypal\.com$/i,
};

export const appHost = (): string => new URL(env.baseUrl).hostname;

/** Runs `fn` with a page of a fresh context logged in as the main account (for beforeAll/afterAll). */
export async function withUserPage(browser: Browser, fn: (page: Page) => Promise<void>): Promise<void> {
  const context = await browser.newContext({ baseURL: env.baseUrl, storageState: USER_STATE });
  try {
    await fn(await context.newPage());
  } finally {
    await context.close();
  }
}

/** Logs `creds` in through the login page in a new context (never the shared main session). */
export async function loginInNewContext(browser: Browser, creds: Credentials): Promise<{ context: BrowserContext; page: Page }> {
  const context = await browser.newContext({ baseURL: env.baseUrl });
  const page = await context.newPage();
  await new LoginPage(page).login(creds);
  return { context, page };
}

/** Cart lines (ticket id + session date key) of the two-way trips bookTwoWay() put in the cart in this worker. */
const bookedTwoWayLegs: { ticketId: string; date: string }[] = [];

/**
 * Removes one cart line the way the cart's × button does: POST /orders/remove-ticket/{ticket}/{date}
 * (OrdersController::removeTicket), with the CSRF token of a form on the current page (_csrfToken),
 * as the × button's postLink sends it. Call it on a page with a form (e.g. the checkout).
 */
export async function removeCartLine(page: Page, ticketId: string, date: string): Promise<void> {
  const tokenField = page.locator('input[name="_csrfToken"]').first();
  if (!(await tokenField.count())) await page.goto('/orders/checkout');
  const token = await tokenField.inputValue();
  const response = await page.request.post(`/orders/remove-ticket/${encodeURIComponent(ticketId)}/${encodeURIComponent(date)}`, {
    form: { _csrfToken: token },
    headers: { 'X-CSRF-Token': token },
    maxRedirects: 0,
  });
  expect(response.status(), `remove ${ticketId} / ${date} from the cart`).toBeLessThan(400);
}

/** Ticket id + date key of the lines on the checkout page, read from booking-question names (question_group[f][ticket][date][n]). */
async function checkoutLinesFromQuestions(page: Page): Promise<{ ticketId: string; date: string }[]> {
  const names = await page.locator('[name^="question_group["]').evaluateAll((els) => els.map((el) => (el as HTMLInputElement).name));
  return names
    .map((n) => n.match(/\[([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})\]\[([^\]]+)\]\[\d+\]$/i))
    .filter((m): m is RegExpMatchArray => !!m)
    .map((m) => ({ ticketId: m[1], date: m[2] }));
}

/**
 * Empties the session cart. Viewers have no cart (OrdersController::beforeFilter sends them home),
 * so it does nothing for them. A two-way pair in the cart can't be removed there (/orders/cart
 * forwards it to the checkout and has no × buttons), so its legs are removed by POSTing
 * remove-ticket for each: legs bookTwoWay() booked, plus any line named in the checkout's booking
 * questions. Last resort: add the settlement ticket (AttractionsController::view() may then clear
 * the cart), which the supplier rules can refuse.
 */
export async function clearCart(page: Page, opts: { viewer?: boolean } = {}): Promise<void> {
  if (opts.viewer ?? env.user.role === 'viewer') return;
  const cart = new CartPage(page);
  try {
    await cart.clear();
    bookedTwoWayLegs.length = 0;
    return;
  } catch (e) {
    if (!String(e).includes(TWO_WAY_IN_CART)) throw e;
  }
  // On the checkout page now (the cart forwarded there).
  const legs = [...bookedTwoWayLegs, ...(await checkoutLinesFromQuestions(page))];
  const seen = new Set<string>();
  for (const leg of legs) {
    const key = `${leg.ticketId}|${leg.date}`;
    if (seen.has(key)) continue;
    seen.add(key);
    await removeCartLine(page, leg.ticketId, leg.date);
  }
  try {
    await cart.clear();
    bookedTwoWayLegs.length = 0;
    return;
  } catch (e) {
    if (!String(e).includes(TWO_WAY_IN_CART)) throw e;
    if (!env.attractions.settlement) {
      throw new Error(`${String(e)}. Set E2E_ATTR_SETTLEMENT so the suite can push the two-way ticket out of the cart.`);
    }
  }
  await addTicket(page, env.attractions.settlement, { skipIfUnavailable: false });
  await cart.clear();
  bookedTwoWayLegs.length = 0;
}

/**
 * Opens a (non two-way) attraction, picks the date/slot by index when it has one, raises the
 * first available ticket (or `ticketId`) to `qty` and submits Add to Cart (or Buy Now). Skips the test when the
 * attraction has no bookable date/ticket or the app refuses the ticket (supplier mix, stock),
 * unless `skipIfUnavailable` is false (then it throws).
 */
export async function addTicket(
  page: Page,
  attractionId: string,
  opts: { qty?: number; dateIndex?: number; timeIndex?: number; via?: 'add' | 'buy'; skipIfUnavailable?: boolean; ticketId?: string } = {},
): Promise<AddedTicket> {
  const unavailable = (reason: string): never => {
    if (opts.skipIfUnavailable ?? true) test.skip(true, reason);
    throw new Error(reason);
  };
  const attraction = new AttractionPage(page);
  const response = await page.goto(`/attractions/view/${attractionId}`);
  if (response?.status() === 404) unavailable(`attraction ${attractionId} is not available to this account (404)`);
  await expect(attraction.form).toBeVisible();
  await expectHealthyPage(page, response);
  if (!(await attraction.addToCart.count())) unavailable('no Add to Cart button (viewer, or tickets of another merchant in the cart)');

  const type = await attraction.pricingType();
  const when = await attraction.chooseDateAndTime(opts.dateIndex ?? 0, opts.timeIndex ?? 0);
  if (!when) unavailable(`attraction ${attractionId} has no bookable date/slot #${(opts.dateIndex ?? 0) + 1}`);
  const selected = await attraction.selectTicket(opts.qty ?? 1, undefined, opts.ticketId);
  if (!selected) {
    const what = opts.ticketId ? `ticket ${opts.ticketId} can't be added` : 'lists no ticket that can be added';
    unavailable(`attraction ${attractionId} ${what}${when!.date ? ` on ${when!.date}` : ''}`);
  }

  const final = await attraction.submit(opts.via === 'buy' ? 'buy' : 'add');
  if (attraction.lastAlerts.length) throw new Error(`the page blocked the submit: ${attraction.lastAlerts.join(' / ')}`);
  await expectHealthyPage(page, final);
  const error = (await flashError(page).allTextContents()).join(' ').trim();
  if (error) unavailable(`the app refused the ticket: ${error}`);
  if (opts.via === 'buy') {
    await expect(page).toHaveURL(/\/orders\/cart/);
  } else {
    await expect(flashSuccess(page)).toContainText('Successfully added');
  }
  const date = when!.date;
  const time = when!.time;
  return {
    ...selected!,
    attractionId,
    type,
    date,
    time,
    cartKey: type === 'settlement' ? '' : time ? `${date} ${time}` : date,
  };
}

/**
 * Two-way: picks departure + return (by index), raises the first depart ticket (the return leg
 * follows automatically) and submits Buy Now (the only button; it clears the cart and goes to
 * checkout). Returns both legs, or skips when there is nothing to book.
 */
export async function bookTwoWay(
  page: Page,
  attractionId: string,
  opts: { qty?: number; departIndex?: number; returnIndex?: number; departTimeIndex?: number; returnTimeIndex?: number; ticketId?: string } = {},
) {
  const attraction = new AttractionPage(page);
  const response = await attraction.gotoTwoWay(attractionId);
  await expectHealthyPage(page, response);
  test.skip(!(await attraction.departureDate.count()), `attraction ${attractionId} is not a two-way attraction`);
  const when = await attraction.chooseTwoWay(opts);
  test.skip(!when, 'no departure/return date + slot to pick');
  const depart = await attraction.selectTicket(opts.qty ?? 1, attraction.departTickets, opts.ticketId);
  test.skip(!depart, opts.ticketId ? `depart ticket ${opts.ticketId} can't be added for the picked trip` : 'no depart ticket can be added for the picked trip');
  // The return leg's input is readonly and mirrors the depart quantity (setInputValue in view_two_way.php).
  const ret =await attraction.returnTickets.locator('input[name^="quantity["]').evaluateAll((els) =>
    els
      .map((el) => el as HTMLInputElement)
      .filter((el) => Number(el.value) > 0)
      .map((el) => ({
        ticketId: el.name.replace(/^quantity\[/, '').replace(/\].*$/, ''),
        price: Number(el.dataset.price),
        qty: Number(el.value),
        name: (el.closest('.ticket-option')?.querySelector('.ticket-name')?.textContent ?? '').trim(),
      })),
  );
  // Remembered so clearCart() can remove the legs if the trip ends up in the cart (Buy Now).
  bookedTwoWayLegs.push({ ticketId: depart!.ticketId, date: `${when!.departDate} ${when!.departTime}` });
  if (ret[0]) bookedTwoWayLegs.push({ ticketId: ret[0].ticketId, date: `${when!.returnDate} ${when!.returnTime}` });
  return { attraction, when: when!, depart: depart!, returnLeg: ret[0] ?? null };
}

/** Status + ref of the order in the session (`order_id`), from /orders/get-status; null when unavailable. */
export async function sessionOrder(page: Page): Promise<{ status: string; order_ref_id: string } | null> {
  const response = await page.request.get('/orders/get-status').catch(() => null);
  if (!response || !response.ok()) return null;
  const json = await response.json().catch(() => null);
  return json?.data?.order ?? null;
}

/**
 * Checkout creates an (unpaid) order through the backend API, so every checkout → confirmation
 * is recorded. paidWith 'unpaid' tells these apart from paid ones.
 */
export async function recordCheckoutOrder(page: Page, tracking: string, extra: Partial<PlacedOrder> = {}): Promise<string | null> {
  const order = await sessionOrder(page);
  recordOrder({ ref: order?.order_ref_id ?? `unknown (tracking ${tracking})`, paidWith: 'unpaid', ...extra });
  return order?.order_ref_id ?? null;
}

/**
 * Cart (or straight /orders/checkout) → fill customer details + ticket details (traveller fields and
 * booking questions such as ferry passenger info; off with fillQuestions: false) → Continue →
 * confirmation. Records the order checkout created. Fails when a required field is still empty
 * after filling (skips instead when fillQuestions is false).
 */
export async function checkoutToConfirmation(
  page: Page,
  opts: { viaCart?: boolean; tracking?: string; customer?: Customer; fillQuestions?: boolean; attraction?: string } = {},
): Promise<{ tracking: string; ref: string | null }> {
  const tracking = opts.tracking ?? newTrackingNo();
  if (opts.viaCart ?? true) {
    const cart = new CartPage(page);
    await expectHealthyPage(page, await cart.goto());
    await cart.submit();
  } else {
    await new CheckoutPage(page).goto();
  }
  await expect(page).toHaveURL(/\/orders\/checkout/);
  await expectHealthyPage(page);
  const checkout = new CheckoutPage(page);
  await checkout.fillCustomer(opts.customer ?? defaultCustomer());
  await checkout.trackingNo.fill(tracking);
  // Ticket details: traveller fields and booking questions (e.g. ferry passenger info). Filled
  // unless a test passes fillQuestions: false to see them left empty.
  if (opts.fillQuestions ?? true) {
    await checkout.fillTravellers();
    await checkout.fillQuestions();
  }
  const missing = await checkout.emptyRequiredFields();
  if (opts.fillQuestions ?? true) {
    expect(missing, 'required ticket details left empty after filling them (new field type?)').toEqual([]);
  } else {
    test.skip(missing.length > 0, `checkout needs details this suite doesn't fill: ${missing.join(', ')}`);
  }
  const response = await checkout.submit();
  if (!/\/orders\/confirmation/.test(page.url())) {
    const error = (await flashError(page).allTextContents()).join(' ').trim();
    throw new Error(`checkout did not reach confirmation (${page.url()}): ${error || 'no error message'}`);
  }
  await expectHealthyPage(page, response);
  const ref = await recordCheckoutOrder(page, tracking, { attraction: opts.attraction });
  return { tracking, ref };
}

/**
 * On the confirmation page: picks Credits, clicks Pay Now and waits for the thank-you page.
 * Records the paid order (paidWith "credits") and returns its ref and the amount paid. Skips when
 * credits are not offered or the balance is below the total. Places a REAL order.
 */
export async function payWithCredits(page: Page, opts: { attraction?: string } = {}): Promise<{ ref: string; total: number }> {
  const confirmation = new ConfirmationPage(page);
  await expect(confirmation.heading).toBeVisible();
  test.skip((await confirmation.payByCredits.count()) === 0, 'credits are not enabled as a payment method for this agent');
  test.skip(await confirmation.payByCredits.isDisabled(), 'the main account has fewer credits than the order total');
  await confirmation.choose('credits');
  const total = await confirmation.grandTotalValue();
  expect(total, 'credits carry no convenience fee').toBeCloseTo(await confirmation.subtotalValue(), 2);

  // Read the ref from the thank-you response: while the order is processing, the page polls
  // /orders/get-status and may move on by itself.
  const final = await confirmation.payNowAndWait(180_000);
  if (!/\/orders\/thank-you/.test(final?.url() ?? page.url())) {
    const flash = (await flashError(page).allTextContents()).join(' ').trim();
    throw new Error(`paying with credits did not reach thank-you (${page.url()}): ${flash || 'no error message'}`);
  }
  const html = await final!.text().catch(() => page.content());
  expect(html).toContain('Thank you for your purchase!');
  expect(html).not.toContain('There is an error processing this order');
  const ref = ThankYouPage.refFromHtml(html);
  expect(ref, 'thank-you page shows the order ref').toBeTruthy();
  recordOrder({ ref: ref!, paidWith: 'credits', total: total.toFixed(2), attraction: opts.attraction });
  await expectHealthyPage(page);
  return { ref: ref!, total };
}

/** Agent credits shown in the navbar ("Credits: SGD 1,000.00"). */
export async function readCredits(page: Page): Promise<number> {
  const credits = new AppShell(page).credits;
  await expect(credits).toBeVisible();
  return parseMoney(await credits.textContent());
}

/** Number shown in the navbar cart badge (0 when there is none). */
export async function cartBadgeCount(page: Page): Promise<number> {
  const badge = new AppShell(page).cartBadge;
  if (!(await badge.count())) return 0;
  return Number((await badge.textContent())?.trim() || '0');
}

/** The latest order this run paid with credits (pay-credits.spec.ts), if any. */
export function latestCreditsOrder(): PlacedOrder | undefined {
  return placedOrders()
    .filter((o) => o.paidWith === 'credits')
    .at(-1);
}

/** True when the bytes are a PDF. */
export const isPdf = (body: Buffer): boolean => body.subarray(0, 4).toString('latin1') === '%PDF';

export const readFileHead = (file: string): Buffer => {
  const fd = fs.openSync(file, 'r');
  try {
    const buf = Buffer.alloc(8);
    fs.readSync(fd, buf, 0, 8, 0);
    return buf;
  } finally {
    fs.closeSync(fd);
  }
};

/** Credentials of one of the optional extra accounts (env.normalUser, env.lowCredit). */
export const credsOf = (a: { email: string; password: string; totpSecret: string }): Credentials => ({
  email: a.email,
  password: a.password,
  totpSecret: a.totpSecret || undefined,
});
