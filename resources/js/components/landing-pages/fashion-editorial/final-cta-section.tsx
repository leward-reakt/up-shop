import { Link } from '@inertiajs/react';

export function FinalCtaSection() {
    return (
        <section className="border-t border-neutral-200 bg-[#eee8e1] px-5 py-20 sm:px-8 sm:py-24 lg:px-14 lg:py-28">
            <div className="mx-auto max-w-3xl text-center">
                <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                    The complete wardrobe
                </p>

                <h2 className="mt-5 font-serif text-4xl leading-tight tracking-[-0.025em] sm:text-5xl lg:text-6xl">
                    Discover the collection.
                </h2>

                <p className="mx-auto mt-5 max-w-xl text-sm leading-7 text-neutral-600">
                    Modern essentials and refined statement pieces designed to
                    create an elegant, considered wardrobe.
                </p>

                <Link
                    href="/shop"
                    className="mt-9 inline-flex min-h-12 items-center justify-center border border-neutral-950 px-7 text-[10px] font-medium tracking-[0.16em] uppercase transition duration-300 hover:bg-neutral-950 hover:text-white"
                >
                    Shop all
                </Link>
            </div>
        </section>
    );
}
