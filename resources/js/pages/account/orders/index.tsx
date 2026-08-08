import { Head, Link } from '@inertiajs/react';
import { Price } from '@/components/price';
import AppLayout from '@/layouts/app-layout';
import FashionAccountLayout, {
    useFashionAccountTheme,
} from '@/layouts/fashion-account-layout';
import type { AccountOrderSummary } from '@/types/account';
import type { Paginated } from '@/types/catalog';

type OrdersProps = {
    orders: Paginated<AccountOrderSummary>;
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

export default function Orders({ orders }: OrdersProps) {
    const isFashionEditorial = useFashionAccountTheme();

    if (isFashionEditorial) {
        return (
            <FashionAccountLayout
                active="orders"
                title="Your orders."
                description="Review your previous purchases, payment state, and current fulfilment progress."
            >
                <Head title="My Orders" />

                <section>
                    <div className="flex items-end justify-between gap-6">
                        <div>
                            <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                Order history
                            </p>

                            <h2 className="mt-3 font-serif text-3xl leading-tight tracking-[-0.025em]">
                                Purchases
                            </h2>
                        </div>

                        <Link
                            href="/shop"
                            className="inline-flex min-h-9 items-center border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60"
                        >
                            Continue shopping
                        </Link>
                    </div>

                    {orders.data.length === 0 ? (
                        <div className="mt-8 border-y border-neutral-300 py-16">
                            <p className="font-serif text-3xl tracking-[-0.025em]">
                                No orders yet.
                            </p>

                            <p className="mt-4 max-w-lg text-sm leading-7 text-neutral-600">
                                Completed checkouts will appear here so you can
                                follow each purchase from confirmation through
                                fulfilment.
                            </p>

                            <Link
                                href="/shop"
                                className="mt-8 inline-flex min-h-11 items-center border border-neutral-950 px-6 text-[9px] font-medium tracking-[0.14em] uppercase transition hover:bg-neutral-950 hover:text-white"
                            >
                                Discover the collection
                            </Link>
                        </div>
                    ) : (
                        <div className="mt-8 border-t border-neutral-300">
                            {orders.data.map((order) => (
                                <Link
                                    key={order.id}
                                    href={`/account/orders/${order.id}`}
                                    className="grid gap-6 border-b border-neutral-300 py-7 transition-opacity hover:opacity-60 md:grid-cols-[minmax(0,1fr)_180px_150px]"
                                >
                                    <div>
                                        <p className="font-serif text-2xl leading-tight tracking-[-0.02em]">
                                            {order.order_number}
                                        </p>

                                        <p className="mt-2 text-[9px] tracking-[0.12em] text-neutral-500 uppercase">
                                            {formatDate(order.created_at)}
                                        </p>
                                    </div>

                                    <div>
                                        <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                            Status
                                        </p>

                                        <p className="mt-2 text-sm text-neutral-700">
                                            {order.order_status_label}
                                        </p>

                                        <p className="mt-1 text-[10px] text-neutral-500">
                                            Payment:{' '}
                                            {order.payment_status_label}
                                        </p>
                                    </div>

                                    <div className="md:text-right">
                                        <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                            Total
                                        </p>

                                        <Price
                                            amount={order.grand_total}
                                            className="mt-2 block font-serif text-xl"
                                        />
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </section>

                {orders.last_page > 1 && (
                    <nav
                        aria-label="Order history pagination"
                        className="mt-10 flex items-center justify-between border-t border-neutral-300 pt-6"
                    >
                        {orders.prev_page_url ? (
                            <Link
                                href={orders.prev_page_url}
                                className="text-[9px] font-medium tracking-[0.14em] uppercase underline underline-offset-4"
                            >
                                Previous
                            </Link>
                        ) : (
                            <span />
                        )}

                        <p className="text-[9px] tracking-[0.12em] text-neutral-500 uppercase">
                            Page {orders.current_page} of {orders.last_page}
                        </p>

                        {orders.next_page_url ? (
                            <Link
                                href={orders.next_page_url}
                                className="text-[9px] font-medium tracking-[0.14em] uppercase underline underline-offset-4"
                            >
                                Next
                            </Link>
                        ) : (
                            <span />
                        )}
                    </nav>
                )}
            </FashionAccountLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="My Orders" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">My Orders</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        View your order history and current order status.
                    </p>
                </div>

                <div className="overflow-hidden rounded-xl border bg-card">
                    {orders.data.length === 0 ? (
                        <div className="p-8 text-center">
                            <h2 className="font-medium">No orders yet</h2>

                            <p className="mt-2 text-sm text-muted-foreground">
                                Your completed checkout orders will appear here.
                            </p>

                            <Link
                                href="/shop"
                                className="mt-5 inline-block rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                            >
                                Browse products
                            </Link>
                        </div>
                    ) : (
                        <div className="divide-y">
                            {orders.data.map((order) => (
                                <Link
                                    key={order.id}
                                    href={`/account/orders/${order.id}`}
                                    className="grid gap-4 p-5 transition hover:bg-muted/40 sm:grid-cols-[1fr_auto_auto]"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {order.order_number}
                                        </p>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {formatDate(order.created_at)}
                                        </p>
                                    </div>

                                    <div className="text-sm">
                                        <p className="text-muted-foreground">
                                            Order status
                                        </p>
                                        <p className="mt-1 font-medium">
                                            {order.order_status_label}
                                        </p>
                                    </div>

                                    <div className="text-sm sm:text-right">
                                        <p className="text-muted-foreground">
                                            Total
                                        </p>
                                        <Price
                                            amount={order.grand_total}
                                            className="mt-1 block font-medium"
                                        />
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>

                {orders.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        {orders.prev_page_url ? (
                            <Link
                                href={orders.prev_page_url}
                                className="rounded-lg border px-4 py-2 text-sm font-medium"
                            >
                                Previous
                            </Link>
                        ) : (
                            <span />
                        )}

                        <p className="text-sm text-muted-foreground">
                            Page {orders.current_page} of {orders.last_page}
                        </p>

                        {orders.next_page_url ? (
                            <Link
                                href={orders.next_page_url}
                                className="rounded-lg border px-4 py-2 text-sm font-medium"
                            >
                                Next
                            </Link>
                        ) : (
                            <span />
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
