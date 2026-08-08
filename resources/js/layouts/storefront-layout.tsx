import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { StorefrontSeo } from '@/components/storefront-seo';

type StorefrontSharedProps = {
    auth?: {
        user?: {
            name: string;
        } | null;
    };

    store?: {
        name?: string;
        logo_url?: string | null;
        email?: string | null;
        contact_number?: string | null;
        business_address?: string | null;
    };
};

export default function StorefrontLayout({ children }: PropsWithChildren) {
    const page = usePage();

    const props = page.props as unknown as StorefrontSharedProps;

    const auth = props.auth;

    const storeName = props.store?.name || 'Up Shop';

    const accountHref = auth?.user ? '/dashboard' : '/login';

    const accountLabel = auth?.user ? 'Account' : 'Log in';

    return (
        <div className="min-h-screen bg-neutral-50 text-neutral-950">
            <StorefrontSeo />

            <header className="border-b bg-white">
                <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <Link href="/" className="flex min-w-0 items-center gap-3">
                        {props.store?.logo_url && (
                            <img
                                src={props.store.logo_url}
                                alt={`${storeName} logo`}
                                className="h-9 w-9 shrink-0 rounded object-contain"
                            />
                        )}

                        <span className="truncate text-xl font-semibold">
                            {storeName}
                        </span>
                    </Link>

                    <nav
                        aria-label="Primary navigation"
                        className="hidden items-center gap-6 text-sm sm:flex"
                    >
                        <Link href="/" className="hover:text-neutral-600">
                            Home
                        </Link>

                        <Link href="/shop" className="hover:text-neutral-600">
                            Shop
                        </Link>

                        <Link href="/cart" className="hover:text-neutral-600">
                            Cart
                        </Link>

                        <Link
                            href={accountHref}
                            className="hover:text-neutral-600"
                        >
                            {accountLabel}
                        </Link>
                    </nav>

                    <details className="relative sm:hidden">
                        <summary className="cursor-pointer list-none rounded-lg border px-3 py-2 text-sm font-medium [&::-webkit-details-marker]:hidden">
                            Menu
                        </summary>

                        <nav
                            aria-label="Mobile navigation"
                            className="absolute right-0 z-50 mt-2 grid w-48 overflow-hidden rounded-xl border bg-white p-2 text-sm shadow-lg"
                        >
                            <Link
                                href="/"
                                className="rounded-lg px-3 py-2 hover:bg-neutral-100"
                            >
                                Home
                            </Link>

                            <Link
                                href="/shop"
                                className="rounded-lg px-3 py-2 hover:bg-neutral-100"
                            >
                                Shop
                            </Link>

                            <Link
                                href="/cart"
                                className="rounded-lg px-3 py-2 hover:bg-neutral-100"
                            >
                                Cart
                            </Link>

                            <Link
                                href={accountHref}
                                className="rounded-lg px-3 py-2 hover:bg-neutral-100"
                            >
                                {accountLabel}
                            </Link>
                        </nav>
                    </details>
                </div>
            </header>

            <main>{children}</main>

            <footer className="mt-16 border-t bg-white">
                <div className="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-2 lg:px-8">
                    <div className="max-w-md">
                        <p className="font-semibold text-neutral-950">
                            {storeName}
                        </p>

                        {props.store?.business_address && (
                            <p className="mt-3 text-sm leading-6 text-neutral-500">
                                {props.store.business_address}
                            </p>
                        )}

                        <div className="mt-3 space-y-1 text-sm text-neutral-500">
                            {props.store?.email && <p>{props.store.email}</p>}

                            {props.store?.contact_number && (
                                <p>{props.store.contact_number}</p>
                            )}
                        </div>
                    </div>

                    <nav
                        aria-label="Footer navigation"
                        className="grid grid-cols-2 gap-x-6 gap-y-3 text-sm md:justify-self-end"
                    >
                        <Link href="/about" className="hover:text-neutral-600">
                            About
                        </Link>

                        <Link
                            href="/contact"
                            className="hover:text-neutral-600"
                        >
                            Contact
                        </Link>

                        <Link
                            href="/shipping-policy"
                            className="hover:text-neutral-600"
                        >
                            Shipping
                        </Link>

                        <Link
                            href="/return-refund-policy"
                            className="hover:text-neutral-600"
                        >
                            Returns
                        </Link>

                        <Link
                            href="/privacy-policy"
                            className="hover:text-neutral-600"
                        >
                            Privacy
                        </Link>

                        <Link
                            href="/terms-and-conditions"
                            className="hover:text-neutral-600"
                        >
                            Terms
                        </Link>
                    </nav>
                </div>

                <div className="border-t">
                    <div className="mx-auto max-w-7xl px-4 py-5 text-sm text-neutral-500 sm:px-6 lg:px-8">
                        © {new Date().getFullYear()} {storeName}
                    </div>
                </div>
            </footer>
        </div>
    );
}
