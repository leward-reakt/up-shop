import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CartRemoveConfirmationDialog } from '@/components/cart-remove-confirmation-dialog';
import { Price } from '@/components/price';
import { QuantityInput } from '@/components/quantity-input';
import { Checkbox } from '@/components/ui/checkbox';
import type { CartLineItem } from '@/types';

type CartItemProps = {
    item: CartLineItem;
    variant?: 'default' | 'fashion-editorial';
    selectionMode?: boolean;
    selected?: boolean;
    selectionDisabled?: boolean;
    onSelectedChange?: (selected: boolean) => void;
};

export function CartItem({
    item,
    variant = 'default',
    selectionMode = false,
    selected = false,
    selectionDisabled = false,
    onSelectedChange,
}: CartItemProps) {
    const [removeConfirmationOpen, setRemoveConfirmationOpen] = useState(false);

    const form = useForm<{
        quantity: number;
    }>({
        quantity: item.quantity,
    });

    const removeForm = useForm<Record<string, never>>({});

    const isFashionEditorial = variant === 'fashion-editorial';

    const canUpdate =
        item.can_update_quantity &&
        form.data.quantity >= 1 &&
        form.data.quantity <= item.stock_quantity &&
        form.data.quantity !== item.quantity;

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.patch(`/cart/items/${item.product_id}`, {
            preserveScroll: true,
        });
    };

    const requestRemove = () => {
        setRemoveConfirmationOpen(true);
    };

    const confirmRemove = () => {
        removeForm.delete(`/cart/items/${item.product_id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setRemoveConfirmationOpen(false);
            },
        });
    };

    return (
        <>
            <article
                className={
                    isFashionEditorial
                        ? 'border-b border-neutral-300 py-6 sm:py-8'
                        : 'rounded-xl border bg-white p-4 sm:p-6'
                }
            >
                <div className="flex items-start gap-4 sm:gap-6">
                    {selectionMode && (
                        <Checkbox
                            checked={selected}
                            disabled={selectionDisabled}
                            onCheckedChange={(checked) =>
                                onSelectedChange?.(checked === true)
                            }
                            aria-label={`Select ${item.name} for removal`}
                            className="mt-1"
                        />
                    )}

                    <div className="min-w-0 flex-1">
                        <div className="flex flex-col gap-5 sm:flex-row">
                            <div
                                className={
                                    isFashionEditorial
                                        ? 'aspect-[3/4] w-28 shrink-0 overflow-hidden bg-[#ebe6df] sm:w-36'
                                        : 'h-28 w-28 shrink-0 overflow-hidden rounded-lg bg-neutral-100'
                                }
                            >
                                {item.image_url ? (
                                    <img
                                        src={item.image_url}
                                        alt={item.image_alt ?? item.name}
                                        className={
                                            isFashionEditorial
                                                ? 'h-full w-full object-contain transition duration-700 ease-out'
                                                : 'h-full w-full object-contain'
                                        }
                                    />
                                ) : (
                                    <div
                                        className={
                                            isFashionEditorial
                                                ? 'flex h-full items-center justify-center px-4 text-center text-[9px] tracking-[0.14em] text-neutral-500 uppercase'
                                                : 'flex h-full items-center justify-center p-3 text-center text-xs text-neutral-400'
                                        }
                                    >
                                        {isFashionEditorial
                                            ? item.name
                                            : 'No image'}
                                    </div>
                                )}
                            </div>

                            <div className="min-w-0 flex-1">
                                <div className="flex flex-col justify-between gap-4 sm:flex-row">
                                    <div>
                                        {item.is_product_visible ? (
                                            <Link
                                                href={`/products/${item.slug}`}
                                                className={
                                                    isFashionEditorial
                                                        ? 'font-serif text-xl leading-tight tracking-[-0.02em] transition-opacity hover:opacity-60'
                                                        : 'font-semibold hover:text-neutral-600'
                                                }
                                            >
                                                {item.name}
                                            </Link>
                                        ) : (
                                            <p
                                                className={
                                                    isFashionEditorial
                                                        ? 'font-serif text-xl leading-tight tracking-[-0.02em]'
                                                        : 'font-semibold'
                                                }
                                            >
                                                {item.name}
                                            </p>
                                        )}

                                        <p
                                            className={
                                                isFashionEditorial
                                                    ? 'mt-2 text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase'
                                                    : 'mt-1 text-sm text-neutral-500'
                                            }
                                        >
                                            SKU: {item.sku}
                                        </p>

                                        <p
                                            className={
                                                isFashionEditorial
                                                    ? 'mt-3 text-xs text-neutral-600'
                                                    : 'mt-2 text-sm'
                                            }
                                        >
                                            <Price amount={item.price} /> each
                                        </p>
                                    </div>

                                    <Price
                                        amount={item.line_total}
                                        className={
                                            isFashionEditorial
                                                ? 'font-serif text-lg'
                                                : 'font-semibold'
                                        }
                                    />
                                </div>

                                {item.availability_message && (
                                    <p
                                        className={
                                            isFashionEditorial
                                                ? 'mt-5 border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900'
                                                : 'mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800'
                                        }
                                    >
                                        {item.availability_message}
                                    </p>
                                )}

                                <div className="mt-6 flex flex-wrap items-end gap-4">
                                    <form
                                        onSubmit={submit}
                                        className="flex flex-wrap items-end gap-4"
                                    >
                                        <div>
                                            <p
                                                className={
                                                    isFashionEditorial
                                                        ? 'mb-2 text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase'
                                                        : 'mb-1 text-xs font-medium text-neutral-500'
                                                }
                                            >
                                                Quantity
                                            </p>

                                            <QuantityInput
                                                value={form.data.quantity}
                                                max={item.stock_quantity}
                                                variant={variant}
                                                disabled={
                                                    form.processing ||
                                                    removeForm.processing ||
                                                    !item.can_update_quantity
                                                }
                                                onChange={(quantity) =>
                                                    form.setData(
                                                        'quantity',
                                                        quantity,
                                                    )
                                                }
                                            />
                                        </div>

                                        <button
                                            type="submit"
                                            disabled={
                                                form.processing ||
                                                removeForm.processing ||
                                                !canUpdate
                                            }
                                            className={
                                                isFashionEditorial
                                                    ? 'min-h-10 border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60 disabled:cursor-not-allowed disabled:opacity-30'
                                                    : 'h-10 rounded-lg border px-4 text-sm font-medium hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50'
                                            }
                                        >
                                            Update
                                        </button>
                                    </form>

                                    {!selectionMode && (
                                        <button
                                            type="button"
                                            disabled={
                                                form.processing ||
                                                removeForm.processing
                                            }
                                            onClick={requestRemove}
                                            className={
                                                isFashionEditorial
                                                    ? 'min-h-10 border-b border-red-300 text-[9px] font-medium tracking-[0.14em] text-red-700 uppercase transition-opacity hover:opacity-60 disabled:opacity-30'
                                                    : 'h-10 text-sm font-medium text-red-600 hover:text-red-700 disabled:opacity-50'
                                            }
                                        >
                                            Remove
                                        </button>
                                    )}
                                </div>

                                {form.errors.quantity && (
                                    <p className="mt-3 text-sm text-red-600">
                                        {form.errors.quantity}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <CartRemoveConfirmationDialog
                open={removeConfirmationOpen}
                itemCount={1}
                itemName={item.name}
                processing={removeForm.processing}
                onOpenChange={setRemoveConfirmationOpen}
                onConfirm={confirmRemove}
            />
        </>
    );
}
