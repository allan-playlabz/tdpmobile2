import { test as base, expect } from '@playwright/test';
import { trackPageErrors } from '../../utils/checks';
import { env } from '../../utils/env';
import { placedOrders, type PlacedOrder } from '../../utils/orders';

/**
 * `test` for tests/pages: every test tracks uncaught JS errors on its page and fails at the end if
 * there were any (pages also call expectHealthyPage after each navigation).
 */
export const test = base.extend<{ pageErrors: string[] }>({
  pageErrors: [
    async ({ page }, use) => {
      const errors = trackPageErrors(page);
      await use(errors);
      expect(errors, 'uncaught JS errors on the page').toEqual([]);
    },
    { auto: true },
  ],
});

export { expect };

export const role = env.user.role;
export const isMaster = role === 'master';
export const isViewer = role === 'viewer';

/**
 * Paid orders the ordering project placed in this run (may be empty). Every checkout also
 * creates an unpaid ("pending") order; those never show in My Orders / My Tickets / reports.
 */
export const orders = (): PlacedOrder[] => placedOrders().filter((o) => o.paidWith !== 'unpaid' && !o.ref.startsWith('unknown'));

/** Orders of this run paid with agent credits. */
export const creditOrders = (): PlacedOrder[] => placedOrders().filter((o) => /credit/i.test(o.paidWith));
