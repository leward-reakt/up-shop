import { Link } from '@inertiajs/react';
import type { CatalogProduct } from '@/types';

type StorySectionProps = {
    product?: CatalogProduct;
};

export function StorySection({ product }: StorySectionProps) {
    return (
        <section className="bg-[#f1ece5]">
            <div className="mx-auto grid max-w-[1600px] lg:grid-cols-2">
                <div className="self-start overflow-hidden bg-[#d6cec5]">
                    {product?.image_url ? (
                        <img
                            src={product.image_url}
                            alt={product.image_alt ?? product.name}
                            loading="lazy"
                            className="block h-auto w-full"
                        />
                    ) : (
                        <div className="aspect-square bg-gradient-to-br from-[#ded5cb] via-[#c7baad] to-[#a89a8e]" />
                    )}
                </div>

                <div className="flex items-center px-6 py-20 sm:px-12 sm:py-24 lg:px-20">
                    <div className="max-w-lg">
                        <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                            Our approach
                        </p>

                        <h2 className="mt-5 font-serif text-4xl leading-[1.08] tracking-[-0.025em] sm:text-5xl lg:text-6xl">
                            Crafted with care.
                            <br />
                            Designed with purpose.
                        </h2>

                        <p className="mt-7 max-w-md text-sm leading-7 text-neutral-600 sm:text-base">
                            Thoughtful materials, refined proportions, and
                            carefully considered details come together in pieces
                            made to remain part of your wardrobe season after
                            season.
                        </p>

                        <Link
                            href="/about"
                            className="mt-9 inline-flex min-h-11 items-center border-b border-neutral-950 text-[10px] font-medium tracking-[0.15em] uppercase transition-opacity hover:opacity-60"
                        >
                            Discover our story
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    );
}
