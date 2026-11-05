import fs from 'node:fs';
import path from 'node:path';
import { test } from '@playwright/test';

/**
 * Orders placed during this run (one JSON line each). Lives in test-results/, which Playwright
 * empties at the start of every run, so it only ever holds this run's orders (the pages project reads it).
 */
export const ORDERS_LOG = path.resolve(__dirname, '..', 'test-results', 'orders.jsonl');

/** Every order any run has placed, kept across runs so test orders can be found and cleaned up. */
export const ORDERS_HISTORY = path.resolve(__dirname, '..', 'placed-orders.jsonl');

export type PlacedOrder = { ref: string; paidWith: string; total?: string; attraction?: string };

/** Records an order created by the current test: report annotation + a line in both logs. */
export function recordOrder(order: PlacedOrder): void {
  test.info().annotations.push({ type: 'order', description: `${order.ref} (${order.paidWith}${order.total ? `, ${order.total}` : ''})` });
  const line = JSON.stringify({ ...order, test: test.info().title, at: new Date().toISOString() }) + '\n';
  fs.mkdirSync(path.dirname(ORDERS_LOG), { recursive: true });
  fs.appendFileSync(ORDERS_LOG, line);
  fs.appendFileSync(ORDERS_HISTORY, line);
}

/** Orders recorded earlier in this run (e.g. by the ordering project, read by the pages project). */
export function placedOrders(): PlacedOrder[] {
  if (!fs.existsSync(ORDERS_LOG)) {
    return [];
  }
  return fs.readFileSync(ORDERS_LOG, 'utf8').split('\n').filter(Boolean).map((line) => JSON.parse(line) as PlacedOrder);
}
