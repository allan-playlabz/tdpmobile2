import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { AUTH_DIR } from './env';

/**
 * RFC 6238 TOTP matching the app's RobThree/TwoFactorAuth setup (SHA1, 30 s, 6 digits).
 *
 * The app rejects a code whose time step was already used (TwoFactor::verify stores
 * totp_last_step), so freshTotp() remembers the last step per secret across test
 * files and waits for the next step instead of replaying one.
 */
const PERIOD = 30;
const STATE_FILE = path.join(AUTH_DIR, 'totp-steps.json');

function base32Decode(input: string): Buffer {
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  const clean = input.toUpperCase().replace(/[\s=-]/g, '');
  let bits = '';
  for (const ch of clean) {
    const v = alphabet.indexOf(ch);
    if (v < 0) throw new Error(`Invalid base32 character "${ch}" in TOTP secret`);
    bits += v.toString(2).padStart(5, '0');
  }
  const bytes: number[] = [];
  for (let i = 0; i + 8 <= bits.length; i += 8) bytes.push(parseInt(bits.slice(i, i + 8), 2));
  return Buffer.from(bytes);
}

export function totpAt(secret: string, step: number): string {
  const counter = Buffer.alloc(8);
  counter.writeBigUInt64BE(BigInt(step));
  const hmac = crypto.createHmac('sha1', base32Decode(secret)).update(counter).digest();
  const offset = hmac[hmac.length - 1] & 0x0f;
  const bin = (hmac.readUInt32BE(offset) & 0x7fffffff) % 1_000_000;
  return bin.toString().padStart(6, '0');
}

const currentStep = (): number => Math.floor(Date.now() / 1000 / PERIOD);

function readState(): Record<string, number> {
  try {
    return JSON.parse(fs.readFileSync(STATE_FILE, 'utf8'));
  } catch {
    return {};
  }
}

function writeState(state: Record<string, number>): void {
  fs.mkdirSync(AUTH_DIR, { recursive: true });
  fs.writeFileSync(STATE_FILE, JSON.stringify(state));
}

const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms));

/** A code for a time step not used before, with at least `minSecondsLeft` of validity. */
export async function freshTotp(secret: string, minSecondsLeft = 5): Promise<string> {
  const key = crypto.createHash('sha256').update(secret).digest('hex').slice(0, 16);
  for (;;) {
    const state = readState();
    const step = currentStep();
    const secondsLeft = PERIOD - (Math.floor(Date.now() / 1000) % PERIOD);
    if (step > (state[key] ?? 0) && secondsLeft >= minSecondsLeft) {
      state[key] = step;
      writeState(state);
      return totpAt(secret, step);
    }
    await sleep(secondsLeft * 1000 + 250);
  }
}

/** A 6-digit code that is certainly wrong right now (and in the drift window). */
export function wrongTotp(secret: string): string {
  const step = currentStep();
  const valid = new Set([-1, 0, 1].map((d) => totpAt(secret, step + d)));
  let code = '000000';
  for (let i = 0; valid.has(code); i++) code = String(i).padStart(6, '0');
  return code;
}
