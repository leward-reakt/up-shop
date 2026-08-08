import { Form, Head, Link, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';
import { email } from '@/routes/password';

type Props = {
    status?: string;
};

type SharedProps = {
    store?: {
        theme?: 'default' | 'fashion_editorial';
    };
};

const fashionFieldClassName =
    'h-auto w-full rounded-none border-neutral-300 bg-transparent px-4 py-3 text-sm text-neutral-950 shadow-none placeholder:text-neutral-400 focus-visible:border-neutral-950 focus-visible:ring-0';

export default function ForgotPassword({ status }: Props) {
    const page = usePage();

    const sharedProps = page.props as unknown as SharedProps;

    const isFashionEditorial = sharedProps.store?.theme === 'fashion_editorial';

    if (isFashionEditorial) {
        return (
            <>
                <Head title="Forgot password" />

                <div className="space-y-8">
                    <div className="border-b border-neutral-300 pb-7">
                        <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                            Account recovery
                        </p>

                        <h2 className="mt-3 font-serif text-3xl leading-tight font-normal tracking-[-0.025em] text-neutral-950">
                            Reset your password.
                        </h2>

                        <p className="mt-3 max-w-md text-sm leading-7 text-neutral-600">
                            Enter the email address associated with your account
                            and we will send you a secure password reset link.
                        </p>
                    </div>

                    {status && (
                        <div className="border-y border-green-700/30 py-4 text-sm leading-7 text-green-700">
                            {status}
                        </div>
                    )}

                    <Form {...email.form()} className="space-y-7">
                        {({ processing, errors }) => (
                            <>
                                <div>
                                    <label
                                        htmlFor="email"
                                        className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase"
                                    >
                                        Email address
                                    </label>

                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required
                                        autoComplete="email"
                                        autoFocus
                                        placeholder="email@example.com"
                                        className={`mt-2 ${fashionFieldClassName}`}
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.email}
                                    />
                                </div>

                                <div className="border-t border-neutral-300 pt-6">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        data-test="email-password-reset-link-button"
                                        className="inline-flex min-h-12 w-full items-center justify-center gap-2 border border-neutral-950 bg-neutral-950 px-6 text-[9px] font-medium tracking-[0.14em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        {processing && (
                                            <LoaderCircle
                                                className="size-4 animate-spin"
                                                aria-hidden="true"
                                            />
                                        )}

                                        {processing
                                            ? 'Sending reset link...'
                                            : 'Email password reset link'}
                                    </button>
                                </div>
                            </>
                        )}
                    </Form>

                    <div className="border-t border-neutral-300 pt-6 text-center">
                        <p className="text-sm text-neutral-600">
                            Remember your password?{' '}
                            <Link
                                href={login()}
                                className="border-b border-neutral-950 pb-0.5 text-neutral-950 transition-opacity hover:opacity-60"
                            >
                                Return to log in
                            </Link>
                        </p>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Forgot password" />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <div className="space-y-6">
                <Form {...email.form()}>
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autoComplete="off"
                                    autoFocus
                                    placeholder="email@example.com"
                                />

                                <InputError message={errors.email} />
                            </div>

                            <div className="my-6 flex items-center justify-start">
                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                    data-test="email-password-reset-link-button"
                                >
                                    {processing && (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    )}
                                    Email password reset link
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>Or, return to</span>

                    <TextLink href={login()}>log in</TextLink>
                </div>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Forgot password',
    description: 'Enter your email to receive a password reset link',
};
