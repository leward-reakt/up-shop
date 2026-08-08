import { Form, Head, Link, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
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

export default function Login({ status, canResetPassword }: Props) {
    const page = usePage();

    const sharedProps = page.props as unknown as SharedProps;

    const isFashionEditorial = sharedProps.store?.theme === 'fashion_editorial';

    if (isFashionEditorial) {
        return (
            <>
                <Head title="Log in" />

                <div className="space-y-8">
                    <div className="border-b border-neutral-300 pb-7">
                        <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                            Customer account
                        </p>

                        <h2 className="mt-3 font-serif text-3xl leading-tight font-normal tracking-[-0.025em] text-neutral-950">
                            Welcome back.
                        </h2>

                        <p className="mt-3 text-sm leading-7 text-neutral-600">
                            Sign in to view your orders, addresses, and account
                            details.
                        </p>
                    </div>

                    {status && (
                        <div className="border-y border-green-700/30 py-4 text-sm leading-7 text-green-700">
                            {status}
                        </div>
                    )}

                    <PasskeyVerify />

                    <Form
                        {...store.form()}
                        resetOnSuccess={['password']}
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
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="email"
                                        placeholder="email@example.com"
                                        className={`mt-2 ${fashionFieldClassName}`}
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.email}
                                    />
                                </div>

                                <div>
                                    <div className="flex items-center justify-between gap-4">
                                        <label
                                            htmlFor="password"
                                            className={fashionLabelClassName}
                                        >
                                            Password
                                        </label>

                                        {canResetPassword && (
                                            <Link
                                                href={request()}
                                                tabIndex={5}
                                                className="border-b border-neutral-400 pb-0.5 text-[9px] font-medium tracking-[0.12em] text-neutral-600 uppercase transition hover:border-neutral-950 hover:text-neutral-950"
                                            >
                                                Forgot password?
                                            </Link>
                                        )}
                                    </div>

                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        required
                                        tabIndex={2}
                                        autoComplete="current-password"
                                        placeholder="Password"
                                        className={`mt-2 ${fashionFieldClassName}`}
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.password}
                                    />
                                </div>

                                <label
                                    htmlFor="remember"
                                    className="flex cursor-pointer items-center gap-3 border-t border-neutral-300 pt-5"
                                >
                                    <input
                                        id="remember"
                                        name="remember"
                                        type="checkbox"
                                        value="1"
                                        tabIndex={3}
                                        className="size-4 rounded-none border-neutral-400 bg-transparent text-neutral-950 focus:ring-neutral-950"
                                    />

                                    <span className="text-xs text-neutral-700">
                                        Remember me
                                    </span>
                                </label>

                                <button
                                    type="submit"
                                    tabIndex={4}
                                    disabled={processing}
                                    data-test="login-button"
                                    className="inline-flex min-h-12 w-full items-center justify-center gap-2 border border-neutral-950 bg-neutral-950 px-6 text-[9px] font-medium tracking-[0.14em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    {processing && <Spinner />}

                                    {processing ? 'Signing in...' : 'Log in'}
                                </button>
                            </>
                        )}
                    </Form>

                    <div className="border-t border-neutral-300 pt-6 text-center">
                        <p className="text-sm text-neutral-600">
                            Don't have an account?{' '}
                            <Link
                                href={register()}
                                tabIndex={5}
                                className="border-b border-neutral-950 pb-0.5 text-neutral-950 transition-opacity hover:opacity-60"
                            >
                                Create an account
                            </Link>
                        </p>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Log in" />

            <PasskeyVerify />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="email@example.com"
                                />

                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">Password</Label>

                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-sm"
                                            tabIndex={5}
                                        >
                                            Forgot your password?
                                        </TextLink>
                                    )}
                                </div>

                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Password"
                                />

                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />

                                <Label htmlFor="remember">Remember me</Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-4 w-full"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Log in
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Don't have an account?{' '}
                            <TextLink href={register()} tabIndex={5}>
                                Sign up
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Log in to your account',
    description: 'Enter your email and password below to log in',
};
