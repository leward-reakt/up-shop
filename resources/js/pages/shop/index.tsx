import { Head, Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FashionElegantShop from '@/components/fashion-elegant-shop';
import { ProductCard } from '@/components/product-card';
import StorefrontLayout from '@/layouts/storefront-layout';
import type {
    CatalogCategory,
    CatalogFilters,
    CatalogProduct,
    Paginated,
} from '@/types';

type ShopProps = {
    theme: 'default' | 'fashion_editorial';
    products: Paginated<CatalogProduct>;
    categories: CatalogCategory[];
    filters: CatalogFilters;
};

export default function Shop(props: ShopProps) {
    if (props.theme === 'fashion_editorial') {
        return (
            <FashionElegantShop
                products={props.products}
                categories={props.categories}
                filters={props.filters}
            />
        );
    }

    return (
        <DefaultShop
            products={props.products}
            categories={props.categories}
            filters={props.filters}
        />
    );
}

function DefaultShop({
    products,
    categories,
    filters,
}: Omit<ShopProps, 'theme'>) {
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
        <StorefrontLayout>
            <Head title="Shop" />

            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-semibold">Shop</h1>

                    <p className="mt-2 text-neutral-600">
                        Browse and filter available products.
                    </p>
                </div>

                <div className="grid gap-8 lg:grid-cols-[260px_1fr]">
                    <aside>
                        <form
                            onSubmit={submitFilters}
                            className="space-y-5 rounded-xl border bg-white p-5"
                        >
                            <div>
                                <label
                                    htmlFor="search"
                                    className="mb-1 block text-sm font-medium"
                                >
                                    Search
                                </label>

                                <input
                                    id="search"
                                    name="search"
                                    type="search"
                                    defaultValue={filters.search}
                                    placeholder="Product name"
                                    className="w-full rounded-lg border px-3 py-2"
                                />
                            </div>

                            <div>
                                <label
                                    htmlFor="category"
                                    className="mb-1 block text-sm font-medium"
                                >
                                    Category
                                </label>

                                <select
                                    id="category"
                                    name="category"
                                    defaultValue={filters.category}
                                    className="w-full rounded-lg border px-3 py-2"
                                >
                                    <option value="">All categories</option>

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

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        htmlFor="min_price"
                                        className="mb-1 block text-sm font-medium"
                                    >
                                        Min ₱
                                    </label>

                                    <input
                                        id="min_price"
                                        name="min_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        defaultValue={filters.min_price}
                                        className="w-full rounded-lg border px-3 py-2"
                                    />
                                </div>

                                <div>
                                    <label
                                        htmlFor="max_price"
                                        className="mb-1 block text-sm font-medium"
                                    >
                                        Max ₱
                                    </label>

                                    <input
                                        id="max_price"
                                        name="max_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        defaultValue={filters.max_price}
                                        className="w-full rounded-lg border px-3 py-2"
                                    />
                                </div>
                            </div>

                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="availability"
                                    value="in_stock"
                                    defaultChecked={
                                        filters.availability === 'in_stock'
                                    }
                                />
                                In stock only
                            </label>

                            <div>
                                <label
                                    htmlFor="sort"
                                    className="mb-1 block text-sm font-medium"
                                >
                                    Sort
                                </label>

                                <select
                                    id="sort"
                                    name="sort"
                                    defaultValue={filters.sort}
                                    className="w-full rounded-lg border px-3 py-2"
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

                            <div className="flex gap-3">
                                <button
                                    type="submit"
                                    className="flex-1 rounded-lg bg-neutral-950 px-4 py-2 text-sm font-medium text-white"
                                >
                                    Apply
                                </button>

                                <Link
                                    href="/shop"
                                    className="rounded-lg border px-4 py-2 text-sm font-medium"
                                >
                                    Reset
                                </Link>
                            </div>
                        </form>
                    </aside>

                    <section>
                        <div className="mb-4 flex items-center justify-between">
                            <p className="text-sm text-neutral-500">
                                {products.total === 0
                                    ? 'No products'
                                    : `Showing ${products.from}–${products.to} of ${products.total}`}
                            </p>
                        </div>

                        {products.data.length > 0 ? (
                            <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                                {products.data.map((product) => (
                                    <ProductCard
                                        key={product.id}
                                        product={product}
                                    />
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-xl border border-dashed bg-white p-12 text-center">
                                <h2 className="font-medium">
                                    No matching products
                                </h2>

                                <p className="mt-2 text-sm text-neutral-500">
                                    Try changing or clearing the filters.
                                </p>
                            </div>
                        )}

                        {products.last_page > 1 && (
                            <div className="mt-8 flex items-center justify-between border-t pt-6">
                                {products.prev_page_url ? (
                                    <Link
                                        href={products.prev_page_url}
                                        preserveScroll
                                        className="rounded-lg border bg-white px-4 py-2 text-sm"
                                    >
                                        Previous
                                    </Link>
                                ) : (
                                    <span />
                                )}

                                <span className="text-sm text-neutral-500">
                                    Page {products.current_page} of{' '}
                                    {products.last_page}
                                </span>

                                {products.next_page_url ? (
                                    <Link
                                        href={products.next_page_url}
                                        preserveScroll
                                        className="rounded-lg border bg-white px-4 py-2 text-sm"
                                    >
                                        Next
                                    </Link>
                                ) : (
                                    <span />
                                )}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </StorefrontLayout>
    );
}
