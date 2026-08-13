import FashionEditorialLandingPage from '@/components/landing-pages/fashion-editorial';
import type {
    FashionEditorialCategory,
    LandingPageSections,
} from '@/components/landing-pages/fashion-editorial/types';
import type { CatalogProduct } from '@/types';

type HomeProps = {
    featuredProducts: CatalogProduct[];
    newArrivals: CatalogProduct[];
    categories: FashionEditorialCategory[];
    sections: LandingPageSections;
};

export default function Home({
    featuredProducts,
    newArrivals,
    categories,
    sections,
}: HomeProps) {
    return (
        <FashionEditorialLandingPage
            categories={categories}
            featuredProducts={featuredProducts}
            newArrivals={newArrivals}
            sections={sections}
        />
    );
}
