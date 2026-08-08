import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

export default function StorefrontLayout({ children }: PropsWithChildren) {
    const page = usePage();

    const auth = page.props.auth as {
        user?: {
            name: string;
        } | null;
    };

    return (
        <div className="min-h-screen bg-neutral-50 text-neutral-950">
            <header className="border-b bg-white">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <Link href="/" className="text-xl font-semibold">
                        Up Shop
                    </Link>

                    <nav className="flex items-center gap-6 text-sm">
                        <Link href="/" className="hover:text-neutral-600">
                            Home
                        </Link>

                        <Link href="/shop" className="hover:text-neutral-600">
                            Shop
                        </Link>

                        {auth.user ? (
                            <Link
                                href="/dashboard"
                                className="hover:text-neutral-600"
                            >
                                Account
                            </Link>
                        ) : (
                            <Link
                                href="/login"
                                className="hover:text-neutral-600"
                            >
                                Log in
                            </Link>
                        )}
                    </nav>
                </div>
            </header>

            <main>{children}</main>

            <footer className="mt-16 border-t bg-white">
                <div className="mx-auto max-w-7xl px-4 py-8 text-sm text-neutral-500 sm:px-6 lg:px-8">
                    © {new Date().getFullYear()} Up Shop
                </div>
            </footer>
        </div>
    );
}
