import { expect, type Locator, type Page, type Response } from '@playwright/test';

/** Text CakePHP / PHP print when something broke (debug or production error pages). */
const ERROR_TEXT =
  /An Internal Error Has Occurred|Fatal error|Missing (Controller|Route|Template|Method)|Undefined (variable|index|array key|property)|(Warning|Notice|Deprecated) \(\d+\)|Call to (a member function|undefined)|Uncaught/;

/** The page rendered fine: 2xx, no PHP warnings, no Cake error page, no debug error blocks. */
export async function expectHealthyPage(page: Page, response?: Response | null): Promise<void> {
  if (response) {
    expect(response.status(), `HTTP status for ${response.url()}`).toBeLessThan(400);
  }
  await expect(page.locator('body')).not.toContainText(ERROR_TEXT);
  await expect(page.locator('.cake-error, .cake-debug-output, .cake-stack-trace')).toHaveCount(0);
}

/** Collects uncaught JS errors on the page so a test can assert there were none. */
export function trackPageErrors(page: Page): string[] {
  const errors: string[] = [];
  page.on('pageerror', (err) => errors.push(err.message));
  return errors;
}

export const flashError = (page: Page) => page.locator('.alert-danger');
export const flashSuccess = (page: Page) => page.locator('.alert-success');
export const flashWarning = (page: Page) => page.locator('.alert-warning');

/**
 * Clicks `target` when the click opens a native alert()/confirm(), answers the dialog and returns
 * its message. The handler is set up before the click: Playwright's click() doesn't return until a
 * dialog it opened is handled, so waiting for the dialog after the click would hang.
 */
export async function clickWithDialog(page: Page, target: Locator, answer: 'accept' | 'dismiss' = 'dismiss'): Promise<string> {
  let message: string | null = null;
  page.once('dialog', async (d) => {
    message = d.message();
    await (answer === 'accept' ? d.accept() : d.dismiss());
  });
  await target.click();
  await expect.poll(() => message, { message: 'a dialog opened' }).not.toBeNull();
  return message!;
}
