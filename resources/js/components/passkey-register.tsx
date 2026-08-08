import { usePage } from '@inertiajs/react';
import { usePasskeyRegister } from '@laravel/passkeys/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    onSuccess: () => void;
};

type SharedProps = {
    store?: {
        theme?: 'default' | 'fashion_editorial';
    };
};

export default function PasskeyRegistration({ onSuccess }: Props) {
    const page = usePage();

    const sharedProps = page.props as unknown as SharedProps;

    const isFashionEditorial = sharedProps.store?.theme === 'fashion_editorial';

    const [name, setName] = useState(() => {
        const ua = typeof navigator === 'undefined' ? '' : navigator.userAgent;

        const browser = [
            { pattern: /Edg|Edge/, name: 'Edge' },
            { pattern: /OPR|Opera|OPiOS/, name: 'Opera' },
            { pattern: /Firefox|FxiOS/, name: 'Firefox' },
            { pattern: /Chrome|CriOS/, name: 'Chrome' },
            { pattern: /Safari/, name: 'Safari' },
        ].find(({ pattern }) => pattern.test(ua))?.name;

        const os = [
            { pattern: /iPhone/, name: 'iPhone' },
            { pattern: /iPad|Macintosh(?=.*Mobile)/, name: 'iPad' },
            { pattern: /Android/, name: 'Android' },
            { pattern: /Mac/, name: 'Mac' },
            { pattern: /Windows/, name: 'Windows' },
        ].find(({ pattern }) => pattern.test(ua))?.name;

        return [browser, os].filter(Boolean).join(' on ') || '';
    });

    const [showForm, setShowForm] = useState(false);

    const { register, isLoading, error, isSupported } = usePasskeyRegister({
        onSuccess: () => {
            setName('');
            setShowForm(false);
            onSuccess();
        },
    });

    const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const passkeyName = name.trim();

        if (!passkeyName) {
            return;
        }

        await register(passkeyName);
    };

    const handleCancel = () => {
        setShowForm(false);
        setName('');
    };

    if (!isSupported) {
        if (isFashionEditorial) {
            return (
                <div className="border-y border-neutral-300 py-5">
                    <p className="text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                        Browser support
                    </p>

                    <p className="mt-2 text-sm leading-7 text-neutral-600">
                        Passkeys are not supported in this browser.
                    </p>
                </div>
            );
        }

        return (
            <div className="text-sm text-muted-foreground">
                Passkeys are not supported in this browser.
            </div>
        );
    }

    if (!showForm) {
        if (isFashionEditorial) {
            return (
                <button
                    type="button"
                    onClick={() => setShowForm(true)}
                    className="inline-flex min-h-11 items-center justify-center border border-neutral-950 bg-neutral-950 px-6 text-[9px] font-medium tracking-[0.14em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950"
                >
                    Add passkey
                </button>
            );
        }

        return (
            <Button
                type="button"
                variant="outline"
                onClick={() => setShowForm(true)}
            >
                Add passkey
            </Button>
        );
    }

    if (isFashionEditorial) {
        return (
            <form
                onSubmit={handleSubmit}
                className="border-y border-neutral-300 py-7"
            >
                <div>
                    <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                        New passkey
                    </p>

                    <h3 className="mt-3 font-serif text-2xl leading-tight tracking-[-0.02em] text-neutral-950">
                        Name this passkey
                    </h3>

                    <p className="mt-3 max-w-xl text-sm leading-7 text-neutral-600">
                        Give this passkey a recognizable name so you can
                        identify the device or authenticator later.
                    </p>
                </div>

                <div className="mt-7">
                    <label
                        htmlFor="passkey-name"
                        className="mb-2 block text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase"
                    >
                        Passkey name
                    </label>

                    <Input
                        id="passkey-name"
                        type="text"
                        value={name}
                        onChange={(event) => setName(event.target.value)}
                        placeholder="e.g., MacBook Pro, iPhone"
                        className="h-auto w-full rounded-none border-neutral-300 bg-transparent px-4 py-3 text-sm text-neutral-950 shadow-none placeholder:text-neutral-400 focus-visible:border-neutral-950 focus-visible:ring-0"
                        autoComplete="off"
                        autoFocus
                    />

                    <p className="mt-2 text-xs leading-6 text-neutral-500">
                        A name helps you identify this passkey later.
                    </p>

                    {error && <InputError className="mt-2" message={error} />}
                </div>

                <div className="mt-7 flex flex-wrap gap-3">
                    <button
                        type="submit"
                        disabled={isLoading || !name.trim()}
                        className="inline-flex min-h-11 items-center justify-center border border-neutral-950 bg-neutral-950 px-6 text-[9px] font-medium tracking-[0.14em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        {isLoading ? 'Registering...' : 'Register passkey'}
                    </button>

                    <button
                        type="button"
                        onClick={handleCancel}
                        disabled={isLoading}
                        className="inline-flex min-h-11 items-center justify-center border border-neutral-300 px-6 text-[9px] font-medium tracking-[0.14em] text-neutral-800 uppercase transition hover:border-neutral-950 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        );
    }

    return (
        <form
            onSubmit={handleSubmit}
            className="space-y-4 rounded-lg border border-border bg-muted/50 p-4"
        >
            <div className="grid gap-2">
                <Label htmlFor="passkey-name">Passkey name</Label>

                <Input
                    id="passkey-name"
                    type="text"
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                    placeholder="e.g., MacBook Pro, iPhone"
                    className="mt-1 block w-full border-foreground/20"
                    autoFocus
                />

                <p className="text-xs text-muted-foreground">
                    A name helps you identify this passkey later.
                </p>
            </div>

            {error && <InputError message={error} />}

            <div className="flex gap-2">
                <Button type="submit" disabled={isLoading || !name.trim()}>
                    {isLoading ? 'Registering...' : 'Register passkey'}
                </Button>

                <Button
                    type="button"
                    variant="ghost"
                    onClick={handleCancel}
                    disabled={isLoading}
                >
                    Cancel
                </Button>
            </div>
        </form>
    );
}
