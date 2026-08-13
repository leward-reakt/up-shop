import { Head } from '@inertiajs/react';
import { CollectionsSection } from '@/components/landing-pages/fashion-editorial/collections-section';
import { FinalCtaSection } from '@/components/landing-pages/fashion-editorial/final-cta-section';
import { HeroSection } from '@/components/landing-pages/fashion-editorial/hero-section';
import { NewArrivalsSection } from '@/components/landing-pages/fashion-editorial/new-arrivals-section';
import { SignatureSection } from '@/components/landing-pages/fashion-editorial/signature-section';
import { StorySection } from '@/components/landing-pages/fashion-editorial/story-section';
import type {
    FashionEditorialCategory,
    LandingPageSections,
} from '@/components/landing-pages/fashion-editorial/types';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CatalogProduct } from '@/types';

export type { FashionEditorialCategory };

type FashionElegantLandingPageProps = {
    categories: FashionEditorialCategory[];
    featuredProducts: CatalogProduct[];
    newArrivals: CatalogProduct[];
    sections: LandingPageSections;
};

export default function FashionEditorialLandingPage({
    categories,
    featuredProducts,
    newArrivals,
    sections,
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

            {sections.hero && (
                <HeroSection
                    section={sections.hero}
                    fallbackProduct={heroProduct}
                />
            )}

            {sections.collections && (
                <CollectionsSection
                    section={sections.collections}
                    categories={categories}
                />
            )}

            {sections.new_arrivals && (
                <NewArrivalsSection
                    section={sections.new_arrivals}
                    products={newArrivals}
                />
            )}

            {sections.story && (
                <StorySection
                    section={sections.story}
                    fallbackProduct={storyProduct}
                />
            )}

            {sections.signature && (
                <SignatureSection
                    section={sections.signature}
                    products={availableProducts}
                />
            )}

            {sections.final_cta && (
                <FinalCtaSection section={sections.final_cta} />
            )}
        </StorefrontLayout>
    );
}
