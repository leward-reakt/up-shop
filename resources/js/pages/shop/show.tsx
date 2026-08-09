import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import FashionElegantProduct from '@/components/fashion-elegant-product';
import { Price } from '@/components/price';
import { QuantityInput } from '@/components/quantity-input';
import { SeoHead } from '@/components/seo-head';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CatalogCategory, CatalogProductDetails } from '@/types';

type ProductShowProps = {
    theme: 'default' | 'fashion_editorial';
    categories: CatalogCategory[];
    product: CatalogProductDetails;
};

export default function ProductShow({
    theme,
    categories,
    product,
}: ProductShowProps) {
    return (
        <>
            <SeoHead
                title={product.meta_title?.trim() || product.name}
                description={product.meta_description}
                canonicalPath={`/products/${product.slug}`}
                image={product.image_url}
            />

            {theme === 'fashion_editorial' ? (
                <FashionElegantProduct
                    product={product}
                    categories={categories}
                />
            ) : (
                <DefaultProductShow product={product} />
            )}
        </>
    );
}

function DefaultProductShow({ product }: { product: CatalogProductDetails }) {
    const [selectedImageId, setSelectedImageId] = useState<number | null>(null);

    const selectedImage =
        product.images.find((image) => image.id === selectedImageId) ??
        product.images[0] ??
        null;

    const selectedImageUrl = selectedImage?.url ?? product.image_url;

    const selectedImageAlt =
        selectedImage?.alt_text ?? product.image_alt ?? product.name;

    const inStock = product.stock_quantity > 0;

    const form = useForm<{
        product_id: number;
        quantity: number;
    }>({
        product_id: product.id,
        quantity: 1,
    });

    const addToCart = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.post('/cart/items', {
            preserveScroll: true,
        });
    };

    return (
        <StorefrontLayout>
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

                        {inStock && (
                            <form
                                onSubmit={addToCart}
                                className="border-y py-6"
                            >
                                <div className="flex flex-wrap items-end gap-4">
                                    <div>
                                        <p className="mb-2 text-sm font-medium">
                                            Quantity
                                        </p>

                                        <QuantityInput
                                            value={form.data.quantity}
                                            max={product.stock_quantity}
                                            disabled={form.processing}
                                            onChange={(quantity) =>
                                                form.setData(
                                                    'quantity',
                                                    quantity,
                                                )
                                            }
                                        />
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={form.processing || !inStock}
                                        className="h-10 rounded-lg bg-neutral-950 px-6 text-sm font-medium text-white hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {form.processing
                                            ? 'Adding...'
                                            : 'Add to cart'}
                                    </button>
                                </div>

                                {(form.errors.quantity ||
                                    form.errors.product_id) && (
                                    <p className="mt-3 text-sm text-red-600">
                                        {form.errors.quantity ??
                                            form.errors.product_id}
                                    </p>
                                )}
                            </form>
                        )}

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
