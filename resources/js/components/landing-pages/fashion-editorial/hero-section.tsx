import { Link } from '@inertiajs/react';
import type { CatalogProduct } from '@/types';

type HeroSectionProps = {
    product?: CatalogProduct;
};

export function HeroSection({ product }: HeroSectionProps) {
    return (
        <section className="bg-[#8b8177]">
            <div className="relative w-full">
                <div className="relative overflow-hidden">
                    {product?.image_url ? (
                        <img
                            src={product.image_url}
                            alt={product.image_alt ?? product.name}
                            loading="eager"
                            className="block h-auto w-full"
                        />
                    ) : (
                        <div className="aspect-[4/3] bg-gradient-to-br from-[#a99e92] via-[#796f66] to-[#514b45] sm:aspect-[16/9]" />
                    )}

                    <div className="pointer-events-none absolute inset-0 bg-black/20" />
                    <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/10" />
                </div>

                <div className="relative z-10 px-5 py-12 text-center text-white sm:absolute sm:inset-0 sm:flex sm:items-end sm:justify-center sm:px-8 sm:pt-24 sm:pb-14 lg:px-14 lg:pb-24">
                    <div className="mx-auto max-w-3xl">
                        <p className="text-[10px] font-medium tracking-[0.24em] uppercase">
                            The New Collection
                        </p>

                        <h1 className="mt-5 font-serif text-[clamp(3rem,13vw,4.75rem)] leading-[0.95] font-normal tracking-[-0.035em] sm:mt-6 sm:text-[clamp(4rem,7vw,6.5rem)]">
                            Effortless elegance.
                        </h1>

                        <p className="mx-auto mt-6 max-w-xl text-sm leading-7 text-white/90 sm:mt-7 sm:text-base">
                            Refined silhouettes, considered details, and
                            timeless pieces created for modern dressing.
                        </p>

                        <Link
                            href="/shop?sort=newest"
                            className="mt-8 inline-flex min-h-12 items-center justify-center border border-white bg-white px-7 text-[10px] font-medium tracking-[0.16em] text-neutral-950 uppercase transition duration-300 hover:bg-transparent hover:text-white sm:mt-9"
                        >
                            Shop new arrivals
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    );
}
