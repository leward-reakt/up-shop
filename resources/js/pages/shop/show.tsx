import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { Price } from '@/components/price';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CatalogProductDetails } from '@/types';

type ProductShowProps = {
    product: CatalogProductDetails;
};

export default function ProductShow({ product }: ProductShowProps) {
    const [selectedImageId, setSelectedImageId] = useState<number | null>(null);

    const selectedImage =
        product.images.find((image) => image.id === selectedImageId) ??
        product.images[0] ??
        null;

    const selectedImageUrl = selectedImage?.url ?? product.image_url;

    const selectedImageAlt =
        selectedImage?.alt_text ?? product.image_alt ?? product.name;

    const inStock = product.stock_quantity > 0;

    return (
        <StorefrontLayout>
            <Head title={product.name} />

            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <Link
                    href="/shop"
                    className="mb-6 inline-block text-sm text-neutral-500 hover:text-neutral-950"
                >
                    ← Back to shop
                </Link>

                <div className="grid gap-10 lg:grid-cols-2">
                    <section>
                        <div className="aspect-square overflow-hidden rounded-xl bg-neutral-100">
                            {selectedImageUrl ? (
                                <img
                                    src={selectedImageUrl}
                                    alt={selectedImageAlt}
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                <div className="flex h-full items-center justify-center text-neutral-400">
                                    No product image
                                </div>
                            )}
                        </div>

                        {product.images.length > 1 && (
                            <div className="mt-4 grid grid-cols-5 gap-3">
                                {product.images.map((image) => (
                                    <button
                                        key={image.id}
                                        type="button"
                                        onClick={() =>
                                            setSelectedImageId(image.id)
                                        }
                                        className="aspect-square overflow-hidden rounded-lg border bg-neutral-100"
                                    >
                                        <img
                                            src={image.url}
                                            alt={image.alt_text ?? product.name}
                                            className="h-full w-full object-cover"
                                        />
                                    </button>
                                ))}
                            </div>
                        )}
                    </section>

                    <section className="space-y-6">
                        <div>
                            {product.category && (
                                <Link
                                    href={`/shop?category=${product.category.slug}`}
                                    className="text-sm text-neutral-500 hover:text-neutral-950"
                                >
                                    {product.category.name}
                                </Link>
                            )}

                            <h1 className="mt-2 text-4xl font-semibold tracking-tight">
                                {product.name}
                            </h1>

                            <p className="mt-2 text-sm text-neutral-500">
                                SKU: {product.sku}
                            </p>
                        </div>

                        <Price
                            amount={product.price}
                            className="text-2xl font-semibold"
                        />

                        <div>
                            <p
                                className={
                                    inStock
                                        ? 'font-medium text-emerald-700'
                                        : 'font-medium text-red-600'
                                }
                            >
                                {inStock
                                    ? `${product.stock_quantity} in stock`
                                    : 'Out of stock'}
                            </p>
                        </div>

                        {product.description && (
                            <div className="border-t pt-6">
                                <h2 className="mb-3 font-medium">
                                    Product details
                                </h2>

                                <p className="leading-7 whitespace-pre-line text-neutral-600">
                                    {product.description}
                                </p>
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </StorefrontLayout>
    );
}
