import { Link } from '@inertiajs/react';
import type { CatalogProduct } from '@/types';

type HeroSectionProps = {
    product?: CatalogProduct;
};

export function HeroSection({ product }: HeroSectionProps) {
    return (
        <section className="relative isolate overflow-hidden bg-[#8b8177]">
            {product?.image_url ? (
                <img
                    src={product.image_url}
                    alt={product.image_alt ?? product.name}
                    loading="eager"
                    className="block h-auto w-full"
                />
            ) : (
                <div className="min-h-[520px] bg-gradient-to-br from-[#a99e92] via-[#796f66] to-[#514b45] sm:min-h-[640px] lg:min-h-[720px]" />
            )}

            <div className="absolute inset-0 bg-black/20" />

            <div className="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/10" />

            <div className="absolute inset-0">
                <div className="mx-auto flex h-full max-w-[1600px] items-end justify-center px-5 pb-8 text-center sm:px-8 sm:pb-14 lg:px-14 lg:pb-20">
                    <div className="max-w-3xl text-white">
                        <p className="text-[9px] font-medium tracking-[0.24em] uppercase sm:text-[10px]">
                            The New Collection
                        </p>

                        <h1 className="mt-4 font-serif text-[clamp(2.5rem,7vw,6.5rem)] leading-[0.95] font-normal tracking-[-0.035em] sm:mt-6">
                            Effortless elegance.
                        </h1>

                        <p className="mx-auto mt-4 max-w-xl text-xs leading-5 text-white/90 sm:mt-7 sm:text-base sm:leading-7">
                            Refined silhouettes, considered details, and
                            timeless pieces created for modern dressing.
                        </p>

                        <Link
                            href="/shop?sort=newest"
                            className="mt-5 inline-flex min-h-10 items-center justify-center border border-white bg-white px-5 text-[9px] font-medium tracking-[0.16em] text-neutral-950 uppercase transition duration-300 hover:bg-transparent hover:text-white sm:mt-9 sm:min-h-12 sm:px-7 sm:text-[10px]"
                        >
                            Shop new arrivals
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    );
}
