import path from 'node:path';
import dotenv from 'dotenv';

dotenv.config({ path: path.resolve(__dirname, '..', '.env'), quiet: true });

const str = (name: string, fallback = ''): string => (process.env[name] ?? fallback).trim();
const bool = (name: string): boolean => /^(1|true|yes)$/i.test(str(name));
const list = (name: string, fallback = ''): string[] =>
  str(name, fallback).split(',').map((s) => s.trim()).filter(Boolean);

export const AUTH_DIR = path.resolve(__dirname, '..', '.auth');
export const USER_STATE = path.join(AUTH_DIR, 'user.json');

export type Role = 'master' | 'normal' | 'viewer';

export const env = {
  baseUrl: str('BASE_URL', 'http://tdpmobile2.localhost').replace(/\/+$/, ''),
  user: {
    email: str('E2E_USER_EMAIL'),
    password: str('E2E_USER_PASSWORD'),
    totpSecret: str('E2E_USER_TOTP_SECRET'),
    role: (str('E2E_USER_ROLE', 'master') as Role),
  },
  twoFactor: {
    email: str('E2E_2FA_EMAIL'),
    password: str('E2E_2FA_PASSWORD'),
    totpSecret: str('E2E_2FA_TOTP_SECRET'),
    backupCode: str('E2E_2FA_BACKUP_CODE'),
  },
  // Kept for the original booking spec; the ordering specs use `attractions` below.
  attractionId: str('E2E_ATTRACTION_ID') || str('E2E_ATTR_SETTLEMENT'),
  ticketDate: str('E2E_TICKET_DATE'),
  /** One attraction id per case; a test skips when its id is empty. */
  attractions: {
    settlement: str('E2E_ATTR_SETTLEMENT') || str('E2E_ATTRACTION_ID'),
    fdate: str('E2E_ATTR_FDATE'),
    fdatetime: str('E2E_ATTR_FDATETIME'),
    twoWay: str('E2E_ATTR_TWO_WAY'),
    questions: str('E2E_ATTR_QUESTIONS'),
    second: str('E2E_ATTR_SECOND'),
  },
  /** Online payment methods enabled on the target (pay_by values, e.g. credit_card,paynow). */
  paymentMethods: list('E2E_PAYMENT_METHODS'),
  lowCredit: { email: str('E2E_LOW_CREDIT_EMAIL'), password: str('E2E_LOW_CREDIT_PASSWORD'), totpSecret: str('E2E_LOW_CREDIT_TOTP_SECRET') },
  normalUser: { email: str('E2E_NORMAL_EMAIL'), password: str('E2E_NORMAL_PASSWORD'), totpSecret: str('E2E_NORMAL_TOTP_SECRET') },
  /** Hosts the suite may run against; it places real orders, so anything else aborts the run. */
  allowedHosts: list('E2E_ALLOWED_HOSTS', 'tdpmobile2.localhost,tpp.localhost,127.0.0.1,localhost'),
  allowPurchase: bool('E2E_ALLOW_PURCHASE'),
  allowEmail: bool('E2E_ALLOW_EMAIL'),
  allowCancel: bool('E2E_ALLOW_CANCEL'),
  allowReports: bool('E2E_ALLOW_REPORTS'),
  allowTopup: bool('E2E_ALLOW_TOPUP'),
  allowUserAdmin: bool('E2E_ALLOW_USER_ADMIN'),
  allowPasswordChange: bool('E2E_ALLOW_PASSWORD_CHANGE'),
  startServer: bool('E2E_START_SERVER'),
  appDir: str('APP_DIR', '..'),
  phpBin: str('PHP_BIN', 'php'),
};

/** Throws unless BASE_URL's host is in E2E_ALLOWED_HOSTS (never run order-placing tests on production). */
export function assertAllowedHost(): void {
  const host = new URL(env.baseUrl).hostname;
  if (!env.allowedHosts.includes(host)) {
    throw new Error(`BASE_URL host "${host}" is not in E2E_ALLOWED_HOSTS (${env.allowedHosts.join(', ')}). Refusing to run.`);
  }
}

export const hasUser = (): boolean => !!(env.user.email && env.user.password);
export const hasTwoFactorUser = (): boolean =>
  !!(env.twoFactor.email && env.twoFactor.password && env.twoFactor.totpSecret);

/** An email no account uses, so failed-login rate limiting (keyed on IP + email) never hits a real user. */
export const throwawayEmail = (): string =>
  `e2e-nobody-${Date.now()}-${Math.floor(Math.random() * 1e6)}@example.invalid`;
