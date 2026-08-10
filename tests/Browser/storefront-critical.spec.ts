import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';

const product = {
    name: 'Linen Blend Overshirt',
    slug: 'linen-blend-overshirt',
};

async function expectNoHorizontalOverflow(page: Page): Promise<void> {
    const dimensions = await page.evaluate(() => {
        const root = document.documentElement;

        return {
            clientWidth: root.clientWidth,
            scrollWidth: root.scrollWidth,
        };
    });

    expect(dimensions.scrollWidth).toBeLessThanOrEqual(
        dimensions.clientWidth + 1,
    );
}

test('primary navigation and shop work at the configured viewport', async ({
    page,
}, testInfo) => {
    await page.goto('/');

    await expectNoHorizontalOverflow(page);

    const usesCompactNavigation =
        testInfo.project.name === 'mobile-chromium' ||
        testInfo.project.name === 'tablet-chromium';

    if (usesCompactNavigation) {
        const menuToggle = page.getByLabel('Open navigation');

        await expect(menuToggle).toBeVisible();

        await menuToggle.click();

        const navigation = page.getByRole('navigation', {
            name: 'Mobile navigation',
        });

        await expect(navigation).toBeVisible();

        await expectNoHorizontalOverflow(page);

        await navigation
            .getByRole('link', {
                name: 'Shop all',
                exact: true,
            })
            .click();
    } else {
        const navigation = page.getByRole('navigation', {
            name: 'Primary navigation',
        });

        await expect(navigation).toBeVisible();

        await navigation
            .getByRole('link', {
                name: 'Shop',
                exact: true,
            })
            .click();
    }

    await expect(page).toHaveURL(/\/shop$/);

    await expect(
        page.getByRole('heading', {
            name: 'Shop the collection.',
        }),
    ).toBeVisible();

    await expect(
        page.getByLabel('Search', {
            exact: true,
        }),
    ).toBeVisible();

    await expect(
        page.getByRole('heading', {
            name: product.name,
        }),
    ).toBeVisible();

    await expectNoHorizontalOverflow(page);
});

test('product can move through cart to checkout in the browser', async ({
    page,
}, testInfo) => {
    await page.goto(`/products/${product.slug}`);

    await expect(
        page.getByRole('heading', {
            name: product.name,
        }),
    ).toBeVisible();

    await expect(
        page.getByRole('button', {
            name: 'Add to cart',
        }),
    ).toBeVisible();

    await expectNoHorizontalOverflow(page);

    const addToCartButton = page.getByRole('button', {
        name: 'Add to cart',
    });

    await addToCartButton.click();

    await expect(addToCartButton).toHaveText('Add to cart');

    await page
        .getByRole('link', {
            name: 'Shopping cart',
        })
        .click();

    await expect(page).toHaveURL(/\/cart$/);

    await expect(
        page.getByRole('heading', {
            name: 'Your cart.',
        }),
    ).toBeVisible();

    await expect(
        page.getByRole('link', {
            name: product.name,
            exact: true,
        }),
    ).toBeVisible();

    const checkoutLink = page.getByRole('link', {
        name: 'Proceed to checkout',
    });

    await expect(checkoutLink).toBeVisible();

    await expectNoHorizontalOverflow(page);

    await checkoutLink.click();

    await expect(page).toHaveURL(/\/checkout$/);

    await expect(
        page.getByRole('heading', {
            name: 'Complete your order.',
        }),
    ).toBeVisible();

    await expect(
        page.getByLabel('Full name', {
            exact: true,
        }),
    ).toBeVisible();

    await expect(
        page.getByRole('heading', {
            name: 'Shipping method',
        }),
    ).toBeVisible();

    await expect(
        page.getByRole('button', {
            name: 'Place order',
        }),
    ).toBeVisible();

    await expectNoHorizontalOverflow(page);

    // Keep responsive product/cart/checkout coverage on every configured
    // viewport, but create only one order for this browser-level happy path.
    if (testInfo.project.name !== 'desktop-chromium') {
        return;
    }

    await page
        .getByLabel('Full name', {
            exact: true,
        })
        .fill('Playwright Guest');

    await page
        .getByLabel('Email', {
            exact: true,
        })
        .fill('playwright.guest@example.com');

    await page
        .getByLabel('Mobile number', {
            exact: true,
        })
        .fill('09171234567');

    await page
        .getByLabel('Address', {
            exact: true,
        })
        .fill('123 Browser Test Street');

    await page
        .getByLabel('City / Municipality', {
            exact: true,
        })
        .fill('Makati');

    await page
        .getByLabel('Province', {
            exact: true,
        })
        .fill('Metro Manila');

    await page
        .getByLabel('Postal code', {
            exact: true,
        })
        .fill('1200');

    await page
        .locator(
            'input[name="payment_method"][value="cash_on_delivery"]',
        )
        .check();

    await page
        .getByRole('button', {
            name: 'Place order',
        })
        .click();

    await expect(page).toHaveURL(/\/checkout\/success$/);

    await expect(
        page.getByRole('heading', {
            name: 'Thank you, Playwright Guest.',
        }),
    ).toBeVisible();

    await expect(
        page.getByRole('heading', {
            name: 'Cash on Delivery',
            exact: true,
        }),
    ).toBeVisible();

    await expect(
        page.getByText(product.name, {
            exact: true,
        }),
    ).toBeVisible();
});

test('customer can sign in and navigate the account area', async ({ page }) => {
    await page.goto('/login');

    await page.getByLabel('Email address').fill('customer@example.com');

    await page
        .getByLabel('Password', {
            exact: true,
        })
        .fill('password');

    await page
        .getByRole('button', {
            name: 'Log in',
            exact: true,
        })
        .click();

    await expect(page).toHaveURL(/\/dashboard$/);

    await expect(
        page.getByRole('heading', {
            name: 'Your account.',
        }),
    ).toBeVisible();

    const accountNavigation = page.getByRole('navigation', {
        name: 'Account navigation',
    });

    await expect(accountNavigation).toBeVisible();

    await expect(
        accountNavigation.getByRole('link', {
            name: 'Orders',
            exact: true,
        }),
    ).toBeVisible();

    await expect(
        accountNavigation.getByRole('link', {
            name: 'Addresses',
            exact: true,
        }),
    ).toBeVisible();

    await expectNoHorizontalOverflow(page);

    await accountNavigation
        .getByRole('link', {
            name: 'Orders',
            exact: true,
        })
        .click();

    await expect(page).toHaveURL(/\/account\/orders$/);

    await expect(
        page.getByRole('heading', {
            name: 'Your orders.',
        }),
    ).toBeVisible();

    await expectNoHorizontalOverflow(page);
});

test('Filament products table renders at the tablet viewport', async ({
    page,
}, testInfo) => {
    test.skip(
        testInfo.project.name !== 'tablet-chromium',
        'Filament responsive smoke coverage only needs the tablet viewport.',
    );

    await page.goto('/admin/login');

    const loginForm = page.locator('form');

    await loginForm.locator('input[type="email"]').fill('admin@example.com');
    await loginForm.locator('input[type="password"]').fill('password');

    await loginForm.locator('button[type="submit"]').click();

    await expect(page).toHaveURL(/\/admin\/?$/);

    await page.goto('/admin/products');

    await expect(
        page.getByRole('heading', {
            name: 'Products',
            exact: true,
        }),
    ).toBeVisible();

    const table = page.getByRole('table');

    await expect(table).toBeVisible();
    await expect(table.locator('tbody tr').first()).toBeVisible();

    await expectNoHorizontalOverflow(page);
});
