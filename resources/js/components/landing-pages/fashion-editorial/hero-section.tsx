import { Link } from '@inertiajs/react';
import type { CatalogProduct } from '@/types';

type HeroSectionProps = {
    product?: CatalogProduct;
};

export function HeroSection({ product }: HeroSectionProps) {
    return (
        <section className="relative isolate min-h-[640px] overflow-hidden bg-[#8b8177] sm:min-h-[720px] lg:min-h-[800px]">
            {product?.image_url ? (
                <img
                    src={product.image_url}
                    alt={product.image_alt ?? product.name}
                    loading="eager"
                    className="absolute inset-0 h-full w-full object-cover"
                />
            ) : (
                <div className="absolute inset-0 bg-gradient-to-br from-[#a99e92] via-[#796f66] to-[#514b45]" />
            )}

            <div className="absolute inset-0 bg-black/20" />

            <div className="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/10" />

            <div className="relative mx-auto flex min-h-[640px] max-w-[1600px] items-end justify-center px-5 pb-16 text-center sm:min-h-[720px] sm:px-8 sm:pb-20 lg:min-h-[800px] lg:px-14 lg:pb-24">
                <div className="max-w-3xl text-white">
                    <p className="text-[10px] font-medium tracking-[0.24em] uppercase">
                        The New Collection
                    </p>

                    <h1 className="mt-6 font-serif text-[clamp(3.5rem,7vw,6.5rem)] leading-[0.95] font-normal tracking-[-0.035em]">
                        Effortless elegance.
                    </h1>

                    <p className="mx-auto mt-7 max-w-xl text-sm leading-7 text-white/90 sm:text-base">
                        Refined silhouettes, considered details, and timeless
                        pieces created for modern dressing.
                    </p>

                    <Link
                        href="/shop?sort=newest"
                        className="mt-9 inline-flex min-h-12 items-center justify-center border border-white bg-white px-7 text-[10px] font-medium tracking-[0.16em] text-neutral-950 uppercase transition duration-300 hover:bg-transparent hover:text-white"
                    >
                        Shop new arrivals
                    </Link>
                </div>
            </div>
        </section>
    );
}
