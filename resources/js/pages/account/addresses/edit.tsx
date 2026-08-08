import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import FashionAccountLayout, {
    useFashionAccountTheme,
} from '@/layouts/fashion-account-layout';
import type { AccountAddress } from '@/types/account';

type EditAddressProps = {
    address: AccountAddress;
};

type AddressFormProps = {
    address: AccountAddress;
    isFashionEditorial?: boolean;
};

const fashionFieldClassName =
    'w-full rounded-none border border-neutral-300 bg-transparent px-4 py-3 text-sm text-neutral-950 outline-none transition placeholder:text-neutral-400 focus:border-neutral-950';

const fashionLabelClassName =
    'mb-2 block text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase';

function AddressForm({
    address,
    isFashionEditorial = false,
}: AddressFormProps) {
    const fieldClassName = isFashionEditorial
        ? fashionFieldClassName
        : 'w-full rounded-lg border bg-background px-3 py-2 text-sm';

    const labelClassName = isFashionEditorial
        ? fashionLabelClassName
        : 'mb-1 block text-sm font-medium';

    return (
        <Form
            action={`/account/addresses/${address.id}`}
            method="patch"
            options={{
                preserveScroll: true,
            }}
            className={isFashionEditorial ? 'space-y-7' : 'space-y-4'}
        >
            {({ processing, errors }) => (
                <>
                    <div>
                        <label htmlFor="label" className={labelClassName}>
                            Address label
                        </label>

                        <input
                            id="label"
                            name="label"
                            type="text"
                            defaultValue={address.label ?? ''}
                            placeholder={
                                isFashionEditorial ? 'Home, Office' : undefined
                            }
                            className={fieldClassName}
                        />

                        <InputError className="mt-2" message={errors.label} />
                    </div>

                    {isFashionEditorial && (
                        <div className="border-t border-neutral-300 pt-7">
                            <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                Contact details
                            </p>
                        </div>
                    )}

                    <div
                        className={
                            isFashionEditorial
                                ? 'grid gap-6 md:grid-cols-2'
                                : undefined
                        }
                    >
                        <div>
                            <label
                                htmlFor="recipient_name"
                                className={labelClassName}
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
                                className={fieldClassName}
                            />

                            <InputError
                                className="mt-2"
                                message={errors.recipient_name}
                            />
                        </div>

                        <div
                            className={isFashionEditorial ? undefined : 'mt-4'}
                        >
                            <label htmlFor="email" className={labelClassName}>
                                Email
                            </label>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                required
                                autoComplete="email"
                                defaultValue={address.email ?? ''}
                                className={fieldClassName}
                            />

                            <InputError
                                className="mt-2"
                                message={errors.email}
                            />
                        </div>
                    </div>

                    <div>
                        <label htmlFor="phone" className={labelClassName}>
                            Phone
                        </label>

                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            required
                            autoComplete="tel"
                            defaultValue={address.phone}
                            className={fieldClassName}
                        />

                        <InputError className="mt-2" message={errors.phone} />
                    </div>

                    {isFashionEditorial && (
                        <div className="border-t border-neutral-300 pt-7">
                            <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                Delivery address
                            </p>
                        </div>
                    )}

                    <div>
                        <label
                            htmlFor="address_line_1"
                            className={labelClassName}
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
                            className={fieldClassName}
                        />

                        <InputError
                            className="mt-2"
                            message={errors.address_line_1}
                        />
                    </div>

                    <div>
                        <label
                            htmlFor="address_line_2"
                            className={labelClassName}
                        >
                            Apartment, suite, etc.
                        </label>

                        <input
                            id="address_line_2"
                            name="address_line_2"
                            type="text"
                            autoComplete="address-line2"
                            defaultValue={address.address_line_2 ?? ''}
                            className={fieldClassName}
                        />

                        <InputError
                            className="mt-2"
                            message={errors.address_line_2}
                        />
                    </div>

                    <div className="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label htmlFor="city" className={labelClassName}>
                                City
                            </label>

                            <input
                                id="city"
                                name="city"
                                type="text"
                                required
                                autoComplete="address-level2"
                                defaultValue={address.city}
                                className={fieldClassName}
                            />

                            <InputError
                                className="mt-2"
                                message={errors.city}
                            />
                        </div>

                        <div>
                            <label
                                htmlFor="province"
                                className={labelClassName}
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
                                className={fieldClassName}
                            />

                            <InputError
                                className="mt-2"
                                message={errors.province}
                            />
                        </div>
                    </div>

                    <div>
                        <label htmlFor="postal_code" className={labelClassName}>
                            Postal code
                        </label>

                        <input
                            id="postal_code"
                            name="postal_code"
                            type="text"
                            required
                            autoComplete="postal-code"
                            defaultValue={address.postal_code}
                            className={fieldClassName}
                        />

                        <InputError
                            className="mt-2"
                            message={errors.postal_code}
                        />
                    </div>

                    {isFashionEditorial ? (
                        <div className="border-t border-neutral-300 pt-6">
                            <label
                                htmlFor="is_default"
                                className="flex cursor-pointer items-start gap-3"
                            >
                                <input
                                    id="is_default"
                                    type="checkbox"
                                    name="is_default"
                                    value="1"
                                    defaultChecked={address.is_default}
                                    className="mt-0.5 size-4 rounded-none border-neutral-400 bg-transparent text-neutral-950 focus:ring-neutral-950"
                                />

                                <span>
                                    <span className="block text-sm text-neutral-900">
                                        Make this my default address
                                    </span>

                                    <span className="mt-1 block text-xs leading-6 text-neutral-500">
                                        Your default address is used
                                        automatically during checkout.
                                    </span>
                                </span>
                            </label>

                            {address.is_default && (
                                <p className="mt-4 border-l border-neutral-400 pl-4 text-xs leading-6 text-neutral-500">
                                    This is currently your default address. It
                                    remains selected until another address is
                                    made default.
                                </p>
                            )}
                        </div>
                    ) : (
                        <>
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
                                    The current default remains selected until
                                    another address is made default.
                                </p>
                            )}
                        </>
                    )}

                    {isFashionEditorial ? (
                        <div className="flex flex-col gap-3 border-t border-neutral-300 pt-7 sm:flex-row">
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex min-h-11 items-center justify-center border border-neutral-950 bg-neutral-950 px-7 text-[9px] font-medium tracking-[0.14em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                {processing ? 'Saving...' : 'Save changes'}
                            </button>

                            <Link
                                href="/account/addresses"
                                className="inline-flex min-h-11 items-center justify-center border border-neutral-300 px-7 text-[9px] font-medium tracking-[0.14em] text-neutral-800 uppercase transition hover:border-neutral-950"
                            >
                                Cancel
                            </Link>
                        </div>
                    ) : (
                        <div className="flex gap-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                            >
                                {processing ? 'Saving...' : 'Save changes'}
                            </button>

                            <Link
                                href="/account/addresses"
                                className="rounded-lg border px-4 py-2 text-sm font-medium"
                            >
                                Cancel
                            </Link>
                        </div>
                    )}
                </>
            )}
        </Form>
    );
}

export default function EditAddress({ address }: EditAddressProps) {
    const isFashionEditorial = useFashionAccountTheme();

    if (isFashionEditorial) {
        return (
            <>
                <Head title="Edit Address" />

                <FashionAccountLayout
                    active="addresses"
                    title="Edit address."
                    description="Update your delivery details and choose whether this should be your default checkout address."
                >
                    <div className="mx-auto w-full max-w-3xl">
                        <Link
                            href="/account/addresses"
                            className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase transition hover:text-neutral-950"
                        >
                            ← Back to addresses
                        </Link>

                        <section className="mt-8 border-t border-neutral-300 pt-8">
                            <div className="mb-8">
                                <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                    Address details
                                </p>

                                <h2 className="mt-3 font-serif text-2xl leading-tight tracking-[-0.02em] text-neutral-950">
                                    {address.label ?? address.recipient_name}
                                </h2>

                                <p className="mt-2 text-sm leading-7 text-neutral-600">
                                    Review the information below before saving
                                    your changes.
                                </p>
                            </div>

                            <AddressForm address={address} isFashionEditorial />
                        </section>
                    </div>
                </FashionAccountLayout>
            </>
        );
    }

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
                    <AddressForm address={address} />
                </section>
            </div>
        </>
    );
}
