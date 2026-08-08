import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import type { AccountAddress } from '@/types/account';

type AddressesProps = {
    addresses: AccountAddress[];
};

export default function Addresses({ addresses }: AddressesProps) {
    return (
        <>
            <Head title="Shipping Addresses" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Shipping Addresses
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Manage the addresses associated with your account.
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
        </>
    );
}
