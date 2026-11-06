import fs from 'node:fs';
import path from 'node:path';
import type { PricingType } from '../pages/BookingPages';

/**
 * The purchase matrix: pricing_type × ticket type × voucher_type. Each combination maps to one
 * ticket the e2e account can book, in matrix.json (built by scripts/build-matrix.php, editable by
 * hand). `ctc` (zero-price package components) is left out: agents never buy those directly.
 */

export const PRICING_TYPES: PricingType[] = ['settlement', 'fdate', 'fdatetime', 'two_way_fdatetime'];
export const TICKET_TYPES = ['normal', 'complex'] as const;
export const VOUCHER_TYPES = ['generated', 'barcode', 'pdf', 'api'] as const;

export type TicketType = (typeof TICKET_TYPES)[number];
export type VoucherType = (typeof VOUCHER_TYPES)[number];

export interface MatrixCell {
  attraction: string;
  ticket: string;
  /** Attraction — ticket name, for reading the file. */
  note?: string;
}

export interface Combination {
  key: string;
  pricing: PricingType;
  type: TicketType;
  voucher: VoucherType;
  cell: MatrixCell | null;
}

export const MATRIX_FILE = path.resolve(__dirname, '..', process.env.E2E_MATRIX_FILE?.trim() || 'matrix.json');

function loadCells(): Record<string, MatrixCell | null> {
  if (!fs.existsSync(MATRIX_FILE)) return {};
  const json = JSON.parse(fs.readFileSync(MATRIX_FILE, 'utf8')) as { cells?: Record<string, MatrixCell | null> };
  return json.cells ?? {};
}

/** Every combination, with its ticket from matrix.json (null when none is set). */
export function combinations(): Combination[] {
  const cells = loadCells();
  return PRICING_TYPES.flatMap((pricing) =>
    TICKET_TYPES.flatMap((type) =>
      VOUCHER_TYPES.map((voucher) => {
        const key = `${pricing}/${type}/${voucher}`;
        const cell = cells[key];
        return { key, pricing, type, voucher, cell: cell?.attraction && cell?.ticket ? cell : null };
      }),
    ),
  );
}
