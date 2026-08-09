import FashionEditorialLandingPage from '@/components/landing-pages/fashion-editorial';
import type { FashionEditorialCategory } from '@/components/landing-pages/fashion-editorial';
import type { CatalogProduct } from '@/types';

type HomeProps = {
    featuredProducts: CatalogProduct[];
    newArrivals: CatalogProduct[];
    categories: FashionEditorialCategory[];
};

export default function Home({
    featuredProducts,
    newArrivals,
    categories,
}: HomeProps) {
    return (
        <FashionEditorialLandingPage
            categories={categories}
            featuredProducts={featuredProducts}
            newArrivals={newArrivals}
        />
    );
}
