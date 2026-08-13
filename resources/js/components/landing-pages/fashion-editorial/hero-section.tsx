import { Link } from '@inertiajs/react';
import type { CatalogProduct } from '@/types';

type HeroSectionProps = {
    imageUrl?: string | null;
    product?: CatalogProduct;
};

export function HeroSection({
    imageUrl,
    product,
}: HeroSectionProps) {
    const resolvedImageUrl = imageUrl ?? product?.image_url ?? null;

    const resolvedImageAlt = imageUrl
        ? 'Up Shop editorial collection'
        : (product?.image_alt ?? product?.name ?? 'Up Shop collection');

    return (
        <section className="relative isolate overflow-hidden bg-[#6f675f]">
            <div className="relative aspect-video w-full bg-[#8b8177]">
                {resolvedImageUrl ? (
                    <img
                        src={resolvedImageUrl}
                        alt={resolvedImageAlt}
                        loading="eager"
                        className="absolute inset-0 h-full w-full object-contain object-center"
                    />
                ) : (
                    <div className="absolute inset-0 bg-gradient-to-br from-[#a99e92] via-[#796f66] to-[#514b45]" />
                )}

                <div className="absolute inset-0 hidden bg-black/20 lg:block" />
                <div className="absolute inset-0 hidden bg-gradient-to-t from-black/50 via-transparent to-black/10 lg:block" />
            </div>

            <div className="relative mx-auto flex max-w-[1600px] items-center justify-center px-5 py-12 text-center sm:px-8 sm:py-16 lg:absolute lg:inset-0 lg:items-end lg:px-14 lg:pb-24">
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
