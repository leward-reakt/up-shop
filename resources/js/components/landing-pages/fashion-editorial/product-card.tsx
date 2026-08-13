import { Link } from '@inertiajs/react';
import { Price } from '@/components/price';
import type { CatalogProduct } from '@/types';

export function ProductImage({
    product,
    loading = 'lazy',
}: {
    product: CatalogProduct;
    loading?: 'eager' | 'lazy';
}) {
    return (
        <div className="overflow-hidden bg-[#ebe6df]">
            {product.image_url ? (
                <img
                    src={product.image_url}
                    alt={product.image_alt ?? product.name}
                    loading={loading}
                    decoding="async"
                    className="block h-auto w-full transition-opacity duration-300 group-hover:opacity-95"
                />
            ) : (
                <div className="flex aspect-[3/4] items-center justify-center bg-gradient-to-b from-[#eee9e2] to-[#ddd5cc] px-6 text-center">
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
        <div className="mt-4 px-1 sm:px-2">
            {product.category && (
                <p className="mb-1 text-[10px] tracking-[0.14em] text-neutral-500 uppercase">
                    {product.category.name}
                </p>
            )}

            <div className="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-5">
                <h3 className="min-w-0 text-sm font-medium text-neutral-950">
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

export function ElegantProductCard({ product }: { product: CatalogProduct }) {
    return (
        <article className="group min-w-0">
            <Link href={`/products/${product.slug}`} className="block">
                <ProductImage product={product} />

                <ProductDetails product={product} />
            </Link>
        </article>
    );
}
