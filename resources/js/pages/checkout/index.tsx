import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
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
    CatalogCategory,
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
    bank_transfer_instructions: string | null;
    selected_shipping_method: string;
    customer: CheckoutCustomer;
    is_authenticated: boolean;
    saved_addresses: CheckoutAddress[];
};

type CheckoutSharedProps = {
    store?: {
        theme?: 'default' | 'fashion_editorial';
        navigation_categories?: CatalogCategory[];
    };
};

export default function Checkout({
    items,
    totals,
    shipping_options,
    payment_options,
    bank_transfer_instructions,
    selected_shipping_method,
    customer,
    is_authenticated,
    saved_addresses,
}: CheckoutProps) {
    const page = usePage();

    const sharedProps = page.props as unknown as CheckoutSharedProps;

    const isFashionEditorial = sharedProps.store?.theme === 'fashion_editorial';

    const navigationCategories = sharedProps.store?.navigation_categories ?? [];

    const [addressDialogOpen, setAddressDialogOpen] = useState(false);

    const defaultAddress =
        saved_addresses.find((address) => address.is_default) ??
        saved_addresses[0] ??
        null;

    const hasSavedAddresses = saved_addresses.length > 0;

    const { data, setData, post, processing, errors } =
        useForm<CheckoutFormData>({
            customer_name: defaultAddress?.recipient_name ?? customer.name,
            customer_email: defaultAddress?.email ?? customer.email,
            customer_phone: defaultAddress?.phone ?? customer.phone,

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

            customer_name: address.recipient_name,
            customer_email: address.email ?? customer.email,
            customer_phone: address.phone,

            shipping_address_id: address.id,
            shipping_address_line_1: address.address_line_1,
            shipping_address_line_2: address.address_line_2 ?? '',
            shipping_city: address.city,
            shipping_province: address.province,
            shipping_postal_code: address.postal_code,
        });

        setAddressDialogOpen(false);
    };

    const sectionClassName = isFashionEditorial
        ? 'border-t border-neutral-300 py-8 sm:py-10'
        : 'rounded-xl border bg-white p-6';

    const sectionTitleClassName = isFashionEditorial
        ? 'mt-3 font-serif text-3xl leading-tight tracking-[-0.025em]'
        : 'text-lg font-semibold';

    const fieldGridClassName = isFashionEditorial
        ? 'mt-7 grid gap-x-6 gap-y-7 sm:grid-cols-2'
        : 'mt-5 grid gap-5 sm:grid-cols-2';

    const labelClassName = isFashionEditorial
        ? 'mb-2 block text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase'
        : 'mb-1 block text-sm font-medium';

    const inputClassName = isFashionEditorial
        ? 'w-full border-b border-neutral-300 bg-transparent px-0 py-3 text-sm outline-none transition focus:border-neutral-950 disabled:cursor-not-allowed disabled:text-neutral-500'
        : 'w-full rounded-lg border px-3 py-2 disabled:cursor-not-allowed disabled:bg-neutral-50 disabled:text-neutral-500';

    const secondaryActionClassName = isFashionEditorial
        ? 'inline-flex min-h-10 items-center border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60'
        : 'self-start rounded-lg border px-4 py-2 text-sm font-medium hover:bg-neutral-50 sm:self-auto';

    return (
        <StorefrontLayout
            variant={isFashionEditorial ? 'fashion-editorial' : 'default'}
            navigationCategories={navigationCategories}
        >
            <Head title="Checkout" />

            <section className={isFashionEditorial ? 'bg-[#f8f6f1]' : ''}>
                <div
                    className={
                        isFashionEditorial
                            ? 'mx-auto max-w-[1600px] px-5 py-14 sm:px-8 sm:py-20 lg:px-14 lg:py-24'
                            : 'mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8'
                    }
                >
                    {isFashionEditorial ? (
                        <header className="border-b border-neutral-300 pb-10 sm:pb-12">
                            <div className="flex flex-col gap-8 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                                        Secure checkout
                                    </p>

                                    <h1 className="mt-4 font-serif text-5xl leading-none tracking-[-0.035em] sm:text-6xl">
                                        Complete your order.
                                    </h1>

                                    <p className="mt-5 max-w-xl text-sm leading-7 text-neutral-600">
                                        Review your selection, confirm your
                                        delivery details, and choose how you
                                        would like to complete your purchase.
                                    </p>
                                </div>

                                <Link
                                    href="/cart"
                                    className="inline-flex min-h-10 items-center self-start border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60 sm:self-auto"
                                >
                                    Return to cart
                                </Link>
                            </div>
                        </header>
                    ) : (
                        <div className="mb-8">
                            <Link
                                href="/cart"
                                className="text-sm text-neutral-500 hover:text-neutral-950"
                            >
                                ← Back to cart
                            </Link>

                            <h1 className="mt-4 text-3xl font-semibold">
                                Checkout
                            </h1>

                            <p className="mt-2 text-neutral-600">
                                Review your order and enter your delivery
                                details.
                            </p>
                        </div>
                    )}

                    {cartError && (
                        <div
                            className={
                                isFashionEditorial
                                    ? 'mt-8 border-y border-red-200 bg-red-50 px-5 py-4 text-sm leading-6 text-red-700'
                                    : 'mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700'
                            }
                        >
                            {cartError}
                        </div>
                    )}

                    <form
                        onSubmit={submit}
                        className={
                            isFashionEditorial
                                ? 'mt-10 grid gap-12 lg:grid-cols-[minmax(0,1fr)_390px] lg:gap-14 xl:grid-cols-[minmax(0,1fr)_430px] xl:gap-20'
                                : 'grid gap-8 lg:grid-cols-[1fr_420px]'
                        }
                    >
                        <div
                            className={
                                isFashionEditorial ? 'space-y-0' : 'space-y-8'
                            }
                        >
                            <section className={sectionClassName}>
                                {isFashionEditorial && (
                                    <p className="text-[9px] font-medium tracking-[0.18em] text-neutral-500 uppercase">
                                        01 / Contact
                                    </p>
                                )}

                                <h2 className={sectionTitleClassName}>
                                    Contact information
                                </h2>

                                {hasSavedAddresses && selectedAddress && (
                                    <p
                                        className={
                                            isFashionEditorial
                                                ? 'mt-3 max-w-xl text-xs leading-6 text-neutral-600'
                                                : 'mt-1 text-sm text-neutral-500'
                                        }
                                    >
                                        Contact information is provided by the
                                        selected shipping address.
                                    </p>
                                )}

                                <div className={fieldGridClassName}>
                                    <div className="sm:col-span-2">
                                        <label
                                            htmlFor="customer_name"
                                            className={labelClassName}
                                        >
                                            Full name
                                        </label>

                                        <input
                                            id="customer_name"
                                            type="text"
                                            autoComplete="name"
                                            value={data.customer_name}
                                            disabled={hasSavedAddresses}
                                            onChange={(event) =>
                                                setData(
                                                    'customer_name',
                                                    event.target.value,
                                                )
                                            }
                                            className={inputClassName}
                                        />

                                        <InputError
                                            message={errors.customer_name}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="customer_email"
                                            className={labelClassName}
                                        >
                                            Email
                                        </label>

                                        <input
                                            id="customer_email"
                                            type="email"
                                            autoComplete="email"
                                            value={data.customer_email}
                                            disabled={hasSavedAddresses}
                                            onChange={(event) =>
                                                setData(
                                                    'customer_email',
                                                    event.target.value,
                                                )
                                            }
                                            className={inputClassName}
                                        />

                                        <InputError
                                            message={errors.customer_email}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="customer_phone"
                                            className={labelClassName}
                                        >
                                            Mobile number
                                        </label>

                                        <input
                                            id="customer_phone"
                                            type="tel"
                                            autoComplete="tel"
                                            value={data.customer_phone}
                                            disabled={hasSavedAddresses}
                                            onChange={(event) =>
                                                setData(
                                                    'customer_phone',
                                                    event.target.value,
                                                )
                                            }
                                            className={inputClassName}
                                        />

                                        <InputError
                                            message={errors.customer_phone}
                                            className="mt-2"
                                        />
                                    </div>
                                </div>
                            </section>

                            <section className={sectionClassName}>
                                {isFashionEditorial && (
                                    <p className="text-[9px] font-medium tracking-[0.18em] text-neutral-500 uppercase">
                                        02 / Delivery
                                    </p>
                                )}

                                <div
                                    className={
                                        isFashionEditorial
                                            ? 'flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between'
                                            : 'flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between'
                                    }
                                >
                                    <div>
                                        <h2 className={sectionTitleClassName}>
                                            Shipping address
                                        </h2>

                                        {hasSavedAddresses &&
                                            selectedAddress && (
                                                <p
                                                    className={
                                                        isFashionEditorial
                                                            ? 'mt-3 text-xs leading-6 text-neutral-600'
                                                            : 'mt-1 text-sm text-neutral-500'
                                                    }
                                                >
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
                                            className={secondaryActionClassName}
                                        >
                                            Use different address
                                        </button>
                                    )}
                                </div>

                                {is_authenticated && !hasSavedAddresses && (
                                    <p
                                        className={
                                            isFashionEditorial
                                                ? 'mt-6 border-y border-neutral-300 bg-[#eee8e1] px-4 py-4 text-xs leading-6 text-neutral-600'
                                                : 'mt-3 rounded-lg bg-neutral-50 px-4 py-3 text-sm text-neutral-600'
                                        }
                                    >
                                        Your contact information and shipping
                                        address will be saved as your default
                                        address after your order is successfully
                                        placed.
                                    </p>
                                )}

                                <InputError
                                    message={errors.shipping_address_id}
                                    className="mt-3"
                                />

                                <div className={fieldGridClassName}>
                                    <div className="sm:col-span-2">
                                        <label
                                            htmlFor="shipping_address_line_1"
                                            className={labelClassName}
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
                                            className={inputClassName}
                                        />

                                        <InputError
                                            message={
                                                errors.shipping_address_line_1
                                            }
                                            className="mt-2"
                                        />
                                    </div>

                                    <div className="sm:col-span-2">
                                        <label
                                            htmlFor="shipping_address_line_2"
                                            className={labelClassName}
                                        >
                                            Apartment, suite, etc.
                                            <span
                                                className={
                                                    isFashionEditorial
                                                        ? 'ml-2 text-neutral-400'
                                                        : 'ml-1 font-normal text-neutral-500'
                                                }
                                            >
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
                                            className={inputClassName}
                                        />

                                        <InputError
                                            message={
                                                errors.shipping_address_line_2
                                            }
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="shipping_city"
                                            className={labelClassName}
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
                                            className={inputClassName}
                                        />

                                        <InputError
                                            message={errors.shipping_city}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="shipping_province"
                                            className={labelClassName}
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
                                            className={inputClassName}
                                        />

                                        <InputError
                                            message={errors.shipping_province}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="shipping_postal_code"
                                            className={labelClassName}
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
                                            className={inputClassName}
                                        />

                                        <InputError
                                            message={
                                                errors.shipping_postal_code
                                            }
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="shipping_country"
                                            className={labelClassName}
                                        >
                                            Country
                                        </label>

                                        <input
                                            id="shipping_country"
                                            type="text"
                                            value="Philippines"
                                            disabled
                                            className={
                                                isFashionEditorial
                                                    ? `${inputClassName} text-neutral-500`
                                                    : 'w-full rounded-lg border bg-neutral-50 px-3 py-2 text-neutral-500'
                                            }
                                        />
                                    </div>
                                </div>
                            </section>

                            <section className={sectionClassName}>
                                {isFashionEditorial && (
                                    <p className="text-[9px] font-medium tracking-[0.18em] text-neutral-500 uppercase">
                                        03 / Shipping
                                    </p>
                                )}

                                <h2 className={sectionTitleClassName}>
                                    Shipping method
                                </h2>

                                <div
                                    className={
                                        isFashionEditorial
                                            ? 'mt-7 border-t border-neutral-300'
                                            : 'mt-5 space-y-3'
                                    }
                                >
                                    {shipping_options.map((option) => {
                                        const selected =
                                            data.shipping_method ===
                                            option.value;

                                        return (
                                            <label
                                                key={option.value}
                                                className={
                                                    isFashionEditorial
                                                        ? `flex cursor-pointer items-center justify-between gap-5 border-b border-neutral-300 py-5 text-sm transition ${
                                                              selected
                                                                  ? 'text-neutral-950'
                                                                  : 'text-neutral-600'
                                                          }`
                                                        : 'flex cursor-pointer items-center gap-3 rounded-lg border p-4'
                                                }
                                            >
                                                <span
                                                    className={
                                                        isFashionEditorial
                                                            ? 'font-serif text-lg'
                                                            : 'font-medium'
                                                    }
                                                >
                                                    {option.label}
                                                </span>

                                                <input
                                                    type="radio"
                                                    name="shipping_method"
                                                    value={option.value}
                                                    checked={selected}
                                                    onChange={() =>
                                                        changeShippingMethod(
                                                            option.value,
                                                        )
                                                    }
                                                    className={
                                                        isFashionEditorial
                                                            ? 'size-4 accent-neutral-950'
                                                            : undefined
                                                    }
                                                />
                                            </label>
                                        );
                                    })}
                                </div>

                                <InputError
                                    message={errors.shipping_method}
                                    className="mt-3"
                                />
                            </section>

                            <section className={sectionClassName}>
                                {isFashionEditorial && (
                                    <p className="text-[9px] font-medium tracking-[0.18em] text-neutral-500 uppercase">
                                        04 / Payment
                                    </p>
                                )}

                                <h2 className={sectionTitleClassName}>
                                    Payment method
                                </h2>

                                <div
                                    className={
                                        isFashionEditorial
                                            ? 'mt-7 border-t border-neutral-300'
                                            : 'mt-5 space-y-3'
                                    }
                                >
                                    {payment_options.map((option) => {
                                        const selected =
                                            data.payment_method ===
                                            option.value;

                                        return (
                                            <label
                                                key={option.value}
                                                className={
                                                    isFashionEditorial
                                                        ? `flex cursor-pointer items-center justify-between gap-5 border-b border-neutral-300 py-5 text-sm transition ${
                                                              selected
                                                                  ? 'text-neutral-950'
                                                                  : 'text-neutral-600'
                                                          }`
                                                        : 'flex cursor-pointer items-center gap-3 rounded-lg border p-4'
                                                }
                                            >
                                                <span
                                                    className={
                                                        isFashionEditorial
                                                            ? 'font-serif text-lg'
                                                            : 'font-medium'
                                                    }
                                                >
                                                    {option.label}
                                                </span>

                                                <input
                                                    type="radio"
                                                    name="payment_method"
                                                    value={option.value}
                                                    checked={selected}
                                                    onChange={() =>
                                                        setData(
                                                            'payment_method',
                                                            option.value,
                                                        )
                                                    }
                                                    className={
                                                        isFashionEditorial
                                                            ? 'size-4 accent-neutral-950'
                                                            : undefined
                                                    }
                                                />
                                            </label>
                                        );
                                    })}
                                </div>

                                {data.payment_method === 'bank_transfer' &&
                                    bank_transfer_instructions && (
                                        <div
                                            className={
                                                isFashionEditorial
                                                    ? 'mt-6 border-l border-neutral-300 pl-4'
                                                    : 'mt-4 rounded-lg border bg-neutral-50 p-4'
                                            }
                                        >
                                            <p
                                                className={
                                                    isFashionEditorial
                                                        ? 'text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase'
                                                        : 'text-sm font-medium'
                                                }
                                            >
                                                Bank transfer instructions
                                            </p>

                                            <p
                                                className={
                                                    isFashionEditorial
                                                        ? 'mt-3 text-xs leading-6 whitespace-pre-line text-neutral-700'
                                                        : 'mt-2 text-sm leading-6 whitespace-pre-line text-neutral-700'
                                                }
                                            >
                                                {bank_transfer_instructions}
                                            </p>

                                            <p
                                                className={
                                                    isFashionEditorial
                                                        ? 'mt-4 text-xs leading-6 text-neutral-600'
                                                        : 'mt-3 text-sm leading-6 text-neutral-600'
                                                }
                                            >
                                                Your order will remain pending
                                                until the bank transfer is
                                                manually verified.
                                            </p>
                                        </div>
                                    )}

                                <InputError
                                    message={errors.payment_method}
                                    className="mt-3"
                                />
                            </section>

                            <section className={sectionClassName}>
                                {isFashionEditorial && (
                                    <p className="text-[9px] font-medium tracking-[0.18em] text-neutral-500 uppercase">
                                        05 / Notes
                                    </p>
                                )}

                                <label
                                    htmlFor="customer_notes"
                                    className={sectionTitleClassName}
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
                                    className={
                                        isFashionEditorial
                                            ? 'mt-7 min-h-32 w-full resize-y border border-neutral-300 bg-transparent p-4 text-sm leading-6 transition outline-none placeholder:text-neutral-400 focus:border-neutral-950'
                                            : 'mt-5 w-full rounded-lg border px-3 py-2'
                                    }
                                />

                                <InputError
                                    message={errors.customer_notes}
                                    className="mt-2"
                                />
                            </section>
                        </div>

                        <aside
                            className={
                                isFashionEditorial
                                    ? 'lg:border-l lg:border-neutral-300 lg:pl-10 xl:pl-12'
                                    : ''
                            }
                        >
                            {isFashionEditorial ? (
                                <div className="sticky top-6">
                                    <p className="text-[9px] font-medium tracking-[0.18em] text-neutral-500 uppercase">
                                        Order summary
                                    </p>

                                    <h2 className="mt-3 font-serif text-3xl leading-tight tracking-[-0.025em]">
                                        Your selection
                                    </h2>

                                    <div className="mt-8 border-t border-neutral-300">
                                        {items.map((item) => (
                                            <div
                                                key={item.product_id}
                                                className="flex gap-4 border-b border-neutral-300 py-5"
                                            >
                                                <div className="aspect-[4/5] w-16 shrink-0 overflow-hidden bg-[#eee8e1] sm:w-18">
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
                                                        className="line-clamp-2 font-serif text-base leading-5 transition-opacity hover:opacity-60"
                                                    >
                                                        {item.name}
                                                    </Link>

                                                    <p className="mt-2 text-[9px] font-medium tracking-[0.12em] text-neutral-500 uppercase">
                                                        Quantity {item.quantity}
                                                    </p>
                                                </div>

                                                <Price
                                                    amount={item.line_total}
                                                    className="shrink-0 text-xs"
                                                />
                                            </div>
                                        ))}
                                    </div>

                                    <dl className="mt-8 border-t border-neutral-300 text-sm">
                                        <div className="flex justify-between gap-6 border-b border-neutral-300 py-4">
                                            <dt className="text-xs text-neutral-600">
                                                Subtotal
                                            </dt>

                                            <dd>
                                                <Price
                                                    amount={totals.subtotal}
                                                />
                                            </dd>
                                        </div>

                                        {totals.discount_total > 0 && (
                                            <div className="flex justify-between gap-6 border-b border-neutral-300 py-4">
                                                <dt className="text-xs text-neutral-600">
                                                    Discount
                                                    {totals.discount_code
                                                        ? ` (${totals.discount_code})`
                                                        : ''}
                                                </dt>

                                                <dd>
                                                    −
                                                    <Price
                                                        amount={
                                                            totals.discount_total
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
                                                {totals.shipping_total === 0 ? (
                                                    'Free'
                                                ) : (
                                                    <Price
                                                        amount={
                                                            totals.shipping_total
                                                        }
                                                    />
                                                )}
                                            </dd>
                                        </div>

                                        {totals.tax_total > 0 && (
                                            <div className="flex justify-between gap-6 border-b border-neutral-300 py-4">
                                                <dt className="text-xs text-neutral-600">
                                                    Tax
                                                </dt>

                                                <dd>
                                                    <Price
                                                        amount={
                                                            totals.tax_total
                                                        }
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
                                                    amount={totals.grand_total}
                                                    className="font-serif text-xl"
                                                />
                                            </dd>
                                        </div>
                                    </dl>

                                    <div className="mt-7 border-t border-neutral-300 pt-8">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="flex min-h-13 w-full items-center justify-center border border-neutral-950 bg-neutral-950 px-6 text-[10px] font-medium tracking-[0.16em] text-white uppercase transition duration-300 hover:bg-transparent hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {processing
                                                ? 'Placing order...'
                                                : 'Place order'}
                                        </button>

                                        <p className="mt-4 text-center text-[11px] leading-5 text-neutral-500">
                                            Your order totals and available
                                            inventory are verified again before
                                            the order is created.
                                        </p>
                                    </div>
                                </div>
                            ) : (
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
                                                        amount={
                                                            totals.discount_total
                                                        }
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
                                                <Price
                                                    amount={
                                                        totals.shipping_total
                                                    }
                                                />
                                            )}
                                        </div>

                                        {totals.tax_total > 0 && (
                                            <div className="flex justify-between gap-4">
                                                <span className="text-neutral-600">
                                                    Tax
                                                </span>

                                                <Price
                                                    amount={totals.tax_total}
                                                />
                                            </div>
                                        )}

                                        <div className="flex justify-between gap-4 border-t pt-4 text-base font-semibold">
                                            <span>Total</span>

                                            <Price
                                                amount={totals.grand_total}
                                            />
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
                                        Your order totals are verified again on
                                        the server before the order is created.
                                    </p>
                                </div>
                            )}
                        </aside>
                    </form>
                </div>
            </section>

            <Dialog
                open={addressDialogOpen}
                onOpenChange={setAddressDialogOpen}
            >
                <DialogContent
                    className={
                        isFashionEditorial
                            ? 'rounded-none border-neutral-300 bg-[#f8f6f1] sm:max-w-xl'
                            : 'sm:max-w-xl'
                    }
                >
                    <DialogHeader>
                        <DialogTitle
                            className={
                                isFashionEditorial
                                    ? 'font-serif text-2xl font-normal tracking-[-0.02em]'
                                    : undefined
                            }
                        >
                            Choose shipping address
                        </DialogTitle>

                        <DialogDescription>
                            Select one of the shipping addresses saved to your
                            account. Contact information will also use the
                            selected address.
                        </DialogDescription>
                    </DialogHeader>

                    <div
                        className={
                            isFashionEditorial
                                ? 'max-h-[60vh] overflow-y-auto border-t border-neutral-300'
                                : 'max-h-[60vh] space-y-3 overflow-y-auto py-2'
                        }
                    >
                        {saved_addresses.map((address) => {
                            const isSelected =
                                address.id === data.shipping_address_id;

                            return (
                                <button
                                    key={address.id}
                                    type="button"
                                    onClick={() => selectAddress(address)}
                                    className={
                                        isFashionEditorial
                                            ? `w-full border-b border-neutral-300 px-1 py-5 text-left transition ${
                                                  isSelected
                                                      ? 'bg-[#eee8e1]'
                                                      : 'hover:bg-[#eee8e1]'
                                              }`
                                            : `w-full rounded-lg border p-4 text-left transition ${
                                                  isSelected
                                                      ? 'border-neutral-950 ring-1 ring-neutral-950'
                                                      : 'hover:bg-neutral-50'
                                              }`
                                    }
                                >
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p
                                                    className={
                                                        isFashionEditorial
                                                            ? 'font-serif text-lg'
                                                            : 'font-medium'
                                                    }
                                                >
                                                    {address.label ??
                                                        address.recipient_name}
                                                </p>

                                                {address.is_default && (
                                                    <span
                                                        className={
                                                            isFashionEditorial
                                                                ? 'text-[8px] font-medium tracking-[0.12em] text-neutral-500 uppercase'
                                                                : 'rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium'
                                                        }
                                                    >
                                                        Default
                                                    </span>
                                                )}
                                            </div>

                                            <p className="mt-2 text-sm leading-6 text-neutral-600">
                                                {address.recipient_name}
                                                <br />
                                                {address.email && (
                                                    <>
                                                        {address.email}
                                                        <br />
                                                    </>
                                                )}
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
                                            <span
                                                className={
                                                    isFashionEditorial
                                                        ? 'text-[8px] font-medium tracking-[0.12em] uppercase'
                                                        : 'text-sm font-medium'
                                                }
                                            >
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
                                className={
                                    isFashionEditorial
                                        ? 'min-h-11 border border-neutral-950 px-5 text-[9px] font-medium tracking-[0.14em] uppercase transition hover:bg-neutral-950 hover:text-white'
                                        : 'rounded-lg border px-4 py-2 text-sm font-medium'
                                }
                            >
                                Cancel
                            </button>
                        </DialogClose>

                        <Link
                            href="/account/addresses"
                            className={
                                isFashionEditorial
                                    ? 'inline-flex min-h-11 items-center justify-center border border-neutral-950 bg-neutral-950 px-5 text-[9px] font-medium tracking-[0.14em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950'
                                    : 'rounded-lg bg-neutral-950 px-4 py-2 text-center text-sm font-medium text-white'
                            }
                        >
                            Add address
                        </Link>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </StorefrontLayout>
    );
}
