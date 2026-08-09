import { Head, Link, router, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Price } from '@/components/price';
import StorefrontLayout from '@/layouts/storefront-layout';
import type {
    CatalogCategory,
    CatalogFilters,
    CatalogProduct,
    Paginated,
} from '@/types';

type FashionElegantShopProps = {
    products: Paginated<CatalogProduct>;
    categories: CatalogCategory[];
    filters: CatalogFilters;
};

type StoreSharedProps = {
    store?: {
        currency?: string;
    };
};

function resolveCurrency(currency: string | undefined): string {
    const normalized = currency?.trim().toUpperCase();

    return normalized && /^[A-Z]{3}$/.test(normalized) ? normalized : 'PHP';
}

function FashionProductCard({ product }: { product: CatalogProduct }) {
    return (
        <article className="group min-w-0">
            <Link href={`/products/${product.slug}`} className="block">
                <div className="aspect-[3/4] overflow-hidden bg-[#ebe6df]">
                    {product.image_url ? (
                        <img
                            src={product.image_url}
                            alt={product.image_alt ?? product.name}
                            loading="lazy"
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

                <div className="mt-4 px-1">
                    {product.category && (
                        <p className="mb-1 text-[9px] tracking-[0.14em] text-neutral-500 uppercase">
                            {product.category.name}
                        </p>
                    )}

                    <div className="flex items-start justify-between gap-5">
                        <h2 className="min-w-0 truncate text-sm font-medium text-neutral-950">
                            {product.name}
                        </h2>

                        <Price
                            amount={product.price}
                            className="shrink-0 text-xs text-neutral-700"
                        />
                    </div>

                    {product.stock_quantity <= 0 && (
                        <p className="mt-2 text-[9px] tracking-[0.14em] text-neutral-500 uppercase">
                            Out of stock
                        </p>
                    )}
                </div>
            </Link>
        </article>
    );
}

export default function FashionElegantShop({
    products,
    categories,
    filters,
}: FashionElegantShopProps) {
    const page = usePage();

    const sharedProps = page.props as unknown as StoreSharedProps;

    const currency = resolveCurrency(sharedProps.store?.currency);

    const submitFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const formData = new FormData(event.currentTarget);

        const data = Object.fromEntries(
            Array.from(formData.entries())
                .filter(([, value]) => String(value) !== '')
                .map(([key, value]) => [key, String(value)]),
        );

        router.get('/shop', data, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    return (
        <StorefrontLayout
            variant="fashion-editorial"
            navigationCategories={categories}
        >
            <Head title="Shop" />

            <section className="border-b border-neutral-200 bg-[#f8f6f1]">
                <div className="mx-auto max-w-[1600px] px-5 py-16 text-center sm:px-8 sm:py-20 lg:px-14 lg:py-24">
                    <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                        The collection
                    </p>

                    <h1 className="mt-4 font-serif text-[clamp(2.75rem,6vw,5rem)] leading-[0.98] font-normal tracking-[-0.035em]">
                        Shop the collection.
                    </h1>

                    <p className="mx-auto mt-5 max-w-xl text-sm leading-7 text-neutral-600">
                        Discover the complete selection of refined essentials,
                        considered pieces, and modern wardrobe staples.
                    </p>
                </div>
            </section>

            <section className="border-b border-neutral-200 bg-[#f8f6f1]">
                <form
                    onSubmit={submitFilters}
                    className="mx-auto max-w-[1600px] px-5 py-7 sm:px-8 lg:px-14"
                >
                    <div className="grid gap-x-8 gap-y-6 md:grid-cols-2 xl:grid-cols-6">
                        <div className="xl:col-span-1">
                            <label
                                htmlFor="search"
                                className="block text-[9px] font-medium tracking-[0.15em] text-neutral-500 uppercase"
                            >
                                Search
                            </label>

                            <input
                                id="search"
                                name="search"
                                type="search"
                                defaultValue={filters.search}
                                placeholder="Product name"
                                className="mt-2 w-full border-0 border-b border-neutral-300 bg-transparent px-0 py-2 text-sm transition outline-none focus:border-neutral-950 focus:ring-0"
                            />
                        </div>

                        <div>
                            <label
                                htmlFor="category"
                                className="block text-[9px] font-medium tracking-[0.15em] text-neutral-500 uppercase"
                            >
                                Collection
                            </label>

                            <select
                                id="category"
                                name="category"
                                defaultValue={filters.category}
                                className="mt-2 w-full border-0 border-b border-neutral-300 bg-transparent px-0 py-2 text-sm transition outline-none focus:border-neutral-950 focus:ring-0"
                            >
                                <option value="">All collections</option>

                                {categories.map((category) => (
                                    <option
                                        key={category.id}
                                        value={category.slug}
                                    >
                                        {category.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <span className="block text-[9px] font-medium tracking-[0.15em] text-neutral-500 uppercase">
                                Price
                            </span>

                            <div className="mt-2 grid grid-cols-2 gap-3">
                                <label className="sr-only" htmlFor="min_price">
                                    Minimum price in {currency}
                                </label>

                                <input
                                    id="min_price"
                                    name="min_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    defaultValue={filters.min_price}
                                    placeholder={`Min ${currency}`}
                                    className="w-full border-0 border-b border-neutral-300 bg-transparent px-0 py-2 text-sm transition outline-none focus:border-neutral-950 focus:ring-0"
                                />

                                <label className="sr-only" htmlFor="max_price">
                                    Maximum price in {currency}
                                </label>

                                <input
                                    id="max_price"
                                    name="max_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    defaultValue={filters.max_price}
                                    placeholder={`Max ${currency}`}
                                    className="w-full border-0 border-b border-neutral-300 bg-transparent px-0 py-2 text-sm transition outline-none focus:border-neutral-950 focus:ring-0"
                                />
                            </div>
                        </div>

                        <div>
                            <span className="block text-[9px] font-medium tracking-[0.15em] text-neutral-500 uppercase">
                                Availability
                            </span>

                            <label className="mt-4 flex min-h-8 items-center gap-3 text-sm">
                                <input
                                    type="checkbox"
                                    name="availability"
                                    value="in_stock"
                                    defaultChecked={
                                        filters.availability === 'in_stock'
                                    }
                                    className="size-4 rounded-none border-neutral-400"
                                />
                                In stock only
                            </label>
                        </div>

                        <div>
                            <label
                                htmlFor="sort"
                                className="block text-[9px] font-medium tracking-[0.15em] text-neutral-500 uppercase"
                            >
                                Sort by
                            </label>

                            <select
                                id="sort"
                                name="sort"
                                defaultValue={filters.sort}
                                className="mt-2 w-full border-0 border-b border-neutral-300 bg-transparent px-0 py-2 text-sm transition outline-none focus:border-neutral-950 focus:ring-0"
                            >
                                <option value="featured">Featured</option>
                                <option value="newest">Newest</option>
                                <option value="price_asc">
                                    Price: Low to High
                                </option>
                                <option value="price_desc">
                                    Price: High to Low
                                </option>
                            </select>
                        </div>

                        <div className="flex items-end gap-4">
                            <button
                                type="submit"
                                className="inline-flex min-h-11 flex-1 items-center justify-center border border-neutral-950 bg-neutral-950 px-5 text-[10px] font-medium tracking-[0.14em] text-white uppercase transition hover:bg-transparent hover:text-neutral-950"
                            >
                                Apply
                            </button>

                            <Link
                                href="/shop"
                                className="inline-flex min-h-11 items-center border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60"
                            >
                                Reset
                            </Link>
                        </div>
                    </div>
                </form>
            </section>

            <section className="bg-[#f8f6f1] px-5 py-14 sm:px-8 sm:py-16 lg:px-14 lg:py-20">
                <div className="mx-auto max-w-[1600px]">
                    <div className="mb-8 flex items-center justify-between gap-6">
                        <p className="text-[10px] tracking-[0.12em] text-neutral-500 uppercase">
                            {products.total === 0
                                ? 'No products'
                                : `${products.from}–${products.to} of ${products.total} pieces`}
                        </p>
                    </div>

                    {products.data.length > 0 ? (
                        <div className="grid grid-cols-2 gap-x-3 gap-y-12 sm:gap-x-5 lg:grid-cols-3 xl:grid-cols-4 xl:gap-x-6">
                            {products.data.map((product) => (
                                <FashionProductCard
                                    key={product.id}
                                    product={product}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="border-y border-neutral-200 py-20 text-center">
                            <p className="text-[10px] font-medium tracking-[0.18em] text-neutral-500 uppercase">
                                No matching pieces
                            </p>

                            <h2 className="mt-4 font-serif text-3xl">
                                Refine your selection.
                            </h2>

                            <p className="mx-auto mt-4 max-w-md text-sm leading-7 text-neutral-600">
                                Try adjusting your search, collection, price, or
                                availability filters.
                            </p>

                            <Link
                                href="/shop"
                                className="mt-7 inline-flex min-h-11 items-center border-b border-neutral-950 text-[10px] font-medium tracking-[0.15em] uppercase"
                            >
                                Clear filters
                            </Link>
                        </div>
                    )}

                    {products.last_page > 1 && (
                        <nav
                            aria-label="Product pagination"
                            className="mt-16 flex items-center justify-between border-t border-neutral-200 pt-7"
                        >
                            {products.prev_page_url ? (
                                <Link
                                    href={products.prev_page_url}
                                    preserveScroll
                                    className="inline-flex min-h-10 items-center border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60"
                                >
                                    Previous
                                </Link>
                            ) : (
                                <span />
                            )}

                            <span className="text-[9px] tracking-[0.14em] text-neutral-500 uppercase">
                                Page {products.current_page} of{' '}
                                {products.last_page}
                            </span>

                            {products.next_page_url ? (
                                <Link
                                    href={products.next_page_url}
                                    preserveScroll
                                    className="inline-flex min-h-10 items-center border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60"
                                >
                                    Next
                                </Link>
                            ) : (
                                <span />
                            )}
                        </nav>
                    )}
                </div>
            </section>
        </StorefrontLayout>
    );
}
