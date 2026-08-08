import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { CartItem } from '@/components/cart-item';
import { CartOrderSummary } from '@/components/cart-order-summary';
import { CartRemoveConfirmationDialog } from '@/components/cart-remove-confirmation-dialog';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CartLineItem, CartTotals, CatalogCategory } from '@/types';

type CartPageProps = {
    items: CartLineItem[];
    totals: CartTotals;
    bulk_remove_enabled: boolean;
};

type CartSharedProps = {
    store?: {
        theme?: 'default' | 'fashion_editorial';
        navigation_categories?: CatalogCategory[];
    };
};

export default function CartPage({
    items,
    totals,
    bulk_remove_enabled,
}: CartPageProps) {
    const page = usePage();

    const sharedProps = page.props as unknown as CartSharedProps;

    const isFashionEditorial = sharedProps.store?.theme === 'fashion_editorial';

    const navigationCategories = sharedProps.store?.navigation_categories ?? [];

    const [isBulkRemoveMode, setIsBulkRemoveMode] = useState(false);

    const [bulkRemoveConfirmationOpen, setBulkRemoveConfirmationOpen] =
        useState(false);

    const bulkRemoveForm = useForm<{
        product_ids: number[];
    }>({
        product_ids: [],
    });

    const hasAvailabilityIssues = items.some((item) => !item.is_available);

    const canCheckout = !hasAvailabilityIssues && !totals.discount_error;

    const selectedCount = bulkRemoveForm.data.product_ids.length;

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

    if (isFashionEditorial) {
        return (
            <StorefrontLayout
                variant="fashion-editorial"
                navigationCategories={navigationCategories}
            >
                <Head title="Cart" />

                <section className="bg-[#f8f6f1]">
                    <div className="mx-auto max-w-[1600px] px-5 py-14 sm:px-8 sm:py-20 lg:px-14 lg:py-24">
                        <header className="border-b border-neutral-300 pb-10">
                            <div className="flex flex-col gap-7 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                                        Shopping bag
                                    </p>

                                    <h1 className="mt-4 font-serif text-5xl leading-none tracking-[-0.035em] sm:text-6xl">
                                        Your cart.
                                    </h1>

                                    <p className="mt-5 max-w-xl text-sm leading-7 text-neutral-600">
                                        Review the pieces in your selection
                                        before continuing to checkout.
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-6">
                                    {bulk_remove_enabled &&
                                        items.length > 1 && (
                                            <button
                                                type="button"
                                                onClick={toggleBulkRemoveMode}
                                                disabled={
                                                    bulkRemoveForm.processing
                                                }
                                                aria-pressed={isBulkRemoveMode}
                                                className="inline-flex min-h-10 items-center border-b border-red-300 text-[9px] font-medium tracking-[0.14em] text-red-700 uppercase transition-opacity hover:opacity-60 disabled:opacity-40"
                                            >
                                                {isBulkRemoveMode
                                                    ? 'Cancel'
                                                    : 'Remove multiple'}
                                            </button>
                                        )}

                                    <Link
                                        href="/shop"
                                        className="inline-flex min-h-10 items-center border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60"
                                    >
                                        Continue shopping
                                    </Link>
                                </div>
                            </div>
                        </header>

                        {items.length === 0 ? (
                            <div className="py-24 text-center sm:py-32">
                                <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                                    Your selection
                                </p>

                                <h2 className="mt-5 font-serif text-4xl leading-tight tracking-[-0.025em] sm:text-5xl">
                                    Your cart is empty.
                                </h2>

                                <p className="mx-auto mt-5 max-w-lg text-sm leading-7 text-neutral-600">
                                    Discover the collection and add pieces to
                                    begin your order.
                                </p>

                                <Link
                                    href="/shop"
                                    className="mt-9 inline-flex min-h-12 items-center justify-center border border-neutral-950 px-7 text-[10px] font-medium tracking-[0.16em] uppercase transition duration-300 hover:bg-neutral-950 hover:text-white"
                                >
                                    Shop the collection
                                </Link>
                            </div>
                        ) : (
                            <div className="grid gap-12 pt-10 lg:grid-cols-[minmax(0,1fr)_390px] lg:gap-14 xl:grid-cols-[minmax(0,1fr)_430px] xl:gap-20">
                                <section className="border-t border-neutral-300">
                                    {isBulkRemoveMode && (
                                        <div className="flex flex-wrap items-center justify-between gap-4 border-b border-neutral-300 bg-[#eee8e1] px-4 py-4 sm:px-5">
                                            <p className="text-[10px] font-medium tracking-[0.12em] text-neutral-600 uppercase">
                                                {selectedCount}{' '}
                                                {selectedCount === 1
                                                    ? 'item'
                                                    : 'items'}{' '}
                                                selected
                                            </p>

                                            <button
                                                type="button"
                                                onClick={requestRemoveSelected}
                                                disabled={
                                                    selectedCount === 0 ||
                                                    bulkRemoveForm.processing
                                                }
                                                className="min-h-10 border border-red-700 px-4 text-[9px] font-medium tracking-[0.14em] text-red-700 uppercase transition hover:bg-red-700 hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                {bulkRemoveForm.processing
                                                    ? 'Removing...'
                                                    : `Remove selected (${selectedCount})`}
                                            </button>
                                        </div>
                                    )}

                                    {hasAvailabilityIssues && (
                                        <div className="border-b border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-900">
                                            One or more cart items need
                                            attention. Update or remove
                                            unavailable quantities before
                                            checkout.
                                        </div>
                                    )}

                                    {items.map((item) => (
                                        <CartItem
                                            key={item.product_id}
                                            item={item}
                                            variant="fashion-editorial"
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

                                <aside className="lg:border-l lg:border-neutral-300 lg:pl-10 xl:pl-12">
                                    <CartOrderSummary
                                        totals={totals}
                                        canCheckout={canCheckout}
                                        variant="fashion-editorial"
                                    />
                                </aside>
                            </div>
                        )}
                    </div>
                </section>

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
                            <CartOrderSummary
                                totals={totals}
                                canCheckout={canCheckout}
                            />
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
