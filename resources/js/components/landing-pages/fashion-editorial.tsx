import { Head, Link } from '@inertiajs/react';
import { Price } from '@/components/price';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CatalogCategory, CatalogProduct } from '@/types';

export type FashionEditorialCategory = CatalogCategory & {
    image_url: string | null;
    image_alt: string | null;
};

type FashionElegantLandingPageProps = {
    categories: FashionEditorialCategory[];
    featuredProducts: CatalogProduct[];
    newArrivals: CatalogProduct[];
};

function ProductImage({
    product,
    loading = 'lazy',
}: {
    product: CatalogProduct;
    loading?: 'eager' | 'lazy';
}) {
    return (
        <div className="aspect-[3/4] overflow-hidden bg-[#ebe6df]">
            {product.image_url ? (
                <img
                    src={product.image_url}
                    alt={product.image_alt ?? product.name}
                    loading={loading}
                    className="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.02]"
                />
            ) : (
                <div className="flex h-full items-center justify-center bg-gradient-to-b from-[#eee9e2] to-[#ddd5cc] px-6 text-center">
                    <span className="text-[10px] tracking-[0.16em] text-neutral-500 uppercase">
                        {product.name}
                    </span>
                </div>
            )}
        </div>
    );
}

function ProductDetails({ product }: { product: CatalogProduct }) {
    return (
        <div className="mt-4 px-2">
            {product.category && (
                <p className="mb-1 text-[10px] tracking-[0.14em] text-neutral-500 uppercase">
                    {product.category.name}
                </p>
            )}

            <div className="flex items-start justify-between gap-5">
                <h3 className="min-w-0 truncate text-sm font-medium text-neutral-950">
                    {product.name}
                </h3>

                <Price
                    amount={product.price}
                    className="shrink-0 text-xs text-neutral-700"
                />
            </div>

            {product.stock_quantity <= 0 && (
                <p className="mt-2 text-[10px] tracking-[0.12em] text-neutral-500 uppercase">
                    Out of stock
                </p>
            )}
        </div>
    );
}

function ElegantProductCard({ product }: { product: CatalogProduct }) {
    return (
        <article className="group min-w-0">
            <Link href={`/products/${product.slug}`}>
                <ProductImage product={product} />

                <ProductDetails product={product} />
            </Link>
        </article>
    );
}

function CategoryPlaceholder({ name }: { name: string }) {
    return (
        <div className="flex h-full items-center justify-center bg-gradient-to-b from-[#ede7df] to-[#d9d0c6] px-6 text-center">
            <span className="font-serif text-3xl text-neutral-700">{name}</span>
        </div>
    );
}

export default function FashionEditorialLandingPage({
    categories,
    featuredProducts,
    newArrivals,
}: FashionElegantLandingPageProps) {
    const availableProducts =
        featuredProducts.length > 0 ? featuredProducts : newArrivals;

    const heroProduct =
        availableProducts.find((product) => product.image_url !== null) ??
        newArrivals.find((product) => product.image_url !== null) ??
        availableProducts[0] ??
        newArrivals[0];

    const storyProduct =
        [...newArrivals, ...availableProducts].find(
            (product) =>
                product.id !== heroProduct?.id && product.image_url !== null,
        ) ??
        availableProducts[1] ??
        newArrivals[1] ??
        heroProduct;

    const collectionCategories = categories.slice(0, 3);

    const signatureProducts = availableProducts.slice(0, 2);

    const collectionGridClass =
        collectionCategories.length === 1
            ? 'md:grid-cols-1 md:max-w-xl'
            : collectionCategories.length === 2
              ? 'md:grid-cols-2 md:max-w-5xl'
              : 'md:grid-cols-3';

    const signatureGridClass =
        signatureProducts.length === 1
            ? 'max-w-2xl grid-cols-1'
            : 'max-w-6xl md:grid-cols-2';

    return (
        <StorefrontLayout
            variant="fashion-editorial"
            navigationCategories={categories}
        >
            <Head title="Home" />

            <section className="relative isolate min-h-[640px] overflow-hidden bg-[#8b8177] sm:min-h-[720px] lg:min-h-[800px]">
                {heroProduct?.image_url ? (
                    <img
                        src={heroProduct.image_url}
                        alt={heroProduct.image_alt ?? heroProduct.name}
                        loading="eager"
                        className="absolute inset-0 h-full w-full object-cover"
                    />
                ) : (
                    <div className="absolute inset-0 bg-gradient-to-br from-[#a99e92] via-[#796f66] to-[#514b45]" />
                )}

                <div className="absolute inset-0 bg-black/20" />

                <div className="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/10" />

                <div className="relative mx-auto flex min-h-[640px] max-w-[1600px] items-end justify-center px-5 pb-16 text-center sm:min-h-[720px] sm:px-8 sm:pb-20 lg:min-h-[800px] lg:px-14 lg:pb-24">
                    <div className="max-w-3xl text-white">
                        <p className="text-[10px] font-medium tracking-[0.24em] uppercase">
                            The New Collection
                        </p>

                        <h1 className="mt-6 font-serif text-[clamp(3.5rem,7vw,6.5rem)] leading-[0.95] font-normal tracking-[-0.035em]">
                            Effortless elegance.
                        </h1>

                        <p className="mx-auto mt-7 max-w-xl text-sm leading-7 text-white/90 sm:text-base">
                            Refined silhouettes, considered details, and
                            timeless pieces created for modern dressing.
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

            {collectionCategories.length > 0 && (
                <section className="bg-[#f8f6f1] px-5 py-20 sm:px-8 sm:py-24 lg:px-14 lg:py-32">
                    <div className="mx-auto max-w-[1600px]">
                        <header className="mx-auto max-w-2xl text-center">
                            <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                                Collections
                            </p>

                            <h2 className="mt-4 font-serif text-4xl leading-tight tracking-[-0.025em] sm:text-5xl">
                                Shop by collection
                            </h2>

                            <p className="mx-auto mt-4 max-w-xl text-sm leading-6 text-neutral-600">
                                Discover a considered selection of pieces
                                designed for an elegant and versatile wardrobe.
                            </p>
                        </header>

                        <div
                            className={`mx-auto mt-12 grid gap-5 sm:gap-6 ${collectionGridClass}`}
                        >
                            {collectionCategories.map((category) => (
                                <article key={category.id}>
                                    <Link
                                        href={`/shop?category=${category.slug}`}
                                        className="group block"
                                    >
                                        <div className="aspect-[3/4] overflow-hidden bg-[#e5ded5]">
                                            {category.image_url ? (
                                                <img
                                                    src={category.image_url}
                                                    alt={
                                                        category.image_alt ??
                                                        category.name
                                                    }
                                                    loading="lazy"
                                                    className="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.02]"
                                                />
                                            ) : (
                                                <CategoryPlaceholder
                                                    name={category.name}
                                                />
                                            )}
                                        </div>

                                        <div className="mt-5 text-center">
                                            <h3 className="font-serif text-2xl">
                                                {category.name}
                                            </h3>

                                            <span className="mt-3 inline-flex border-b border-neutral-950 pb-1 text-[10px] font-medium tracking-[0.14em] uppercase transition-opacity group-hover:opacity-60">
                                                Explore
                                            </span>
                                        </div>
                                    </Link>
                                </article>
                            ))}
                        </div>

                        {categories.length > 3 && (
                            <div className="mt-12 text-center">
                                <Link
                                    href="/shop"
                                    className="inline-flex min-h-11 items-center border-b border-neutral-950 text-[10px] font-medium tracking-[0.15em] uppercase transition-opacity hover:opacity-60"
                                >
                                    View all collections
                                </Link>
                            </div>
                        )}
                    </div>
                </section>
            )}

            {newArrivals.length > 0 && (
                <section className="border-t border-neutral-200 bg-white px-5 py-20 sm:px-8 sm:py-24 lg:px-14 lg:py-32">
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

                        <div className="mt-10 grid grid-cols-2 gap-x-3 gap-y-10 sm:gap-x-5 lg:grid-cols-4 lg:gap-x-6">
                            {newArrivals.slice(0, 4).map((product) => (
                                <ElegantProductCard
                                    key={product.id}
                                    product={product}
                                />
                            ))}
                        </div>
                    </div>
                </section>
            )}

            <section className="bg-[#f1ece5]">
                <div className="mx-auto grid max-w-[1600px] lg:grid-cols-2">
                    <div className="relative min-h-[480px] overflow-hidden bg-[#d6cec5] sm:min-h-[580px] lg:min-h-[680px]">
                        {storyProduct?.image_url ? (
                            <img
                                src={storyProduct.image_url}
                                alt={
                                    storyProduct.image_alt ?? storyProduct.name
                                }
                                loading="lazy"
                                className="absolute inset-0 h-full w-full object-cover"
                            />
                        ) : (
                            <div className="absolute inset-0 bg-gradient-to-br from-[#ded5cb] via-[#c7baad] to-[#a89a8e]" />
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
                                carefully considered details come together in
                                pieces made to remain part of your wardrobe
                                season after season.
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

            {signatureProducts.length > 0 && (
                <section className="bg-[#f8f6f1] px-5 py-20 sm:px-8 sm:py-24 lg:px-14 lg:py-32">
                    <div className="mx-auto max-w-[1600px]">
                        <header className="mx-auto max-w-2xl text-center">
                            <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                                Signature selection
                            </p>

                            <h2 className="mt-4 font-serif text-4xl leading-tight tracking-[-0.025em] sm:text-5xl">
                                Timeless by design
                            </h2>

                            <p className="mx-auto mt-4 max-w-xl text-sm leading-6 text-neutral-600">
                                A refined selection of defining pieces chosen
                                for their versatility, proportion, and enduring
                                appeal.
                            </p>
                        </header>

                        <div
                            className={`mx-auto mt-12 grid gap-8 sm:gap-10 ${signatureGridClass}`}
                        >
                            {signatureProducts.map((product) => (
                                <article
                                    key={product.id}
                                    className="group min-w-0"
                                >
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
                    </div>
                </section>
            )}

            <section className="border-t border-neutral-200 bg-[#eee8e1] px-5 py-20 sm:px-8 sm:py-24 lg:px-14 lg:py-28">
                <div className="mx-auto max-w-3xl text-center">
                    <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                        The complete wardrobe
                    </p>

                    <h2 className="mt-5 font-serif text-4xl leading-tight tracking-[-0.025em] sm:text-5xl lg:text-6xl">
                        Discover the collection.
                    </h2>

                    <p className="mx-auto mt-5 max-w-xl text-sm leading-7 text-neutral-600">
                        Modern essentials and refined statement pieces designed
                        to create an elegant, considered wardrobe.
                    </p>

                    <Link
                        href="/shop"
                        className="mt-9 inline-flex min-h-12 items-center justify-center border border-neutral-950 px-7 text-[10px] font-medium tracking-[0.16em] uppercase transition duration-300 hover:bg-neutral-950 hover:text-white"
                    >
                        Shop all
                    </Link>
                </div>
            </section>
        </StorefrontLayout>
    );
}
