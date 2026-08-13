import { Link } from '@inertiajs/react';
import { ProductImage } from '@/components/landing-pages/fashion-editorial/product-card';
import { toStorefrontHref } from '@/components/landing-pages/fashion-editorial/types';
import type { LandingPageSection } from '@/components/landing-pages/fashion-editorial/types';
import { Price } from '@/components/price';
import type { CatalogProduct } from '@/types';

type SignatureSectionProps = {
    section: LandingPageSection;
    products: CatalogProduct[];
};

export function SignatureSection({ section, products }: SignatureSectionProps) {
    const signatureProducts = products.slice(0, 2);
    const buttonHref = toStorefrontHref(section.button_url);

    if (signatureProducts.length === 0) {
        return null;
    }

    const gridClass =
        signatureProducts.length === 1
            ? 'max-w-2xl grid-cols-1'
            : 'max-w-6xl md:grid-cols-2';

    return (
        <section className="bg-[#f8f6f1] px-5 py-20 sm:px-8 sm:py-24 lg:px-14 lg:py-32">
            <div className="mx-auto max-w-[1600px]">
                <header className="mx-auto max-w-2xl text-center">
                    {section.eyebrow && (
                        <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                            {section.eyebrow}
                        </p>
                    )}

                    {section.title && (
                        <h2 className="mt-4 font-serif text-4xl leading-tight tracking-[-0.025em] sm:text-5xl">
                            {section.title}
                        </h2>
                    )}

                    {section.body && (
                        <p className="mx-auto mt-4 max-w-xl text-sm leading-6 text-neutral-600">
                            {section.body}
                        </p>
                    )}
                </header>

                <div
                    className={`mx-auto mt-12 grid gap-8 sm:gap-10 ${gridClass}`}
                >
                    {signatureProducts.map((product) => (
                        <article key={product.id} className="group min-w-0">
                            <Link
                                href={`/products/${product.slug}`}
                                className="block"
                            >
                                <ProductImage product={product} />

                                <div className="mt-5">
                                    {product.category && (
                                        <p className="mb-2 text-[10px] tracking-[0.14em] text-neutral-500 uppercase">
                                            {product.category.name}
                                        </p>
                                    )}

                                    <div className="flex items-start justify-between gap-6">
                                        <h3 className="font-serif text-2xl leading-tight">
                                            {product.name}
                                        </h3>

                                        <Price
                                            amount={product.price}
                                            className="shrink-0 pt-1 text-sm text-neutral-700"
                                        />
                                    </div>

                                    {product.stock_quantity > 0 ? (
                                        <span className="mt-5 inline-flex border-b border-neutral-950 pb-1 text-[10px] font-medium tracking-[0.14em] uppercase transition-opacity group-hover:opacity-60">
                                            Discover
                                        </span>
                                    ) : (
                                        <p className="mt-4 text-[10px] tracking-[0.14em] text-neutral-500 uppercase">
                                            Out of stock
                                        </p>
                                    )}
                                </div>
                            </Link>
                        </article>
                    ))}
                </div>

                {section.button_label && buttonHref && (
                    <div className="mt-12 text-center">
                        <Link
                            href={buttonHref}
                            className="inline-flex min-h-11 items-center border-b border-neutral-950 text-[10px] font-medium tracking-[0.15em] uppercase transition-opacity hover:opacity-60"
                        >
                            {section.button_label}
                        </Link>
                    </div>
                )}
            </div>
        </section>
    );
}
