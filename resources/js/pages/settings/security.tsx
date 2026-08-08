import { Form, Head } from '@inertiajs/react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import type { Props as ManagePasskeysProps } from '@/components/manage-passkeys';
import ManagePasskeys from '@/components/manage-passkeys';
import type { Props as ManageTwoFactorProps } from '@/components/manage-two-factor';
import ManageTwoFactor from '@/components/manage-two-factor';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import FashionAccountLayout, {
    useFashionAccountTheme,
} from '@/layouts/fashion-account-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/security';

type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const fashionPasswordFieldClassName =
    'block h-auto w-full rounded-none border-neutral-300 bg-transparent px-4 py-3 text-sm shadow-none outline-none transition placeholder:text-neutral-400 focus-visible:border-neutral-950 focus-visible:ring-0';

const fashionLabelClassName =
    'mb-2 block text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase';

export default function Security(props: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);

    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const isFashionEditorial = useFashionAccountTheme();

    const twoFactorStatus = !props.canManageTwoFactor
        ? 'Unavailable'
        : props.twoFactorEnabled
          ? 'Enabled'
          : 'Not enabled';

    const passkeyCount = props.passkeys?.length ?? 0;

    if (isFashionEditorial) {
        return (
            <FashionAccountLayout
                active="security"
                title="Your security."
                description="Manage your password and additional authentication methods without leaving your storefront account."
            >
                <Head title="Security settings" />

                <div className="grid gap-14 xl:grid-cols-[minmax(0,1fr)_360px] xl:gap-20">
                    <div className="space-y-14">
                        <section>
                            <div>
                                <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                    Password
                                </p>

                                <h2 className="mt-3 font-serif text-3xl leading-tight tracking-[-0.025em]">
                                    Update password
                                </h2>

                                <p className="mt-3 max-w-xl text-sm leading-7 text-neutral-600">
                                    Use a strong, unique password that you do
                                    not reuse on other accounts.
                                </p>
                            </div>

                            <Form
                                {...SecurityController.update.form()}
                                options={{
                                    preserveScroll: true,
                                }}
                                resetOnError={[
                                    'password',
                                    'password_confirmation',
                                    'current_password',
                                ]}
                                resetOnSuccess
                                onError={(errors) => {
                                    if (errors.password) {
                                        passwordInput.current?.focus();
                                    }

                                    if (errors.current_password) {
                                        currentPasswordInput.current?.focus();
                                    }
                                }}
                                className="mt-8 space-y-7 border-t border-neutral-300 pt-7"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div>
                                            <label
                                                htmlFor="current_password"
                                                className={
                                                    fashionLabelClassName
                                                }
                                            >
                                                Current password
                                            </label>

                                            <PasswordInput
                                                id="current_password"
                                                ref={currentPasswordInput}
                                                name="current_password"
                                                className={
                                                    fashionPasswordFieldClassName
                                                }
                                                autoComplete="current-password"
                                                placeholder="Current password"
                                            />

                                            <InputError
                                                className="mt-2"
                                                message={
                                                    errors.current_password
                                                }
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="password"
                                                className={
                                                    fashionLabelClassName
                                                }
                                            >
                                                New password
                                            </label>

                                            <PasswordInput
                                                id="password"
                                                ref={passwordInput}
                                                name="password"
                                                className={
                                                    fashionPasswordFieldClassName
                                                }
                                                autoComplete="new-password"
                                                placeholder="New password"
                                                passwordrules={
                                                    props.passwordRules
                                                }
                                            />

                                            <InputError
                                                className="mt-2"
                                                message={errors.password}
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="password_confirmation"
                                                className={
                                                    fashionLabelClassName
                                                }
                                            >
                                                Confirm password
                                            </label>

                                            <PasswordInput
                                                id="password_confirmation"
                                                name="password_confirmation"
                                                className={
                                                    fashionPasswordFieldClassName
                                                }
                                                autoComplete="new-password"
                                                placeholder="Confirm password"
                                                passwordrules={
                                                    props.passwordRules
                                                }
                                            />

                                            <InputError
                                                className="mt-2"
                                                message={
                                                    errors.password_confirmation
                                                }
                                            />
                                        </div>

                                        <div className="pt-1">
                                            <button
                                                type="submit"
                                                disabled={processing}
                                                data-test="update-password-button"
                                                className="inline-flex min-h-11 items-center justify-center border border-neutral-950 bg-neutral-950 px-7 text-[9px] font-medium tracking-[0.14em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                {processing
                                                    ? 'Updating...'
                                                    : 'Update password'}
                                            </button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </section>

                        {props.canManageTwoFactor && (
                            <section className="border-t border-neutral-300 pt-10">
                                <ManageTwoFactor
                                    canManageTwoFactor={
                                        props.canManageTwoFactor
                                    }
                                    requiresConfirmation={
                                        props.requiresConfirmation
                                    }
                                    twoFactorEnabled={props.twoFactorEnabled}
                                />
                            </section>
                        )}

                        {props.canManagePasskeys && (
                            <section className="border-t border-neutral-300 pt-10">
                                <ManagePasskeys
                                    canManagePasskeys={props.canManagePasskeys}
                                    passkeys={props.passkeys}
                                />
                            </section>
                        )}
                    </div>

                    <aside className="border-t border-neutral-300 pt-8 xl:border-t-0 xl:border-l xl:pt-0 xl:pl-10">
                        <section>
                            <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                Account protection
                            </p>

                            <h2 className="mt-3 font-serif text-3xl leading-tight tracking-[-0.025em]">
                                Security overview
                            </h2>

                            <p className="mt-4 text-sm leading-7 text-neutral-600">
                                Review the authentication methods currently
                                available for your account.
                            </p>

                            <dl className="mt-7 border-t border-neutral-300 text-sm">
                                <div className="flex items-start justify-between gap-6 border-b border-neutral-300 py-5">
                                    <dt>
                                        <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                            Password
                                        </p>

                                        <p className="mt-2 text-neutral-600">
                                            Primary sign-in credential
                                        </p>
                                    </dt>

                                    <dd className="text-right text-neutral-800">
                                        Active
                                    </dd>
                                </div>

                                <div className="flex items-start justify-between gap-6 border-b border-neutral-300 py-5">
                                    <dt>
                                        <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                            Two-factor authentication
                                        </p>

                                        <p className="mt-2 text-neutral-600">
                                            Additional login verification
                                        </p>
                                    </dt>

                                    <dd className="text-right text-neutral-800">
                                        {twoFactorStatus}
                                    </dd>
                                </div>

                                <div className="flex items-start justify-between gap-6 border-b border-neutral-300 py-5">
                                    <dt>
                                        <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                            Passkeys
                                        </p>

                                        <p className="mt-2 text-neutral-600">
                                            Passwordless authentication
                                        </p>
                                    </dt>

                                    <dd className="text-right text-neutral-800">
                                        {!props.canManagePasskeys
                                            ? 'Unavailable'
                                            : passkeyCount === 0
                                              ? 'None'
                                              : `${passkeyCount} registered`}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section className="mt-12 border-t border-neutral-300 pt-8">
                            <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                Security guidance
                            </p>

                            <div className="mt-5 space-y-4 text-sm leading-7 text-neutral-600">
                                <p>
                                    Use a unique password for this account and
                                    avoid sharing it with anyone.
                                </p>

                                {props.canManageTwoFactor && (
                                    <p>
                                        Two-factor authentication adds another
                                        verification step when signing in.
                                    </p>
                                )}

                                {props.canManagePasskeys && (
                                    <p>
                                        Passkeys can provide a convenient
                                        passwordless sign-in method on supported
                                        devices.
                                    </p>
                                )}
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
                    title: 'Security settings',
                    href: edit(),
                },
            ]}
        >
            <SettingsLayout>
                <Head title="Security settings" />

                <h1 className="sr-only">Security settings</h1>

                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Update password"
                        description="Ensure your account is using a long, random password to stay secure"
                    />

                    <Form
                        {...SecurityController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        resetOnError={[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]}
                        resetOnSuccess
                        onError={(errors) => {
                            if (errors.password) {
                                passwordInput.current?.focus();
                            }

                            if (errors.current_password) {
                                currentPasswordInput.current?.focus();
                            }
                        }}
                        className="space-y-6"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="current_password">
                                        Current password
                                    </Label>

                                    <PasswordInput
                                        id="current_password"
                                        ref={currentPasswordInput}
                                        name="current_password"
                                        className="mt-1 block w-full"
                                        autoComplete="current-password"
                                        placeholder="Current password"
                                    />

                                    <InputError
                                        message={errors.current_password}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password">
                                        New password
                                    </Label>

                                    <PasswordInput
                                        id="password"
                                        ref={passwordInput}
                                        name="password"
                                        className="mt-1 block w-full"
                                        autoComplete="new-password"
                                        placeholder="New password"
                                        passwordrules={props.passwordRules}
                                    />

                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirm password
                                    </Label>

                                    <PasswordInput
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        className="mt-1 block w-full"
                                        autoComplete="new-password"
                                        placeholder="Confirm password"
                                        passwordrules={props.passwordRules}
                                    />

                                    <InputError
                                        message={errors.password_confirmation}
                                    />
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-password-button"
                                    >
                                        Save
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                <ManageTwoFactor
                    canManageTwoFactor={props.canManageTwoFactor}
                    requiresConfirmation={props.requiresConfirmation}
                    twoFactorEnabled={props.twoFactorEnabled}
                />

                <ManagePasskeys
                    canManagePasskeys={props.canManagePasskeys}
                    passkeys={props.passkeys}
                />
            </SettingsLayout>
        </AppLayout>
    );
}
