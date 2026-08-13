import type { CatalogCategory } from '@/types';

export type FashionEditorialCategory = CatalogCategory & {
    image_url: string | null;
    image_alt: string | null;
};

export type LandingPageSectionKey =
    | 'hero'
    | 'collections'
    | 'new_arrivals'
    | 'story'
    | 'signature'
    | 'final_cta';

export type LandingPageSection = {
    key: LandingPageSectionKey;
    eyebrow: string | null;
    title: string | null;
    body: string | null;
    button_label: string | null;
    button_url: string | null;
    image_url: string | null;
    image_alt: string | null;
};

export type LandingPageSections = Partial<
    Record<LandingPageSectionKey, LandingPageSection>
>;

export function toStorefrontHref(path: string | null): string | null {
    if (!path) {
        return null;
    }

    const normalizedPath = path.trim().replace(/^\/+/, '');

    return normalizedPath === '' ? '/' : `/${normalizedPath}`;
}
