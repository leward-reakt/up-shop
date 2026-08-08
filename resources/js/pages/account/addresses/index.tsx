import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import FashionAccountLayout, {
    useFashionAccountTheme,
} from '@/layouts/fashion-account-layout';
import type { AccountAddress } from '@/types/account';

type AddressesProps = {
    addresses: AccountAddress[];
};

const fashionFieldClassName =
    'w-full border border-neutral-300 bg-transparent px-4 py-3 text-sm outline-none transition focus:border-neutral-950';

const fashionLabelClassName =
    'mb-2 block text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase';

export default function Addresses({ addresses }: AddressesProps) {
    const isFashionEditorial = useFashionAccountTheme();

    if (isFashionEditorial) {
        return (
            <FashionAccountLayout
                active="addresses"
                title="Your addresses."
                description="Manage the delivery addresses connected with your account and choose where future purchases should be sent."
            >
                <Head title="Shipping Addresses" />

                <div className="grid gap-14 xl:grid-cols-[minmax(320px,0.75fr)_minmax(0,1.25fr)] xl:gap-20">
                    <section>
                        <div>
                            <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                Delivery
                            </p>

                            <h2 className="mt-3 font-serif text-3xl leading-tight tracking-[-0.025em]">
                                Add address
                            </h2>

                            <p className="mt-3 max-w-lg text-sm leading-7 text-neutral-600">
                                Save another delivery location for future
                                purchases.
                            </p>
                        </div>

                        <Form
                            action="/account/addresses"
                            method="post"
                            options={{
                                preserveScroll: true,
                            }}
                            className="mt-8 space-y-6 border-t border-neutral-300 pt-7"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div>
                                        <label
                                            htmlFor="label"
                                            className={fashionLabelClassName}
                                        >
                                            Label
                                        </label>

                                        <input
                                            id="label"
                                            name="label"
                                            type="text"
                                            placeholder="Home, Office"
                                            className={fashionFieldClassName}
                                        />

                                        <InputError
                                            className="mt-2"
                                            message={errors.label}
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="recipient_name"
                                            className={fashionLabelClassName}
                                        >
                                            Recipient name
                                        </label>

                                        <input
                                            id="recipient_name"
                                            name="recipient_name"
                                            type="text"
                                            required
                                            autoComplete="name"
                                            className={fashionFieldClassName}
                                        />

                                        <InputError
                                            className="mt-2"
                                            message={errors.recipient_name}
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="email"
                                            className={fashionLabelClassName}
                                        >
                                            Email
                                        </label>

                                        <input
                                            id="email"
                                            name="email"
                                            type="email"
                                            required
                                            autoComplete="email"
                                            className={fashionFieldClassName}
                                        />

                                        <InputError
                                            className="mt-2"
                                            message={errors.email}
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="phone"
                                            className={fashionLabelClassName}
                                        >
                                            Phone
                                        </label>

                                        <input
                                            id="phone"
                                            name="phone"
                                            type="tel"
                                            required
                                            autoComplete="tel"
                                            className={fashionFieldClassName}
                                        />

                                        <InputError
                                            className="mt-2"
                                            message={errors.phone}
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="address_line_1"
                                            className={fashionLabelClassName}
                                        >
                                            Address
                                        </label>

                                        <input
                                            id="address_line_1"
                                            name="address_line_1"
                                            type="text"
                                            required
                                            autoComplete="address-line1"
                                            className={fashionFieldClassName}
                                        />

                                        <InputError
                                            className="mt-2"
                                            message={errors.address_line_1}
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="address_line_2"
                                            className={fashionLabelClassName}
                                        >
                                            Apartment, suite, etc.
                                        </label>

                                        <input
                                            id="address_line_2"
                                            name="address_line_2"
                                            type="text"
                                            autoComplete="address-line2"
                                            className={fashionFieldClassName}
                                        />

                                        <InputError
                                            className="mt-2"
                                            message={errors.address_line_2}
                                        />
                                    </div>

                                    <div className="grid gap-6 sm:grid-cols-2">
                                        <div>
                                            <label
                                                htmlFor="city"
                                                className={
                                                    fashionLabelClassName
                                                }
                                            >
                                                City
                                            </label>

                                            <input
                                                id="city"
                                                name="city"
                                                type="text"
                                                required
                                                autoComplete="address-level2"
                                                className={
                                                    fashionFieldClassName
                                                }
                                            />

                                            <InputError
                                                className="mt-2"
                                                message={errors.city}
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="province"
                                                className={
                                                    fashionLabelClassName
                                                }
                                            >
                                                Province
                                            </label>

                                            <input
                                                id="province"
                                                name="province"
                                                type="text"
                                                required
                                                autoComplete="address-level1"
                                                className={
                                                    fashionFieldClassName
                                                }
                                            />

                                            <InputError
                                                className="mt-2"
                                                message={errors.province}
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="postal_code"
                                            className={fashionLabelClassName}
                                        >
                                            Postal code
                                        </label>

                                        <input
                                            id="postal_code"
                                            name="postal_code"
                                            type="text"
                                            required
                                            autoComplete="postal-code"
                                            className={fashionFieldClassName}
                                        />

                                        <InputError
                                            className="mt-2"
                                            message={errors.postal_code}
                                        />
                                    </div>

                                    <label className="flex cursor-pointer items-center gap-3 border-t border-neutral-300 pt-5 text-xs text-neutral-700">
                                        <input
                                            type="checkbox"
                                            name="is_default"
                                            value="1"
                                            className="size-4 border border-neutral-400 bg-transparent"
                                        />

                                        <span>
                                            Make this my default address
                                        </span>
                                    </label>

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex min-h-11 items-center justify-center border border-neutral-950 bg-neutral-950 px-6 text-[9px] font-medium tracking-[0.14em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        {processing
                                            ? 'Saving...'
                                            : 'Add address'}
                                    </button>
                                </>
                            )}
                        </Form>
                    </section>

                    <section className="border-t border-neutral-300 pt-8 xl:border-t-0 xl:border-l xl:pt-0 xl:pl-12">
                        <div className="flex items-end justify-between gap-6">
                            <div>
                                <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                    Saved locations
                                </p>

                                <h2 className="mt-3 font-serif text-3xl leading-tight tracking-[-0.025em]">
                                    Saved addresses
                                </h2>
                            </div>

                            {addresses.length > 0 && (
                                <p className="text-[9px] tracking-[0.12em] text-neutral-500 uppercase">
                                    {addresses.length}{' '}
                                    {addresses.length === 1
                                        ? 'address'
                                        : 'addresses'}
                                </p>
                            )}
                        </div>

                        {addresses.length === 0 ? (
                            <div className="mt-8 border-y border-neutral-300 py-12">
                                <p className="font-serif text-2xl tracking-[-0.02em]">
                                    No saved addresses.
                                </p>

                                <p className="mt-3 max-w-md text-sm leading-7 text-neutral-600">
                                    Add your first delivery address using the
                                    form alongside.
                                </p>
                            </div>
                        ) : (
                            <div className="mt-8 border-t border-neutral-300">
                                {addresses.map((address) => (
                                    <article
                                        key={address.id}
                                        className="border-b border-neutral-300 py-8"
                                    >
                                        <div className="grid gap-7 sm:grid-cols-[minmax(0,1fr)_auto]">
                                            <div>
                                                <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
                                                    <h3 className="font-serif text-2xl leading-tight tracking-[-0.02em]">
                                                        {address.label ??
                                                            address.recipient_name}
                                                    </h3>

                                                    {address.is_default && (
                                                        <span className="border border-neutral-400 px-2.5 py-1 text-[8px] font-medium tracking-[0.14em] text-neutral-600 uppercase">
                                                            Default
                                                        </span>
                                                    )}
                                                </div>

                                                <div className="mt-5 grid gap-6 text-sm leading-7 text-neutral-600 sm:grid-cols-2">
                                                    <div>
                                                        <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                                            Recipient
                                                        </p>

                                                        <p className="mt-2 text-neutral-800">
                                                            {
                                                                address.recipient_name
                                                            }
                                                        </p>

                                                        {address.email && (
                                                            <p>
                                                                {address.email}
                                                            </p>
                                                        )}

                                                        <p>{address.phone}</p>
                                                    </div>

                                                    <div>
                                                        <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                                            Delivery address
                                                        </p>

                                                        <p className="mt-2">
                                                            {
                                                                address.address_line_1
                                                            }
                                                            {address.address_line_2 && (
                                                                <>
                                                                    <br />
                                                                    {
                                                                        address.address_line_2
                                                                    }
                                                                </>
                                                            )}
                                                            <br />
                                                            {address.city},{' '}
                                                            {address.province}{' '}
                                                            {
                                                                address.postal_code
                                                            }
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-start gap-5 sm:flex-col sm:items-end">
                                                <Link
                                                    href={`/account/addresses/${address.id}/edit`}
                                                    className="inline-flex min-h-9 items-center border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60"
                                                >
                                                    Edit
                                                </Link>

                                                <Form
                                                    action={`/account/addresses/${address.id}`}
                                                    method="delete"
                                                    options={{
                                                        preserveScroll: true,
                                                    }}
                                                >
                                                    {({ processing }) => (
                                                        <button
                                                            type="submit"
                                                            disabled={
                                                                processing
                                                            }
                                                            className="inline-flex min-h-9 items-center border-b border-red-300 text-[9px] font-medium tracking-[0.14em] text-red-700 uppercase transition-opacity hover:opacity-60 disabled:cursor-not-allowed disabled:opacity-40"
                                                        >
                                                            {processing
                                                                ? 'Deleting...'
                                                                : 'Delete'}
                                                        </button>
                                                    )}
                                                </Form>
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}
                    </section>
                </div>
            </FashionAccountLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="Shipping Addresses" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Shipping Addresses
                    </h1>

                    <p className="mt-1 text-sm text-muted-foreground">
                        Manage the delivery addresses associated with your
                        account.
                    </p>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1fr_1.2fr]">
                    <section className="rounded-xl border bg-card p-5">
                        <h2 className="font-semibold">Add address</h2>

                        <Form
                            action="/account/addresses"
                            method="post"
                            options={{
                                preserveScroll: true,
                            }}
                            className="mt-5 space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div>
                                        <label
                                            htmlFor="label"
                                            className="mb-1 block text-sm font-medium"
                                        >
                                            Label
                                        </label>

                                        <input
                                            id="label"
                                            name="label"
                                            type="text"
                                            placeholder="Home, Office"
                                            className="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                                        />

                                        <InputError
                                            className="mt-1"
                                            message={errors.label}
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="recipient_name"
                                            className="mb-1 block text-sm font-medium"
                                        >
                                            Recipient name
                                        </label>

                                        <input
                                            id="recipient_name"
                                            name="recipient_name"
                                            type="text"
                                            required
                                            autoComplete="name"
                                            className="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                                        />

                                        <InputError
                                            className="mt-1"
                                            message={errors.recipient_name}
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="email"
                                            className="mb-1 block text-sm font-medium"
                                        >
                                            Email
                                        </label>

                                        <input
                                            id="email"
                                            name="email"
                                            type="email"
                                            required
                                            autoComplete="email"
                                            className="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                                        />

                                        <InputError
                                            className="mt-1"
                                            message={errors.email}
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="phone"
                                            className="mb-1 block text-sm font-medium"
                                        >
                                            Phone
                                        </label>

                                        <input
                                            id="phone"
                                            name="phone"
                                            type="tel"
                                            required
                                            autoComplete="tel"
                                            className="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                                        />

                                        <InputError
                                            className="mt-1"
                                            message={errors.phone}
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="address_line_1"
                                            className="mb-1 block text-sm font-medium"
                                        >
                                            Address
                                        </label>

                                        <input
                                            id="address_line_1"
                                            name="address_line_1"
                                            type="text"
                                            required
                                            autoComplete="address-line1"
                                            className="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                                        />

                                        <InputError
                                            className="mt-1"
                                            message={errors.address_line_1}
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="address_line_2"
                                            className="mb-1 block text-sm font-medium"
                                        >
                                            Apartment, suite, etc.
                                        </label>

                                        <input
                                            id="address_line_2"
                                            name="address_line_2"
                                            type="text"
                                            autoComplete="address-line2"
                                            className="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                                        />

                                        <InputError
                                            className="mt-1"
                                            message={errors.address_line_2}
                                        />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label
                                                htmlFor="city"
                                                className="mb-1 block text-sm font-medium"
                                            >
                                                City
                                            </label>

                                            <input
                                                id="city"
                                                name="city"
                                                type="text"
                                                required
                                                autoComplete="address-level2"
                                                className="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                                            />

                                            <InputError
                                                className="mt-1"
                                                message={errors.city}
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="province"
                                                className="mb-1 block text-sm font-medium"
                                            >
                                                Province
                                            </label>

                                            <input
                                                id="province"
                                                name="province"
                                                type="text"
                                                required
                                                autoComplete="address-level1"
                                                className="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                                            />

                                            <InputError
                                                className="mt-1"
                                                message={errors.province}
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="postal_code"
                                            className="mb-1 block text-sm font-medium"
                                        >
                                            Postal code
                                        </label>

                                        <input
                                            id="postal_code"
                                            name="postal_code"
                                            type="text"
                                            required
                                            autoComplete="postal-code"
                                            className="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                                        />

                                        <InputError
                                            className="mt-1"
                                            message={errors.postal_code}
                                        />
                                    </div>

                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            name="is_default"
                                            value="1"
                                            className="size-4 rounded border"
                                        />
                                        Make this my default address
                                    </label>

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                                    >
                                        {processing
                                            ? 'Saving...'
                                            : 'Add address'}
                                    </button>
                                </>
                            )}
                        </Form>
                    </section>

                    <section>
                        <h2 className="font-semibold">Saved addresses</h2>

                        {addresses.length === 0 ? (
                            <div className="mt-4 rounded-xl border bg-card p-6 text-sm text-muted-foreground">
                                You do not have any saved addresses yet.
                            </div>
                        ) : (
                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                {addresses.map((address) => (
                                    <div
                                        key={address.id}
                                        className="rounded-xl border bg-card p-5"
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div>
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="font-medium">
                                                        {address.label ??
                                                            address.recipient_name}
                                                    </p>

                                                    {address.is_default && (
                                                        <span className="rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
                                                            Default
                                                        </span>
                                                    )}
                                                </div>

                                                <p className="mt-3 text-sm text-muted-foreground">
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
                                                            {
                                                                address.address_line_2
                                                            }
                                                            <br />
                                                        </>
                                                    )}
                                                    {address.city},{' '}
                                                    {address.province}{' '}
                                                    {address.postal_code}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="mt-5 flex gap-2">
                                            <Link
                                                href={`/account/addresses/${address.id}/edit`}
                                                className="rounded-lg border px-3 py-2 text-sm font-medium"
                                            >
                                                Edit
                                            </Link>

                                            <Form
                                                action={`/account/addresses/${address.id}`}
                                                method="delete"
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                            >
                                                {({ processing }) => (
                                                    <button
                                                        type="submit"
                                                        disabled={processing}
                                                        className="rounded-lg border px-3 py-2 text-sm font-medium text-destructive disabled:opacity-50"
                                                    >
                                                        Delete
                                                    </button>
                                                )}
                                            </Form>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
