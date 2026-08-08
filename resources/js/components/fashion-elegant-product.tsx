import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Price } from '@/components/price';
import { QuantityInput } from '@/components/quantity-input';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CatalogCategory, CatalogProductDetails } from '@/types';

type FashionElegantProductProps = {
    product: CatalogProductDetails;
    categories: CatalogCategory[];
};

export default function FashionElegantProduct({
    product,
    categories,
}: FashionElegantProductProps) {
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
        <StorefrontLayout
            variant="fashion-editorial"
            navigationCategories={categories}
        >
            <Head title={product.name} />

            <div className="bg-[#f8f6f1]">
                <div className="mx-auto max-w-[1600px] px-5 py-6 sm:px-8 lg:px-14">
                    <div className="flex flex-wrap items-center gap-2 text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                        <Link
                            href="/shop"
                            className="transition-opacity hover:opacity-60"
                        >
                            Shop
                        </Link>

                        {product.category && (
                            <>
                                <span aria-hidden="true">/</span>

                                <Link
                                    href={`/shop?category=${product.category.slug}`}
                                    className="transition-opacity hover:opacity-60"
                                >
                                    {product.category.name}
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </div>

            <section className="border-t border-neutral-200 bg-[#f8f6f1]">
                <div className="mx-auto grid max-w-[1600px] gap-10 px-5 py-8 sm:px-8 sm:py-12 lg:grid-cols-[minmax(0,1.15fr)_minmax(360px,0.85fr)] lg:gap-16 lg:px-14 lg:py-16 xl:gap-24">
                    <div>
                        <div className="aspect-[4/5] overflow-hidden bg-[#ebe6df]">
                            {selectedImageUrl ? (
                                <img
                                    src={selectedImageUrl}
                                    alt={selectedImageAlt}
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                <div className="flex h-full items-center justify-center bg-gradient-to-b from-[#eee9e2] to-[#ddd5cc] px-8 text-center">
                                    <span className="text-[10px] tracking-[0.16em] text-neutral-500 uppercase">
                                        {product.name}
                                    </span>
                                </div>
                            )}
                        </div>

                        {product.images.length > 1 && (
                            <div className="mt-4 grid grid-cols-5 gap-2 sm:grid-cols-6 sm:gap-3">
                                {product.images.map((image) => {
                                    const selected =
                                        selectedImage?.id === image.id;

                                    return (
                                        <button
                                            key={image.id}
                                            type="button"
                                            aria-label={`View ${product.name} image`}
                                            aria-pressed={selected}
                                            onClick={() =>
                                                setSelectedImageId(image.id)
                                            }
                                            className={
                                                selected
                                                    ? 'aspect-[3/4] overflow-hidden border border-neutral-950 bg-[#ebe6df]'
                                                    : 'aspect-[3/4] overflow-hidden border border-transparent bg-[#ebe6df] transition-opacity hover:opacity-70'
                                            }
                                        >
                                            <img
                                                src={image.url}
                                                alt={
                                                    image.alt_text ??
                                                    product.name
                                                }
                                                className="h-full w-full object-cover"
                                            />
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    <div className="lg:sticky lg:top-8 lg:self-start">
                        {product.category && (
                            <Link
                                href={`/shop?category=${product.category.slug}`}
                                className="text-[10px] font-medium tracking-[0.18em] text-neutral-500 uppercase transition-opacity hover:opacity-60"
                            >
                                {product.category.name}
                            </Link>
                        )}

                        <h1 className="mt-4 max-w-xl font-serif text-4xl leading-[1.03] font-normal tracking-[-0.03em] sm:text-5xl lg:text-6xl">
                            {product.name}
                        </h1>

                        <p className="mt-4 text-[9px] tracking-[0.12em] text-neutral-500 uppercase">
                            SKU {product.sku}
                        </p>

                        <Price
                            amount={product.price}
                            className="mt-8 block text-lg font-normal text-neutral-950"
                        />

                        <div className="mt-6 border-t border-neutral-200 pt-5">
                            <p className="text-[10px] font-medium tracking-[0.14em] uppercase">
                                {inStock
                                    ? `${product.stock_quantity} in stock`
                                    : 'Out of stock'}
                            </p>
                        </div>

                        {inStock && (
                            <form
                                onSubmit={addToCart}
                                className="mt-6 border-y border-neutral-200 py-7"
                            >
                                <div>
                                    <p className="mb-3 text-[9px] font-medium tracking-[0.14em] text-neutral-500 uppercase">
                                        Quantity
                                    </p>

                                    <QuantityInput
                                        value={form.data.quantity}
                                        max={product.stock_quantity}
                                        disabled={form.processing}
                                        onChange={(quantity) =>
                                            form.setData('quantity', quantity)
                                        }
                                    />
                                </div>

                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="mt-6 inline-flex min-h-12 w-full items-center justify-center border border-neutral-950 bg-neutral-950 px-7 text-[10px] font-medium tracking-[0.16em] text-white uppercase transition duration-300 hover:bg-transparent hover:text-neutral-950 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {form.processing
                                        ? 'Adding...'
                                        : 'Add to cart'}
                                </button>

                                {(form.errors.quantity ||
                                    form.errors.product_id) && (
                                    <p className="mt-4 text-sm text-red-700">
                                        {form.errors.quantity ??
                                            form.errors.product_id}
                                    </p>
                                )}
                            </form>
                        )}

                        {product.description && (
                            <section className="mt-8">
                                <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                                    Product details
                                </p>

                                <p className="mt-5 max-w-lg text-sm leading-7 whitespace-pre-line text-neutral-600">
                                    {product.description}
                                </p>
                            </section>
                        )}

                        <div className="mt-10 border-t border-neutral-200 pt-6">
                            <Link
                                href="/shop"
                                className="inline-flex min-h-10 items-center border-b border-neutral-950 text-[9px] font-medium tracking-[0.14em] uppercase transition-opacity hover:opacity-60"
                            >
                                Return to collection
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </StorefrontLayout>
    );
}
