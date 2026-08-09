import { Head, Link, usePage } from '@inertiajs/react';
import { Price } from '@/components/price';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CatalogCategory, CheckoutOrder } from '@/types';

type CheckoutSuccessProps = {
    order: CheckoutOrder;
    bank_transfer_instructions: string | null;
};

type CheckoutSuccessSharedProps = {
    store?: {
        theme?: 'default' | 'fashion_editorial';
        navigation_categories?: CatalogCategory[];
    };
};

export default function CheckoutSuccess({
    order,
    bank_transfer_instructions,
}: CheckoutSuccessProps) {
    const page = usePage();

    const sharedProps = page.props as unknown as CheckoutSuccessSharedProps;

    const isFashionEditorial = sharedProps.store?.theme === 'fashion_editorial';

    const navigationCategories = sharedProps.store?.navigation_categories ?? [];

    if (isFashionEditorial) {
        return (
            <StorefrontLayout
                variant="fashion-editorial"
                navigationCategories={navigationCategories}
            >
                <Head title="Order confirmed" />

                <section className="bg-[#f8f6f1]">
                    <div className="mx-auto max-w-[1600px] px-5 py-14 sm:px-8 sm:py-20 lg:px-14 lg:py-24">
                        <header className="border-b border-neutral-300 pb-10 sm:pb-14">
                            <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                                Order received
                            </p>

                            <h1 className="mt-4 max-w-4xl font-serif text-5xl leading-[0.98] tracking-[-0.035em] sm:text-6xl lg:text-7xl">
                                Thank you, {order.customer_name}.
                            </h1>

                            <p className="mt-6 max-w-2xl text-sm leading-7 text-neutral-600">
                                Your order has been placed successfully. Order
                                details have been recorded for{' '}
                                <span className="text-neutral-950">
                                    {order.customer_email}
                                </span>
                                .
                            </p>
                        </header>

                        <section className="grid border-b border-neutral-300 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="border-b border-neutral-300 py-6 sm:border-r sm:pr-6 lg:border-b-0">
                                <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                    Order number
                                </p>

                                <p className="mt-3 font-serif text-lg">
                                    {order.order_number}
                                </p>
                            </div>

                            <div className="border-b border-neutral-300 py-6 sm:pl-6 lg:border-r lg:border-b-0 lg:pr-6">
                                <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                    Order status
                                </p>

                                <p className="mt-3 font-serif text-lg">
                                    {order.order_status_label}
                                </p>
                            </div>

                            <div className="border-b border-neutral-300 py-6 sm:border-r sm:pr-6 lg:border-b-0 lg:pl-6">
                                <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                    Payment method
                                </p>

                                <p className="mt-3 font-serif text-lg">
                                    {order.payment_method_label}
                                </p>
                            </div>

                            <div className="py-6 sm:pl-6">
                                <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                    Payment status
                                </p>

                                <p className="mt-3 font-serif text-lg">
                                    {order.payment_status_label}
                                </p>
                            </div>
                        </section>

                        <div className="grid gap-12 pt-10 lg:grid-cols-[minmax(0,1fr)_390px] lg:gap-14 xl:grid-cols-[minmax(0,1fr)_430px] xl:gap-20">
                            <div>
                                {order.payment_method === 'bank_transfer' && (
                                    <section className="border-y border-neutral-300 bg-[#eee8e1] px-5 py-6 sm:px-6">
                                        <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                            Payment
                                        </p>

                                        <h2 className="mt-3 font-serif text-2xl tracking-[-0.02em]">
                                            Bank transfer
                                        </h2>

                                        {bank_transfer_instructions ? (
                                            <>
                                                <p className="mt-3 max-w-xl text-sm leading-7 text-neutral-600">
                                                    Complete your payment using
                                                    the instructions below.
                                                </p>

                                                <p className="mt-4 max-w-xl border-l border-neutral-300 pl-4 text-sm leading-7 whitespace-pre-line text-neutral-700">
                                                    {bank_transfer_instructions}
                                                </p>

                                                <p className="mt-4 max-w-xl text-sm leading-7 text-neutral-600">
                                                    Your payment will remain
                                                    pending until the transfer
                                                    is manually verified.
                                                </p>
                                            </>
                                        ) : (
                                            <p className="mt-3 max-w-xl text-sm leading-7 text-neutral-600">
                                                Bank transfer instructions are
                                                currently unavailable. Contact
                                                the store before sending
                                                payment.
                                            </p>
                                        )}
                                    </section>
                                )}

                                {order.payment_method ===
                                    'cash_on_delivery' && (
                                    <section className="border-y border-neutral-300 bg-[#eee8e1] px-5 py-6 sm:px-6">
                                        <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                            Payment
                                        </p>

                                        <h2 className="mt-3 font-serif text-2xl tracking-[-0.02em]">
                                            Cash on Delivery
                                        </h2>

                                        <p className="mt-3 max-w-xl text-sm leading-7 text-neutral-600">
                                            Payment will remain pending until
                                            the amount is collected and
                                            confirmed.
                                        </p>
                                    </section>
                                )}

                                <section className="border-b border-neutral-300 py-10">
                                    <p className="text-[9px] font-medium tracking-[0.18em] text-neutral-500 uppercase">
                                        Your selection
                                    </p>

                                    <h2 className="mt-3 font-serif text-3xl tracking-[-0.025em]">
                                        Order items
                                    </h2>

                                    <div className="mt-8 border-t border-neutral-300">
                                        {order.items.map((item) => (
                                            <div
                                                key={`${item.sku}-${item.product_name}`}
                                                className="grid gap-4 border-b border-neutral-300 py-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start"
                                            >
                                                <div>
                                                    <p className="font-serif text-lg leading-6">
                                                        {item.product_name}
                                                    </p>

                                                    <div className="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-[9px] font-medium tracking-[0.12em] text-neutral-500 uppercase">
                                                        <span>
                                                            SKU {item.sku}
                                                        </span>

                                                        <span>
                                                            Quantity{' '}
                                                            {item.quantity}
                                                        </span>

                                                        <span>
                                                            Unit{' '}
                                                            <Price
                                                                amount={
                                                                    item.unit_price
                                                                }
                                                            />
                                                        </span>
                                                    </div>
                                                </div>

                                                <Price
                                                    amount={item.subtotal}
                                                    className="font-serif text-lg"
                                                />
                                            </div>
                                        ))}
                                    </div>
                                </section>

                                <section className="border-b border-neutral-300 py-10">
                                    <p className="text-[9px] font-medium tracking-[0.18em] text-neutral-500 uppercase">
                                        Delivery
                                    </p>

                                    <h2 className="mt-3 font-serif text-3xl tracking-[-0.025em]">
                                        Shipping details
                                    </h2>

                                    <div className="mt-8 grid gap-8 sm:grid-cols-2">
                                        <div>
                                            <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                                Shipping method
                                            </p>

                                            <p className="mt-3 font-serif text-lg">
                                                {order.shipping_method_label}
                                            </p>
                                        </div>

                                        <div>
                                            <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                                Delivery address
                                            </p>

                                            <address className="mt-3 text-sm leading-7 text-neutral-600 not-italic">
                                                {
                                                    order.shipping_address
                                                        .address_line_1
                                                }
                                                {order.shipping_address
                                                    .address_line_2 && (
                                                    <>
                                                        <br />

                                                        {
                                                            order
                                                                .shipping_address
                                                                .address_line_2
                                                        }
                                                    </>
                                                )}
                                                <br />
                                                {
                                                    order.shipping_address.city
                                                },{' '}
                                                {
                                                    order.shipping_address
                                                        .province
                                                }{' '}
                                                {
                                                    order.shipping_address
                                                        .postal_code
                                                }
                                                <br />
                                                Philippines
                                            </address>
                                        </div>
                                    </div>
                                </section>

                                <div className="flex flex-col gap-3 py-10 sm:flex-row">
                                    <Link
                                        href="/shop"
                                        className="inline-flex min-h-12 items-center justify-center border border-neutral-950 bg-neutral-950 px-7 text-[10px] font-medium tracking-[0.16em] text-white uppercase transition duration-300 hover:bg-transparent hover:text-neutral-950"
                                    >
                                        Continue shopping
                                    </Link>

                                    <Link
                                        href="/"
                                        className="inline-flex min-h-12 items-center justify-center border border-neutral-950 px-7 text-[10px] font-medium tracking-[0.16em] uppercase transition duration-300 hover:bg-neutral-950 hover:text-white"
                                    >
                                        Back to home
                                    </Link>
                                </div>
                            </div>

                            <aside className="lg:border-l lg:border-neutral-300 lg:pl-10 xl:pl-12">
                                <div className="sticky top-6">
                                    <p className="text-[9px] font-medium tracking-[0.18em] text-neutral-500 uppercase">
                                        Order summary
                                    </p>

                                    <h2 className="mt-3 font-serif text-3xl tracking-[-0.025em]">
                                        Total
                                    </h2>

                                    <dl className="mt-8 border-t border-neutral-300 text-sm">
                                        <div className="flex justify-between gap-6 border-b border-neutral-300 py-4">
                                            <dt className="text-xs text-neutral-600">
                                                Subtotal
                                            </dt>

                                            <dd>
                                                <Price
                                                    amount={order.subtotal}
                                                />
                                            </dd>
                                        </div>

                                        {order.discount_total > 0 && (
                                            <div className="flex justify-between gap-6 border-b border-neutral-300 py-4">
                                                <dt className="text-xs text-neutral-600">
                                                    Discount
                                                    {order.discount_code
                                                        ? ` (${order.discount_code})`
                                                        : ''}
                                                </dt>

                                                <dd>
                                                    −
                                                    <Price
                                                        amount={
                                                            order.discount_total
                                                        }
                                                    />
                                                </dd>
                                            </div>
                                        )}

                                        <div className="flex justify-between gap-6 border-b border-neutral-300 py-4">
                                            <dt className="text-xs text-neutral-600">
                                                Shipping
                                            </dt>

                                            <dd>
                                                {order.shipping_total === 0 ? (
                                                    'Free'
                                                ) : (
                                                    <Price
                                                        amount={
                                                            order.shipping_total
                                                        }
                                                    />
                                                )}
                                            </dd>
                                        </div>

                                        {order.tax_total > 0 && (
                                            <div className="flex justify-between gap-6 border-b border-neutral-300 py-4">
                                                <dt className="text-xs text-neutral-600">
                                                    Tax
                                                </dt>

                                                <dd>
                                                    <Price
                                                        amount={order.tax_total}
                                                    />
                                                </dd>
                                            </div>
                                        )}

                                        <div className="flex items-end justify-between gap-6 py-5">
                                            <dt className="font-serif text-xl">
                                                Grand total
                                            </dt>

                                            <dd>
                                                <Price
                                                    amount={order.grand_total}
                                                    className="font-serif text-xl"
                                                />
                                            </dd>
                                        </div>
                                    </dl>

                                    <div className="mt-7 border-t border-neutral-300 pt-7">
                                        <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                            Reference
                                        </p>

                                        <p className="mt-3 font-serif text-lg break-all">
                                            {order.order_number}
                                        </p>

                                        <p className="mt-4 text-xs leading-6 text-neutral-500">
                                            Keep this order number for your
                                            records and future order inquiries.
                                        </p>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>
                </section>
            </StorefrontLayout>
        );
    }

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

                            {bank_transfer_instructions ? (
                                <>
                                    <p className="mt-2 text-sm leading-6 text-neutral-600">
                                        Complete your payment using the
                                        instructions below.
                                    </p>

                                    <p className="mt-3 border-l pl-4 text-sm leading-6 whitespace-pre-line text-neutral-700">
                                        {bank_transfer_instructions}
                                    </p>

                                    <p className="mt-3 text-sm leading-6 text-neutral-600">
                                        Your payment will remain pending until
                                        the transfer is manually verified.
                                    </p>
                                </>
                            ) : (
                                <p className="mt-2 text-sm leading-6 text-neutral-600">
                                    Bank transfer instructions are currently
                                    unavailable. Contact the store before
                                    sending payment.
                                </p>
                            )}
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
