import { Link } from '@inertiajs/react';
import { Price } from '@/components/price';
import type { CatalogProduct } from '@/types';

type ProductCardProps = {
    product: CatalogProduct;
};

export function ProductCard({ product }: ProductCardProps) {
    const inStock = product.stock_quantity > 0;

    return (
        <article className="overflow-hidden rounded-xl border bg-white">
            <Link href={`/products/${product.slug}`} className="block">
                <div className="aspect-square bg-neutral-100">
                    {product.image_url ? (
                        <img
                            src={product.image_url}
                            alt={product.image_alt ?? product.name}
                            loading="lazy"
                            className="h-full w-full object-contain"
                        />
                    ) : (
                        <div className="flex h-full items-center justify-center text-sm text-neutral-400">
                            No image
                        </div>
                    )}
                </div>

                <div className="space-y-2 p-4">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            {product.category && (
                                <p className="text-xs tracking-wide text-neutral-500 uppercase">
                                    {product.category.name}
                                </p>
                            )}

                            <h2 className="font-medium text-neutral-950">
                                {product.name}
                            </h2>
                        </div>

                        {product.is_featured && (
                            <span className="rounded-full bg-neutral-950 px-2 py-1 text-xs text-white">
                                Featured
                            </span>
                        )}
                    </div>

                    <Price amount={product.price} className="font-semibold" />

                    <p
                        className={
                            inStock
                                ? 'text-sm text-emerald-700'
                                : 'text-sm text-red-600'
                        }
                    >
                        {inStock ? 'In stock' : 'Out of stock'}
                    </p>
                </div>
            </Link>
        </article>
    );
}
