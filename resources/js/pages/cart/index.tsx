import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CartItem } from '@/components/cart-item';
import { CartRemoveConfirmationDialog } from '@/components/cart-remove-confirmation-dialog';
import { Price } from '@/components/price';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CartLineItem, CartTotals } from '@/types';

type CartPageProps = {
    items: CartLineItem[];
    totals: CartTotals;
    bulk_remove_enabled: boolean;
};

export default function CartPage({
    items,
    totals,
    bulk_remove_enabled,
}: CartPageProps) {
    const [isBulkRemoveMode, setIsBulkRemoveMode] = useState(false);
    const [bulkRemoveConfirmationOpen, setBulkRemoveConfirmationOpen] =
        useState(false);

    const discountForm = useForm<{
        discount_code: string;
    }>({
        discount_code: totals.discount_code ?? '',
    });

    const bulkRemoveForm = useForm<{
        product_ids: number[];
    }>({
        product_ids: [],
    });

    const hasAvailabilityIssues = items.some((item) => !item.is_available);

    const canCheckout = !hasAvailabilityIssues && !totals.discount_error;

    const selectedCount = bulkRemoveForm.data.product_ids.length;

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

    const toggleBulkRemoveMode = () => {
        if (bulkRemoveForm.processing) {
            return;
        }

        bulkRemoveForm.reset();
        setBulkRemoveConfirmationOpen(false);
        setIsBulkRemoveMode((current) => !current);
    };

    const setItemSelected = (productId: number, selected: boolean) => {
        const productIds = bulkRemoveForm.data.product_ids;

        if (selected) {
            if (productIds.includes(productId)) {
                return;
            }

            bulkRemoveForm.setData('product_ids', [...productIds, productId]);

            return;
        }

        bulkRemoveForm.setData(
            'product_ids',
            productIds.filter(
                (selectedProductId) => selectedProductId !== productId,
            ),
        );
    };

    const requestRemoveSelected = () => {
        if (selectedCount === 0) {
            return;
        }

        setBulkRemoveConfirmationOpen(true);
    };

    const confirmRemoveSelected = () => {
        if (selectedCount === 0) {
            return;
        }

        bulkRemoveForm.delete('/cart/items', {
            preserveScroll: true,
            onSuccess: () => {
                bulkRemoveForm.reset();
                setBulkRemoveConfirmationOpen(false);
                setIsBulkRemoveMode(false);
            },
        });
    };

    return (
        <StorefrontLayout>
            <Head title="Cart" />

            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-3xl font-semibold tracking-tight">
                            Shopping cart
                        </h1>

                        <p className="mt-2 text-neutral-600">
                            Review your products and quantities.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-4">
                        {bulk_remove_enabled && items.length > 1 && (
                            <button
                                type="button"
                                onClick={toggleBulkRemoveMode}
                                disabled={bulkRemoveForm.processing}
                                aria-pressed={isBulkRemoveMode}
                                className="text-sm font-medium text-red-600 hover:text-red-700 disabled:opacity-50"
                            >
                                {isBulkRemoveMode
                                    ? 'Cancel'
                                    : 'Remove multiple'}
                            </button>
                        )}

                        <Link
                            href="/shop"
                            className="text-sm font-medium hover:text-neutral-600"
                        >
                            Continue shopping
                        </Link>
                    </div>
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
                            {isBulkRemoveMode && (
                                <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-neutral-50 px-4 py-3">
                                    <p className="text-sm text-neutral-600">
                                        {selectedCount}{' '}
                                        {selectedCount === 1 ? 'item' : 'items'}{' '}
                                        selected
                                    </p>

                                    <button
                                        type="button"
                                        onClick={requestRemoveSelected}
                                        disabled={
                                            selectedCount === 0 ||
                                            bulkRemoveForm.processing
                                        }
                                        className="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {bulkRemoveForm.processing
                                            ? 'Removing...'
                                            : `Remove selected (${selectedCount})`}
                                    </button>
                                </div>
                            )}

                            {hasAvailabilityIssues && (
                                <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                    One or more cart items need attention.
                                    Update or remove unavailable quantities
                                    before checkout.
                                </div>
                            )}

                            {items.map((item) => (
                                <CartItem
                                    key={item.product_id}
                                    item={item}
                                    selectionMode={isBulkRemoveMode}
                                    selected={bulkRemoveForm.data.product_ids.includes(
                                        item.product_id,
                                    )}
                                    selectionDisabled={
                                        bulkRemoveForm.processing
                                    }
                                    onSelectedChange={(selected) =>
                                        setItemSelected(
                                            item.product_id,
                                            selected,
                                        )
                                    }
                                />
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

            <CartRemoveConfirmationDialog
                open={bulkRemoveConfirmationOpen}
                itemCount={selectedCount}
                processing={bulkRemoveForm.processing}
                onOpenChange={setBulkRemoveConfirmationOpen}
                onConfirm={confirmRemoveSelected}
            />
        </StorefrontLayout>
    );
}
