import { Link, router, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import StorefrontLayout from '@/layouts/storefront-layout';
import { dashboard, logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { CatalogCategory } from '@/types/catalog';

type AccountSection =
    'overview' | 'orders' | 'addresses' | 'profile' | 'security';

type FashionAccountLayoutProps = PropsWithChildren<{
    active: AccountSection;
    title: string;
    description: string;
}>;

type AccountSharedProps = {
    store?: {
        theme?: 'default' | 'fashion_editorial';
        navigation_categories?: CatalogCategory[];
    };
};

export function useFashionAccountTheme(): boolean {
    const page = usePage();

    const sharedProps = page.props as unknown as AccountSharedProps;

    return sharedProps.store?.theme === 'fashion_editorial';
}

export default function FashionAccountLayout({
    active,
    title,
    description,
    children,
}: FashionAccountLayoutProps) {
    const page = usePage();

    const sharedProps = page.props as unknown as AccountSharedProps;

    const navigationCategories = sharedProps.store?.navigation_categories ?? [];

    const handleLogout = () => {
        router.flushAll();
    };

    const navigationItems: Array<{
        key: AccountSection;
        label: string;
        href: ReturnType<typeof dashboard> | string;
    }> = [
        {
            key: 'overview',
            label: 'Overview',
            href: dashboard(),
        },
        {
            key: 'orders',
            label: 'Orders',
            href: '/account/orders',
        },
        {
            key: 'addresses',
            label: 'Addresses',
            href: '/account/addresses',
        },
        {
            key: 'profile',
            label: 'Profile',
            href: editProfile(),
        },
        {
            key: 'security',
            label: 'Security',
            href: editSecurity(),
        },
    ];

    return (
        <StorefrontLayout
            variant="fashion-editorial"
            navigationCategories={navigationCategories}
        >
            <section className="bg-[#f8f6f1]">
                <div className="mx-auto max-w-[1600px] px-5 py-14 sm:px-8 sm:py-20 lg:px-14 lg:py-24">
                    <header className="border-b border-neutral-300 pb-10">
                        <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                            My account
                        </p>

                        <h1 className="mt-4 font-serif text-5xl leading-none font-normal tracking-[-0.035em] sm:text-6xl">
                            {title}
                        </h1>

                        <p className="mt-5 max-w-xl text-sm leading-7 text-neutral-600">
                            {description}
                        </p>
                    </header>

                    <nav
                        aria-label="Account navigation"
                        className="flex flex-wrap items-center gap-x-8 gap-y-3 border-b border-neutral-300 py-5 text-[9px] font-medium tracking-[0.14em] uppercase"
                    >
                        {navigationItems.map((item) => (
                            <Link
                                key={item.key}
                                href={item.href}
                                aria-current={
                                    active === item.key ? 'page' : undefined
                                }
                                className={
                                    active === item.key
                                        ? 'border-b border-neutral-950 pb-1'
                                        : 'pb-1 transition-opacity hover:opacity-60'
                                }
                            >
                                {item.label}
                            </Link>
                        ))}

                        <Link
                            href={logout()}
                            as="button"
                            onClick={handleLogout}
                            className="pb-1 text-red-700 transition-opacity hover:opacity-60"
                            data-test="logout-button"
                        >
                            Log out
                        </Link>
                    </nav>

                    <div className="pt-10 sm:pt-12">{children}</div>
                </div>
            </section>
        </StorefrontLayout>
    );
}
