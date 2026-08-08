import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Price } from '@/components/price';
import type { CartTotals } from '@/types';

type CartOrderSummaryProps = {
    totals: CartTotals;
    canCheckout: boolean;
    variant?: 'default' | 'fashion-editorial';
};

export function CartOrderSummary({
    totals,
    canCheckout,
    variant = 'default',
}: CartOrderSummaryProps) {
    const isFashionEditorial = variant === 'fashion-editorial';

    const discountForm = useForm<{
        discount_code: string;
    }>({
        discount_code: totals.discount_code ?? '',
    });

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

    if (isFashionEditorial) {
        return (
            <div className="sticky top-6">
                <p className="text-[9px] font-medium tracking-[0.18em] text-neutral-500 uppercase">
                    Order summary
                </p>

                <h2 className="mt-3 font-serif text-3xl leading-tight tracking-[-0.025em]">
                    Summary
                </h2>

                <dl className="mt-8 border-t border-neutral-300 text-sm">
                    <div className="flex justify-between gap-6 border-b border-neutral-300 py-4">
                        <dt className="text-xs text-neutral-600">Subtotal</dt>

                        <dd>
                            <Price amount={totals.subtotal} />
                        </dd>
                    </div>

                    <div className="flex justify-between gap-6 border-b border-neutral-300 py-4">
                        <dt className="text-xs text-neutral-600">
                            Shipping estimate
                        </dt>

                        <dd>
                            <Price amount={totals.shipping_total} />
                        </dd>
                    </div>

                    <div className="flex justify-between gap-6 border-b border-neutral-300 py-4">
                        <dt className="text-xs text-neutral-600">Discount</dt>

                        <dd>
                            {totals.discount_total > 0 ? '−' : ''}

                            <Price amount={totals.discount_total} />
                        </dd>
                    </div>

                    <div className="flex items-end justify-between gap-6 py-5">
                        <dt className="font-serif text-xl">Grand total</dt>

                        <dd>
                            <Price
                                amount={totals.grand_total}
                                className="font-serif text-xl"
                            />
                        </dd>
                    </div>
                </dl>

                <div className="mt-7 border-t border-neutral-300 pt-7">
                    <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                        Discount code
                    </p>

                    <form
                        onSubmit={applyDiscount}
                        className="mt-4 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] lg:grid-cols-1 xl:grid-cols-[minmax(0,1fr)_auto]"
                    >
                        <input
                            type="text"
                            value={discountForm.data.discount_code}
                            onChange={(event) =>
                                discountForm.setData(
                                    'discount_code',
                                    event.target.value,
                                )
                            }
                            placeholder="WELCOME10"
                            disabled={discountForm.processing}
                            className="min-h-12 min-w-0 border border-neutral-300 bg-transparent px-4 text-xs transition outline-none focus:border-neutral-950"
                        />

                        <button
                            type="submit"
                            disabled={discountForm.processing}
                            className="min-h-12 border border-neutral-950 bg-neutral-950 px-5 text-[9px] font-medium tracking-[0.15em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950 disabled:opacity-50"
                        >
                            Apply
                        </button>
                    </form>

                    {discountForm.errors.discount_code && (
                        <p className="mt-3 text-sm text-red-600">
                            {discountForm.errors.discount_code}
                        </p>
                    )}

                    {totals.discount_error && (
                        <p className="mt-3 text-sm text-amber-700">
                            {totals.discount_error}
                        </p>
                    )}

                    {totals.discount_code && !totals.discount_error && (
                        <div className="mt-4 flex items-center justify-between gap-4 text-xs">
                            <span className="text-neutral-700">
                                {totals.discount_code} applied
                            </span>

                            <button
                                type="button"
                                onClick={removeDiscount}
                                className="border-b border-neutral-950 pb-1 text-[9px] font-medium tracking-[0.12em] uppercase transition-opacity hover:opacity-60"
                            >
                                Remove
                            </button>
                        </div>
                    )}
                </div>

                <div className="mt-8 border-t border-neutral-300 pt-8">
                    {canCheckout ? (
                        <Link
                            href="/checkout"
                            className="flex min-h-13 w-full items-center justify-center border border-neutral-950 bg-neutral-950 px-6 text-[10px] font-medium tracking-[0.16em] text-white uppercase transition duration-300 hover:bg-transparent hover:text-neutral-950"
                        >
                            Proceed to checkout
                        </Link>
                    ) : (
                        <>
                            <button
                                type="button"
                                disabled
                                className="min-h-13 w-full cursor-not-allowed border border-neutral-300 bg-neutral-200 px-6 text-[10px] font-medium tracking-[0.16em] text-neutral-500 uppercase"
                            >
                                Proceed to checkout
                            </button>

                            <p className="mt-4 text-center text-xs leading-5 text-neutral-500">
                                Resolve the cart issues before continuing.
                            </p>
                        </>
                    )}
                </div>
            </div>
        );
    }

    return (
        <div className="sticky top-6 rounded-xl border bg-white p-6">
            <h2 className="text-lg font-semibold">Order summary</h2>

            <dl className="mt-6 space-y-4 text-sm">
                <div className="flex justify-between gap-4">
                    <dt className="text-neutral-600">Subtotal</dt>

                    <dd>
                        <Price amount={totals.subtotal} />
                    </dd>
                </div>

                <div className="flex justify-between gap-4">
                    <dt className="text-neutral-600">Shipping estimate</dt>

                    <dd>
                        <Price amount={totals.shipping_total} />
                    </dd>
                </div>

                <div className="flex justify-between gap-4">
                    <dt className="text-neutral-600">Discount</dt>

                    <dd>
                        {totals.discount_total > 0 ? '−' : ''}

                        <Price amount={totals.discount_total} />
                    </dd>
                </div>

                <div className="flex justify-between gap-4 border-t pt-4 text-base font-semibold">
                    <dt>Grand total</dt>

                    <dd>
                        <Price amount={totals.grand_total} />
                    </dd>
                </div>
            </dl>

            <div className="mt-8 border-t pt-6">
                <h3 className="text-sm font-semibold">Discount code</h3>

                <form onSubmit={applyDiscount} className="mt-3 flex gap-2">
                    <input
                        type="text"
                        value={discountForm.data.discount_code}
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

                {totals.discount_code && !totals.discount_error && (
                    <div className="mt-3 flex items-center justify-between gap-3 text-sm">
                        <span className="text-emerald-700">
                            {totals.discount_code} applied
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
                            Resolve the cart issues above before continuing.
                        </p>
                    </>
                )}
            </div>
        </div>
    );
}
