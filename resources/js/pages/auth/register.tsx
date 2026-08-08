import { Form, Head, Link, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
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

export default function Register({ passwordRules }: Props) {
    const page = usePage();

    const sharedProps = page.props as unknown as SharedProps;

    const isFashionEditorial = sharedProps.store?.theme === 'fashion_editorial';

    if (isFashionEditorial) {
        return (
            <>
                <Head title="Register" />

                <div className="space-y-8">
                    <div className="border-b border-neutral-300 pb-7">
                        <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                            Customer account
                        </p>

                        <h2 className="mt-3 font-serif text-3xl leading-tight font-normal tracking-[-0.025em] text-neutral-950">
                            Create your account.
                        </h2>

                        <p className="mt-3 text-sm leading-7 text-neutral-600">
                            Create an account to manage your orders, delivery
                            addresses, and account details.
                        </p>
                    </div>

                    <Form
                        {...store.form()}
                        resetOnSuccess={['password', 'password_confirmation']}
                        disableWhileProcessing
                        className="space-y-7"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div>
                                    <label
                                        htmlFor="name"
                                        className={fashionLabelClassName}
                                    >
                                        Full name
                                    </label>

                                    <Input
                                        id="name"
                                        type="text"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="name"
                                        name="name"
                                        placeholder="Full name"
                                        className={`mt-2 ${fashionFieldClassName}`}
                                    />

                                    <InputError
                                        message={errors.name}
                                        className="mt-2"
                                    />
                                </div>

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
                                        required
                                        tabIndex={2}
                                        autoComplete="email"
                                        name="email"
                                        placeholder="email@example.com"
                                        className={`mt-2 ${fashionFieldClassName}`}
                                    />

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
                                        Password
                                    </label>

                                    <PasswordInput
                                        id="password"
                                        required
                                        tabIndex={3}
                                        autoComplete="new-password"
                                        name="password"
                                        placeholder="Password"
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
                                        Confirm password
                                    </label>

                                    <PasswordInput
                                        id="password_confirmation"
                                        required
                                        tabIndex={4}
                                        autoComplete="new-password"
                                        name="password_confirmation"
                                        placeholder="Confirm password"
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
                                        tabIndex={5}
                                        disabled={processing}
                                        data-test="register-user-button"
                                        className="inline-flex min-h-12 w-full items-center justify-center gap-2 border border-neutral-950 bg-neutral-950 px-6 text-[9px] font-medium tracking-[0.14em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        {processing && <Spinner />}

                                        {processing
                                            ? 'Creating account...'
                                            : 'Create account'}
                                    </button>
                                </div>
                            </>
                        )}
                    </Form>

                    <div className="border-t border-neutral-300 pt-6 text-center">
                        <p className="text-sm text-neutral-600">
                            Already have an account?{' '}
                            <Link
                                href={login()}
                                tabIndex={6}
                                className="border-b border-neutral-950 pb-0.5 text-neutral-950 transition-opacity hover:opacity-60"
                            >
                                Log in
                            </Link>
                        </p>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Register" />

            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>

                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Full name"
                                />

                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={2}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                />

                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>

                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={3}
                                    autoComplete="new-password"
                                    name="password"
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
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
                                    passwordrules={passwordRules}
                                />

                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={5}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                Create account
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink href={login()} tabIndex={6}>
                                Log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create an account',
    description: 'Enter your details below to create your account',
};
