import { Head, Link, router, usePage } from '@inertiajs/react';
import { Price } from '@/components/price';
import AppLayout from '@/layouts/app-layout';
import StorefrontLayout from '@/layouts/storefront-layout';
import { dashboard, logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type {
    AccountAddress,
    AccountDashboardSummary,
    AccountOrderSummary,
} from '@/types/account';
import type { CatalogCategory } from '@/types/catalog';

type DashboardProps = {
    summary: AccountDashboardSummary;
    recent_orders: AccountOrderSummary[];
    default_address: AccountAddress | null;
};

type DashboardSharedProps = {
    store?: {
        theme?: 'default' | 'fashion_editorial';
        navigation_categories?: CatalogCategory[];
    };
};

function formatDate(value: string | null) {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString('en-PH', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export default function Dashboard({
    summary,
    recent_orders,
    default_address,
}: DashboardProps) {
    const page = usePage();

    const sharedProps = page.props as unknown as DashboardSharedProps;

    const isFashionEditorial = sharedProps.store?.theme === 'fashion_editorial';

    const navigationCategories = sharedProps.store?.navigation_categories ?? [];

    const handleLogout = () => {
        router.flushAll();
    };

    if (isFashionEditorial) {
        return (
            <StorefrontLayout
                variant="fashion-editorial"
                navigationCategories={navigationCategories}
            >
                <Head title="My Account" />

                <section className="bg-[#f8f6f1]">
                    <div className="mx-auto max-w-[1600px] px-5 py-14 sm:px-8 sm:py-20 lg:px-14 lg:py-24">
                        <header className="border-b border-neutral-300 pb-10">
                            <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                                My account
                            </p>

                            <h1 className="mt-4 font-serif text-5xl leading-none font-normal tracking-[-0.035em] sm:text-6xl">
                                Your account.
                            </h1>

                            <p className="mt-5 max-w-xl text-sm leading-7 text-neutral-600">
                                Review your orders and manage the details
                                connected with your purchases.
                            </p>
                        </header>

                        <nav
                            aria-label="Account navigation"
                            className="flex flex-wrap items-center gap-x-8 gap-y-3 border-b border-neutral-300 py-5 text-[9px] font-medium tracking-[0.14em] uppercase"
                        >
                            <Link
                                href={dashboard()}
                                aria-current="page"
                                className="border-b border-neutral-950 pb-1"
                            >
                                Overview
                            </Link>

                            <Link
                                href="/account/orders"
                                className="pb-1 transition-opacity hover:opacity-60"
                            >
                                Orders
                            </Link>

                            <Link
                                href="/account/addresses"
                                className="pb-1 transition-opacity hover:opacity-60"
                            >
                                Addresses
                            </Link>

                            <Link
                                href={editProfile()}
                                className="pb-1 transition-opacity hover:opacity-60"
                            >
                                Profile
                            </Link>

                            <Link
                                href={editSecurity()}
                                className="pb-1 transition-opacity hover:opacity-60"
                            >
                                Security
                            </Link>

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

                        <section
                            aria-label="Order summary"
                            className="grid border-b border-neutral-300 sm:grid-cols-3"
                        >
                            <div className="py-7 sm:border-r sm:border-neutral-300 sm:pr-8">
                                <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                    Total orders
                                </p>

                                <p className="mt-4 font-serif text-4xl leading-none tracking-[-0.03em]">
                                    {summary.total_orders}
                                </p>
                            </div>

                            <div className="border-t border-neutral-300 py-7 sm:border-t-0 sm:border-r sm:px-8">
                                <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                    Active orders
                                </p>

                                <p className="mt-4 font-serif text-4xl leading-none tracking-[-0.03em]">
                                    {summary.active_orders}
                                </p>
                            </div>

                            <div className="border-t border-neutral-300 py-7 sm:border-t-0 sm:pl-8">
                                <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                    Completed orders
                                </p>

                                <p className="mt-4 font-serif text-4xl leading-none tracking-[-0.03em]">
                                    {summary.completed_orders}
                                </p>
                            </div>
                        </section>

                        <div className="grid gap-12 py-12 lg:grid-cols-[minmax(0,1.55fr)_minmax(300px,0.65fr)] lg:gap-16 xl:gap-20">
                            <section>
                                <div className="flex items-end justify-between gap-6">
                                    <div>
                                        <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                            Order history
                                        </p>

                                        <h2 className="mt-3 font-serif text-3xl leading-tight tracking-[-0.025em]">
                                            Recent orders
                                        </h2>
                                    </div>

                                    <Link
                                        href="/account/orders"
                                        className="inline-flex min-h-9 items-center border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60"
                                    >
                                        View all
                                    </Link>
                                </div>

                                {recent_orders.length === 0 ? (
                                    <div className="mt-8 border-y border-neutral-300 py-12">
                                        <p className="font-serif text-2xl tracking-[-0.02em]">
                                            No orders yet.
                                        </p>

                                        <p className="mt-3 max-w-md text-sm leading-7 text-neutral-600">
                                            Your purchases will appear here
                                            after you complete checkout.
                                        </p>

                                        <Link
                                            href="/shop"
                                            className="mt-7 inline-flex min-h-10 items-center border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60"
                                        >
                                            Discover the collection
                                        </Link>
                                    </div>
                                ) : (
                                    <div className="mt-8 border-t border-neutral-300">
                                        {recent_orders.map((order) => (
                                            <Link
                                                key={order.id}
                                                href={`/account/orders/${order.id}`}
                                                className="grid gap-5 border-b border-neutral-300 py-6 transition-opacity hover:opacity-60 sm:grid-cols-[minmax(0,1fr)_auto]"
                                            >
                                                <div>
                                                    <p className="font-serif text-xl leading-tight tracking-[-0.02em]">
                                                        {order.order_number}
                                                    </p>

                                                    <p className="mt-2 text-[9px] tracking-[0.12em] text-neutral-500 uppercase">
                                                        {formatDate(
                                                            order.created_at,
                                                        )}
                                                    </p>
                                                </div>

                                                <div className="grid grid-cols-2 gap-8 sm:flex sm:items-start sm:gap-12">
                                                    <div>
                                                        <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                                            Status
                                                        </p>

                                                        <p className="mt-2 text-xs text-neutral-700">
                                                            {
                                                                order.order_status_label
                                                            }
                                                        </p>

                                                        <p className="mt-1 text-[10px] text-neutral-500">
                                                            Payment:{' '}
                                                            {
                                                                order.payment_status_label
                                                            }
                                                        </p>
                                                    </div>

                                                    <div className="sm:min-w-28 sm:text-right">
                                                        <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                                            Total
                                                        </p>

                                                        <Price
                                                            amount={
                                                                order.grand_total
                                                            }
                                                            className="mt-2 block font-serif text-lg"
                                                        />
                                                    </div>
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </section>

                            <aside className="border-t border-neutral-300 pt-8 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-10 xl:pl-12">
                                <section>
                                    <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                        Delivery
                                    </p>

                                    <div className="mt-3 flex items-end justify-between gap-5">
                                        <h2 className="font-serif text-3xl leading-tight tracking-[-0.025em]">
                                            Default address
                                        </h2>
                                    </div>

                                    {default_address === null ? (
                                        <div className="mt-7 border-t border-neutral-300 pt-6">
                                            <p className="text-sm leading-7 text-neutral-600">
                                                No saved shipping address yet.
                                            </p>

                                            <Link
                                                href="/account/addresses"
                                                className="mt-6 inline-flex min-h-10 items-center border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60"
                                            >
                                                Add address
                                            </Link>
                                        </div>
                                    ) : (
                                        <div className="mt-7 border-t border-neutral-300 pt-6">
                                            <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                                {default_address.label ??
                                                    'Default'}
                                            </p>

                                            <p className="mt-4 font-serif text-xl tracking-[-0.02em]">
                                                {default_address.recipient_name}
                                            </p>

                                            <p className="mt-4 text-sm leading-7 text-neutral-600">
                                                {default_address.address_line_1}
                                                <br />
                                                {default_address.address_line_2 && (
                                                    <>
                                                        {
                                                            default_address.address_line_2
                                                        }
                                                        <br />
                                                    </>
                                                )}
                                                {default_address.city},{' '}
                                                {default_address.province}{' '}
                                                {default_address.postal_code}
                                                <br />
                                                {default_address.phone}
                                            </p>

                                            <Link
                                                href="/account/addresses"
                                                className="mt-6 inline-flex min-h-10 items-center border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60"
                                            >
                                                Manage addresses
                                            </Link>
                                        </div>
                                    )}
                                </section>

                                <section className="mt-12 border-t border-neutral-300 pt-8">
                                    <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                        Account details
                                    </p>

                                    <div className="mt-5 grid border-t border-neutral-300">
                                        <Link
                                            href={editProfile()}
                                            className="flex items-center justify-between border-b border-neutral-300 py-4 text-xs transition-opacity hover:opacity-60"
                                        >
                                            <span>Profile information</span>
                                            <span aria-hidden="true">→</span>
                                        </Link>

                                        <Link
                                            href={editSecurity()}
                                            className="flex items-center justify-between border-b border-neutral-300 py-4 text-xs transition-opacity hover:opacity-60"
                                        >
                                            <span>Security</span>
                                            <span aria-hidden="true">→</span>
                                        </Link>
                                    </div>
                                </section>
                            </aside>
                        </div>
                    </div>
                </section>
            </StorefrontLayout>
        );
    }

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Dashboard',
                    href: dashboard(),
                },
            ]}
        >
            <Head title="My Account" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">My Account</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        View your orders and manage your account information.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-xl border bg-card p-5">
                        <p className="text-sm text-muted-foreground">
                            Total orders
                        </p>
                        <p className="mt-2 text-3xl font-semibold">
                            {summary.total_orders}
                        </p>
                    </div>

                    <div className="rounded-xl border bg-card p-5">
                        <p className="text-sm text-muted-foreground">
                            Active orders
                        </p>
                        <p className="mt-2 text-3xl font-semibold">
                            {summary.active_orders}
                        </p>
                    </div>

                    <div className="rounded-xl border bg-card p-5">
                        <p className="text-sm text-muted-foreground">
                            Completed orders
                        </p>
                        <p className="mt-2 text-3xl font-semibold">
                            {summary.completed_orders}
                        </p>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <section className="rounded-xl border bg-card">
                        <div className="flex items-center justify-between border-b p-5">
                            <div>
                                <h2 className="font-semibold">Recent orders</h2>
                                <p className="text-sm text-muted-foreground">
                                    Your latest purchases.
                                </p>
                            </div>

                            <Link
                                href="/account/orders"
                                className="text-sm font-medium underline underline-offset-4"
                            >
                                View all
                            </Link>
                        </div>

                        {recent_orders.length === 0 ? (
                            <div className="p-5">
                                <p className="text-sm text-muted-foreground">
                                    You have not placed any orders yet.
                                </p>

                                <Link
                                    href="/shop"
                                    className="mt-4 inline-block rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                                >
                                    Browse products
                                </Link>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {recent_orders.map((order) => (
                                    <Link
                                        key={order.id}
                                        href={`/account/orders/${order.id}`}
                                        className="flex flex-col gap-3 p-5 transition hover:bg-muted/40 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {order.order_number}
                                            </p>

                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {formatDate(order.created_at)}
                                            </p>
                                        </div>

                                        <div className="text-left sm:text-right">
                                            <Price
                                                amount={order.grand_total}
                                                className="font-medium"
                                            />

                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {order.order_status_label}
                                            </p>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </section>

                    <section className="rounded-xl border bg-card p-5">
                        <div className="flex items-center justify-between">
                            <h2 className="font-semibold">Default address</h2>

                            <Link
                                href="/account/addresses"
                                className="text-sm font-medium underline underline-offset-4"
                            >
                                Manage
                            </Link>
                        </div>

                        {default_address === null ? (
                            <div className="mt-4">
                                <p className="text-sm text-muted-foreground">
                                    No saved shipping address yet.
                                </p>

                                <Link
                                    href="/account/addresses"
                                    className="mt-4 inline-block rounded-lg border px-4 py-2 text-sm font-medium"
                                >
                                    Add address
                                </Link>
                            </div>
                        ) : (
                            <div className="mt-4 text-sm">
                                <p className="font-medium">
                                    {default_address.label ??
                                        default_address.recipient_name}
                                </p>

                                <p className="mt-2 text-muted-foreground">
                                    {default_address.recipient_name}
                                    <br />
                                    {default_address.address_line_1}
                                    <br />
                                    {default_address.address_line_2 && (
                                        <>
                                            {default_address.address_line_2}
                                            <br />
                                        </>
                                    )}
                                    {default_address.city},{' '}
                                    {default_address.province}{' '}
                                    {default_address.postal_code}
                                    <br />
                                    {default_address.phone}
                                </p>
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
