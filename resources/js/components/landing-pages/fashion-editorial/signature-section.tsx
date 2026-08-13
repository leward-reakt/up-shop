import { Link } from '@inertiajs/react';
import { ProductImage } from '@/components/landing-pages/fashion-editorial/product-card';
import { Price } from '@/components/price';
import type { CatalogProduct } from '@/types';

type SignatureSectionVariables = {
    eyebrow?: string | null;
    label?: string | null;
    heading?: string | null;
    title?: string | null;
    description?: string | null;
    button_label?: string | null;
    button_text?: string | null;
    button_url?: string | null;
};

type SignatureSectionProps = {
    products: CatalogProduct[];
    variables?: SignatureSectionVariables | null;
};

export function SignatureSection({
    products,
    variables,
}: SignatureSectionProps) {
    const signatureProducts = products.slice(0, 2);

    if (signatureProducts.length === 0) {
        return null;
    }

    /*
     * Section variables may be absent for existing or partially configured
     * landing-page records. Always keep the storefront renderable by using
     * the original editorial copy as the fallback.
     */
    const eyebrow =
        variables?.eyebrow?.trim() ||
        variables?.label?.trim() ||
        'Signature selection';

    const heading =
        variables?.heading?.trim() ||
        variables?.title?.trim() ||
        'Timeless by design';

    const description =
        variables?.description?.trim() ||
        'A refined selection of defining pieces chosen for their versatility, proportion, and enduring appeal.';

    const buttonLabel =
        variables?.button_label?.trim() ||
        variables?.button_text?.trim() ||
        'Discover';

    const buttonUrl = variables?.button_url?.trim();

    const gridClass =
        signatureProducts.length === 1
            ? 'max-w-2xl grid-cols-1'
            : 'max-w-6xl md:grid-cols-2';

    return (
        <section className="bg-[#f8f6f1] px-5 py-20 sm:px-8 sm:py-24 lg:px-14 lg:py-32">
            <div className="mx-auto max-w-[1600px]">
                <header className="mx-auto max-w-2xl text-center">
                    <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                        {eyebrow}
                    </p>

                    <h2 className="mt-4 font-serif text-4xl leading-tight tracking-[-0.025em] sm:text-5xl">
                        {heading}
                    </h2>

                    <p className="mx-auto mt-4 max-w-xl text-sm leading-6 text-neutral-600">
                        {description}
                    </p>
                </header>

                <div
                    className={`mx-auto mt-12 grid gap-8 sm:gap-10 ${gridClass}`}
                >
                    {signatureProducts.map((product) => {
                        const productUrl =
                            buttonUrl || `/products/${product.slug}`;

                        return (
                            <article key={product.id} className="group min-w-0">
                                <Link href={productUrl} className="block">
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
                                                {buttonLabel}
                                            </span>
                                        ) : (
                                            <p className="mt-4 text-[10px] tracking-[0.14em] text-neutral-500 uppercase">
                                                Out of stock
                                            </p>
                                        )}
                                    </div>
                                </Link>
                            </article>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}
