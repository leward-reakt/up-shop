import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import type { AccountAddress } from '@/types/account';

type EditAddressProps = {
    address: AccountAddress;
};

export default function EditAddress({ address }: EditAddressProps) {
    return (
        <>
            <Head title="Edit Address" />

            <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <Link
                        href="/account/addresses"
                        className="text-sm text-muted-foreground underline underline-offset-4"
                    >
                        Back to addresses
                    </Link>

                    <h1 className="mt-4 text-2xl font-semibold">
                        Edit Address
                    </h1>
                </div>

                <section className="rounded-xl border bg-card p-5">
                    <Form
                        action={`/account/addresses/${address.id}`}
                        method="patch"
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-4"
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
                                        defaultValue={address.label ?? ''}
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
                                        defaultValue={address.recipient_name}
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
                                        defaultValue={address.phone}
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
                                        defaultValue={address.address_line_1}
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
                                        defaultValue={
                                            address.address_line_2 ?? ''
                                        }
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
                                            defaultValue={address.city}
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
                                            defaultValue={address.province}
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
                                        defaultValue={address.postal_code}
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
                                        defaultChecked={address.is_default}
                                        className="size-4 rounded border"
                                    />
                                    Make this my default address
                                </label>

                                {address.is_default && (
                                    <p className="text-xs text-muted-foreground">
                                        The current default remains selected
                                        until another address is made default.
                                    </p>
                                )}

                                <div className="flex gap-2">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                                    >
                                        {processing
                                            ? 'Saving...'
                                            : 'Save changes'}
                                    </button>

                                    <Link
                                        href="/account/addresses"
                                        className="rounded-lg border px-4 py-2 text-sm font-medium"
                                    >
                                        Cancel
                                    </Link>
                                </div>
                            </>
                        )}
                    </Form>
                </section>
            </div>
        </>
    );
}
