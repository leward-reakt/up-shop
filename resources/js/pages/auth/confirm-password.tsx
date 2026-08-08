import { Form, Head, usePage } from '@inertiajs/react';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';

type SharedProps = {
    store?: {
        theme?: 'default' | 'fashion_editorial';
    };
};

const fashionPasswordFieldClassName =
    'h-auto w-full rounded-none border-neutral-300 bg-transparent px-4 py-3 text-sm text-neutral-950 shadow-none placeholder:text-neutral-400 focus-visible:border-neutral-950 focus-visible:ring-0';

export default function ConfirmPassword() {
    const page = usePage();

    const sharedProps = page.props as unknown as SharedProps;

    const isFashionEditorial = sharedProps.store?.theme === 'fashion_editorial';

    if (isFashionEditorial) {
        return (
            <>
                <Head title="Confirm password" />

                <div className="space-y-8">
                    <div className="border-b border-neutral-300 pb-7">
                        <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                            Account security
                        </p>

                        <h2 className="mt-3 font-serif text-3xl leading-tight font-normal tracking-[-0.025em] text-neutral-950">
                            Confirm your identity.
                        </h2>

                        <p className="mt-3 max-w-md text-sm leading-7 text-neutral-600">
                            This is a secure area of your account. Confirm your
                            identity before continuing.
                        </p>
                    </div>

                    <PasskeyVerify
                        routes={{
                            options: confirmOptions(),
                            submit: confirmStore(),
                        }}
                        label="Confirm with passkey"
                        loadingLabel="Confirming..."
                        separator="Or confirm with password"
                    />

                    <Form
                        {...store.form()}
                        resetOnSuccess={['password']}
                        className="space-y-7"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div>
                                    <label
                                        htmlFor="password"
                                        className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase"
                                    >
                                        Password
                                    </label>

                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        required
                                        placeholder="Password"
                                        autoComplete="current-password"
                                        autoFocus
                                        className={`mt-2 ${fashionPasswordFieldClassName}`}
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.password}
                                    />
                                </div>

                                <div className="border-t border-neutral-300 pt-6">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        data-test="confirm-password-button"
                                        className="inline-flex min-h-12 w-full items-center justify-center gap-2 border border-neutral-950 bg-neutral-950 px-6 text-[9px] font-medium tracking-[0.14em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        {processing && <Spinner />}

                                        {processing
                                            ? 'Confirming...'
                                            : 'Confirm password'}
                                    </button>
                                </div>
                            </>
                        )}
                    </Form>

                    <div className="border-t border-neutral-300 pt-6">
                        <p className="text-center text-xs leading-6 text-neutral-500">
                            This additional verification helps protect sensitive
                            account actions.
                        </p>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Confirm password" />

            <PasskeyVerify
                routes={{
                    options: confirmOptions(),
                    submit: confirmStore(),
                }}
                label="Confirm with passkey"
                loadingLabel="Confirming..."
                separator="Or confirm with password"
            />

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>

                            <PasswordInput
                                id="password"
                                name="password"
                                placeholder="Password"
                                autoComplete="current-password"
                                autoFocus
                            />

                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center">
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                                data-test="confirm-password-button"
                            >
                                {processing && <Spinner />}
                                Confirm password
                            </Button>
                        </div>
                    </div>
                )}
            </Form>
        </>
    );
}

ConfirmPassword.layout = {
    title: 'Confirm password',
    description:
        'This is a secure area of the application. Please confirm your password before continuing.',
};
