import { Form, Head, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';

type Props = {
    token: string;
    email: string;
    passwordRules: string;
};

type SharedProps = {
    store?: {
        theme?: 'default' | 'fashion_editorial';
    };
};

const fashionFieldClassName =
    'h-auto w-full rounded-none border-neutral-300 bg-transparent px-4 py-3 text-sm text-neutral-950 shadow-none placeholder:text-neutral-400 focus-visible:border-neutral-950 focus-visible:ring-0';

const fashionLabelClassName =
    'text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase';

export default function ResetPassword({ token, email, passwordRules }: Props) {
    const page = usePage();

    const sharedProps = page.props as unknown as SharedProps;

    const isFashionEditorial = sharedProps.store?.theme === 'fashion_editorial';

    if (isFashionEditorial) {
        return (
            <>
                <Head title="Reset password" />

                <div className="space-y-8">
                    <div className="border-b border-neutral-300 pb-7">
                        <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                            Account recovery
                        </p>

                        <h2 className="mt-3 font-serif text-3xl leading-tight font-normal tracking-[-0.025em] text-neutral-950">
                            Choose a new password.
                        </h2>

                        <p className="mt-3 max-w-md text-sm leading-7 text-neutral-600">
                            Set a new password for your account. Use a strong,
                            unique password that you do not use elsewhere.
                        </p>
                    </div>

                    <Form
                        {...update.form()}
                        transform={(data) => ({
                            ...data,
                            token,
                            email,
                        })}
                        resetOnSuccess={['password', 'password_confirmation']}
                        className="space-y-7"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div>
                                    <label
                                        htmlFor="email"
                                        className={fashionLabelClassName}
                                    >
                                        Email address
                                    </label>

                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        autoComplete="email"
                                        value={email}
                                        readOnly
                                        className={`mt-2 cursor-default bg-neutral-100/60 text-neutral-600 ${fashionFieldClassName}`}
                                    />

                                    <p className="mt-2 text-xs leading-6 text-neutral-500">
                                        This password reset request is linked to
                                        this email address.
                                    </p>

                                    <InputError
                                        message={errors.email}
                                        className="mt-2"
                                    />
                                </div>

                                <div>
                                    <label
                                        htmlFor="password"
                                        className={fashionLabelClassName}
                                    >
                                        New password
                                    </label>

                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        required
                                        autoComplete="new-password"
                                        autoFocus
                                        placeholder="New password"
                                        passwordrules={passwordRules}
                                        className={`mt-2 ${fashionFieldClassName}`}
                                    />

                                    <InputError
                                        message={errors.password}
                                        className="mt-2"
                                    />
                                </div>

                                <div>
                                    <label
                                        htmlFor="password_confirmation"
                                        className={fashionLabelClassName}
                                    >
                                        Confirm new password
                                    </label>

                                    <PasswordInput
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        required
                                        autoComplete="new-password"
                                        placeholder="Confirm new password"
                                        passwordrules={passwordRules}
                                        className={`mt-2 ${fashionFieldClassName}`}
                                    />

                                    <InputError
                                        message={errors.password_confirmation}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="border-t border-neutral-300 pt-6">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        data-test="reset-password-button"
                                        className="inline-flex min-h-12 w-full items-center justify-center gap-2 border border-neutral-950 bg-neutral-950 px-6 text-[9px] font-medium tracking-[0.14em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        {processing && <Spinner />}

                                        {processing
                                            ? 'Resetting password...'
                                            : 'Reset password'}
                                    </button>
                                </div>
                            </>
                        )}
                    </Form>

                    <div className="border-t border-neutral-300 pt-6">
                        <p className="text-center text-xs leading-6 text-neutral-500">
                            After your password is reset, use your new password
                            the next time you sign in.
                        </p>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Reset password" />

            <Form
                {...update.form()}
                transform={(data) => ({
                    ...data,
                    token,
                    email,
                })}
                resetOnSuccess={['password', 'password_confirmation']}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>

                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="email"
                                value={email}
                                className="mt-1 block w-full"
                                readOnly
                            />

                            <InputError
                                message={errors.email}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>

                            <PasswordInput
                                id="password"
                                name="password"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                autoFocus
                                placeholder="Password"
                                passwordrules={passwordRules}
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
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                placeholder="Confirm password"
                                passwordrules={passwordRules}
                            />

                            <InputError
                                message={errors.password_confirmation}
                                className="mt-2"
                            />
                        </div>

                        <Button
                            type="submit"
                            className="mt-4 w-full"
                            disabled={processing}
                            data-test="reset-password-button"
                        >
                            {processing && <Spinner />}
                            Reset password
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}

ResetPassword.layout = {
    title: 'Reset password',
    description: 'Please enter your new password below',
};
