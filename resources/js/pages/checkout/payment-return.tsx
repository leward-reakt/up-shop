import { Head, Link, usePage } from '@inertiajs/react';
import { Price } from '@/components/price';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CatalogCategory } from '@/types';

type PaymentReturnOrder = {
    order_number: string;
    customer_name: string;
    payment_method: string;
    payment_method_label: string;
    payment_status: string;
    payment_status_label: string;
    grand_total: number;
};

type PaymentReturnProps = {
    context: 'status' | 'success' | 'cancelled';
    can_resume: boolean;
    is_authenticated: boolean;
    order: PaymentReturnOrder;
};

type SharedProps = {
    errors?: Record<string, string>;
    store?: {
        theme?: 'default' | 'fashion_editorial';
        navigation_categories?: CatalogCategory[];
    };
};

function statusCopy(
    order: PaymentReturnOrder,
    context: PaymentReturnProps['context'],
): {
    title: string;
    description: string;
} {
    switch (order.payment_status) {
        case 'paid':
            return {
                title: 'Payment confirmed',
                description:
                    'Your payment has been verified and recorded for this order.',
            };

        case 'failed':
            return {
                title: 'Payment failed',
                description:
                    'The payment could not be completed. Review the order before attempting payment again.',
            };

        case 'cancelled':
            return {
                title: 'Payment cancelled',
                description:
                    'This payment has been cancelled and can no longer be resumed.',
            };

        case 'refunded':
            return {
                title: 'Payment refunded',
                description:
                    'The payment for this order has been refunded.',
            };

        default:
            if (context === 'cancelled') {
                return {
                    title: 'Payment not completed',
                    description:
                        'You returned from PayMongo without a verified payment. Your order is still saved and the payment remains pending.',
                };
            }

            if (context === 'success') {
                return {
                    title: 'Payment verification pending',
                    description:
                        'You returned from PayMongo, but the browser return is not proof of payment. We will show the payment as confirmed only after server-side verification.',
                };
            }

            return {
                title: 'Payment pending',
                description:
                    'Your order is saved. You can resume the PayMongo payment while this order remains eligible.',
            };
    }
}

export default function PaymentReturn({
    context,
    can_resume,
    is_authenticated,
    order,
}: PaymentReturnProps) {
    const page = usePage();

    const sharedProps = page.props as unknown as SharedProps;

    const isFashionEditorial =
        sharedProps.store?.theme === 'fashion_editorial';

    const navigationCategories =
        sharedProps.store?.navigation_categories ?? [];

    const paymentError = sharedProps.errors?.payment;

    const copy = statusCopy(order, context);

    return (
        <StorefrontLayout
            variant={isFashionEditorial ? 'fashion-editorial' : undefined}
            navigationCategories={navigationCategories}
        >
            <Head title={copy.title} />

            <main className="mx-auto w-full max-w-3xl px-5 py-12 sm:px-8 sm:py-16 lg:py-20">
                <div className="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm sm:p-8">
                    <p className="text-xs font-medium tracking-[0.16em] text-neutral-500 uppercase">
                        Order {order.order_number}
                    </p>

                    <h1 className="mt-3 text-3xl font-semibold tracking-tight text-neutral-950 sm:text-4xl">
                        {copy.title}
                    </h1>

                    <p className="mt-4 text-sm leading-7 text-neutral-600">
                        {copy.description}
                    </p>

                    {paymentError && (
                        <div
                            role="alert"
                            className="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                        >
                            {paymentError}
                        </div>
                    )}

                    <dl className="mt-8 divide-y divide-neutral-200 border-y border-neutral-200">
                        <div className="flex items-center justify-between gap-6 py-4">
                            <dt className="text-sm text-neutral-600">
                                Payment method
                            </dt>
                            <dd className="text-sm font-medium text-neutral-950">
                                {order.payment_method_label}
                            </dd>
                        </div>

                        <div className="flex items-center justify-between gap-6 py-4">
                            <dt className="text-sm text-neutral-600">
                                Payment status
                            </dt>
                            <dd className="text-sm font-medium text-neutral-950">
                                {order.payment_status_label}
                            </dd>
                        </div>

                        <div className="flex items-center justify-between gap-6 py-4">
                            <dt className="text-sm text-neutral-600">
                                Order total
                            </dt>
                            <dd className="text-sm font-semibold text-neutral-950">
                                <Price amount={order.grand_total} />
                            </dd>
                        </div>
                    </dl>

                    <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                        {can_resume && (
                            <Link
                                href={`/checkout/payment/${encodeURIComponent(
                                    order.order_number,
                                )}/resume`}
                                method="post"
                                as="button"
                                className="inline-flex min-h-11 items-center justify-center rounded-lg bg-neutral-950 px-5 py-3 text-sm font-medium text-white transition hover:bg-neutral-800"
                            >
                                Resume payment
                            </Link>
                        )}

                        {is_authenticated && (
                            <Link
                                href="/account/orders"
                                className="inline-flex min-h-11 items-center justify-center rounded-lg border border-neutral-300 px-5 py-3 text-sm font-medium text-neutral-900 transition hover:bg-neutral-50"
                            >
                                View orders
                            </Link>
                        )}

                        <Link
                            href="/shop"
                            className="inline-flex min-h-11 items-center justify-center rounded-lg border border-neutral-300 px-5 py-3 text-sm font-medium text-neutral-900 transition hover:bg-neutral-50"
                        >
                            Continue shopping
                        </Link>
                    </div>
                </div>
            </main>
        </StorefrontLayout>
    );
}
