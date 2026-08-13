import { Head } from '@inertiajs/react';
import { CollectionsSection } from '@/components/landing-pages/fashion-editorial/collections-section';
import { FinalCtaSection } from '@/components/landing-pages/fashion-editorial/final-cta-section';
import { HeroSection } from '@/components/landing-pages/fashion-editorial/hero-section';
import { NewArrivalsSection } from '@/components/landing-pages/fashion-editorial/new-arrivals-section';
import { SignatureSection } from '@/components/landing-pages/fashion-editorial/signature-section';
import { StorySection } from '@/components/landing-pages/fashion-editorial/story-section';
import type { FashionEditorialCategory } from '@/components/landing-pages/fashion-editorial/types';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CatalogProduct } from '@/types';

export type { FashionEditorialCategory };

type FashionElegantLandingPageProps = {
    categories: FashionEditorialCategory[];
    featuredProducts: CatalogProduct[];
    newArrivals: CatalogProduct[];
    heroImageUrl: string | null;
};

export default function FashionEditorialLandingPage({
    categories,
    featuredProducts,
    newArrivals,
    heroImageUrl,
}: FashionElegantLandingPageProps) {
    const availableProducts =
        featuredProducts.length > 0 ? featuredProducts : newArrivals;

    const heroProduct =
        availableProducts.find((product) => product.image_url !== null) ??
        newArrivals.find((product) => product.image_url !== null) ??
        availableProducts[0] ??
        newArrivals[0];

    const storyProduct =
        [...newArrivals, ...availableProducts].find(
            (product) =>
                product.id !== heroProduct?.id && product.image_url !== null,
        ) ??
        availableProducts[1] ??
        newArrivals[1] ??
        heroProduct;

    return (
        <StorefrontLayout
            variant="fashion-editorial"
            navigationCategories={categories}
        >
            <Head title="Home" />

            <HeroSection imageUrl={heroImageUrl} product={heroProduct} />

            <CollectionsSection categories={categories} />

            <NewArrivalsSection products={newArrivals} />

            <StorySection product={storyProduct} />

            <SignatureSection products={availableProducts} />

            <FinalCtaSection />
        </StorefrontLayout>
    );
}
