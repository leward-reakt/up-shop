import { Link, usePage } from '@inertiajs/react';
import { Menu, Search, ShoppingBag, UserRound } from 'lucide-react';
import { useState } from 'react';
import type { PropsWithChildren } from 'react';
import { StorefrontSeo } from '@/components/storefront-seo';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { CatalogCategory } from '@/types';

type StorefrontSharedProps = {
    auth?: {
        user?: {
            name: string;
        } | null;
    };

    cart?: {
        guest_has_items?: boolean;
    };

    store?: {
        name?: string;
        logo_url?: string | null;
        email?: string | null;
        contact_number?: string | null;
        business_address?: string | null;
    };
};

type StorefrontLayoutProps = PropsWithChildren<{
    variant?: 'default' | 'fashion-editorial';
    navigationCategories?: CatalogCategory[];
}>;

export default function StorefrontLayout({
    children,
    variant = 'default',
    navigationCategories = [],
}: StorefrontLayoutProps) {
    const page = usePage();

    const props = page.props as unknown as StorefrontSharedProps;

    const auth = props.auth;

    const storeName = props.store?.name || 'Up Shop';

    const [loginDialogOpen, setLoginDialogOpen] = useState(false);

    const shouldConfirmGuestCartSync =
        !auth?.user && Boolean(props.cart?.guest_has_items);

    const isFashionEditorial = variant === 'fashion-editorial';

    return (
        <div
            className={
                isFashionEditorial
                    ? 'min-h-screen bg-[#f4f1eb] text-neutral-950'
                    : 'min-h-screen bg-neutral-50 text-neutral-950'
            }
        >
            <StorefrontSeo />

            {isFashionEditorial ? (
                <>
                    <div className="bg-neutral-950 px-4 py-2 text-center text-[10px] font-medium tracking-[0.16em] text-white uppercase">
                        New collection available now.
                    </div>

                    <header className="border-b border-neutral-200 bg-[#f8f6f1]">
                        <div className="mx-auto grid max-w-[1600px] grid-cols-[1fr_auto_1fr] items-center gap-4 px-4 py-5 sm:px-6 lg:px-10">
                            <nav
                                aria-label="Primary navigation"
                                className="hidden min-w-0 items-center gap-5 overflow-hidden text-[10px] font-medium tracking-[0.14em] uppercase xl:flex"
                            >
                                <Link
                                    href="/shop"
                                    className="transition-opacity hover:opacity-60"
                                >
                                    Shop
                                </Link>

                                {navigationCategories
                                    .slice(0, 4)
                                    .map((category) => (
                                        <Link
                                            key={category.id}
                                            href={`/shop?category=${category.slug}`}
                                            className="truncate transition-opacity hover:opacity-60"
                                        >
                                            {category.name}
                                        </Link>
                                    ))}

                                <Link
                                    href="/about"
                                    className="transition-opacity hover:opacity-60"
                                >
                                    Stories
                                </Link>
                            </nav>

                            <Link
                                href="/"
                                className="justify-self-center font-serif text-xl tracking-[0.14em] whitespace-nowrap uppercase sm:text-2xl"
                            >
                                {storeName}
                            </Link>

                            <div className="flex items-center justify-end gap-4">
                                <Link
                                    href="/shop"
                                    aria-label="Search products"
                                    className="transition-opacity hover:opacity-60"
                                >
                                    <Search
                                        aria-hidden="true"
                                        className="size-5"
                                        strokeWidth={1.5}
                                    />
                                </Link>

                                {auth?.user ? (
                                    <Link
                                        href="/dashboard"
                                        aria-label="Account"
                                        className="transition-opacity hover:opacity-60"
                                    >
                                        <UserRound
                                            aria-hidden="true"
                                            className="size-5"
                                            strokeWidth={1.5}
                                        />
                                    </Link>
                                ) : shouldConfirmGuestCartSync ? (
                                    <button
                                        type="button"
                                        aria-label="Log in"
                                        className="transition-opacity hover:opacity-60"
                                        onClick={() => setLoginDialogOpen(true)}
                                    >
                                        <UserRound
                                            aria-hidden="true"
                                            className="size-5"
                                            strokeWidth={1.5}
                                        />
                                    </button>
                                ) : (
                                    <Link
                                        href="/login"
                                        aria-label="Log in"
                                        className="transition-opacity hover:opacity-60"
                                    >
                                        <UserRound
                                            aria-hidden="true"
                                            className="size-5"
                                            strokeWidth={1.5}
                                        />
                                    </Link>
                                )}

                                <Link
                                    href="/cart"
                                    aria-label="Shopping cart"
                                    className="transition-opacity hover:opacity-60"
                                >
                                    <ShoppingBag
                                        aria-hidden="true"
                                        className="size-5"
                                        strokeWidth={1.5}
                                    />
                                </Link>

                                <details className="relative xl:hidden">
                                    <summary
                                        aria-label="Open navigation"
                                        className="cursor-pointer list-none [&::-webkit-details-marker]:hidden"
                                    >
                                        <Menu
                                            aria-hidden="true"
                                            className="size-5"
                                            strokeWidth={1.5}
                                        />
                                    </summary>

                                    <nav
                                        aria-label="Mobile navigation"
                                        className="absolute right-0 z-50 mt-4 grid w-64 border border-neutral-200 bg-[#f8f6f1] p-5 text-xs tracking-[0.12em] uppercase shadow-xl"
                                    >
                                        <Link
                                            href="/shop"
                                            className="border-b border-neutral-200 py-3"
                                        >
                                            Shop all
                                        </Link>

                                        {navigationCategories.map(
                                            (category) => (
                                                <Link
                                                    key={category.id}
                                                    href={`/shop?category=${category.slug}`}
                                                    className="border-b border-neutral-200 py-3"
                                                >
                                                    {category.name}
                                                </Link>
                                            ),
                                        )}

                                        <Link href="/about" className="py-3">
                                            Stories
                                        </Link>
                                    </nav>
                                </details>
                            </div>
                        </div>
                    </header>
                </>
            ) : (
                <header className="border-b bg-white">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                        <Link
                            href="/"
                            className="flex min-w-0 items-center gap-3"
                        >
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

                            <Link
                                href="/shop"
                                className="hover:text-neutral-600"
                            >
                                Shop
                            </Link>

                            <Link
                                href="/cart"
                                className="hover:text-neutral-600"
                            >
                                Cart
                            </Link>

                            {auth?.user ? (
                                <Link
                                    href="/dashboard"
                                    className="hover:text-neutral-600"
                                >
                                    Account
                                </Link>
                            ) : shouldConfirmGuestCartSync ? (
                                <button
                                    type="button"
                                    className="hover:text-neutral-600"
                                    onClick={() => setLoginDialogOpen(true)}
                                >
                                    Log in
                                </button>
                            ) : (
                                <Link
                                    href="/login"
                                    className="hover:text-neutral-600"
                                >
                                    Log in
                                </Link>
                            )}
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

                                {auth?.user ? (
                                    <Link
                                        href="/dashboard"
                                        className="rounded-lg px-3 py-2 hover:bg-neutral-100"
                                    >
                                        Account
                                    </Link>
                                ) : shouldConfirmGuestCartSync ? (
                                    <button
                                        type="button"
                                        className="rounded-lg px-3 py-2 text-left hover:bg-neutral-100"
                                        onClick={() => setLoginDialogOpen(true)}
                                    >
                                        Log in
                                    </button>
                                ) : (
                                    <Link
                                        href="/login"
                                        className="rounded-lg px-3 py-2 hover:bg-neutral-100"
                                    >
                                        Log in
                                    </Link>
                                )}
                            </nav>
                        </details>
                    </div>
                </header>
            )}

            <Dialog open={loginDialogOpen} onOpenChange={setLoginDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Sync cart to your account?</DialogTitle>

                        <DialogDescription>
                            Items in your cart will sync to your account,
                            Continue?
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setLoginDialogOpen(false)}
                        >
                            Cancel
                        </Button>

                        <Button asChild>
                            <Link
                                href="/login?sync_cart=1"
                                onClick={() => setLoginDialogOpen(false)}
                            >
                                Continue
                            </Link>
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <main>{children}</main>

            {isFashionEditorial ? (
                <footer className="border-t border-neutral-200 bg-[#f8f6f1]">
                    <div className="mx-auto grid max-w-[1600px] gap-10 px-5 py-12 text-xs sm:grid-cols-2 lg:grid-cols-4 lg:px-10">
                        <div>
                            <p className="mb-4 font-medium tracking-[0.12em] uppercase">
                                Client services
                            </p>

                            <nav className="grid gap-2 text-neutral-600">
                                <Link href="/contact">Contact us</Link>

                                <Link href="/shipping-policy">Shipping</Link>

                                <Link href="/return-refund-policy">
                                    Returns
                                </Link>
                            </nav>
                        </div>

                        <div>
                            <p className="mb-4 font-medium tracking-[0.12em] uppercase">
                                About
                            </p>

                            <nav className="grid gap-2 text-neutral-600">
                                <Link href="/about">Our story</Link>

                                <Link href="/shop">Collections</Link>
                            </nav>
                        </div>

                        <div>
                            <p className="mb-4 font-medium tracking-[0.12em] uppercase">
                                Legal
                            </p>

                            <nav className="grid gap-2 text-neutral-600">
                                <Link href="/terms-and-conditions">
                                    Terms &amp; Conditions
                                </Link>

                                <Link href="/privacy-policy">
                                    Privacy Policy
                                </Link>
                            </nav>
                        </div>

                        <div>
                            <p className="mb-4 font-medium tracking-[0.12em] uppercase">
                                {storeName}
                            </p>

                            <div className="grid gap-2 leading-5 text-neutral-600">
                                {props.store?.email && (
                                    <p>{props.store.email}</p>
                                )}

                                {props.store?.contact_number && (
                                    <p>{props.store.contact_number}</p>
                                )}

                                {props.store?.business_address && (
                                    <p>{props.store.business_address}</p>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="border-t border-neutral-200">
                        <div className="mx-auto flex max-w-[1600px] flex-wrap items-center justify-between gap-3 px-5 py-5 text-[10px] tracking-[0.08em] text-neutral-500 uppercase lg:px-10">
                            <p>
                                © {new Date().getFullYear()} {storeName}
                            </p>

                            <p>United States / Philippines</p>
                        </div>
                    </div>
                </footer>
            ) : (
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
                                {props.store?.email && (
                                    <p>{props.store.email}</p>
                                )}

                                {props.store?.contact_number && (
                                    <p>{props.store.contact_number}</p>
                                )}
                            </div>
                        </div>

                        <nav
                            aria-label="Footer navigation"
                            className="grid grid-cols-2 gap-x-6 gap-y-3 text-sm md:justify-self-end"
                        >
                            <Link
                                href="/about"
                                className="hover:text-neutral-600"
                            >
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
            )}
        </div>
    );
}
