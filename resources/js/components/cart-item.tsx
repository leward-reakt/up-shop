import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Price } from '@/components/price';
import { QuantityInput } from '@/components/quantity-input';
import type { CartLineItem } from '@/types';

type CartItemProps = {
    item: CartLineItem;
};

export function CartItem({ item }: CartItemProps) {
    const form = useForm<{
        quantity: number;
    }>({
        quantity: item.quantity,
    });

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

    const remove = () => {
        router.delete(`/cart/items/${item.product_id}`, {
            preserveScroll: true,
        });
    };

    return (
        <article className="rounded-xl border bg-white p-4 sm:p-6">
            <div className="flex flex-col gap-5 sm:flex-row">
                <div className="h-28 w-28 shrink-0 overflow-hidden rounded-lg bg-neutral-100">
                    {item.image_url ? (
                        <img
                            src={item.image_url}
                            alt={item.image_alt ?? item.name}
                            className="h-full w-full object-cover"
                        />
                    ) : (
                        <div className="flex h-full items-center justify-center p-3 text-center text-xs text-neutral-400">
                            No image
                        </div>
                    )}
                </div>

                <div className="min-w-0 flex-1">
                    <div className="flex flex-col justify-between gap-4 sm:flex-row">
                        <div>
                            {item.is_product_visible ? (
                                <Link
                                    href={`/products/${item.slug}`}
                                    className="font-semibold hover:text-neutral-600"
                                >
                                    {item.name}
                                </Link>
                            ) : (
                                <p className="font-semibold">{item.name}</p>
                            )}

                            <p className="mt-1 text-sm text-neutral-500">
                                SKU: {item.sku}
                            </p>

                            <p className="mt-2 text-sm">
                                <Price amount={item.price} /> each
                            </p>
                        </div>

                        <Price
                            amount={item.line_total}
                            className="font-semibold"
                        />
                    </div>

                    {item.availability_message && (
                        <p className="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                            {item.availability_message}
                        </p>
                    )}

                    <div className="mt-5 flex flex-wrap items-end gap-3">
                        <form
                            onSubmit={submit}
                            className="flex flex-wrap items-end gap-3"
                        >
                            <div>
                                <p className="mb-1 text-xs font-medium text-neutral-500">
                                    Quantity
                                </p>

                                <QuantityInput
                                    value={form.data.quantity}
                                    max={item.stock_quantity}
                                    disabled={
                                        form.processing ||
                                        !item.can_update_quantity
                                    }
                                    onChange={(quantity) =>
                                        form.setData('quantity', quantity)
                                    }
                                />
                            </div>

                            <button
                                type="submit"
                                disabled={form.processing || !canUpdate}
                                className="h-10 rounded-lg border px-4 text-sm font-medium hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Update
                            </button>
                        </form>

                        <button
                            type="button"
                            disabled={form.processing}
                            onClick={remove}
                            className="h-10 text-sm font-medium text-red-600 hover:text-red-700 disabled:opacity-50"
                        >
                            Remove
                        </button>
                    </div>

                    {form.errors.quantity && (
                        <p className="mt-2 text-sm text-red-600">
                            {form.errors.quantity}
                        </p>
                    )}
                </div>
            </div>
        </article>
    );
}
