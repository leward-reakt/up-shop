import { Form, Head, Link, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import FashionAccountLayout, {
    useFashionAccountTheme,
} from '@/layouts/fashion-account-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

type ProfileProps = {
    mustVerifyEmail: boolean;
    status?: string;
};

const fashionFieldClassName =
    'w-full border border-neutral-300 bg-transparent px-4 py-3 text-sm text-neutral-950 outline-none transition placeholder:text-neutral-400 focus:border-neutral-950';

const fashionLabelClassName =
    'mb-2 block text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase';

export default function Profile({ mustVerifyEmail, status }: ProfileProps) {
    const { auth } = usePage<PageProps>().props;

    const isFashionEditorial = useFashionAccountTheme();

    if (isFashionEditorial) {
        return (
            <FashionAccountLayout
                active="profile"
                title="Your profile."
                description="Keep your personal and contact information current for your account and future purchases."
            >
                <Head title="Profile settings" />

                <div className="grid gap-14 xl:grid-cols-[minmax(0,1fr)_360px] xl:gap-20">
                    <section>
                        <div>
                            <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                Personal details
                            </p>

                            <h2 className="mt-3 font-serif text-3xl leading-tight tracking-[-0.025em]">
                                Profile information
                            </h2>

                            <p className="mt-3 max-w-xl text-sm leading-7 text-neutral-600">
                                Update the information associated with your
                                customer account.
                            </p>
                        </div>

                        <Form
                            {...ProfileController.update.form()}
                            options={{
                                preserveScroll: true,
                            }}
                            className="mt-8 space-y-7 border-t border-neutral-300 pt-7"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div>
                                        <label
                                            htmlFor="name"
                                            className={fashionLabelClassName}
                                        >
                                            Name
                                        </label>

                                        <input
                                            id="name"
                                            name="name"
                                            type="text"
                                            defaultValue={auth.user.name}
                                            required
                                            autoComplete="name"
                                            placeholder="Full name"
                                            className={fashionFieldClassName}
                                        />

                                        <InputError
                                            className="mt-2"
                                            message={errors.name}
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="email"
                                            className={fashionLabelClassName}
                                        >
                                            Email address
                                        </label>

                                        <input
                                            id="email"
                                            name="email"
                                            type="email"
                                            defaultValue={auth.user.email}
                                            required
                                            autoComplete="username"
                                            placeholder="Email address"
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
                                            Phone number
                                        </label>

                                        <input
                                            id="phone"
                                            name="phone"
                                            type="tel"
                                            defaultValue={auth.user.phone ?? ''}
                                            autoComplete="tel"
                                            placeholder="09171234567"
                                            className={fashionFieldClassName}
                                        />

                                        <InputError
                                            className="mt-2"
                                            message={errors.phone}
                                        />
                                    </div>

                                    {mustVerifyEmail &&
                                        auth.user.email_verified_at ===
                                            null && (
                                            <div className="border-y border-neutral-300 py-5">
                                                <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                                    Email verification
                                                </p>

                                                <p className="mt-3 max-w-xl text-sm leading-7 text-neutral-600">
                                                    Your email address is
                                                    currently unverified.{' '}
                                                    <Link
                                                        href={send()}
                                                        as="button"
                                                        className="border-b border-neutral-950 text-neutral-950 transition-opacity hover:opacity-60"
                                                    >
                                                        Re-send the verification
                                                        email.
                                                    </Link>
                                                </p>

                                                {status ===
                                                    'verification-link-sent' && (
                                                    <p className="mt-3 text-sm leading-7 text-green-700">
                                                        A new verification link
                                                        has been sent to your
                                                        email address.
                                                    </p>
                                                )}
                                            </div>
                                        )}

                                    <div className="pt-1">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            data-test="update-profile-button"
                                            className="inline-flex min-h-11 items-center justify-center border border-neutral-950 bg-neutral-950 px-7 text-[9px] font-medium tracking-[0.14em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-40"
                                        >
                                            {processing
                                                ? 'Saving...'
                                                : 'Save changes'}
                                        </button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </section>

                    <aside className="border-t border-neutral-300 pt-8 xl:border-t-0 xl:border-l xl:pt-0 xl:pl-10">
                        <section>
                            <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                Account
                            </p>

                            <h2 className="mt-3 font-serif text-3xl leading-tight tracking-[-0.025em]">
                                Account details
                            </h2>

                            <dl className="mt-7 border-t border-neutral-300 text-sm">
                                <div className="border-b border-neutral-300 py-4">
                                    <dt className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                        Name
                                    </dt>

                                    <dd className="mt-2 text-neutral-800">
                                        {auth.user.name}
                                    </dd>
                                </div>

                                <div className="border-b border-neutral-300 py-4">
                                    <dt className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                        Email
                                    </dt>

                                    <dd className="mt-2 break-words text-neutral-800">
                                        {auth.user.email}
                                    </dd>
                                </div>

                                <div className="border-b border-neutral-300 py-4">
                                    <dt className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                        Phone
                                    </dt>

                                    <dd className="mt-2 text-neutral-800">
                                        {auth.user.phone || 'Not provided'}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section className="mt-12 border-t border-neutral-300 pt-8">
                            <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                Account removal
                            </p>

                            <h2 className="mt-3 font-serif text-3xl leading-tight tracking-[-0.025em]">
                                Delete account
                            </h2>

                            <p className="mt-4 text-sm leading-7 text-neutral-600">
                                Permanently remove your account and associated
                                account data. This action cannot be undone.
                            </p>

                            <div className="mt-7">
                                <DeleteUser />
                            </div>
                        </section>
                    </aside>
                </div>
            </FashionAccountLayout>
        );
    }

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Profile settings',
                    href: edit(),
                },
            ]}
        >
            <SettingsLayout>
                <Head title="Profile settings" />

                <h1 className="sr-only">Profile settings</h1>

                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Profile"
                        description="Update your name, email address, and phone number"
                    />

                    <Form
                        {...ProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>

                                    <Input
                                        id="name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.name}
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder="Full name"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>

                                    <Input
                                        id="email"
                                        type="email"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.email}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        placeholder="Email address"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.email}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Phone number</Label>

                                    <Input
                                        id="phone"
                                        type="tel"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.phone ?? ''}
                                        name="phone"
                                        autoComplete="tel"
                                        placeholder="09171234567"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.phone}
                                    />
                                </div>

                                {mustVerifyEmail &&
                                    auth.user.email_verified_at === null && (
                                        <div>
                                            <p className="-mt-4 text-sm text-muted-foreground">
                                                Your email address is
                                                unverified.{' '}
                                                <Link
                                                    href={send()}
                                                    as="button"
                                                    className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                                >
                                                    Click here to re-send the
                                                    verification email.
                                                </Link>
                                            </p>

                                            {status ===
                                                'verification-link-sent' && (
                                                <div className="mt-2 text-sm font-medium text-green-600">
                                                    A new verification link has
                                                    been sent to your email
                                                    address.
                                                </div>
                                            )}
                                        </div>
                                    )}

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-profile-button"
                                    >
                                        Save
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
