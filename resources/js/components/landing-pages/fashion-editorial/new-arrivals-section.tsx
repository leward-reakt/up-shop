import { Link } from '@inertiajs/react';
import { ElegantProductCard } from '@/components/landing-pages/fashion-editorial/product-card';
import { toStorefrontHref } from '@/components/landing-pages/fashion-editorial/types';
import type { LandingPageSection } from '@/components/landing-pages/fashion-editorial/types';
import type { CatalogProduct } from '@/types';

type NewArrivalsSectionProps = {
    section: LandingPageSection;
    products: CatalogProduct[];
};

export function NewArrivalsSection({
    section,
    products,
}: NewArrivalsSectionProps) {
    const buttonHref = toStorefrontHref(section.button_url);

    if (products.length === 0) {
        return null;
    }

    return (
        <section className="border-t border-neutral-200 bg-white px-5 py-20 sm:px-8 sm:py-24 lg:px-14 lg:py-32">
            <div className="mx-auto max-w-[1600px]">
                <header className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        {section.eyebrow && (
                            <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                                {section.eyebrow}
                            </p>
                        )}

                        {section.title && (
                            <h2 className="mt-3 font-serif text-4xl leading-tight tracking-[-0.025em] sm:text-5xl">
                                {section.title}
                            </h2>
                        )}

                        {section.body && (
                            <p className="mt-4 max-w-xl text-sm leading-6 text-neutral-600">
                                {section.body}
                            </p>
                        )}
                    </div>

                    {section.button_label && buttonHref && (
                        <Link
                            href={buttonHref}
                            className="inline-flex min-h-11 w-fit items-center border-b border-neutral-950 text-[10px] font-medium tracking-[0.15em] uppercase transition-opacity hover:opacity-60"
                        >
                            {section.button_label}
                        </Link>
                    )}
                </header>

                <div className="mt-10 grid grid-cols-2 gap-x-3 gap-y-10 sm:gap-x-5 lg:grid-cols-4 lg:gap-x-6">
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
