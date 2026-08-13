import { Link } from '@inertiajs/react';
import { ElegantProductCard } from '@/components/landing-pages/fashion-editorial/product-card';
import type { CatalogProduct } from '@/types';

type NewArrivalsVariables = {
    eyebrow?: string | null;
    label?: string | null;
    heading?: string | null;
    title?: string | null;
    button_label?: string | null;
    button_text?: string | null;
    button_url?: string | null;
};

type NewArrivalsSectionProps = {
    products: CatalogProduct[];
    variables?: NewArrivalsVariables | null;
};

export function NewArrivalsSection({
    products,
    variables,
}: NewArrivalsSectionProps) {
    if (products.length === 0) {
        return null;
    }

    /*
     * Landing-section configuration may be absent for existing stores or
     * immediately after seeding. Keep the storefront operational by falling
     * back to the original section content.
     */
    const eyebrow =
        variables?.eyebrow?.trim() ||
        variables?.label?.trim() ||
        'New arrivals';

    const heading =
        variables?.heading?.trim() ||
        variables?.title?.trim() ||
        'The latest pieces';

    const buttonLabel =
        variables?.button_label?.trim() ||
        variables?.button_text?.trim() ||
        'View all';

    const buttonUrl =
        variables?.button_url?.trim() ||
        '/shop?sort=newest';

    return (
        <section className="border-t border-neutral-200 bg-white px-5 py-20 sm:px-8 sm:py-24 lg:px-14 lg:py-32">
            <div className="mx-auto max-w-[1600px]">
                <header className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                            {eyebrow}
                        </p>

                        <h2 className="mt-3 font-serif text-4xl leading-tight tracking-[-0.025em] sm:text-5xl">
                            {heading}
                        </h2>
                    </div>

                    <Link
                        href={buttonUrl}
                        className="inline-flex min-h-11 w-fit items-center border-b border-neutral-950 text-[10px] font-medium tracking-[0.15em] uppercase transition-opacity hover:opacity-60"
                    >
                        {buttonLabel}
                    </Link>
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
