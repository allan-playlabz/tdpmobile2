import { expect, type Dialog, type Locator, type Page, type Response } from '@playwright/test';

/** Last number in a text like "Total: SGD 1,234.50" or "Credits: $12.00 LOW" (NaN when there is none). */
export function parseMoney(text: string | null | undefined): number {
  const matches = (text ?? '').replace(/ /g, ' ').match(/-?\d[\d,]*(?:\.\d+)?/g);
  if (!matches) return NaN;
  return Number(matches[matches.length - 1].replace(/,/g, ''));
}

/** Regex matching an amount as a gateway may print it ("1234.50" or "1,234.50"). */
export function amountPattern(amount: number): RegExp {
  const [int, dec] = amount.toFixed(2).split('.');
  const grouped = int.replace(/\B(?=(\d{3})+(?!\d))/g, ',?');
  return new RegExp(`${grouped}\\.${dec}`);
}

const MONTHS = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];

/** A date as the app's datepickers write it ("d M yyyy", e.g. "5 Nov 2026"). */
export function displayDate(d: Date): string {
  const month = MONTHS[d.getMonth()];
  return `${d.getDate()} ${month[0].toUpperCase()}${month.slice(1)} ${d.getFullYear()}`;
}

/** Today shifted by whole years, months and days (negative = past), as displayDate(). */
export function dateFromToday(shift: { years?: number; months?: number; days?: number }): string {
  const d = new Date();
  d.setFullYear(d.getFullYear() + (shift.years ?? 0), d.getMonth() + (shift.months ?? 0), d.getDate() + (shift.days ?? 0));
  return displayDate(d);
}

/** True for an input bootstrap-datepicker is attached to (class date-picker / dob / passport-expiry ...). */
export async function isDatePicker(field: Locator): Promise<boolean> {
  return field.evaluate((el) => {
    const $ = (window as any).jQuery;
    return !!($ && $(el).data('datepicker')) || /\b(date-picker|datepicker|dob|passport-expiry)\b/.test(el.className);
  });
}

/**
 * Fills a bootstrap-datepicker input so the value sticks. fill() alone only sets the text: the
 * plugin's own date stays empty, and when the picker closes (Escape, blur, autoclose) it writes
 * that empty date back and wipes the field. So the plugin is told the date too ('update'), then
 * closed, and the field must still hold the value (a date outside the picker's start/end range
 * would be dropped here).
 */
export async function fillDatePicker(field: Locator, value: string): Promise<void> {
  await field.fill(value);
  await field.evaluate((el, v) => {
    const $ = (window as any).jQuery;
    if ($ && $(el).data('datepicker')) {
      $(el).datepicker('update', v);
      $(el).datepicker('hide');
    }
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }, value);
  await expect(field, `date field ${await field.getAttribute('name')} keeps ${value}`).toHaveValue(value);
}

/** Passenger date of birth: 25 years ago, so the passenger is always an adult (18+). */
export const adultDateOfBirth = (): string => dateFromToday({ years: -25 });

/**
 * Passport expiry: more than 1 year ahead (1 year + 1 month from today), so it is still valid a
 * year after the travel date chosen in the next weeks.
 */
export const passportExpiry = (): string => dateFromToday({ years: 1, months: 1 });

/** "5 Nov 2026" / "05 Nov 2026" / "5 Nov 2026 10:00" → "2026-11-05" ('' when it doesn't start with a d M Y date). */
export function isoDate(display: string | null | undefined): string {
  const m = (display ?? '').trim().match(/^(\d{1,2})\s+([A-Za-z]{3})[A-Za-z]*\s+(\d{4})/);
  if (!m) return '';
  const month = MONTHS.indexOf(m[2].toLowerCase()) + 1;
  if (month < 1) return '';
  return `${m[3]}-${String(month).padStart(2, '0')}-${m[1].padStart(2, '0')}`;
}

/**
 * Runs `action` (a click that submits a form or follows a link) and waits until the main frame
 * committed the resulting document and it loaded. Returns the final (non-redirect) response.
 */
export async function navigateBy(page: Page, action: () => Promise<unknown>, timeout = 60_000): Promise<Response | null> {
  const final = page
    .waitForResponse(
      (r) => r.request().isNavigationRequest() && r.frame() === page.mainFrame() && (r.status() < 300 || r.status() >= 400),
      { timeout },
    )
    .catch(() => null);
  await Promise.all([page.waitForEvent('framenavigated', { predicate: (f) => f === page.mainFrame(), timeout }), action()]);
  await page.waitForLoadState('load');
  return final;
}

/** One ticket as it was selected on the attraction page. */
export interface SelectedTicket {
  ticketId: string;
  name: string;
  /** data-price of the quantity input (the agent's unit price). */
  price: number;
  qty: number;
}

/** A ticket card in a `#tickets-sidebar` (checkout, confirmation, order detail). */
export interface SidebarLine {
  title: string;
  name: string;
  unitPrice: number;
  /** One row per date: the date/time text shown (may be empty), quantity, line total. */
  rows: { date: string; qty: number; total: number }[];
}

/** Reads the `#tickets-sidebar` ticket cards plus Subtotal / Convenience Fee / Grand Total. */
export async function readSidebar(page: Page): Promise<{ lines: SidebarLine[]; subtotal: number; fee: number; grandTotal: number }> {
  const raw = await page.locator('#tickets-sidebar').first().evaluate((root) => {
    const text = (el: Element | null | undefined) => (el?.textContent ?? '').replace(/\s+/g, ' ').trim();
    const lines = Array.from(root.querySelectorAll('.card')).map((card) => {
      const body = card.querySelector('.card-body') ?? card;
      const children = Array.from(body.children);
      const muted = card.querySelector('.text-muted') as HTMLElement | null;
      return {
        title: text(card.querySelector('.fw-bold')),
        name: (muted?.innerText ?? '').split('\n')[0].trim(),
        unitPrice: text(children[1]),
        rows: Array.from(card.querySelectorAll('.row.py-2')).map((row) => ({
          date: text(row.querySelector('.col-md-6')),
          qty: text(row.querySelector('.col-md-2')),
          total: text(row.querySelector('.col-md-4')),
        })),
      };
    });
    const footerRows = Array.from(root.querySelectorAll('.footer-total .row')).map((row) => ({
      label: text(row.querySelector('.col')),
      value: text(row.querySelector('.col.text-end')),
    }));
    return { lines, footerRows };
  });
  const footer = (label: RegExp) => parseMoney(raw.footerRows.find((r) => label.test(r.label))?.value);
  // The order detail page prints the "Convenience Fee" label with an empty value when there is no fee (credits).
  const fee = footer(/Convenience Fee/i);
  return {
    lines: raw.lines.map((l) => ({
      title: l.title,
      name: l.name,
      unitPrice: parseMoney(l.unitPrice),
      rows: l.rows.map((r) => ({ date: r.date, qty: parseMoney(r.qty), total: parseMoney(r.total) })),
    })),
    subtotal: footer(/Subtotal/i),
    fee: Number.isNaN(fee) ? 0 : fee,
    grandTotal: footer(/Grand Total/i),
  };
}

/**
 * Value for a passenger-type booking question, chosen from its field name (question_group[<field>]...)
 * and label ("[1] Passport No (Alphanumeric only)"); null when it is not one of these.
 */
export function passengerAnswer(name: string, label: string): string | null {
  const field = name.match(/^question_group\[([^\]]+)\]/)?.[1] ?? name;
  const key = `${field} ${label.replace(/^\[\d+\]\s*/, '')}`.toLowerCase();
  if (/expir/.test(key)) return passportExpiry();
  if (/passport|document.?no|id.?no|nric/.test(key)) return 'E1234567';
  if (/birth|\bdob\b/.test(key)) return adultDateOfBirth();
  // Letters only: carriers (e.g. the BRF ferry) may refuse digits in a passenger name.
  if (/first.?name|given.?name/.test(key)) return 'Tester';
  if (/last.?name|surname|family.?name/.test(key)) return 'Playwright';
  if (/e-?mail/.test(key)) return 'e2e@example.com';
  if (/phone|mobile|contact.?(no|number)/.test(key)) return '6512345678';
  return null;
}

/** Thrown by CartPage.clear() when the cart holds a two-way pair (the cart page forwards to the checkout). */
export const TWO_WAY_IN_CART = 'the cart holds a two-way trip (/orders/cart forwards to the checkout)';

export type PricingType = 'settlement' | 'fdate' | 'fdatetime' | 'two_way_fdatetime';

/**
 * /attractions/view/{id} (templates/Attractions/view.php) and /attractions/two-way/{id}
 * (view_two_way.php). Pickers are bootstrap-datepicker 1.6 with format "d M yyyy" and only the
 * agent's fixed dates enabled. settlement: no date; fdate: #date; fdatetime: #date + #time;
 * two-way: #departure-date/#departure-time + #return-date/#return-time.
 */
export class AttractionPage {
  readonly title: Locator;
  readonly form: Locator;
  readonly date: Locator;
  readonly time: Locator;
  readonly quantities: Locator;
  readonly addToCart: Locator;
  readonly buyNow: Locator;
  readonly viewCart: Locator;
  /** Total under the ticket list ("Total: SGD0.00"). */
  readonly formTotal: Locator;
  /** "No tickets found!" shown when a date/slot has no tickets. */
  readonly noTickets: Locator;
  readonly departureDate: Locator;
  readonly departureTime: Locator;
  readonly returnDate: Locator;
  readonly returnTime: Locator;
  readonly departTickets: Locator;
  readonly returnTickets: Locator;
  /** The open bootstrap-datepicker dropdown. */
  readonly picker: Locator;

  constructor(private readonly page: Page) {
    this.title = page.locator('h1.mt-4').first();
    this.form = page.locator('form.application-form');
    this.date = page.locator('#date');
    this.time = page.locator('#time');
    this.quantities = this.form.locator('input[name^="quantity["]');
    this.addToCart = page.locator('#btn-add-to-cart');
    this.buyNow = page.locator('#btn-buy-now');
    this.viewCart = this.form.locator('a.view-cart', { hasText: 'View Cart' });
    this.formTotal = this.form.locator('.total').first();
    this.noTickets = this.form.locator('.tickets .alert-danger');
    this.departureDate = page.locator('#departure-date');
    this.departureTime = page.locator('#departure-time');
    this.returnDate = page.locator('#return-date');
    this.returnTime = page.locator('#return-time');
    this.departTickets = this.form.locator('.depart-tickets');
    this.returnTickets = this.form.locator('.return-tickets');
    this.picker = page.locator('.datepicker-dropdown').filter({ visible: true });
  }

  async goto(id: string): Promise<Response | null> {
    const response = await this.page.goto(`/attractions/view/${id}`);
    await expect(this.form).toBeVisible();
    return response;
  }

  async gotoTwoWay(id: string): Promise<Response | null> {
    const response = await this.page.goto(`/attractions/two-way/${id}`);
    await expect(this.form).toBeVisible();
    return response;
  }

  /** Pricing type, from the controls the template rendered. */
  async pricingType(): Promise<PricingType> {
    if (await this.departureDate.count()) return 'two_way_fdatetime';
    if (await this.time.count()) return 'fdatetime';
    if (await this.date.count()) return 'fdate';
    return 'settlement';
  }

  private enabledDays(): Locator {
    return this.picker.locator('.datepicker-days td.day:not(.disabled):not(.old):not(.new)');
  }

  async closePicker(): Promise<void> {
    if (await this.picker.count()) await this.page.keyboard.press('Escape');
  }

  /**
   * Opens the picker of `input` and clicks the `index`-th enabled day (counting forward over up
   * to 12 months). Returns the value ("5 Nov 2026"), or null when there aren't that many enabled
   * days. `waitFor` is a URL part of the AJAX call the change triggers; it is awaited.
   */
  async pickAvailableDate(input: Locator, index = 0, waitFor?: string): Promise<string | null> {
    await input.click();
    await expect(this.picker).toBeVisible();
    let skipped = 0;
    for (let month = 0; month < 12; month++) {
      const days = this.enabledDays();
      const count = await days.count();
      if (skipped + count > index) {
        const day = days.nth(index - skipped);
        if (waitFor) {
          await Promise.all([this.page.waitForResponse((r) => r.url().includes(waitFor)), day.click()]);
        } else {
          await day.click();
        }
        await expect(input).not.toHaveValue('');
        await this.closePicker();
        return input.inputValue();
      }
      skipped += count;
      await this.picker.locator('.datepicker-days th.next').click();
    }
    await this.closePicker();
    return null;
  }

  /**
   * Clicks a day after today that the picker shows disabled (no fixed date for this agent).
   * Returns its label ("12 Nov 2026"), or null when every future day of the next 3 months is
   * bookable. Leaves the picker as the click left it.
   */
  async clickUnavailableFutureDay(input: Locator): Promise<string | null> {
    await input.click();
    await expect(this.picker).toBeVisible();
    for (let month = 0; month < 3; month++) {
      const found = await this.picker.locator('.datepicker-days').evaluate((days) => {
        const switchText = (days.querySelector('.datepicker-switch')?.textContent ?? '').trim();
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const cells = Array.from(days.querySelectorAll('td.day'));
        for (let i = 0; i < cells.length; i++) {
          const cl = cells[i].classList;
          if (!cl.contains('disabled') || cl.contains('old') || cl.contains('new')) continue;
          const d = new Date(`${(cells[i].textContent ?? '').trim()} ${switchText}`);
          if (!Number.isNaN(d.getTime()) && d > today) {
            return { index: i, label: d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }).replace('Sept', 'Sep') };
          }
        }
        return null;
      });
      if (found) {
        await this.picker.locator('.datepicker-days td.day').nth(found.index).click({ force: true });
        return found.label;
      }
      await this.picker.locator('.datepicker-days th.next').click();
    }
    return null;
  }

  /** Picks E2E_TICKET_DATE (YYYY-MM-DD) or the first enabled day in the date picker. */
  async pickDate(isoDate?: string): Promise<void> {
    if (!(await this.date.isVisible())) return;
    if (isoDate) {
      const [y, m, d] = isoDate.split('-').map(Number);
      await this.page.evaluate(
        ([yy, mm, dd]) => {
          const $ = (window as any).jQuery;
          $('#date').datepicker('setDate', new Date(yy, mm - 1, dd)).trigger('change');
        },
        [y, m, d],
      );
    } else {
      expect(await this.pickAvailableDate(this.date, 0), 'no bookable day in the date picker').not.toBeNull();
    }
    await expect(this.date).not.toHaveValue('');
  }

  /**
   * Picks the `index`-th slot (0 = first real slot, after "Select time") once the AJAX call
   * filled the select. Returns the value (H:i), or null when there is no such slot.
   * `waitFor` is the URL part of the AJAX call the change triggers; it is awaited.
   */
  async pickTime(select: Locator = this.time, index = 0, waitFor?: string): Promise<string | null> {
    const options = select.locator('option');
    await expect
      .poll(async () => (await options.count()) > 1 || (await this.noTickets.count()) > 0, { timeout: 20_000 })
      .toBe(true);
    if ((await options.count()) <= index + 1) return null;
    const value = (await options.nth(index + 1).getAttribute('value')) ?? '';
    if (waitFor) {
      // Two-way tickets come from the external supplier's API, which can take well over 15 s.
      await Promise.all([this.page.waitForResponse((r) => r.url().includes(waitFor), { timeout: 60_000 }), select.selectOption(value)]);
    } else {
      await select.selectOption(value);
    }
    return value;
  }

  /** For fixed date/time tickets: picks the first time slot. */
  async pickTimeIfNeeded(): Promise<void> {
    if (!(await this.time.isVisible())) return;
    await expect(this.time.locator('option')).not.toHaveCount(1, { timeout: 20_000 });
    const value = await this.time.locator('option').nth(1).getAttribute('value');
    if (value) await this.time.selectOption(value);
  }

  /**
   * settlement/fdate/fdatetime: picks the `dateIndex`-th date (and `timeIndex`-th slot) so the
   * ticket list loads. Returns null when that date/slot doesn't exist.
   */
  async chooseDateAndTime(dateIndex = 0, timeIndex = 0): Promise<{ date: string; time: string } | null> {
    const type = await this.pricingType();
    if (type === 'settlement') return { date: '', time: '' };
    if (type === 'fdate') {
      const date = await this.pickAvailableDate(this.date, dateIndex, '/attractions/get-tickets/');
      return date ? { date, time: '' } : null;
    }
    if (type === 'fdatetime') {
      const date = await this.pickAvailableDate(this.date, dateIndex, '/attractions/get-fixeddate-time/');
      if (!date) return null;
      const time = await this.pickTime(this.time, timeIndex, '/attractions/get-tickets/');
      return time ? { date, time } : null;
    }
    throw new Error('two-way attractions: use chooseTwoWay()');
  }

  /**
   * Two-way: departure date + slot, then return date + slot (by index). Waits for the
   * get-two-way-tickets call the second slot triggers. Null when a date or slot doesn't exist.
   */
  async chooseTwoWay(opts: { departIndex?: number; returnIndex?: number; departTimeIndex?: number; returnTimeIndex?: number } = {}): Promise<{
    departDate: string;
    departTime: string;
    returnDate: string;
    returnTime: string;
  } | null> {
    const departDate = await this.pickAvailableDate(this.departureDate, opts.departIndex ?? 0, '/attractions/get-fixeddate-time/');
    if (!departDate) return null;
    const departTime = await this.pickTime(this.departureTime, opts.departTimeIndex ?? 0);
    if (!departTime) return null;
    const returnDate = await this.pickAvailableDate(this.returnDate, opts.returnIndex ?? 0, '/attractions/get-fixeddate-time/');
    if (!returnDate) return null;
    const returnTime = await this.pickTime(this.returnTime, opts.returnTimeIndex ?? 0, '/attractions/get-two-way-tickets/');
    if (!returnTime) return null;
    return { departDate, departTime, returnDate, returnTime };
  }

  /**
   * First quantity group whose + can raise it (not sold out, not a synced/readonly return leg);
   * only the group of `ticketId` when one is given.
   */
  private async firstAddableGroup(scope: Locator, ticketId?: string): Promise<Locator | null> {
    let groups = scope.locator('.input-group').filter({ has: this.page.locator('.btn-add:not([disabled])') });
    if (ticketId) groups = groups.filter({ has: this.page.locator(`input[name^="quantity[${ticketId}]"]`) });
    const count = await groups.count();
    for (let i = 0; i < count; i++) {
      const group = groups.nth(i);
      const max = await group.locator('input[name^="quantity["]').getAttribute('max');
      if (max !== null && max !== '' && Number(max) <= 0) continue;
      if (!(await group.locator('.btn-add').isVisible())) continue;
      return group;
    }
    return null;
  }

  /**
   * Raises the first addable ticket in `scope` (or ticket `ticketId`) to `qty` with its + button
   * (stops at its max). Returns what was selected, or null when no ticket can be added.
   */
  async selectTicket(qty = 1, scope: Locator = this.form, ticketId?: string): Promise<SelectedTicket | null> {
    await expect(scope.locator('input[name^="quantity["]').or(this.noTickets).first(), 'ticket list did not load').toBeVisible({ timeout: 60_000 });
    const group = await this.firstAddableGroup(scope, ticketId);
    if (!group) return null;
    const input = group.locator('input[name^="quantity["]');
    for (let i = 0; i < qty; i++) {
      const before = Number(await input.inputValue());
      await group.locator('.btn-add').click();
      if (Number(await input.inputValue()) === before) break; // reached max
    }
    const selected = Number(await input.inputValue());
    if (selected <= 0) return null;
    const nameAttr = (await input.getAttribute('name')) ?? '';
    const name = await group.evaluate((el) => {
      // element/ticket_row.php + ticket-row.js: .ticket-option > .ticket-info > .ticket-name
      const row = el.closest('.ticket-option');
      return (row?.querySelector('.ticket-name')?.textContent ?? '').trim().split('\n')[0].trim();
    });
    return {
      ticketId: nameAttr.replace(/^quantity\[/, '').replace(/\].*$/, ''),
      name,
      price: Number(await input.getAttribute('data-price')),
      qty: selected,
    };
  }

  /** Waits for the ticket list (loaded by AJAX) and adds one of the first ticket that isn't sold out. */
  async addFirstAvailableTicket(): Promise<string> {
    await expect(this.quantities.first(), 'no tickets listed for this attraction/date').toBeVisible({ timeout: 30_000 });
    const groups = this.form.locator('.input-group').filter({ has: this.page.locator('.btn-add') });
    const count = await groups.count();
    for (let i = 0; i < count; i++) {
      const group = groups.nth(i);
      const plus = group.locator('.btn-add');
      if (!(await plus.isVisible()) || !(await plus.isEnabled())) continue;
      await plus.click();
      const input = group.locator('input[name^="quantity["]');
      if (Number(await input.inputValue()) > 0) {
        return (await input.getAttribute('name')) ?? '';
      }
    }
    throw new Error('Could not add any ticket (all sold out or disabled?)');
  }

  /** Native alerts shown by the last submit() (the page now uses toasts and disabled buttons, so normally none). */
  lastAlerts: string[] = [];

  /**
   * Clicks Add to Cart or Buy Now and waits for the resulting page. Accepts the
   * "Cart will be cleared to make this purchase" confirm. When the page's JS blocks the submit
   * with an alert, the alert is dismissed, kept in `lastAlerts`, and null is returned.
   */
  async submit(button: 'add' | 'buy' = 'add'): Promise<Response | null> {
    const target = button === 'add' ? this.addToCart : this.buyNow;
    await expect(target).toBeVisible();
    // The buttons are always shown but stay disabled until a quantity is picked.
    await expect(target).toBeEnabled();
    this.lastAlerts = [];
    const timeout = 90_000;
    let markAlert = () => {};
    const alerted = new Promise<'alert'>((resolve) => (markAlert = () => resolve('alert')));
    const onDialog = (d: Dialog) => {
      if (d.type() === 'confirm') {
        d.accept().catch(() => undefined);
      } else {
        this.lastAlerts.push(d.message());
        d.dismiss().catch(() => undefined);
        markAlert();
      }
    };
    const final = this.page
      .waitForResponse(
        (r) => r.request().isNavigationRequest() && r.frame() === this.page.mainFrame() && (r.status() < 300 || r.status() >= 400),
        { timeout },
      )
      .catch(() => null);
    const navigated = this.page
      .waitForEvent('framenavigated', { predicate: (f) => f === this.page.mainFrame(), timeout })
      .then(() => 'nav' as const);
    navigated.catch(() => undefined);
    this.page.on('dialog', onDialog);
    try {
      await target.click();
      if ((await Promise.race([navigated, alerted])) === 'alert') return null;
      await this.page.waitForLoadState('load');
      return await final;
    } finally {
      this.page.off('dialog', onDialog);
    }
  }
}

/** One cart line (one ticket on one date). */
export interface CartLine {
  ticketId: string;
  /** Session key: date (d M yyyy), "date H:i" for timed tickets, today's date for settlement. */
  cartKey: string;
  title: string;
  name: string;
  /** Date shown on the line ('' for settlement tickets). */
  shownDate: string;
  /** data-price of the input (unit price). */
  price: number;
  qty: number;
  rowTotal: number;
}

/** /orders/cart (templates/Orders/cart.php). */
export class CartPage {
  readonly heading: Locator;
  readonly emptyMessage: Locator;
  readonly quantities: Locator;
  readonly removeButtons: Locator;
  readonly total: Locator;
  readonly checkout: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'Your Cart' });
    this.emptyMessage = page.getByText('No items on cart.');
    this.quantities = page.locator('input[name^="quantity["]');
    this.removeButtons = page.locator('a.btn-danger.tr-10');
    this.total = page.locator('.total').first();
    this.checkout = page.getByRole('button', { name: 'Checkout' });
  }

  async goto(): Promise<Response | null> {
    return this.page.goto('/orders/cart');
  }

  async isEmpty(): Promise<boolean> {
    return this.emptyMessage.isVisible();
  }

  /** Removes every line (each removal asks for a JS confirm). */
  async clear(): Promise<void> {
    const accept = (d: Dialog) => {
      d.accept().catch(() => undefined);
    };
    this.page.on('dialog', accept);
    try {
      for (let guard = 0; guard < 20; guard++) {
        const response = await this.goto();
        if (response && response.status() >= 500) {
          throw new Error(`/orders/cart answered ${response.status()}`);
        }
        if (/\/orders\/checkout/.test(this.page.url())) {
          // OrdersController::cart() forwards a two-way pair straight to the checkout; it has no remove buttons.
          throw new Error(TWO_WAY_IN_CART);
        }
        if (await this.isEmpty()) break;
        await navigateBy(this.page, () => this.removeButtons.first().click());
      }
      await expect(this.emptyMessage).toBeVisible();
    } finally {
      this.page.off('dialog', accept);
    }
  }

  /** Every line with ticket id, session key, unit price, quantity and line total. */
  async lines(): Promise<CartLine[]> {
    return this.quantities.evaluateAll((inputs) =>
      inputs.map((el) => {
        const input = el as HTMLInputElement;
        const text = (e: Element | null | undefined) => (e?.textContent ?? '').replace(/\s+/g, ' ').trim();
        const money = (s: string) => {
          const all = s.match(/-?\d[\d,]*(?:\.\d+)?/g);
          return all ? Number(all[all.length - 1].replace(/,/g, '')) : NaN;
        };
        const m = input.name.match(/^quantity\[([^\]]+)\]\[([^\]]*)\]$/) ?? ['', '', ''];
        const row = input.closest('.row.py-2');
        const card = input.closest('.ticket-card');
        const muted = card?.querySelector('.text-muted') as HTMLElement | null;
        return {
          ticketId: m[1],
          cartKey: m[2],
          title: text(card?.querySelector('.fw-bold')),
          name: (muted?.innerText ?? '').split('\n')[0].trim(),
          shownDate: text(row?.querySelector('.col-4')),
          price: Number(input.dataset.price),
          qty: Number(input.value),
          rowTotal: money(text(row?.querySelector('.row-total'))),
        };
      }),
    );
  }

  async totalValue(): Promise<number> {
    return parseMoney(await this.total.textContent());
  }

  quantity(ticketId: string, cartKey: string): Locator {
    return this.page.locator(`input[name="quantity[${ticketId}][${cartKey}]"]`);
  }

  private line(ticketId: string, cartKey: string): Locator {
    return this.page.locator('.ticket-card .row.py-2').filter({ has: this.quantity(ticketId, cartKey) });
  }

  plus(ticketId: string, cartKey: string): Locator {
    return this.line(ticketId, cartKey).locator('.btn-add');
  }

  /** Removes one line, accepting the "Remove … from cart?" confirm. */
  async remove(ticketId: string, cartKey: string): Promise<void> {
    this.page.once('dialog', (d) => d.accept().catch(() => undefined));
    await navigateBy(this.page, () => this.line(ticketId, cartKey).locator('a.btn-danger').click());
  }

  /** Clicks Checkout (POSTs the quantities; the app redirects to checkout, or re-renders the cart on errors). */
  async submit(): Promise<Response | null> {
    return navigateBy(this.page, () => this.checkout.click());
  }
}

/** /orders/checkout (templates/Orders/checkout.php). */
export class CheckoutPage {
  readonly firstName: Locator;
  readonly lastName: Locator;
  readonly email: Locator;
  readonly mobile: Locator;
  readonly trackingNo: Locator;
  readonly continueButton: Locator;
  readonly backLink: Locator;
  /** Booking-question controls (QuestionGroupHelper): question_group[field][ticket][date][n]. */
  readonly questionFields: Locator;
  readonly ticketDetailsCard: Locator;

  constructor(private readonly page: Page) {
    this.firstName = page.locator('input[name="first_name"]');
    this.lastName = page.locator('input[name="last_name"]');
    this.email = page.locator('input[name="email"]');
    this.mobile = page.locator('input[name="mobile"]');
    this.trackingNo = page.locator('input[name="tracking_no"]');
    this.continueButton = page.getByRole('button', { name: 'Continue' });
    this.backLink = page.locator('a[href$="/orders/cart"]', { hasText: 'Back' });
    this.questionFields = page.locator('[name^="question_group["]');
    this.ticketDetailsCard = page.locator('.ticket-details-card');
  }

  async goto(): Promise<Response | null> {
    return this.page.goto('/orders/checkout');
  }

  async fillCustomer(c: { firstName: string; lastName: string; email: string; mobile: string }): Promise<void> {
    await expect(this.firstName).toBeVisible();
    await this.firstName.fill(c.firstName);
    await this.lastName.fill(c.lastName);
    await this.email.fill(c.email);
    await this.mobile.fill(c.mobile);
  }

  /** Names of required fields that are still empty (traveller/transfer info this suite doesn't fill). */
  async emptyRequiredFields(): Promise<string[]> {
    return this.page.locator('form input[required], form select[required], form textarea[required]').evaluateAll(
      (els) =>
        els
          .filter((el) => (el as HTMLInputElement).offsetParent !== null && !(el as HTMLInputElement).value)
          .map((el) => (el as HTMLInputElement).name),
    );
  }

  /** Names of visible fields the browser considers invalid (required, pattern, type). */
  async invalidFields(): Promise<string[]> {
    return this.page.locator('form input, form select, form textarea').evaluateAll((els) =>
      els
        .filter((el) => (el as HTMLInputElement).offsetParent !== null && !(el as HTMLInputElement).validity.valid)
        .map((el) => (el as HTMLInputElement).name),
    );
  }

  /** Visible booking-question controls marked required (unique names). */
  async requiredQuestionNames(): Promise<string[]> {
    return this.questionFields.evaluateAll((els) =>
      Array.from(
        new Set(
          els
            .filter((el) => (el as HTMLInputElement).required && (el as HTMLElement).offsetParent !== null)
            .map((el) => (el as HTMLInputElement).name),
        ),
      ),
    );
  }

  /** Current values of the booking-question controls ({name: value}; radios/checkboxes: checked value). */
  async questionValues(): Promise<Record<string, string>> {
    return this.questionFields.evaluateAll((els) => {
      const out: Record<string, string> = {};
      for (const el of els as HTMLInputElement[]) {
        if (el.type === 'radio' || el.type === 'checkbox') {
          if (el.checked) out[el.name] = el.value;
        } else if (el.type !== 'hidden') {
          out[el.name] = el.value;
        }
      }
      return out;
    });
  }

  /**
   * Answers every visible booking question (question_group[<field>][<ticket>.<date>.<n>], from
   * QuestionGroupHelper) with a value that suits its control and returns {name: value} of what was
   * entered (radio/checkbox: the option value). Passenger fields get realistic values from their
   * field name / label (first + last name, date of birth in the past, alphanumeric passport no,
   * passport expiry in the future, email, phone); selects keep a value already chosen (Gender,
   * Nationality default); other text fields get `text`.
   */
  async fillQuestions(text = 'E2E answer'): Promise<Record<string, string>> {
    const answers: Record<string, string> = {};
    const doneGroups = new Set<string>();
    const count = await this.questionFields.count();
    for (let i = 0; i < count; i++) {
      const field = this.questionFields.nth(i);
      if (!(await field.isVisible()) || !(await field.isEditable())) continue;
      const name = (await field.getAttribute('name')) ?? '';
      const { tag, label } = await field.evaluate((el) => ({
        tag: el.tagName.toLowerCase(),
        label: ((el as HTMLInputElement).labels?.[0]?.textContent ?? '').trim(),
      }));
      const type = ((await field.getAttribute('type')) ?? '').toLowerCase();
      const known = passengerAnswer(name, label);
      if (tag === 'select') {
        const current = await field.inputValue();
        const values = await field.locator('option').evaluateAll((os) => os.map((o) => (o as HTMLOptionElement).value).filter(Boolean));
        const value = current || values[0];
        if (value) {
          await field.selectOption(value);
          answers[name] = value;
        }
      } else if (type === 'radio' || type === 'checkbox') {
        if (doneGroups.has(name)) continue;
        doneGroups.add(name);
        await field.check();
        answers[name] = (await field.getAttribute('value')) ?? 'on';
      } else if (type === 'hidden') {
        continue;
      } else if (type === 'number' || type === 'range') {
        const min = await field.getAttribute('min');
        const value = min && Number(min) > 0 ? min : '1';
        await field.fill(value);
        answers[name] = value;
      } else if (type === 'email') {
        await field.fill('e2e@example.com');
        answers[name] = 'e2e@example.com';
      } else if (await isDatePicker(field)) {
        const value = known ?? '1 Jan 2030';
        await fillDatePicker(field, value);
        answers[name] = value;
      } else {
        const minlength = Number((await field.getAttribute('minlength')) ?? '0');
        const maxlength = Number((await field.getAttribute('maxlength')) ?? '0');
        let value = (known ?? text).padEnd(minlength, 'x');
        if (maxlength > 0) value = value.slice(0, maxlength);
        await field.fill(value);
        answers[name] = value;
      }
    }
    return answers;
  }

  /** Fills traveller (passport) fields of tickets that require them. */
  async fillTravellers(): Promise<void> {
    const fill = async (prefix: string, value: string) => {
      const fields = this.page.locator(`input[name^="${prefix}["]`);
      const n = await fields.count();
      for (let i = 0; i < n; i++) {
        const field = fields.nth(i);
        if (!(await field.isVisible())) continue;
        if (await isDatePicker(field)) await fillDatePicker(field, value);
        else await field.fill(value);
      }
    };
    await fill('passport_number', 'E1234567');
    await fill('passport_expiry', passportExpiry());
    await fill('passport_first_name', 'Tester');
    await fill('passport_last_name', 'Playwright');
    await fill('date_of_birth', adultDateOfBirth());
    await fill('nationality', 'Singaporean');
  }

  /** Clicks Continue and waits for the result (confirmation, or checkout again with a flash on errors). */
  async submit(): Promise<Response | null> {
    return navigateBy(this.page, () => this.continueButton.click(), 90_000);
  }
}

/** /orders/confirmation (templates/Orders/confirmation.php). */
export class ConfirmationPage {
  readonly heading: Locator;
  readonly payByCredits: Locator;
  readonly payNow: Locator;
  readonly grandTotal: Locator;
  readonly paymentOptions: Locator;
  readonly backLink: Locator;
  readonly convenienceFee: Locator;
  readonly ticketInformation: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'Confirmation' });
    this.payByCredits = page.locator('input[name="pay_by"][value="credits"]');
    this.payNow = page.locator('#btn-pay-now');
    this.grandTotal = page.locator('.row', { hasText: 'Grand Total' }).last();
    this.paymentOptions = page.locator('input[name="pay_by"]');
    this.backLink = page.locator('a[href$="/orders/checkout"]', { hasText: 'Back' });
    this.convenienceFee = page.locator('.convenience-fee');
    this.ticketInformation = page.locator('.card', { has: page.locator('.card-header', { hasText: 'Ticket Information' }) });
  }

  async goto(): Promise<Response | null> {
    return this.page.goto('/orders/confirmation');
  }

  method(value: string): Locator {
    return this.page.locator(`input[name="pay_by"][value="${value}"]`);
  }

  async methodValues(): Promise<string[]> {
    return this.paymentOptions.evaluateAll((els) => els.map((el) => (el as HTMLInputElement).value));
  }

  /** Picks a payment method (the page then enables Pay Now and recomputes fee + Grand Total). */
  async choose(value: string): Promise<void> {
    await this.method(value).check();
    await expect(this.payNow).toBeEnabled();
  }

  /** Read-only customer value on the confirmation summary (first_name, last_name, email, mobile, tracking_no). */
  customerField(name: string): Locator {
    return this.page.locator(`.customer-summary [data-field="${name}"]`);
  }

  /** Grand Total as shown now (rewritten by the page JS when a method with a fee is picked). */
  async grandTotalValue(): Promise<number> {
    return parseMoney(await this.page.locator('#tickets-sidebar .grandtotal-row .col.text-end').first().textContent());
  }

  async subtotalValue(): Promise<number> {
    return (await readSidebar(this.page)).subtotal;
  }

  /** Convenience fee shown for the picked method (0 when there is no fee row). */
  async feeValue(): Promise<number> {
    if (!(await this.convenienceFee.count())) return 0;
    return parseMoney(await this.convenienceFee.first().textContent());
  }

  /** pay_by → provider ("2C2P", "Stripe", "Paypal"), from the page's payment_providers JS var. */
  async providers(): Promise<Record<string, string>> {
    return this.page.evaluate(() => ((window as any).payment_providers ?? {}) as Record<string, string>);
  }

  /** Clicks Pay Now and waits for the next document (thank-you, a gateway, or confirmation again). */
  async payNowAndWait(timeout = 150_000): Promise<Response | null> {
    return navigateBy(this.page, () => this.payNow.click(), timeout);
  }
}
