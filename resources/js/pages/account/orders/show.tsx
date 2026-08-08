import { Head, Link } from '@inertiajs/react';
import { Price } from '@/components/price';
import type { AccountOrderDetails } from '@/types/account';

type OrderProps = {
    order: AccountOrderDetails;
};

function formatDate(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('en-PH', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export default function OrderDetails({ order }: OrderProps) {
    return (
        <>
            <Head title={`Order ${order.order_number}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <Link
                        href="/account/orders"
                        className="text-sm text-muted-foreground underline underline-offset-4"
                    >
                        Back to orders
                    </Link>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-semibold">
                                {order.order_number}
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Placed {formatDate(order.created_at)}
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-2 text-sm">
                            <span className="rounded-full border px-3 py-1">
                                {order.order_status_label}
                            </span>

                            <span className="rounded-full border px-3 py-1">
                                Payment: {order.payment_status_label}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <div className="space-y-6">
                        <section className="overflow-hidden rounded-xl border bg-card">
                            <div className="border-b p-5">
                                <h2 className="font-semibold">Items</h2>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b bg-muted/40 text-left">
                                        <tr>
                                            <th className="px-5 py-3 font-medium">
                                                Product
                                            </th>
                                            <th className="px-5 py-3 font-medium">
                                                Qty
                                            </th>
                                            <th className="px-5 py-3 text-right font-medium">
                                                Price
                                            </th>
                                            <th className="px-5 py-3 text-right font-medium">
                                                Subtotal
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody className="divide-y">
                                        {order.items.map((item) => (
                                            <tr key={item.id}>
                                                <td className="px-5 py-4">
                                                    <p className="font-medium">
                                                        {item.product_name}
                                                    </p>
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        SKU: {item.sku}
                                                    </p>
                                                </td>

                                                <td className="px-5 py-4">
                                                    {item.quantity}
                                                </td>

                                                <td className="px-5 py-4 text-right">
                                                    <Price
                                                        amount={item.unit_price}
                                                    />
                                                </td>

                                                <td className="px-5 py-4 text-right font-medium">
                                                    <Price
                                                        amount={item.subtotal}
                                                    />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section className="rounded-xl border bg-card p-5">
                            <h2 className="font-semibold">
                                Shipping information
                            </h2>

                            <div className="mt-4 grid gap-6 text-sm sm:grid-cols-2">
                                <div>
                                    <p className="font-medium">
                                        {order.customer_name}
                                    </p>

                                    <p className="mt-2 text-muted-foreground">
                                        {order.customer_email}
                                        <br />
                                        {order.customer_phone}
                                    </p>
                                </div>

                                <div>
                                    <p className="font-medium">
                                        {order.shipping_method_label}
                                    </p>

                                    <p className="mt-2 text-muted-foreground">
                                        {order.shipping_address.address_line_1}
                                        <br />
                                        {order.shipping_address
                                            .address_line_2 && (
                                            <>
                                                {
                                                    order.shipping_address
                                                        .address_line_2
                                                }
                                                <br />
                                            </>
                                        )}
                                        {order.shipping_address.city},{' '}
                                        {order.shipping_address.province}{' '}
                                        {order.shipping_address.postal_code}
                                        <br />
                                        {order.shipping_address.country}
                                    </p>
                                </div>
                            </div>
                        </section>

                        {order.customer_notes && (
                            <section className="rounded-xl border bg-card p-5">
                                <h2 className="font-semibold">Order notes</h2>
                                <p className="mt-3 text-sm whitespace-pre-wrap text-muted-foreground">
                                    {order.customer_notes}
                                </p>
                            </section>
                        )}
                    </div>

                    <div className="space-y-6">
                        <section className="rounded-xl border bg-card p-5">
                            <h2 className="font-semibold">Payment</h2>

                            <dl className="mt-4 space-y-3 text-sm">
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">
                                        Method
                                    </dt>
                                    <dd className="text-right font-medium">
                                        {order.payment_method_label}
                                    </dd>
                                </div>

                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">
                                        Status
                                    </dt>
                                    <dd className="text-right font-medium">
                                        {order.payment_status_label}
                                    </dd>
                                </div>

                                {order.payment?.reference && (
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-muted-foreground">
                                            Reference
                                        </dt>
                                        <dd className="text-right font-medium break-all">
                                            {order.payment.reference}
                                        </dd>
                                    </div>
                                )}

                                {order.payment?.paid_at && (
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-muted-foreground">
                                            Paid
                                        </dt>
                                        <dd className="text-right font-medium">
                                            {formatDate(order.payment.paid_at)}
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        </section>

                        <section className="rounded-xl border bg-card p-5">
                            <h2 className="font-semibold">Order summary</h2>

                            <dl className="mt-4 space-y-3 text-sm">
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">
                                        Subtotal
                                    </dt>
                                    <dd>
                                        <Price amount={order.subtotal} />
                                    </dd>
                                </div>

                                {order.discount_total > 0 && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">
                                            Discount
                                            {order.discount_code
                                                ? ` (${order.discount_code})`
                                                : ''}
                                        </dt>
                                        <dd>
                                            -
                                            <Price
                                                amount={order.discount_total}
                                            />
                                        </dd>
                                    </div>
                                )}

                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">
                                        Shipping
                                    </dt>
                                    <dd>
                                        <Price amount={order.shipping_total} />
                                    </dd>
                                </div>

                                {order.tax_total > 0 && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">
                                            Tax
                                        </dt>
                                        <dd>
                                            <Price amount={order.tax_total} />
                                        </dd>
                                    </div>
                                )}

                                <div className="flex justify-between border-t pt-3 text-base font-semibold">
                                    <dt>Total</dt>
                                    <dd>
                                        <Price amount={order.grand_total} />
                                    </dd>
                                </div>
                            </dl>
                        </section>
                    </div>
                </div>
            </div>
        </>
    );
}
