import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Price } from '@/components/price';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import StorefrontLayout from '@/layouts/storefront-layout';
import type {
    CheckoutAddress,
    CheckoutCustomer,
    CheckoutFormData,
    CheckoutItem,
    CheckoutOption,
    CheckoutTotals,
} from '@/types';

type CheckoutProps = {
    items: CheckoutItem[];
    totals: CheckoutTotals;
    shipping_options: CheckoutOption[];
    payment_options: CheckoutOption[];
    selected_shipping_method: string;
    customer: CheckoutCustomer;
    is_authenticated: boolean;
    saved_addresses: CheckoutAddress[];
};

export default function Checkout({
    items,
    totals,
    shipping_options,
    payment_options,
    selected_shipping_method,
    customer,
    is_authenticated,
    saved_addresses,
}: CheckoutProps) {
    const [addressDialogOpen, setAddressDialogOpen] = useState(false);

    const defaultAddress =
        saved_addresses.find((address) => address.is_default) ??
        saved_addresses[0] ??
        null;

    const hasSavedAddresses = saved_addresses.length > 0;

    const { data, setData, post, processing, errors } =
        useForm<CheckoutFormData>({
            customer_name: customer.name,
            customer_email: customer.email,
            customer_phone: customer.phone,

            shipping_address_id: defaultAddress?.id ?? null,
            shipping_address_line_1: defaultAddress?.address_line_1 ?? '',
            shipping_address_line_2: defaultAddress?.address_line_2 ?? '',
            shipping_city: defaultAddress?.city ?? '',
            shipping_province: defaultAddress?.province ?? '',
            shipping_postal_code: defaultAddress?.postal_code ?? '',

            shipping_method: selected_shipping_method,
            payment_method: 'cash_on_delivery',

            customer_notes: '',
        });

    const selectedAddress =
        saved_addresses.find(
            (address) => address.id === data.shipping_address_id,
        ) ?? null;

    const cartErrorValue = Object.entries(errors).find(
        ([field]) => field === 'cart',
    )?.[1];

    const cartError =
        typeof cartErrorValue === 'string' ? cartErrorValue : undefined;

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post('/checkout');
    };

    const changeShippingMethod = (value: string) => {
        setData('shipping_method', value);

        router.get(
            '/checkout',
            {
                shipping_method: value,
            },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                only: ['totals', 'selected_shipping_method'],
            },
        );
    };

    const selectAddress = (address: CheckoutAddress) => {
        setData({
            ...data,
            shipping_address_id: address.id,
            shipping_address_line_1: address.address_line_1,
            shipping_address_line_2: address.address_line_2 ?? '',
            shipping_city: address.city,
            shipping_province: address.province,
            shipping_postal_code: address.postal_code,
        });

        setAddressDialogOpen(false);
    };

    return (
        <StorefrontLayout>
            <Head title="Checkout" />

            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <Link
                        href="/cart"
                        className="text-sm text-neutral-500 hover:text-neutral-950"
                    >
                        ← Back to cart
                    </Link>

                    <h1 className="mt-4 text-3xl font-semibold">Checkout</h1>

                    <p className="mt-2 text-neutral-600">
                        Review your order and enter your delivery details.
                    </p>
                </div>

                {cartError && (
                    <div className="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        {cartError}
                    </div>
                )}

                <form
                    onSubmit={submit}
                    className="grid gap-8 lg:grid-cols-[1fr_420px]"
                >
                    <div className="space-y-8">
                        <section className="rounded-xl border bg-white p-6">
                            <h2 className="text-lg font-semibold">
                                Contact information
                            </h2>

                            <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <label
                                        htmlFor="customer_name"
                                        className="mb-1 block text-sm font-medium"
                                    >
                                        Full name
                                    </label>

                                    <input
                                        id="customer_name"
                                        type="text"
                                        autoComplete="name"
                                        value={data.customer_name}
                                        onChange={(event) =>
                                            setData(
                                                'customer_name',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-lg border px-3 py-2"
                                    />

                                    <InputError
                                        message={errors.customer_name}
                                        className="mt-1"
                                    />
                                </div>

                                <div>
                                    <label
                                        htmlFor="customer_email"
                                        className="mb-1 block text-sm font-medium"
                                    >
                                        Email
                                    </label>

                                    <input
                                        id="customer_email"
                                        type="email"
                                        autoComplete="email"
                                        value={data.customer_email}
                                        onChange={(event) =>
                                            setData(
                                                'customer_email',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-lg border px-3 py-2"
                                    />

                                    <InputError
                                        message={errors.customer_email}
                                        className="mt-1"
                                    />
                                </div>

                                <div>
                                    <label
                                        htmlFor="customer_phone"
                                        className="mb-1 block text-sm font-medium"
                                    >
                                        Mobile number
                                    </label>

                                    <input
                                        id="customer_phone"
                                        type="tel"
                                        autoComplete="tel"
                                        value={data.customer_phone}
                                        onChange={(event) =>
                                            setData(
                                                'customer_phone',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-lg border px-3 py-2"
                                    />

                                    <InputError
                                        message={errors.customer_phone}
                                        className="mt-1"
                                    />
                                </div>
                            </div>
                        </section>

                        <section className="rounded-xl border bg-white p-6">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold">
                                        Shipping address
                                    </h2>

                                    {hasSavedAddresses && selectedAddress && (
                                        <p className="mt-1 text-sm text-neutral-500">
                                            Using{' '}
                                            {selectedAddress.label ??
                                                selectedAddress.recipient_name}
                                            {selectedAddress.is_default
                                                ? ' — Default'
                                                : ''}
                                        </p>
                                    )}
                                </div>

                                {hasSavedAddresses && (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setAddressDialogOpen(true)
                                        }
                                        className="self-start rounded-lg border px-4 py-2 text-sm font-medium hover:bg-neutral-50 sm:self-auto"
                                    >
                                        Use different address
                                    </button>
                                )}
                            </div>

                            {is_authenticated && !hasSavedAddresses && (
                                <p className="mt-3 rounded-lg bg-neutral-50 px-4 py-3 text-sm text-neutral-600">
                                    This will be saved as your default shipping
                                    address after your order is successfully
                                    placed.
                                </p>
                            )}

                            <InputError
                                message={errors.shipping_address_id}
                                className="mt-3"
                            />

                            <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <label
                                        htmlFor="shipping_address_line_1"
                                        className="mb-1 block text-sm font-medium"
                                    >
                                        Address
                                    </label>

                                    <input
                                        id="shipping_address_line_1"
                                        type="text"
                                        autoComplete="address-line1"
                                        value={data.shipping_address_line_1}
                                        disabled={hasSavedAddresses}
                                        onChange={(event) =>
                                            setData(
                                                'shipping_address_line_1',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-lg border px-3 py-2 disabled:cursor-not-allowed disabled:bg-neutral-50 disabled:text-neutral-500"
                                    />

                                    <InputError
                                        message={errors.shipping_address_line_1}
                                        className="mt-1"
                                    />
                                </div>

                                <div className="sm:col-span-2">
                                    <label
                                        htmlFor="shipping_address_line_2"
                                        className="mb-1 block text-sm font-medium"
                                    >
                                        Apartment, suite, etc.
                                        <span className="ml-1 font-normal text-neutral-500">
                                            (optional)
                                        </span>
                                    </label>

                                    <input
                                        id="shipping_address_line_2"
                                        type="text"
                                        autoComplete="address-line2"
                                        value={data.shipping_address_line_2}
                                        disabled={hasSavedAddresses}
                                        onChange={(event) =>
                                            setData(
                                                'shipping_address_line_2',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-lg border px-3 py-2 disabled:cursor-not-allowed disabled:bg-neutral-50 disabled:text-neutral-500"
                                    />

                                    <InputError
                                        message={errors.shipping_address_line_2}
                                        className="mt-1"
                                    />
                                </div>

                                <div>
                                    <label
                                        htmlFor="shipping_city"
                                        className="mb-1 block text-sm font-medium"
                                    >
                                        City / Municipality
                                    </label>

                                    <input
                                        id="shipping_city"
                                        type="text"
                                        autoComplete="address-level2"
                                        value={data.shipping_city}
                                        disabled={hasSavedAddresses}
                                        onChange={(event) =>
                                            setData(
                                                'shipping_city',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-lg border px-3 py-2 disabled:cursor-not-allowed disabled:bg-neutral-50 disabled:text-neutral-500"
                                    />

                                    <InputError
                                        message={errors.shipping_city}
                                        className="mt-1"
                                    />
                                </div>

                                <div>
                                    <label
                                        htmlFor="shipping_province"
                                        className="mb-1 block text-sm font-medium"
                                    >
                                        Province
                                    </label>

                                    <input
                                        id="shipping_province"
                                        type="text"
                                        autoComplete="address-level1"
                                        value={data.shipping_province}
                                        disabled={hasSavedAddresses}
                                        onChange={(event) =>
                                            setData(
                                                'shipping_province',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-lg border px-3 py-2 disabled:cursor-not-allowed disabled:bg-neutral-50 disabled:text-neutral-500"
                                    />

                                    <InputError
                                        message={errors.shipping_province}
                                        className="mt-1"
                                    />
                                </div>

                                <div>
                                    <label
                                        htmlFor="shipping_postal_code"
                                        className="mb-1 block text-sm font-medium"
                                    >
                                        Postal code
                                    </label>

                                    <input
                                        id="shipping_postal_code"
                                        type="text"
                                        autoComplete="postal-code"
                                        value={data.shipping_postal_code}
                                        disabled={hasSavedAddresses}
                                        onChange={(event) =>
                                            setData(
                                                'shipping_postal_code',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-lg border px-3 py-2 disabled:cursor-not-allowed disabled:bg-neutral-50 disabled:text-neutral-500"
                                    />

                                    <InputError
                                        message={errors.shipping_postal_code}
                                        className="mt-1"
                                    />
                                </div>

                                <div>
                                    <label
                                        htmlFor="shipping_country"
                                        className="mb-1 block text-sm font-medium"
                                    >
                                        Country
                                    </label>

                                    <input
                                        id="shipping_country"
                                        type="text"
                                        value="Philippines"
                                        disabled
                                        className="w-full rounded-lg border bg-neutral-50 px-3 py-2 text-neutral-500"
                                    />
                                </div>
                            </div>
                        </section>

                        <section className="rounded-xl border bg-white p-6">
                            <h2 className="text-lg font-semibold">
                                Shipping method
                            </h2>

                            <div className="mt-5 space-y-3">
                                {shipping_options.map((option) => (
                                    <label
                                        key={option.value}
                                        className="flex cursor-pointer items-center gap-3 rounded-lg border p-4"
                                    >
                                        <input
                                            type="radio"
                                            name="shipping_method"
                                            value={option.value}
                                            checked={
                                                data.shipping_method ===
                                                option.value
                                            }
                                            onChange={() =>
                                                changeShippingMethod(
                                                    option.value,
                                                )
                                            }
                                        />

                                        <span className="font-medium">
                                            {option.label}
                                        </span>
                                    </label>
                                ))}
                            </div>

                            <InputError
                                message={errors.shipping_method}
                                className="mt-2"
                            />
                        </section>

                        <section className="rounded-xl border bg-white p-6">
                            <h2 className="text-lg font-semibold">
                                Payment method
                            </h2>

                            <div className="mt-5 space-y-3">
                                {payment_options.map((option) => (
                                    <label
                                        key={option.value}
                                        className="flex cursor-pointer items-center gap-3 rounded-lg border p-4"
                                    >
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value={option.value}
                                            checked={
                                                data.payment_method ===
                                                option.value
                                            }
                                            onChange={() =>
                                                setData(
                                                    'payment_method',
                                                    option.value,
                                                )
                                            }
                                        />

                                        <span className="font-medium">
                                            {option.label}
                                        </span>
                                    </label>
                                ))}
                            </div>

                            {data.payment_method === 'bank_transfer' && (
                                <p className="mt-4 text-sm leading-6 text-neutral-600">
                                    Your order will remain pending until the
                                    bank transfer is manually verified.
                                </p>
                            )}

                            <InputError
                                message={errors.payment_method}
                                className="mt-2"
                            />
                        </section>

                        <section className="rounded-xl border bg-white p-6">
                            <label
                                htmlFor="customer_notes"
                                className="block text-lg font-semibold"
                            >
                                Order notes
                            </label>

                            <textarea
                                id="customer_notes"
                                rows={4}
                                value={data.customer_notes}
                                onChange={(event) =>
                                    setData(
                                        'customer_notes',
                                        event.target.value,
                                    )
                                }
                                placeholder="Optional delivery or order instructions"
                                className="mt-5 w-full rounded-lg border px-3 py-2"
                            />

                            <InputError
                                message={errors.customer_notes}
                                className="mt-1"
                            />
                        </section>
                    </div>

                    <aside>
                        <div className="sticky top-6 rounded-xl border bg-white p-6">
                            <h2 className="text-lg font-semibold">
                                Order summary
                            </h2>

                            <div className="mt-5 space-y-5">
                                {items.map((item) => (
                                    <div
                                        key={item.product_id}
                                        className="flex gap-4"
                                    >
                                        <div className="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-neutral-100">
                                            {item.image_url ? (
                                                <img
                                                    src={item.image_url}
                                                    alt={item.name}
                                                    className="h-full w-full object-cover"
                                                />
                                            ) : null}
                                        </div>

                                        <div className="min-w-0 flex-1">
                                            <Link
                                                href={`/products/${item.slug}`}
                                                className="font-medium hover:underline"
                                            >
                                                {item.name}
                                            </Link>

                                            <p className="mt-1 text-sm text-neutral-500">
                                                Qty: {item.quantity}
                                            </p>
                                        </div>

                                        <Price
                                            amount={item.line_total}
                                            className="text-sm font-medium"
                                        />
                                    </div>
                                ))}
                            </div>

                            <div className="mt-6 space-y-3 border-t pt-5 text-sm">
                                <div className="flex justify-between gap-4">
                                    <span className="text-neutral-600">
                                        Subtotal
                                    </span>

                                    <Price amount={totals.subtotal} />
                                </div>

                                {totals.discount_total > 0 && (
                                    <div className="flex justify-between gap-4">
                                        <span className="text-neutral-600">
                                            Discount
                                            {totals.discount_code
                                                ? ` (${totals.discount_code})`
                                                : ''}
                                        </span>

                                        <span>
                                            -
                                            <Price
                                                amount={totals.discount_total}
                                            />
                                        </span>
                                    </div>
                                )}

                                <div className="flex justify-between gap-4">
                                    <span className="text-neutral-600">
                                        Shipping
                                    </span>

                                    {totals.shipping_total === 0 ? (
                                        <span>Free</span>
                                    ) : (
                                        <Price amount={totals.shipping_total} />
                                    )}
                                </div>

                                {totals.tax_total > 0 && (
                                    <div className="flex justify-between gap-4">
                                        <span className="text-neutral-600">
                                            Tax
                                        </span>

                                        <Price amount={totals.tax_total} />
                                    </div>
                                )}

                                <div className="flex justify-between gap-4 border-t pt-4 text-base font-semibold">
                                    <span>Total</span>

                                    <Price amount={totals.grand_total} />
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="mt-6 w-full rounded-lg bg-neutral-950 px-5 py-3 font-medium text-white disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {processing
                                    ? 'Placing order...'
                                    : 'Place order'}
                            </button>

                            <p className="mt-3 text-center text-xs leading-5 text-neutral-500">
                                Your order totals are verified again on the
                                server before the order is created.
                            </p>
                        </div>
                    </aside>
                </form>
            </div>

            <Dialog
                open={addressDialogOpen}
                onOpenChange={setAddressDialogOpen}
            >
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Choose shipping address</DialogTitle>

                        <DialogDescription>
                            Select one of the shipping addresses saved to your
                            account.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="max-h-[60vh] space-y-3 overflow-y-auto py-2">
                        {saved_addresses.map((address) => {
                            const isSelected =
                                address.id === data.shipping_address_id;

                            return (
                                <button
                                    key={address.id}
                                    type="button"
                                    onClick={() => selectAddress(address)}
                                    className={`w-full rounded-lg border p-4 text-left transition ${
                                        isSelected
                                            ? 'border-neutral-950 ring-1 ring-neutral-950'
                                            : 'hover:bg-neutral-50'
                                    }`}
                                >
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-medium">
                                                    {address.label ??
                                                        address.recipient_name}
                                                </p>

                                                {address.is_default && (
                                                    <span className="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium">
                                                        Default
                                                    </span>
                                                )}
                                            </div>

                                            <p className="mt-2 text-sm leading-6 text-neutral-600">
                                                {address.recipient_name}
                                                <br />
                                                {address.phone}
                                                <br />
                                                {address.address_line_1}
                                                <br />
                                                {address.address_line_2 && (
                                                    <>
                                                        {address.address_line_2}
                                                        <br />
                                                    </>
                                                )}
                                                {address.city},{' '}
                                                {address.province}{' '}
                                                {address.postal_code}
                                            </p>
                                        </div>

                                        {isSelected && (
                                            <span className="text-sm font-medium">
                                                Selected
                                            </span>
                                        )}
                                    </div>
                                </button>
                            );
                        })}
                    </div>

                    <DialogFooter className="sm:justify-between">
                        <DialogClose asChild>
                            <button
                                type="button"
                                className="rounded-lg border px-4 py-2 text-sm font-medium"
                            >
                                Cancel
                            </button>
                        </DialogClose>

                        <Link
                            href="/account/addresses"
                            className="rounded-lg bg-neutral-950 px-4 py-2 text-center text-sm font-medium text-white"
                        >
                            Add address
                        </Link>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </StorefrontLayout>
    );
}
