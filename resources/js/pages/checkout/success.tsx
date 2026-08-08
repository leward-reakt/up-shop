import { Head, Link } from '@inertiajs/react';
import { Price } from '@/components/price';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CheckoutOrder } from '@/types';

type CheckoutSuccessProps = {
    order: CheckoutOrder;
};

export default function CheckoutSuccess({ order }: CheckoutSuccessProps) {
    return (
        <StorefrontLayout>
            <Head title="Order confirmed" />

            <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="rounded-xl border bg-white p-6 sm:p-8">
                    <div className="border-b pb-6">
                        <p className="text-sm font-medium text-emerald-700">
                            Order received
                        </p>

                        <h1 className="mt-2 text-3xl font-semibold">
                            Thank you, {order.customer_name}.
                        </h1>

                        <p className="mt-3 text-neutral-600">
                            Your order has been created successfully. A
                            confirmation has been recorded for{' '}
                            <strong>{order.customer_email}</strong>.
                        </p>
                    </div>

                    <div className="grid gap-6 border-b py-6 sm:grid-cols-2">
                        <div>
                            <p className="text-sm text-neutral-500">
                                Order number
                            </p>

                            <p className="mt-1 font-semibold">
                                {order.order_number}
                            </p>
                        </div>

                        <div>
                            <p className="text-sm text-neutral-500">
                                Order status
                            </p>

                            <p className="mt-1 font-semibold">
                                {order.order_status_label}
                            </p>
                        </div>

                        <div>
                            <p className="text-sm text-neutral-500">
                                Payment method
                            </p>

                            <p className="mt-1 font-semibold">
                                {order.payment_method_label}
                            </p>
                        </div>

                        <div>
                            <p className="text-sm text-neutral-500">
                                Payment status
                            </p>

                            <p className="mt-1 font-semibold">
                                {order.payment_status_label}
                            </p>
                        </div>
                    </div>

                    {order.payment_method === 'bank_transfer' && (
                        <div className="my-6 rounded-lg border bg-neutral-50 p-4">
                            <h2 className="font-medium">Bank transfer</h2>

                            <p className="mt-2 text-sm leading-6 text-neutral-600">
                                Your payment is pending manual bank transfer
                                verification. Store payment instructions can be
                                provided separately once configured by the
                                administrator.
                            </p>
                        </div>
                    )}

                    {order.payment_method === 'cash_on_delivery' && (
                        <div className="my-6 rounded-lg border bg-neutral-50 p-4">
                            <h2 className="font-medium">Cash on Delivery</h2>

                            <p className="mt-2 text-sm leading-6 text-neutral-600">
                                Payment will remain pending until payment is
                                collected and confirmed.
                            </p>
                        </div>
                    )}

                    <section className="border-b py-6">
                        <h2 className="text-lg font-semibold">Items</h2>

                        <div className="mt-5 divide-y">
                            {order.items.map((item) => (
                                <div
                                    key={`${item.sku}-${item.product_name}`}
                                    className="flex justify-between gap-6 py-4 first:pt-0 last:pb-0"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {item.product_name}
                                        </p>

                                        <p className="mt-1 text-sm text-neutral-500">
                                            SKU: {item.sku}
                                        </p>

                                        <p className="mt-1 text-sm text-neutral-500">
                                            Qty: {item.quantity}
                                        </p>
                                    </div>

                                    <Price
                                        amount={item.subtotal}
                                        className="font-medium"
                                    />
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="border-b py-6">
                        <h2 className="text-lg font-semibold">Shipping</h2>

                        <p className="mt-4 font-medium">
                            {order.shipping_method_label}
                        </p>

                        <address className="mt-3 text-sm leading-6 text-neutral-600 not-italic">
                            {order.shipping_address.address_line_1}
                            {order.shipping_address.address_line_2 && (
                                <>
                                    <br />
                                    {order.shipping_address.address_line_2}
                                </>
                            )}
                            <br />
                            {order.shipping_address.city},{' '}
                            {order.shipping_address.province}{' '}
                            {order.shipping_address.postal_code}
                            <br />
                            Philippines
                        </address>
                    </section>

                    <section className="py-6">
                        <div className="ml-auto max-w-sm space-y-3 text-sm">
                            <div className="flex justify-between gap-4">
                                <span className="text-neutral-600">
                                    Subtotal
                                </span>

                                <Price amount={order.subtotal} />
                            </div>

                            {order.discount_total > 0 && (
                                <div className="flex justify-between gap-4">
                                    <span className="text-neutral-600">
                                        Discount
                                        {order.discount_code
                                            ? ` (${order.discount_code})`
                                            : ''}
                                    </span>

                                    <span>
                                        -
                                        <Price amount={order.discount_total} />
                                    </span>
                                </div>
                            )}

                            <div className="flex justify-between gap-4">
                                <span className="text-neutral-600">
                                    Shipping
                                </span>

                                {order.shipping_total === 0 ? (
                                    <span>Free</span>
                                ) : (
                                    <Price amount={order.shipping_total} />
                                )}
                            </div>

                            {order.tax_total > 0 && (
                                <div className="flex justify-between gap-4">
                                    <span className="text-neutral-600">
                                        Tax
                                    </span>

                                    <Price amount={order.tax_total} />
                                </div>
                            )}

                            <div className="flex justify-between gap-4 border-t pt-4 text-base font-semibold">
                                <span>Total</span>

                                <Price amount={order.grand_total} />
                            </div>
                        </div>
                    </section>

                    <div className="flex flex-col gap-3 border-t pt-6 sm:flex-row">
                        <Link
                            href="/shop"
                            className="rounded-lg bg-neutral-950 px-5 py-3 text-center text-sm font-medium text-white"
                        >
                            Continue shopping
                        </Link>

                        <Link
                            href="/"
                            className="rounded-lg border px-5 py-3 text-center text-sm font-medium"
                        >
                            Back to home
                        </Link>
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    );
}
