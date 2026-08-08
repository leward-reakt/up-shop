import { router, usePage } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import Heading from '@/components/heading';
import PasskeyItem from '@/components/passkey-item';
import PasskeyRegistration from '@/components/passkey-register';
import type { Passkey } from '@/types/auth';

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
};

type SharedProps = {
    store?: {
        theme?: 'default' | 'fashion_editorial';
    };
};

function DefaultEmptyState() {
    return (
        <div className="p-8 text-center">
            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                <KeyRound className="h-7 w-7 text-muted-foreground" />
            </div>

            <p className="font-medium">No passkeys yet</p>

            <p className="mt-1 text-sm text-muted-foreground">
                Add a passkey to sign in without a password
            </p>
        </div>
    );
}

function FashionEmptyState() {
    return (
        <div className="border-b border-neutral-300 py-10">
            <div className="flex items-start gap-5">
                <div className="flex size-10 shrink-0 items-center justify-center border border-neutral-300">
                    <KeyRound
                        className="size-4 text-neutral-600"
                        strokeWidth={1.5}
                        aria-hidden="true"
                    />
                </div>

                <div>
                    <p className="font-serif text-xl tracking-[-0.02em]">
                        No passkeys yet.
                    </p>

                    <p className="mt-2 max-w-lg text-sm leading-7 text-neutral-600">
                        Register a passkey to use a supported device, security
                        key, or biometric authenticator for passwordless
                        sign-in.
                    </p>
                </div>
            </div>
        </div>
    );
}

export default function ManagePasskeys(props: Props) {
    const page = usePage();

    const sharedProps = page.props as unknown as SharedProps;

    const isFashionEditorial = sharedProps.store?.theme === 'fashion_editorial';

    const passkeys = props.passkeys ?? [];

    const handleDelete = (id: number, onError: () => void) => {
        router.delete(destroy.url(id), {
            preserveScroll: true,
            onError,
        });
    };

    const handleRegisterSuccess = () => {
        router.reload();
    };

    if (!(props.canManagePasskeys ?? false)) {
        return null;
    }

    if (isFashionEditorial) {
        return (
            <div>
                <div className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                            Passkeys
                        </p>

                        <h2 className="mt-3 font-serif text-3xl leading-tight tracking-[-0.025em]">
                            Passwordless sign-in
                        </h2>
                    </div>

                    <div>
                        <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                            Registered
                        </p>

                        <p className="mt-2 text-sm text-neutral-800">
                            {passkeys.length}{' '}
                            {passkeys.length === 1 ? 'passkey' : 'passkeys'}
                        </p>
                    </div>
                </div>

                <p className="mt-4 max-w-2xl text-sm leading-7 text-neutral-600">
                    Passkeys provide a secure way to sign in using a supported
                    device, security key, or biometric authenticator instead of
                    entering your password.
                </p>

                <div className="mt-7 border-t border-neutral-300">
                    {passkeys.length > 0 ? (
                        <div className="border-b border-neutral-300">
                            {passkeys.map((passkey) => (
                                <PasskeyItem
                                    key={passkey.id}
                                    passkey={passkey}
                                    onDelete={handleDelete}
                                />
                            ))}
                        </div>
                    ) : (
                        <FashionEmptyState />
                    )}
                </div>

                <div className="mt-7">
                    <p className="mb-4 text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                        Add authentication method
                    </p>

                    <PasskeyRegistration onSuccess={handleRegisterSuccess} />
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <Heading
                variant="small"
                title="Passkeys"
                description="Manage your passkeys for passwordless sign-in"
            />

            <div className="overflow-hidden rounded-lg border border-border">
                {passkeys.length > 0 ? (
                    passkeys.map((passkey) => (
                        <PasskeyItem
                            key={passkey.id}
                            passkey={passkey}
                            onDelete={handleDelete}
                        />
                    ))
                ) : (
                    <DefaultEmptyState />
                )}
            </div>

            <PasskeyRegistration onSuccess={handleRegisterSuccess} />
        </div>
    );
}
