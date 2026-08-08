import type { CatalogCategory } from '@/types';

export type FashionEditorialCategory = CatalogCategory & {
    image_url: string | null;
    image_alt: string | null;
};
