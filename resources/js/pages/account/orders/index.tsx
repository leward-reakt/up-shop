import { Head, Link } from '@inertiajs/react';
import { Price } from '@/components/price';
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
    return (
        <>
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
        </>
    );
}
