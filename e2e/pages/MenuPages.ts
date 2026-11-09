import fs from 'node:fs';
import zlib from 'node:zlib';
import { expect, type Browser, type BrowserContext, type Download, type Locator, type Page, type Response } from '@playwright/test';
import { expectHealthyPage, trackPageErrors } from '../utils/checks';
import { env } from '../utils/env';
import { LoginPage, type Credentials } from './LoginPage';

/*
 * Page objects for the logged-in menu pages (tests/pages). Selectors come from
 * templates/Orders/{index,my_tickets,view}.php, templates/Reports/*, templates/CreditLogs/index.php,
 * templates/AgentTopups/*, templates/AgentUsers/* and templates/layout/default.php.
 */

// ---------------------------------------------------------------- helpers

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/** Date in the app's filter format (`d M Y` in PHP, `d M yyyy` in the datepicker), e.g. "3 Nov 2026". */
export function fmtDate(d: Date): string {
  return `${d.getDate()} ${MONTHS[d.getMonth()]} ${d.getFullYear()}`;
}

/** Today plus `n` days (local time), as a filter date string. */
export function dayOffset(n: number): string {
  const d = new Date();
  d.setDate(d.getDate() + n);
  return fmtDate(d);
}

/** Parses the leading "dd Mon yyyy" of a table cell ("03 Nov 2026 14:05pm") into a Y-M-D number for comparisons. */
export function cellDay(text: string): number | null {
  const m = text.trim().match(/^(\d{1,2}) ([A-Z][a-z]{2}) (\d{4})/);
  if (!m) return null;
  const month = MONTHS.indexOf(m[2]);
  return month < 0 ? null : Number(m[3]) * 10000 + (month + 1) * 100 + Number(m[1]);
}

/** Same Y-M-D number for a filter date string / offset. */
export function dayNumber(offsetDays: number): number {
  return cellDay(dayOffset(offsetDays)) as number;
}

/** Number out of a money string ("SGD 1,234.50", "$12.00", "(12.00)", "12.5"); null when there is none. */
export function parseMoney(text: string | undefined | null): number | null {
  if (!text) return null;
  const m = text.replace(/,/g, '').match(/-?\d+(?:\.\d+)?/);
  return m ? Number(m[0]) : null;
}

/** page.goto + healthy-page check; returns the response. */
export async function openHealthy(page: Page, url: string): Promise<Response | null> {
  const res = await page.goto(url);
  await expectHealthyPage(page, res);
  await expect(page, `${url} should not bounce to login`).not.toHaveURL(/\/agent-users\/login/);
  return res;
}

/** CSRF token of the current page (hidden _csrfToken of any POST form, else the csrfToken cookie). */
export async function csrfToken(page: Page): Promise<string> {
  const input = page.locator('input[name="_csrfToken"]').first();
  if (await input.count()) {
    const v = await input.getAttribute('value');
    if (v) return v;
  }
  const cookie = (await page.context().cookies()).find((c) => c.name === 'csrfToken');
  if (!cookie) throw new Error('No CSRF token on the page or in the cookies');
  return cookie.value;
}

/**
 * POSTs like a Form->postLink would (same cookies, CSRF token from the page), without following the
 * redirect, so the flash message stays in the session for the next page.goto.
 */
export async function postLikeForm(page: Page, url: string, data: Record<string, string> = {}) {
  const token = await csrfToken(page);
  return page.request.post(url, { form: { _csrfToken: token, ...data }, maxRedirects: 0 });
}

/** Clicks something that answers with a file and returns the download (fails if the page navigates instead). */
export async function downloadFrom(page: Page, trigger: Locator, timeout = 30_000): Promise<Download> {
  const [download] = await Promise.all([page.waitForEvent('download', { timeout }), trigger.click()]);
  return download;
}

export async function downloadText(download: Download): Promise<string> {
  const p = await download.path();
  return fs.readFileSync(p, 'utf8');
}

/** Minimal RFC 4180 parser for the fputcsv output the app writes. */
export function parseCsv(text: string): string[][] {
  const rows: string[][] = [];
  let row: string[] = [];
  let field = '';
  let quoted = false;
  for (let i = 0; i < text.length; i++) {
    const c = text[i];
    if (quoted) {
      if (c === '"' && text[i + 1] === '"') { field += '"'; i++; }
      else if (c === '"') quoted = false;
      else field += c;
    } else if (c === '"') quoted = true;
    else if (c === ',') { row.push(field); field = ''; }
    else if (c === '\n' || c === '\r') {
      if (c === '\r' && text[i + 1] === '\n') i++;
      row.push(field); rows.push(row); row = []; field = '';
    } else field += c;
  }
  if (field !== '' || row.length) { row.push(field); rows.push(row); }
  return rows;
}

/** Rows of a CSV report after its column header row (the row whose first cell is `firstHeader`). */
export function csvDataRows(text: string, firstHeader: string): string[][] {
  const rows = parseCsv(text);
  const h = rows.findIndex((r) => r[0] === firstHeader);
  if (h < 0) throw new Error(`CSV has no "${firstHeader}" header row:\n${text.slice(0, 500)}`);
  return rows.slice(h + 1).filter((r) => r.some((c) => c.trim() !== ''));
}

/** A tiny valid PNG (1x1, RGBA) built in memory, so getimagesize() on the server accepts it. */
export function tinyPng(): Buffer {
  const crcTable = Array.from({ length: 256 }, (_, n) => {
    let c = n;
    for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
    return c >>> 0;
  });
  const crc32 = (buf: Buffer) => {
    let c = 0xffffffff;
    for (const b of buf) c = crcTable[(c ^ b) & 0xff] ^ (c >>> 8);
    return (c ^ 0xffffffff) >>> 0;
  };
  const chunk = (type: string, data: Buffer) => {
    const len = Buffer.alloc(4);
    len.writeUInt32BE(data.length);
    const td = Buffer.concat([Buffer.from(type, 'ascii'), data]);
    const crc = Buffer.alloc(4);
    crc.writeUInt32BE(crc32(td));
    return Buffer.concat([len, td, crc]);
  };
  const ihdr = Buffer.alloc(13);
  ihdr.writeUInt32BE(1, 0); // width
  ihdr.writeUInt32BE(1, 4); // height
  ihdr[8] = 8; // bit depth
  ihdr[9] = 6; // RGBA
  // One scanline: filter byte 0 + transparent RGBA pixel.
  const idat = zlib.deflateSync(Buffer.from([0, 0, 0, 0, 0]));
  return Buffer.concat([
    Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]),
    chunk('IHDR', ihdr),
    chunk('IDAT', idat),
    chunk('IEND', Buffer.alloc(0)),
  ]);
}

/**
 * Logs another account in, in its own browser context (doesn't touch the main session; it is a
 * different agent user). Skips via the returned error message when the login can't be automated.
 */
export async function loginInFreshContext(
  browser: Browser,
  creds: Credentials,
): Promise<{ context: BrowserContext; page: Page; errors: string[] }> {
  const context = await browser.newContext({ baseURL: env.baseUrl });
  const page = await context.newPage();
  const errors = trackPageErrors(page);
  try {
    await new LoginPage(page).login(creds);
  } catch (e) {
    await context.close();
    throw e;
  }
  return { context, page, errors };
}

/** Pagination block shared by the index templates ("Page x of y, showing n record(s) out of m total"). */
export class Paginator {
  readonly counter: Locator;
  readonly next: Locator;

  constructor(page: Page) {
    this.counter = page.locator('.paginator p');
    this.next = page.locator('.paginator .pagination a[rel="next"]');
  }

  async info(): Promise<{ page: number; pages: number; current: number; count: number }> {
    const t = (await this.counter.textContent()) ?? '';
    const m = t.match(/Page (\d+) of (\d+), showing (\d+) record\(s\) out of (\d+) total/);
    if (!m) throw new Error(`Unexpected paginator text: "${t}"`);
    return { page: +m[1], pages: +m[2], current: +m[3], count: +m[4] };
  }
}

// ---------------------------------------------------------------- My Orders (/orders)

export class MyOrdersPage {
  readonly heading: Locator;
  readonly orderRef: Locator;
  readonly trackingNo: Locator;
  readonly status: Locator;
  readonly dateType: Locator;
  readonly from: Locator;
  readonly to: Locator;
  readonly filterButton: Locator;
  readonly showAll: Locator;
  readonly rows: Locator;
  readonly paginator: Paginator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'My Orders' });
    const form = page.locator('form.order-search');
    this.orderRef = form.locator('[name="order_ref_id"]');
    this.trackingNo = form.locator('[name="tracking_no"]');
    this.status = form.locator('select[name="status"]');
    this.dateType = form.locator('select[name="date_type"]');
    this.from = form.locator('[name="from"]');
    this.to = form.locator('[name="to"]');
    this.filterButton = form.locator('input[type="submit"][value="Filter"]');
    this.showAll = form.getByRole('link', { name: 'Show All' });
    this.rows = page.locator('table tbody tr');
    this.paginator = new Paginator(page);
  }

  async goto(query: Record<string, string> = {}): Promise<Response | null> {
    const qs = new URLSearchParams(query).toString();
    return openHealthy(this.page, `/orders${qs ? `?${qs}` : ''}`);
  }

  /** Fills the filter form (datepickers are typed into, then closed) and submits it. */
  async filter(f: { ref?: string; tracking?: string; status?: string; dateType?: string; from?: string; to?: string }): Promise<void> {
    if (f.ref !== undefined) await this.orderRef.fill(f.ref);
    if (f.tracking !== undefined) await this.trackingNo.fill(f.tracking);
    if (f.status !== undefined) await this.status.selectOption(f.status);
    if (f.dateType !== undefined) await this.dateType.selectOption(f.dateType);
    if (f.from !== undefined) await fillDate(this.page, this.from, f.from);
    if (f.to !== undefined) await fillDate(this.page, this.to, f.to);
    await Promise.all([this.page.waitForURL(/\/orders\?/), this.filterButton.click()]);
    await expectHealthyPage(this.page);
  }

  row(ref: string): Locator {
    return this.rows.filter({ has: this.page.getByRole('link', { name: ref, exact: true }) });
  }

  /** Cells of a row: date, ref, tracking, items, status, total. */
  async rowData(row: Locator) {
    const cells = row.locator('td');
    return {
      date: ((await cells.nth(0).textContent()) ?? '').trim(),
      ref: ((await cells.nth(1).textContent()) ?? '').trim(),
      tracking: ((await cells.nth(2).textContent()) ?? '').trim(),
      items: ((await cells.nth(3).textContent()) ?? '').trim(),
      status: ((await cells.nth(4).textContent()) ?? '').trim(),
      total: ((await cells.nth(5).textContent()) ?? '').trim(),
    };
  }

  async columnTexts(index: number): Promise<string[]> {
    return (await this.rows.locator(`td:nth-child(${index + 1})`).allTextContents()).map((s) => s.trim());
  }
}

/** Types a date into a bootstrap-datepicker input and closes the picker. */
export async function fillDate(page: Page, input: Locator, value: string): Promise<void> {
  await input.fill(value);
  await input.press('Escape').catch(() => undefined);
  await input.evaluate((el) => (el as HTMLInputElement).blur());
  await page.locator('.datepicker-dropdown').waitFor({ state: 'hidden', timeout: 2_000 }).catch(() => undefined);
}

/** Labels templates/Orders/index.php shows for each status filter. */
export const ORDER_STATUS_LABELS: Record<string, RegExp> = {
  done: /^Completed$/,
  cancelled: /^Cancelled$/,
  waiting: /^Waiting$/,
  refunded: /^Refunded$/,
  rejected: /^Failed$/,
};

// ---------------------------------------------------------------- Order detail (/orders/view/<ref>)

export class OrderDetailPage {
  readonly info: Locator;
  readonly products: Locator;
  readonly voucherLinks: Locator;
  readonly grandTotal: Locator;

  constructor(private readonly page: Page) {
    this.info = page.locator('.card', { has: page.getByRole('heading', { name: 'Order Information' }) });
    this.products = page.locator('.card', { has: page.getByRole('heading', { name: 'Product(s)' }) });
    this.voucherLinks = this.products.locator('a[target="_blank"]');
    this.grandTotal = page.locator('.grandtotal-row .col.text-end');
  }

  async goto(ref: string): Promise<Response | null> {
    return this.page.goto(`/orders/view/${encodeURIComponent(ref)}`);
  }

  infoValue(label: string): Locator {
    return this.info.locator('tr', { has: this.page.locator('td:first-child', { hasText: label }) }).locator('td').nth(1);
  }

  /** Voucher codes + hrefs listed under Product(s). */
  async vouchers(): Promise<{ code: string; href: string }[]> {
    return this.voucherLinks.evaluateAll((as) =>
      as.map((a) => ({ code: (a.textContent ?? '').trim(), href: (a as HTMLAnchorElement).href })),
    );
  }
}

// ---------------------------------------------------------------- My Tickets (/orders/my-tickets)

export class MyTicketsPage {
  readonly heading: Locator;
  readonly code: Locator;
  readonly status: Locator;
  readonly from: Locator;
  readonly to: Locator;
  readonly filterButton: Locator;
  readonly showAll: Locator;
  readonly headers: Locator;
  readonly rows: Locator;
  readonly paginator: Paginator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'My Tickets' });
    const form = page.locator('form.order-search');
    this.code = form.locator('[name="code"]');
    this.status = form.locator('select[name="status"]');
    this.from = form.locator('[name="from"]');
    this.to = form.locator('[name="to"]');
    this.filterButton = form.locator('input[type="submit"][value="Filter"]');
    this.showAll = form.getByRole('link', { name: 'Show All' });
    this.headers = page.locator('table thead th');
    this.rows = page.locator('table tbody tr');
    this.paginator = new Paginator(page);
  }

  async goto(query: Record<string, string> = {}): Promise<Response | null> {
    const qs = new URLSearchParams(query).toString();
    return openHealthy(this.page, `/orders/my-tickets${qs ? `?${qs}` : ''}`);
  }

  async filter(f: { code?: string; from?: string; to?: string }): Promise<void> {
    if (f.code !== undefined) await this.code.fill(f.code);
    if (f.from !== undefined) await fillDate(this.page, this.from, f.from);
    if (f.to !== undefined) await fillDate(this.page, this.to, f.to);
    await Promise.all([this.page.waitForURL(/\/orders\/my-tickets\?/), this.filterButton.click()]);
    await expectHealthyPage(this.page);
  }

  /** Index of a header column by its text, or -1. */
  async columnIndex(name: string): Promise<number> {
    const texts = (await this.headers.allTextContents()).map((s) => s.trim());
    return texts.indexOf(name);
  }

  rowByCode(code: string): Locator {
    return this.rows.filter({ has: this.page.locator('td:nth-child(2)', { hasText: new RegExp(`^\\s*${escapeRe(code)}\\s*$`) }) });
  }
}

export const escapeRe = (s: string): string => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

/** Badge texts of templates/Orders/my_tickets.php. */
export const TICKET_STATUS = /^(Valid|Purchased|Redeemed|Cancelled|Expired|Partially Redeemed Expired|Partially Redeemed Cancelled)$/;

// ---------------------------------------------------------------- Sales Reports (/reports, /reports/reports)

export class ReportsPage {
  readonly heading: Locator;
  readonly startDate: Locator;
  readonly endDate: Locator;
  readonly generate: Locator;
  readonly generatedLink: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'Sales Reports' });
    this.startDate = page.locator('input[name="issue_start_date"]');
    this.endDate = page.locator('input[name="issue_end_date"]');
    this.generate = page.getByRole('button', { name: 'Generate Report' });
    this.generatedLink = page.getByRole('link', { name: 'Download Generated Reports' });
  }

  async goto(): Promise<Response | null> {
    return openHealthy(this.page, '/reports');
  }

  /**
   * Sets the dates without focusing the inputs: the datepicker (endDate: today) would clear a future
   * date on blur, and an empty start date means "last 6 months", which would really generate a report.
   */
  async submit(start: string, end: string): Promise<void> {
    for (const [input, value] of [[this.startDate, start], [this.endDate, end]] as const) {
      await input.evaluate((el, v) => { (el as HTMLInputElement).value = v; }, value);
      await expect(input).toHaveValue(value);
    }
    // Posts back to /reports: wait for the new document, not just the URL (it already matches).
    await Promise.all([this.page.waitForEvent('load'), this.generate.click()]);
    await expect(this.page).toHaveURL(/\/reports$/);
    await expectHealthyPage(this.page);
  }
}

export class GeneratedReportsPage {
  readonly heading: Locator;
  readonly downloadForms: Locator;
  readonly processing: Locator;
  readonly none: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'Reports' });
    this.downloadForms = page.locator('form[action*="/reports/download/"]');
    this.processing = page.locator('table td', { hasText: /^\s*Processing\s*$/ });
    this.none = page.getByText('No reports available.');
  }

  async goto(): Promise<Response | null> {
    return openHealthy(this.page, '/reports/reports');
  }

  async reportActions(): Promise<string[]> {
    return this.downloadForms.evaluateAll((fs) => fs.map((f) => (f as HTMLFormElement).getAttribute('action') ?? ''));
  }

  /** The visible postLink of the report whose form posts to `action`. */
  linkFor(action: string): Locator {
    return this.page.locator(`form[action="${action}"] + a`);
  }
}

// ---------------------------------------------------------------- Balance Report (/credit-logs)

export class BalanceReportPage {
  readonly heading: Locator;
  readonly from: Locator;
  readonly to: Locator;
  readonly filterButton: Locator;
  readonly download: Locator;
  readonly rows: Locator;
  readonly paginator: Paginator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'Balance Report' });
    const form = page.locator('form.order-search');
    this.from = form.locator('[name="from"]');
    this.to = form.locator('[name="to"]');
    this.filterButton = form.getByRole('button', { name: 'Filter' });
    this.download = page.getByRole('link', { name: 'Download' });
    this.rows = page.locator('table tbody tr');
    this.paginator = new Paginator(page);
  }

  async goto(query: Record<string, string> = {}): Promise<Response | null> {
    const qs = new URLSearchParams(query).toString();
    return openHealthy(this.page, `/credit-logs${qs ? `?${qs}` : ''}`);
  }

  async filter(from: string, to: string): Promise<void> {
    await fillDate(this.page, this.from, from);
    await fillDate(this.page, this.to, to);
    await Promise.all([this.page.waitForURL(/\/credit-logs(\/index)?\?/), this.filterButton.click()]);
    await expectHealthyPage(this.page);
  }

  rowByRef(ref: string): Locator {
    return this.rows.filter({ has: this.page.getByRole('link', { name: ref, exact: true }) });
  }
}

// ---------------------------------------------------------------- Top Up (/agent-topups)

export class TopupsPage {
  readonly heading: Locator;
  readonly addLink: Locator;
  readonly expressButton: Locator;
  readonly expressModal: Locator;
  readonly download: Locator;
  readonly rows: Locator;
  readonly viewLinks: Locator;
  readonly paginator: Paginator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'Top Up' });
    this.addLink = page.getByRole('link', { name: 'Add', exact: true });
    this.expressButton = page.getByRole('button', { name: 'Express Topup' });
    this.expressModal = page.locator('#expressTopupModal');
    this.download = page.getByRole('link', { name: 'Download' });
    this.rows = page.locator('.agentTopups table tbody tr');
    this.viewLinks = page.locator('.agentTopups a[href*="/agent-topups/view/"]');
    this.paginator = new Paginator(page);
  }

  async goto(): Promise<Response | null> {
    return openHealthy(this.page, '/agent-topups');
  }
}

export class TopupAddPage {
  readonly amount: Locator;
  readonly image: Locator;
  readonly remarks: Locator;
  readonly submit: Locator;

  constructor(private readonly page: Page) {
    this.amount = page.locator('[name="amount"]');
    this.image = page.locator('input[type="file"][name="image"]');
    this.remarks = page.locator('textarea[name="remarks"]');
    this.submit = page.getByRole('button', { name: 'Submit' });
  }

  async goto(): Promise<Response | null> {
    return openHealthy(this.page, '/agent-topups/add');
  }

  async send(): Promise<void> {
    await Promise.all([this.page.waitForEvent('load'), this.submit.click()]);
    await expectHealthyPage(this.page);
  }
}

// ---------------------------------------------------------------- Agent Users (/agent-users)

export class AgentUsersPage {
  readonly addUser: Locator;
  readonly rows: Locator;

  constructor(private readonly page: Page) {
    this.addUser = page.getByRole('link', { name: /Add User/ });
    this.rows = page.locator('table tbody tr');
  }

  async goto(query: Record<string, string> = {}): Promise<Response | null> {
    const qs = new URLSearchParams(query).toString();
    return openHealthy(this.page, `/agent-users${qs ? `?${qs}` : ''}`);
  }

  sortLink(label: string): Locator {
    return this.page.locator('table thead th a', { hasText: label });
  }

  row(email: string): Locator {
    return this.rows.filter({ has: this.page.locator('td:nth-child(2)', { hasText: new RegExp(`^\\s*${escapeRe(email)}\\s*$`) }) });
  }

  async emails(): Promise<string[]> {
    return (await this.rows.locator('td:nth-child(2)').allTextContents()).map((s) => s.trim());
  }

  async names(): Promise<string[]> {
    return (await this.rows.locator('td:nth-child(1)').allTextContents()).map((s) => s.trim());
  }

  /** Agent user id from the row's edit link (/agent-users/edit/<id>). */
  async idOf(email: string): Promise<string> {
    const href = await this.row(email).locator('a[href*="/agent-users/edit/"]').getAttribute('href');
    const id = href?.split('/agent-users/edit/')[1];
    if (!id) throw new Error(`No edit link for ${email}`);
    return decodeURIComponent(id);
  }
}

export class AgentUserFormPage {
  readonly name: Locator;
  readonly email: Locator;
  readonly role: Locator;
  readonly status: Locator;
  readonly save: Locator;
  readonly update: Locator;
  readonly errors: Locator;

  constructor(private readonly page: Page) {
    this.name = page.locator('[name="name"]');
    this.email = page.locator('[name="email"]');
    this.role = page.locator('select[name="role"]');
    this.status = page.locator('select[name="status"]');
    this.save = page.getByRole('button', { name: 'Save' });
    this.update = page.getByRole('button', { name: 'Update' });
    this.errors = page.locator('.error-message');
  }
}

// ---------------------------------------------------------------- My Account, Change Password, Contact

export class AccountPage {
  readonly card: Locator;
  readonly company: Locator;
  readonly credit: Locator;
  readonly editLink: Locator;
  readonly changePasswordLink: Locator;

  constructor(private readonly page: Page) {
    this.card = page.locator('.card').filter({ has: page.locator('.card-title') }).first();
    this.company = this.card.locator('.card-title');
    this.credit = this.card.getByText(/Current Credit:/);
    this.editLink = this.card.getByRole('link', { name: 'Edit', exact: true });
    this.changePasswordLink = this.card.getByRole('link', { name: 'Change Password' });
  }

  async goto(): Promise<Response | null> {
    return openHealthy(this.page, '/agent-users/view');
  }

  /** "SGD 1,234.00" part after "Current Credit:". */
  async creditText(): Promise<string> {
    return ((await this.credit.textContent()) ?? '').replace(/^.*Current Credit:\s*/s, '').trim();
  }
}

export class ChangePasswordPage {
  readonly oldPassword: Locator;
  readonly password: Locator;
  readonly confirm: Locator;
  readonly save: Locator;

  constructor(private readonly page: Page) {
    this.oldPassword = page.locator('[name="old_password"]');
    this.password = page.locator('[name="password"]');
    this.confirm = page.locator('[name="confirm_password"]');
    this.save = page.getByRole('button', { name: 'Save' });
  }

  async goto(): Promise<Response | null> {
    return openHealthy(this.page, '/agent-users/change-password');
  }

  /** Submits the form; returns the flash texts shown on the page it lands on. */
  async change(oldPw: string, newPw: string, confirmPw = newPw): Promise<{ success: string; error: string }> {
    await this.goto();
    await this.oldPassword.fill(oldPw);
    await this.password.fill(newPw);
    await this.confirm.fill(confirmPw);
    await Promise.all([this.page.waitForEvent('load'), this.save.click()]);
    await expectHealthyPage(this.page);
    return {
      success: (await this.page.locator('.alert-success').allTextContents()).join(' ').trim(),
      error: (await this.page.locator('.alert-danger').allTextContents()).join(' ').trim(),
    };
  }
}

export class ContactPage {
  readonly subject: Locator;
  readonly message: Locator;
  readonly send: Locator;

  constructor(private readonly page: Page) {
    this.subject = page.locator('[name="subject"]');
    this.message = page.locator('textarea[name="message"]');
    this.send = page.getByRole('button', { name: 'Send' });
  }

  async goto(): Promise<Response | null> {
    return openHealthy(this.page, '/agent-users/contact');
  }

  async submit(subject: string, message: string): Promise<void> {
    await this.subject.fill(subject);
    await this.message.fill(message);
    await Promise.all([this.page.waitForEvent('load'), this.send.click()]);
    await expectHealthyPage(this.page);
  }
}

// ---------------------------------------------------------------- Header (templates/layout/default.php)

export class HeaderBar {
  readonly navbar: Locator;
  readonly languageMenu: Locator;
  readonly languageToggle: Locator;
  readonly credits: Locator;
  readonly lowCredit: Locator;
  readonly bell: Locator;
  readonly unreadDot: Locator;
  readonly popover: Locator;
  readonly popoverClose: Locator;

  constructor(private readonly page: Page) {
    this.navbar = page.locator('nav.navbar');
    // Language dropdown only exists when Features.translation is on; it is then the first dropdown of two.
    const dropdowns = this.navbar.locator('.nav-item.dropdown');
    this.languageMenu = dropdowns.filter({ has: page.locator('[aria-labelledby="navbarLanguageDropdown"]') });
    this.languageToggle = this.languageMenu.locator('a.dropdown-toggle');
    this.credits = this.navbar.locator('.nav-link', { hasText: 'Credits' });
    this.lowCredit = this.credits.locator('.low-credit');
    this.bell = page.locator('#read-new');
    this.unreadDot = this.bell.locator('.announcements_unread');
    this.popover = page.locator('.popover');
    this.popoverClose = this.popover.locator('.btn-close-popover');
  }

  /** "SGD 1,234.00" part after "Credits:" (without the LOW badge). */
  async creditText(): Promise<string> {
    const t = await this.credits.evaluate((el) => {
      const clone = el.cloneNode(true) as HTMLElement;
      clone.querySelectorAll('.badge').forEach((b) => b.remove());
      return clone.textContent ?? '';
    });
    return t.replace(/^.*Credits:\s*/s, '').trim();
  }

  async expandIfCollapsed(): Promise<void> {
    const toggler = this.page.locator('.navbar-toggler');
    if ((await toggler.isVisible()) && (await toggler.getAttribute('aria-expanded')) !== 'true') {
      await toggler.click();
    }
  }
}
