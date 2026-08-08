import { Head, Link } from '@inertiajs/react';
import { Price } from '@/components/price';
import { dashboard } from '@/routes';
import type {
    AccountAddress,
    AccountDashboardSummary,
    AccountOrderSummary,
} from '@/types/account';

type DashboardProps = {
    summary: AccountDashboardSummary;
    recent_orders: AccountOrderSummary[];
    default_address: AccountAddress | null;
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
    return (
        <>
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
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
