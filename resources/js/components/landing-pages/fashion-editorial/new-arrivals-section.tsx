import { Link } from '@inertiajs/react';
import { ElegantProductCard } from '@/components/landing-pages/fashion-editorial/product-card';
import type { CatalogProduct } from '@/types';

type NewArrivalsSectionProps = {
    products: CatalogProduct[];
};

export function NewArrivalsSection({ products }: NewArrivalsSectionProps) {
    if (products.length === 0) {
        return null;
    }

    return (
        <section className="border-t border-neutral-200 bg-white px-5 py-16 sm:px-8 sm:py-24 lg:px-14 lg:py-32">
            <div className="mx-auto max-w-[1600px]">
                <header className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                            New arrivals
                        </p>

                        <h2 className="mt-3 font-serif text-4xl leading-tight tracking-[-0.025em] sm:text-5xl">
                            The latest pieces
                        </h2>
                    </div>

                    <Link
                        href="/shop?sort=newest"
                        className="inline-flex min-h-11 w-fit items-center border-b border-neutral-950 text-[10px] font-medium tracking-[0.15em] uppercase transition-opacity hover:opacity-60"
                    >
                        View all
                    </Link>
                </header>

                <div className="mt-10 grid grid-cols-1 gap-x-5 gap-y-12 sm:grid-cols-2 lg:grid-cols-4 lg:gap-x-6">
                    {products.slice(0, 4).map((product) => (
                        <ElegantProductCard
                            key={product.id}
                            product={product}
                        />
                    ))}
                </div>
            </div>
        </section>
    );
}
