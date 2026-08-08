import { Form, Head, Link, usePage } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

type Props = {
    status?: string;
};

type SharedProps = {
    store?: {
        theme?: 'default' | 'fashion_editorial';
    };
};

export default function VerifyEmail({ status }: Props) {
    const page = usePage();

    const sharedProps = page.props as unknown as SharedProps;

    const isFashionEditorial = sharedProps.store?.theme === 'fashion_editorial';

    const verificationLinkSent = status === 'verification-link-sent';

    if (isFashionEditorial) {
        return (
            <>
                <Head title="Email verification" />

                <div className="space-y-8">
                    <div className="border-b border-neutral-300 pb-7">
                        <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                            Account verification
                        </p>

                        <h2 className="mt-3 font-serif text-3xl leading-tight font-normal tracking-[-0.025em] text-neutral-950">
                            Verify your email.
                        </h2>

                        <p className="mt-3 max-w-md text-sm leading-7 text-neutral-600">
                            We sent a verification link to the email address
                            associated with your account. Open that email and
                            follow the link to finish setting up your account.
                        </p>
                    </div>

                    {verificationLinkSent && (
                        <div
                            className="border-y border-green-700/30 py-4 text-sm leading-7 text-green-700"
                            role="status"
                        >
                            A new verification link has been sent to the email
                            address you provided during registration.
                        </div>
                    )}

                    <div className="space-y-5">
                        <div>
                            <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                Didn't receive the email?
                            </p>

                            <p className="mt-2 text-sm leading-7 text-neutral-600">
                                Check your spam folder first. You can request
                                another verification email if needed.
                            </p>
                        </div>

                        <Form
                            {...send.form()}
                            className="border-t border-neutral-300 pt-6"
                        >
                            {({ processing }) => (
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex min-h-12 w-full items-center justify-center gap-2 border border-neutral-950 bg-neutral-950 px-6 text-[9px] font-medium tracking-[0.14em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    {processing && <Spinner />}

                                    {processing
                                        ? 'Sending...'
                                        : 'Resend verification email'}
                                </button>
                            )}
                        </Form>
                    </div>

                    <div className="border-t border-neutral-300 pt-6 text-center">
                        <p className="text-sm text-neutral-600">
                            Want to use a different account?{' '}
                            <Link
                                href={logout()}
                                className="border-b border-neutral-950 pb-0.5 text-neutral-950 transition-opacity hover:opacity-60"
                            >
                                Log out
                            </Link>
                        </p>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Email verification" />

            {verificationLinkSent && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    A new verification link has been sent to the email address
                    you provided during registration.
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button
                            type="submit"
                            disabled={processing}
                            variant="secondary"
                        >
                            {processing && <Spinner />}
                            Resend verification email
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            Log out
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Email verification',
    description:
        'Please verify your email address by clicking on the link we just emailed to you.',
};
