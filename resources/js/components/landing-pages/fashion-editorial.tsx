import { Head, Link } from '@inertiajs/react';
import { Price } from '@/components/price';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CatalogCategory, CatalogProduct } from '@/types';

export type FashionEditorialCategory = CatalogCategory & {
    image_url: string | null;
    image_alt: string | null;
};

type FashionEditorialLandingPageProps = {
    categories: FashionEditorialCategory[];
    featuredProducts: CatalogProduct[];
    newArrivals: CatalogProduct[];
};

type ProductShelfProps = {
    title: string;
    products: CatalogProduct[];
};

function EditorialProductCard({ product }: { product: CatalogProduct }) {
    return (
        <article className="group min-w-0">
            <Link href={`/products/${product.slug}`}>
                <div className="aspect-[4/5] overflow-hidden bg-[#ebe7df]">
                    {product.image_url ? (
                        <img
                            src={product.image_url}
                            alt={product.image_alt ?? product.name}
                            loading="lazy"
                            className="h-full w-full object-cover transition duration-500 group-hover:scale-[1.02]"
                        />
                    ) : (
                        <div className="flex h-full items-center justify-center px-4 text-center text-xs text-neutral-400">
                            No image
                        </div>
                    )}
                </div>

                <div className="mt-3 space-y-1">
                    <h3 className="truncate text-xs font-medium">
                        {product.name}
                    </h3>

                    <Price
                        amount={product.price}
                        className="block text-[11px] text-neutral-700"
                    />

                    {product.stock_quantity <= 0 && (
                        <p className="text-[10px] tracking-wide text-neutral-500 uppercase">
                            Out of stock
                        </p>
                    )}
                </div>
            </Link>
        </article>
    );
}

function ProductShelf({ title, products }: ProductShelfProps) {
    if (products.length === 0) {
        return null;
    }

    return (
        <section className="bg-[#f8f6f1]">
            <div className="mx-auto max-w-[1600px] px-5 py-10 lg:px-10">
                <div className="mb-6 flex items-center justify-between gap-6">
                    <h2 className="text-xs font-medium tracking-[0.12em] uppercase">
                        {title}
                    </h2>

                    <Link
                        href="/shop"
                        className="text-[10px] font-medium tracking-[0.12em] uppercase underline underline-offset-4"
                    >
                        View all
                    </Link>
                </div>

                <div className="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-6">
                    {products.slice(0, 6).map((product) => (
                        <EditorialProductCard
                            key={product.id}
                            product={product}
                        />
                    ))}
                </div>
            </div>
        </section>
    );
}

export default function FashionEditorialLandingPage({
    categories,
    featuredProducts,
    newArrivals,
}: FashionEditorialLandingPageProps) {
    const curatedProducts =
        featuredProducts.length > 0 ? featuredProducts : newArrivals;

    return (
        <StorefrontLayout
            variant="fashion-editorial"
            navigationCategories={categories}
        >
            <Head title="Home" />

            <section
                className="relative isolate min-h-[480px] overflow-hidden bg-[#75695d] bg-cover bg-center sm:min-h-[560px] lg:min-h-[620px]"
                style={{
                    backgroundImage:
                        "linear-gradient(90deg, rgba(28, 24, 20, 0.64) 0%, rgba(28, 24, 20, 0.24) 48%, rgba(28, 24, 20, 0.08) 100%), url('/images/landing/fashion-editorial/hero.webp')",
                }}
            >
                <div className="mx-auto flex min-h-[480px] max-w-[1600px] items-center px-6 sm:min-h-[560px] lg:min-h-[620px] lg:px-16">
                    <div className="max-w-xl text-white">
                        <p className="mb-5 text-[11px] font-medium tracking-[0.26em] uppercase">
                            The Editorial Collection
                        </p>

                        <h1 className="font-serif text-5xl leading-[0.92] font-normal tracking-[-0.03em] uppercase sm:text-6xl lg:text-7xl">
                            Form in
                            <br />
                            Movement
                        </h1>

                        <p className="mt-6 max-w-sm text-sm leading-6 text-white/90">
                            A study of contrast and ease, where thoughtful
                            design meets quiet expression.
                        </p>

                        <Link
                            href="/shop"
                            className="mt-8 inline-flex border border-white px-5 py-3 text-[10px] font-medium tracking-[0.14em] uppercase transition hover:bg-white hover:text-neutral-950"
                        >
                            Explore the collection
                        </Link>
                    </div>
                </div>
            </section>

            {categories.length > 0 && (
                <section className="bg-[#f8f6f1]">
                    <div className="mx-auto max-w-[1600px] px-5 py-10 lg:px-10">
                        <div className="mb-6 flex items-center justify-between">
                            <h2 className="text-xs font-medium tracking-[0.12em] uppercase">
                                Shop by category
                            </h2>

                            <Link
                                href="/shop"
                                className="text-[10px] font-medium tracking-[0.12em] uppercase underline underline-offset-4"
                            >
                                View all
                            </Link>
                        </div>

                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                            {categories.map((category) => (
                                <Link
                                    key={category.id}
                                    href={`/shop?category=${category.slug}`}
                                    className="group relative aspect-[4/5] overflow-hidden bg-[#e8e2d8]"
                                >
                                    {category.image_url ? (
                                        <img
                                            src={category.image_url}
                                            alt={
                                                category.image_alt ??
                                                category.name
                                            }
                                            loading="lazy"
                                            className="h-full w-full object-cover transition duration-500 group-hover:scale-[1.02]"
                                        />
                                    ) : (
                                        <div className="h-full w-full bg-gradient-to-b from-[#e9e4db] to-[#d9d1c4]" />
                                    )}

                                    <div className="absolute inset-x-0 bottom-4 flex justify-center">
                                        <span className="bg-[#f8f6f1] px-4 py-2 text-[10px] font-medium tracking-[0.12em] uppercase">
                                            {category.name}
                                        </span>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            <section className="grid bg-[#f8f6f1] lg:grid-cols-2">
                <div
                    className="min-h-[360px] bg-[#55463a] bg-cover bg-center sm:min-h-[460px]"
                    style={{
                        backgroundImage:
                            "url('/images/landing/fashion-editorial/craftsmanship.webp')",
                    }}
                />

                <div className="flex items-center px-8 py-16 sm:px-12 lg:px-20">
                    <div className="max-w-lg">
                        <p className="mb-5 text-[10px] font-medium tracking-[0.18em] uppercase">
                            The art of making
                        </p>

                        <h2 className="font-serif text-4xl leading-[1.02] font-normal tracking-[-0.025em] sm:text-5xl">
                            Crafted with intention.
                            <br />
                            Made to endure.
                        </h2>

                        <p className="mt-6 max-w-md text-sm leading-6 text-neutral-600">
                            Every piece is shaped by thoughtful design, exacting
                            materials, and considered details.
                        </p>

                        <Link
                            href="/about"
                            className="mt-7 inline-flex border-b border-neutral-950 pb-1 text-[10px] font-medium tracking-[0.14em] uppercase"
                        >
                            Discover our process
                        </Link>
                    </div>
                </div>
            </section>

            <ProductShelf title="New arrivals" products={newArrivals} />

            <section
                className="relative isolate min-h-[380px] bg-[#695b4f] bg-cover bg-center"
                style={{
                    backgroundImage:
                        "linear-gradient(90deg, rgba(25, 22, 18, 0.6) 0%, rgba(25, 22, 18, 0.18) 60%, rgba(25, 22, 18, 0.05) 100%), url('/images/landing/fashion-editorial/campaign.webp')",
                }}
            >
                <div className="mx-auto flex min-h-[380px] max-w-[1600px] items-center px-6 lg:px-16">
                    <div className="text-white">
                        <p className="mb-4 text-[10px] font-medium tracking-[0.18em] uppercase">
                            The seasonal edit
                        </p>

                        <h2 className="font-serif text-4xl font-normal tracking-[-0.025em] sm:text-5xl">
                            Lightness by design.
                        </h2>

                        <Link
                            href="/shop?sort=newest"
                            className="mt-7 inline-flex border border-white px-5 py-3 text-[10px] font-medium tracking-[0.14em] uppercase transition hover:bg-white hover:text-neutral-950"
                        >
                            Explore the edit
                        </Link>
                    </div>
                </div>
            </section>

            <ProductShelf title="Curated edit" products={curatedProducts} />
        </StorefrontLayout>
    );
}
