import { Link } from '@inertiajs/react';
import type { FashionEditorialCategory } from '@/components/landing-pages/fashion-editorial/types';

type CollectionsSectionProps = {
    categories: FashionEditorialCategory[];
};

function CategoryPlaceholder({ name }: { name: string }) {
    return (
        <div className="flex h-full items-center justify-center bg-gradient-to-b from-[#ede7df] to-[#d9d0c6] px-6 text-center">
            <span className="font-serif text-3xl text-neutral-700">{name}</span>
        </div>
    );
}

export function CollectionsSection({ categories }: CollectionsSectionProps) {
    const collectionCategories = categories.slice(0, 3);

    if (collectionCategories.length === 0) {
        return null;
    }

    const gridClass =
        collectionCategories.length === 1
            ? 'md:grid-cols-1 md:max-w-xl'
            : collectionCategories.length === 2
              ? 'md:grid-cols-2 md:max-w-5xl'
              : 'md:grid-cols-3';

    return (
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
                        Discover a considered selection of pieces designed for
                        an elegant and versatile wardrobe.
                    </p>
                </header>

                <div
                    className={`mx-auto mt-12 grid gap-5 sm:gap-6 ${gridClass}`}
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
                                            className="h-full w-full object-contain object-center"
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
    );
}
