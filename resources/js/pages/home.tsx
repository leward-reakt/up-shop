import FashionEditorialLandingPage from '@/components/landing-pages/fashion-editorial';
import type { FashionEditorialCategory } from '@/components/landing-pages/fashion-editorial';
import type { CatalogProduct } from '@/types';

type HomeProps = {
    featuredProducts: CatalogProduct[];
    newArrivals: CatalogProduct[];
    categories: FashionEditorialCategory[];
    heroImageUrl: string | null;
};

export default function Home({
    featuredProducts,
    newArrivals,
    categories,
    heroImageUrl,
}: HomeProps) {
    return (
        <FashionEditorialLandingPage
            categories={categories}
            featuredProducts={featuredProducts}
            newArrivals={newArrivals}
            heroImageUrl={heroImageUrl}
        />
    );
}
