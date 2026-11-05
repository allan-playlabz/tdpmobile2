import { expect, type Locator, type Page, type Response } from '@playwright/test';
import { navigateBy, readSidebar, type SidebarLine } from './BookingPages';

/** /orders/thank-you (templates/Orders/thank_you.php). */
export class ThankYouPage {
  readonly heading: Locator;
  /** "Click here to view order details" (only when the order is already done). */
  readonly orderLink: Locator;
  readonly processing: Locator;
  readonly failed: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'Thank you for your purchase!' });
    this.orderLink = page.locator('a[href*="/orders/view/"]');
    this.processing = page.locator('.processing-message');
    this.failed = page.locator('.alert-danger', { hasText: 'There is an error processing this order' });
  }

  /**
   * Order ref from the thank-you HTML: the "here" link when the order is done, or the page's
   * `order_ref_id` JS var while it is still processing. Read from the response body because the
   * processing page polls /orders/get-status and may navigate away on its own.
   */
  static refFromHtml(html: string): string | null {
    return html.match(/\/orders\/view\/([^"'?#\s]+)/)?.[1] ?? html.match(/var order_ref_id = '([^']+)'/)?.[1] ?? null;
  }
}

/** /orders (templates/Orders/index.php): My Orders. */
export class MyOrdersPage {
  readonly heading: Locator;
  readonly rows: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'My Orders' });
    this.rows = page.locator('table tbody tr');
  }

  async goto(query: Record<string, string> = {}): Promise<Response | null> {
    const qs = new URLSearchParams(query).toString();
    return this.page.goto(`/orders${qs ? `?${qs}` : ''}`);
  }

  row(ref: string): Locator {
    return this.rows.filter({ has: this.page.locator(`a[href$="/orders/view/${ref}"]`) });
  }

  /** Status badge text of an order row (Completed, Waiting, Cancelled, …). */
  status(ref: string): Locator {
    return this.row(ref).locator('td').nth(4).locator('.badge');
  }

  total(ref: string): Locator {
    return this.row(ref).locator('td').nth(5);
  }

  /** Order refs on the current page. */
  async refs(): Promise<string[]> {
    return this.rows.locator('a[href*="/orders/view/"]').allTextContents().then((t) => t.map((s) => s.trim()));
  }
}

/** /orders/view/{order_ref_id} (templates/Orders/view.php), built from the backend API's order details. */
export class OrderDetailPage {
  readonly infoCard: Locator;
  readonly productsCard: Locator;
  /** Voucher code links (open the voucher PDF in a new tab). */
  readonly voucherLinks: Locator;
  readonly invoiceLink: Locator;
  /** "Download as Single PDF (New Request)": queues a merge. */
  readonly mergeAll: Locator;
  /** "Download as Single PDF (Ready)": the merged file (hidden with d-none until the merge is done). */
  readonly downloadAll: Locator;
  /** "Download as Single PDF (Processing)". */
  readonly mergingMessage: Locator;
  readonly cancelButtons: Locator;

  constructor(private readonly page: Page) {
    this.infoCard = page.locator('.card', { has: page.locator('h5', { hasText: 'Order Information' }) });
    this.productsCard = page.locator('.card', { has: page.locator('h5', { hasText: 'Product(s)' }) });
    this.voucherLinks = this.productsCard.locator('a[target="_blank"]');
    this.invoiceLink = page.locator('a[href*="/orders/download-invoice/"]');
    this.mergeAll = page.locator('#merge-all');
    this.downloadAll = page.locator('#download-all');
    this.mergingMessage = page.locator('#voucher-merging-msg');
    this.cancelButtons = this.productsCard.locator('a.btn-outline-danger', { hasText: 'Cancel' });
  }

  async goto(ref: string): Promise<Response | null> {
    return this.page.goto(`/orders/view/${ref}`);
  }

  /** Value cell of an "Order Information" row (Order Ref, Tracking No, Payment Method, Payment Status, Purchase Date). */
  info(label: string): Locator {
    return this.infoCard.locator('tr', { has: this.page.locator('td:first-child', { hasText: label }) }).locator('td').nth(1);
  }

  /** Sidebar items (name, unit price, date/time, qty, line total) and Subtotal / Convenience Fee / Grand Total. */
  async summary(): Promise<{ lines: SidebarLine[]; subtotal: number; fee: number; grandTotal: number }> {
    return readSidebar(this.page);
  }

  /** Voucher codes with their links, in page order. */
  async vouchers(): Promise<{ code: string; href: string }[]> {
    return this.voucherLinks.evaluateAll((els) =>
      els.map((el) => ({ code: (el.textContent ?? '').trim(), href: (el as HTMLAnchorElement).href })),
    );
  }

  /** Clicks a link that answers with a file and returns the download. */
  async download(link: Locator) {
    const [download] = await Promise.all([this.page.waitForEvent('download'), link.click()]);
    return download;
  }
}

/** /orders/my-tickets (templates/Orders/my_tickets.php): one row per voucher. */
export class MyTicketsPage {
  readonly heading: Locator;
  readonly rows: Locator;
  readonly actionsHeader: Locator;
  readonly statusHeader: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'My Tickets' });
    this.rows = page.locator('table tbody tr');
    this.actionsHeader = page.locator('table thead th', { hasText: 'Actions' });
    this.statusHeader = page.locator('table thead th', { hasText: 'Status' });
  }

  async goto(query: Record<string, string> = {}): Promise<Response | null> {
    const qs = new URLSearchParams(query).toString();
    return this.page.goto(`/orders/my-tickets${qs ? `?${qs}` : ''}`);
  }

  /** Filters by code (the app matches code_id LIKE 'code%'). */
  async gotoCode(code: string): Promise<Response | null> {
    return this.goto({ code });
  }

  row(code: string): Locator {
    return this.rows.filter({ has: this.page.locator('td:nth-child(2)', { hasText: code }) });
  }

  orderRef(code: string): Locator {
    return this.row(code).locator('td:first-child a');
  }

  /** Status badge (Valid, Purchased, Redeemed, Cancelled, Expired, …); absent when the agent hides voucher status. */
  status(code: string): Locator {
    return this.row(code).locator('.badge');
  }

  cancelButton(code: string): Locator {
    return this.row(code).locator('a.btn-outline-danger', { hasText: 'Cancel' });
  }

  /** Codes listed on the current page. */
  async codes(): Promise<string[]> {
    return this.rows.locator('td:nth-child(2)').allTextContents().then((t) => t.map((s) => s.trim()).filter(Boolean));
  }

  /** Cancels a voucher (accepts "Are you sure you want to cancel this voucher?"); returns the dialog text. */
  async cancel(code: string): Promise<string> {
    let message = '';
    this.page.once('dialog', (d) => {
      message = d.message();
      d.accept().catch(() => undefined);
    });
    await navigateBy(this.page, () => this.cancelButton(code).click(), 90_000);
    expect(message).not.toBe('');
    return message;
  }
}
