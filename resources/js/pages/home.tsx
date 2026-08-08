import { Head, Link } from '@inertiajs/react';
import FashionEditorialLandingPage from '@/components/landing-pages/fashion-editorial';
import type { FashionEditorialCategory } from '@/components/landing-pages/fashion-editorial';
import { ProductCard } from '@/components/product-card';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CatalogProduct } from '@/types';

type HomeProps = {
    theme: 'default' | 'fashion_editorial';
    featuredProducts: CatalogProduct[];
    newArrivals: CatalogProduct[];
    categories: FashionEditorialCategory[];
};

export default function Home({
    theme,
    featuredProducts,
    newArrivals,
    categories,
}: HomeProps) {
    if (theme === 'fashion_editorial') {
        return (
            <FashionEditorialLandingPage
                categories={categories}
                featuredProducts={featuredProducts}
                newArrivals={newArrivals}
            />
        );
    }

    return (
        <StorefrontLayout>
            <Head title="Home" />

            <section className="border-b bg-white">
                <div className="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                    <div className="max-w-2xl space-y-6">
                        <p className="text-sm font-medium tracking-widest text-neutral-500 uppercase">
                            Up Shop
                        </p>

                        <h1 className="text-4xl font-semibold tracking-tight sm:text-5xl">
                            Shop products you will actually use.
                        </h1>

                        <p className="text-lg text-neutral-600">
                            Browse our latest products and discover featured
                            items from the store.
                        </p>

                        <Link
                            href="/shop"
                            className="inline-flex rounded-lg bg-neutral-950 px-5 py-3 text-sm font-medium text-white hover:bg-neutral-800"
                        >
                            Shop now
                        </Link>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">
                            Featured products
                        </h2>

                        <p className="mt-1 text-sm text-neutral-500">
                            Selected products from the catalog.
                        </p>
                    </div>

                    <Link
                        href="/shop"
                        className="text-sm font-medium underline"
                    >
                        View all
                    </Link>
                </div>

                {featuredProducts.length > 0 ? (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {featuredProducts.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                ) : (
                    <div className="rounded-xl border border-dashed bg-white p-12 text-center text-neutral-500">
                        No featured products yet.
                    </div>
                )}
            </section>
        </StorefrontLayout>
    );
}
