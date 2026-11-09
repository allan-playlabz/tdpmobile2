import { type Locator, type Page } from '@playwright/test';

/** Navbar from templates/layout/default.php, shown on every logged-in page. */
export class AppShell {
  readonly navbar: Locator;
  readonly brand: Locator;
  readonly credits: Locator;
  readonly cartLink: Locator;
  readonly cartBadge: Locator;
  readonly userMenuToggle: Locator;
  readonly userMenu: Locator;

  constructor(private readonly page: Page) {
    this.navbar = page.locator('nav.navbar');
    this.brand = page.locator('a.navbar-brand');
    this.credits = this.navbar.locator('.nav-link', { hasText: 'Credits' });
    this.cartLink = this.navbar.locator('a[href$="/orders/cart"]');
    this.cartBadge = this.cartLink.locator('.badge');
    // The language and user menus both use id="navbarDropdown"; the user menu is the last one.
    this.userMenuToggle = this.navbar.locator('.nav-item.dropdown > a.dropdown-toggle').last();
    this.userMenu = this.navbar.locator('.nav-item.dropdown').last().locator('.dropdown-menu');
  }

  /** Opens the user dropdown, expanding the collapsed navbar first on small screens. */
  async openUserMenu(): Promise<void> {
    const toggler = this.page.locator('.navbar-toggler');
    if (await toggler.isVisible()) {
      if ((await toggler.getAttribute('aria-expanded')) !== 'true') {
        await toggler.click();
        await this.userMenuToggle.waitFor({ state: 'visible' });
      }
    }
    if (!(await this.userMenu.isVisible())) await this.userMenuToggle.click();
    await this.userMenu.waitFor({ state: 'visible' });
  }

  menuItem(path: string): Locator {
    return this.userMenu.locator(`a.dropdown-item[href$="${path}"]`);
  }

  async goViaMenu(path: string): Promise<void> {
    await this.openUserMenu();
    await Promise.all([this.page.waitForURL((u) => u.pathname.endsWith(path)), this.menuItem(path).click()]);
  }
}
