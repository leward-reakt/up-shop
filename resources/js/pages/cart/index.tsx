import { Head, Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { CartItem } from '@/components/cart-item';
import { Price } from '@/components/price';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CartLineItem, CartTotals } from '@/types';

type CartPageProps = {
    items: CartLineItem[];
    totals: CartTotals;
};

export default function CartPage({ items, totals }: CartPageProps) {
    const discountForm = useForm<{
        discount_code: string;
    }>({
        discount_code: totals.discount_code ?? '',
    });

    const hasAvailabilityIssues = items.some((item) => !item.is_available);

    const canCheckout = !hasAvailabilityIssues && !totals.discount_error;

    const applyDiscount = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        discountForm.post('/cart/discount', {
            preserveScroll: true,
        });
    };

    const removeDiscount = () => {
        router.delete('/cart/discount', {
            preserveScroll: true,
        });
    };

    return (
        <StorefrontLayout>
            <Head title="Cart" />

            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-end justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-semibold tracking-tight">
                            Shopping cart
                        </h1>

                        <p className="mt-2 text-neutral-600">
                            Review your products and quantities.
                        </p>
                    </div>

                    <Link
                        href="/shop"
                        className="text-sm font-medium hover:text-neutral-600"
                    >
                        Continue shopping
                    </Link>
                </div>

                {items.length === 0 ? (
                    <div className="rounded-xl border bg-white px-6 py-16 text-center">
                        <h2 className="text-xl font-semibold">
                            Your cart is empty
                        </h2>

                        <p className="mt-2 text-neutral-500">
                            Browse the shop and add products to get started.
                        </p>

                        <Link
                            href="/shop"
                            className="mt-6 inline-flex rounded-lg bg-neutral-950 px-5 py-3 text-sm font-medium text-white hover:bg-neutral-800"
                        >
                            Browse products
                        </Link>
                    </div>
                ) : (
                    <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
                        <section className="space-y-4">
                            {hasAvailabilityIssues && (
                                <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                    One or more cart items need attention.
                                    Update or remove unavailable quantities
                                    before checkout.
                                </div>
                            )}

                            {items.map((item) => (
                                <CartItem key={item.product_id} item={item} />
                            ))}
                        </section>

                        <aside>
                            <div className="sticky top-6 rounded-xl border bg-white p-6">
                                <h2 className="text-lg font-semibold">
                                    Order summary
                                </h2>

                                <dl className="mt-6 space-y-4 text-sm">
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-neutral-600">
                                            Subtotal
                                        </dt>

                                        <dd>
                                            <Price amount={totals.subtotal} />
                                        </dd>
                                    </div>

                                    <div className="flex justify-between gap-4">
                                        <dt className="text-neutral-600">
                                            Shipping estimate
                                        </dt>

                                        <dd>
                                            <Price
                                                amount={totals.shipping_total}
                                            />
                                        </dd>
                                    </div>

                                    <div className="flex justify-between gap-4">
                                        <dt className="text-neutral-600">
                                            Discount
                                        </dt>

                                        <dd>
                                            {totals.discount_total > 0
                                                ? '−'
                                                : ''}

                                            <Price
                                                amount={totals.discount_total}
                                            />
                                        </dd>
                                    </div>

                                    <div className="flex justify-between gap-4 border-t pt-4 text-base font-semibold">
                                        <dt>Grand total</dt>

                                        <dd>
                                            <Price
                                                amount={totals.grand_total}
                                            />
                                        </dd>
                                    </div>
                                </dl>

                                <div className="mt-8 border-t pt-6">
                                    <h3 className="text-sm font-semibold">
                                        Discount code
                                    </h3>

                                    <form
                                        onSubmit={applyDiscount}
                                        className="mt-3 flex gap-2"
                                    >
                                        <input
                                            type="text"
                                            value={
                                                discountForm.data.discount_code
                                            }
                                            onChange={(event) =>
                                                discountForm.setData(
                                                    'discount_code',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="WELCOME10"
                                            disabled={discountForm.processing}
                                            className="min-w-0 flex-1 rounded-lg border px-3 py-2 text-sm outline-none focus:border-neutral-500"
                                        />

                                        <button
                                            type="submit"
                                            disabled={discountForm.processing}
                                            className="rounded-lg bg-neutral-950 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 disabled:opacity-50"
                                        >
                                            Apply
                                        </button>
                                    </form>

                                    {discountForm.errors.discount_code && (
                                        <p className="mt-2 text-sm text-red-600">
                                            {discountForm.errors.discount_code}
                                        </p>
                                    )}

                                    {totals.discount_error && (
                                        <p className="mt-2 text-sm text-amber-700">
                                            {totals.discount_error}
                                        </p>
                                    )}

                                    {totals.discount_code &&
                                        !totals.discount_error && (
                                            <div className="mt-3 flex items-center justify-between gap-3 text-sm">
                                                <span className="text-emerald-700">
                                                    {totals.discount_code}{' '}
                                                    applied
                                                </span>

                                                <button
                                                    type="button"
                                                    onClick={removeDiscount}
                                                    className="font-medium text-neutral-600 hover:text-neutral-950"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        )}
                                </div>

                                <div className="mt-6 border-t pt-6">
                                    {canCheckout ? (
                                        <Link
                                            href="/checkout"
                                            className="flex w-full items-center justify-center rounded-lg bg-neutral-950 px-5 py-3 text-sm font-medium text-white transition hover:bg-neutral-800"
                                        >
                                            Proceed to checkout
                                        </Link>
                                    ) : (
                                        <>
                                            <button
                                                type="button"
                                                disabled
                                                className="w-full cursor-not-allowed rounded-lg bg-neutral-300 px-5 py-3 text-sm font-medium text-neutral-600"
                                            >
                                                Proceed to checkout
                                            </button>

                                            <p className="mt-3 text-center text-xs leading-5 text-neutral-500">
                                                Resolve the cart issues above
                                                before continuing.
                                            </p>
                                        </>
                                    )}
                                </div>
                            </div>
                        </aside>
                    </div>
                )}
            </div>
        </StorefrontLayout>
    );
}
