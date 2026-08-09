import { Head, Link } from '@inertiajs/react';
import { Price } from '@/components/price';
import AppLayout from '@/layouts/app-layout';
import FashionAccountLayout, {
    useFashionAccountTheme,
} from '@/layouts/fashion-account-layout';
import type { AccountOrderDetails } from '@/types/account';

type OrderProps = {
    order: AccountOrderDetails;
    bank_transfer_instructions: string | null;
    pickup_location: string | null;
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

export default function OrderDetails({
    order,
    bank_transfer_instructions,
    pickup_location,
}: OrderProps) {
    const isFashionEditorial = useFashionAccountTheme();

    const showBankTransferInstructions =
        order.payment_method === 'bank_transfer' &&
        order.payment_status === 'pending';

    const isStorePickup = order.shipping_method === 'store_pickup';

    const pickupLocation = pickup_location?.trim() || null;

    if (isFashionEditorial) {
        return (
            <FashionAccountLayout
                active="orders"
                title={order.order_number}
                description={`Placed ${formatDate(order.created_at)}. Review the items, fulfillment information, payment details, and totals for this purchase.`}
            >
                <Head title={`Order ${order.order_number}`} />

                <div className="flex flex-wrap gap-x-10 gap-y-5 border-b border-neutral-300 pb-8">
                    <div>
                        <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                            Order status
                        </p>
                        <p className="mt-2 text-sm">
                            {order.order_status_label}
                        </p>
                    </div>

                    <div>
                        <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                            Payment
                        </p>
                        <p className="mt-2 text-sm">
                            {order.payment_status_label}
                        </p>
                    </div>

                    <div>
                        <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                            Total
                        </p>
                        <Price
                            amount={order.grand_total}
                            className="mt-2 block font-serif text-xl"
                        />
                    </div>
                </div>

                <div className="grid gap-12 pt-10 lg:grid-cols-[minmax(0,1.6fr)_minmax(300px,0.7fr)] lg:gap-16">
                    <div className="space-y-12">
                        <section>
                            <div>
                                <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                    Purchase
                                </p>

                                <h2 className="mt-3 font-serif text-3xl tracking-[-0.025em]">
                                    Items
                                </h2>
                            </div>

                            <div className="mt-7 overflow-x-auto border-t border-neutral-300">
                                <table className="w-full min-w-[640px] text-sm">
                                    <thead className="border-b border-neutral-300 text-left text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                        <tr>
                                            <th className="py-4 pr-6 font-medium">
                                                Product
                                            </th>
                                            <th className="px-4 py-4 font-medium">
                                                Qty
                                            </th>
                                            <th className="px-4 py-4 text-right font-medium">
                                                Price
                                            </th>
                                            <th className="py-4 pl-6 text-right font-medium">
                                                Subtotal
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        {order.items.map((item) => (
                                            <tr
                                                key={item.id}
                                                className="border-b border-neutral-300"
                                            >
                                                <td className="py-6 pr-6">
                                                    <p className="font-serif text-xl tracking-[-0.02em]">
                                                        {item.product_name}
                                                    </p>

                                                    <p className="mt-2 text-[9px] tracking-[0.12em] text-neutral-500 uppercase">
                                                        SKU {item.sku}
                                                    </p>
                                                </td>

                                                <td className="px-4 py-6">
                                                    {item.quantity}
                                                </td>

                                                <td className="px-4 py-6 text-right">
                                                    <Price
                                                        amount={item.unit_price}
                                                    />
                                                </td>

                                                <td className="py-6 pl-6 text-right font-medium">
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

                        <section className="border-t border-neutral-300 pt-8">
                            <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                {isStorePickup ? 'Pickup' : 'Delivery'}
                            </p>

                            <h2 className="mt-3 font-serif text-3xl tracking-[-0.025em]">
                                {isStorePickup
                                    ? 'Pickup information'
                                    : 'Shipping information'}
                            </h2>

                            <div className="mt-7 grid gap-8 sm:grid-cols-2">
                                <div>
                                    <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                        Recipient
                                    </p>

                                    <p className="mt-4 font-serif text-xl">
                                        {order.customer_name}
                                    </p>

                                    <p className="mt-3 text-sm leading-7 text-neutral-600">
                                        {order.customer_email}
                                        <br />
                                        {order.customer_phone}
                                    </p>
                                </div>

                                <div>
                                    <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                        {order.shipping_method_label}
                                    </p>

                                    {isStorePickup ? (
                                        <div className="mt-4">
                                            <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                                Pickup location
                                            </p>

                                            {pickupLocation ? (
                                                <p className="mt-3 text-sm leading-7 whitespace-pre-line text-neutral-600">
                                                    {pickupLocation}
                                                </p>
                                            ) : (
                                                <p className="mt-3 text-sm leading-7 text-neutral-600">
                                                    Pickup location was not
                                                    captured for this order.
                                                    Contact the store before
                                                    collection.
                                                </p>
                                            )}
                                        </div>
                                    ) : (
                                        <p className="mt-4 text-sm leading-7 text-neutral-600">
                                            {
                                                order.shipping_address
                                                    .address_line_1
                                            }
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
                                    )}
                                </div>
                            </div>
                        </section>

                        {order.customer_notes && (
                            <section className="border-t border-neutral-300 pt-8">
                                <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                    Notes
                                </p>

                                <h2 className="mt-3 font-serif text-3xl tracking-[-0.025em]">
                                    Order notes
                                </h2>

                                <p className="mt-5 max-w-2xl text-sm leading-7 whitespace-pre-wrap text-neutral-600">
                                    {order.customer_notes}
                                </p>
                            </section>
                        )}
                    </div>

                    <aside className="border-t border-neutral-300 pt-8 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-10 xl:pl-12">
                        <section>
                            <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                Payment
                            </p>

                            <h2 className="mt-3 font-serif text-3xl tracking-[-0.025em]">
                                Payment details
                            </h2>

                            <dl className="mt-6 border-t border-neutral-300 text-sm">
                                <div className="flex justify-between gap-5 border-b border-neutral-300 py-4">
                                    <dt className="text-neutral-500">Method</dt>
                                    <dd className="text-right">
                                        {order.payment_method_label}
                                    </dd>
                                </div>

                                <div className="flex justify-between gap-5 border-b border-neutral-300 py-4">
                                    <dt className="text-neutral-500">Status</dt>
                                    <dd className="text-right">
                                        {order.payment_status_label}
                                    </dd>
                                </div>

                                {order.payment?.reference && (
                                    <div className="flex justify-between gap-5 border-b border-neutral-300 py-4">
                                        <dt className="text-neutral-500">
                                            Reference
                                        </dt>
                                        <dd className="max-w-[180px] text-right break-all">
                                            {order.payment.reference}
                                        </dd>
                                    </div>
                                )}

                                {order.payment?.paid_at && (
                                    <div className="flex justify-between gap-5 border-b border-neutral-300 py-4">
                                        <dt className="text-neutral-500">
                                            Paid
                                        </dt>
                                        <dd className="text-right">
                                            {formatDate(order.payment.paid_at)}
                                        </dd>
                                    </div>
                                )}
                            </dl>

                            {showBankTransferInstructions && (
                                <div className="mt-6 border-y border-neutral-300 bg-[#eee8e1] px-4 py-5">
                                    <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                        Bank transfer instructions
                                    </p>

                                    {bank_transfer_instructions ? (
                                        <>
                                            <p className="mt-4 border-l border-neutral-300 pl-4 text-sm leading-7 whitespace-pre-line text-neutral-700">
                                                {bank_transfer_instructions}
                                            </p>

                                            <p className="mt-4 text-sm leading-7 text-neutral-600">
                                                Your payment will remain pending
                                                until the transfer is manually
                                                verified.
                                            </p>
                                        </>
                                    ) : (
                                        <p className="mt-4 text-sm leading-7 text-neutral-600">
                                            Bank transfer instructions are
                                            currently unavailable. Contact the
                                            store before sending payment.
                                        </p>
                                    )}
                                </div>
                            )}
                        </section>

                        <section className="mt-12">
                            <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                Summary
                            </p>

                            <h2 className="mt-3 font-serif text-3xl tracking-[-0.025em]">
                                Order total
                            </h2>

                            <dl className="mt-6 border-t border-neutral-300 text-sm">
                                <div className="flex justify-between gap-5 border-b border-neutral-300 py-4">
                                    <dt className="text-neutral-500">
                                        Subtotal
                                    </dt>
                                    <dd>
                                        <Price amount={order.subtotal} />
                                    </dd>
                                </div>

                                {order.discount_total > 0 && (
                                    <div className="flex justify-between gap-5 border-b border-neutral-300 py-4">
                                        <dt className="text-neutral-500">
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

                                <div className="flex justify-between gap-5 border-b border-neutral-300 py-4">
                                    <dt className="text-neutral-500">
                                        Shipping
                                    </dt>
                                    <dd>
                                        <Price amount={order.shipping_total} />
                                    </dd>
                                </div>

                                {order.tax_total > 0 && (
                                    <div className="flex justify-between gap-5 border-b border-neutral-300 py-4">
                                        <dt className="text-neutral-500">
                                            Tax
                                        </dt>
                                        <dd>
                                            <Price amount={order.tax_total} />
                                        </dd>
                                    </div>
                                )}

                                <div className="flex items-end justify-between gap-5 py-5">
                                    <dt className="font-serif text-xl">
                                        Total
                                    </dt>
                                    <dd>
                                        <Price
                                            amount={order.grand_total}
                                            className="font-serif text-2xl"
                                        />
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <Link
                            href="/account/orders"
                            className="mt-8 inline-flex min-h-10 items-center border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60"
                        >
                            Back to orders
                        </Link>
                    </aside>
                </div>
            </FashionAccountLayout>
        );
    }

    return (
        <AppLayout>
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
                                {isStorePickup
                                    ? 'Pickup information'
                                    : 'Shipping information'}
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

                                    {isStorePickup ? (
                                        <div className="mt-2">
                                            <p className="font-medium">
                                                Pickup location
                                            </p>

                                            {pickupLocation ? (
                                                <p className="mt-2 whitespace-pre-line text-muted-foreground">
                                                    {pickupLocation}
                                                </p>
                                            ) : (
                                                <p className="mt-2 text-muted-foreground">
                                                    Pickup location was not
                                                    captured for this order.
                                                    Contact the store before
                                                    collection.
                                                </p>
                                            )}
                                        </div>
                                    ) : (
                                        <p className="mt-2 text-muted-foreground">
                                            {
                                                order.shipping_address
                                                    .address_line_1
                                            }
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
                                    )}
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

                            {showBankTransferInstructions && (
                                <div className="mt-5 rounded-lg border bg-muted/40 p-4">
                                    <h3 className="font-medium">
                                        Bank transfer instructions
                                    </h3>

                                    {bank_transfer_instructions ? (
                                        <>
                                            <p className="mt-3 border-l pl-4 text-sm leading-6 whitespace-pre-line text-muted-foreground">
                                                {bank_transfer_instructions}
                                            </p>

                                            <p className="mt-3 text-sm leading-6 text-muted-foreground">
                                                Your payment will remain pending
                                                until the transfer is manually
                                                verified.
                                            </p>
                                        </>
                                    ) : (
                                        <p className="mt-3 text-sm leading-6 text-muted-foreground">
                                            Bank transfer instructions are
                                            currently unavailable. Contact the
                                            store before sending payment.
                                        </p>
                                    )}
                                </div>
                            )}
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
        </AppLayout>
    );
}
