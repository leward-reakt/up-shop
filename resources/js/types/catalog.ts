export type CatalogCategory = {
    id: number;
    name: string;
    slug: string;
};

export type CatalogImage = {
    id: number;
    url: string;
    alt_text: string | null;
};

export type CatalogProduct = {
    id: number;
    name: string;
    slug: string;
    sku: string;
    price: number;
    stock_quantity: number;
    is_featured: boolean;
    category: CatalogCategory | null;
    image_url: string | null;
    image_alt: string | null;
};

export type CatalogProductDetails = CatalogProduct & {
    description: string | null;
    images: CatalogImage[];
};

export type CatalogFilters = {
    search: string;
    category: string;
    min_price: string;
    max_price: string;
    availability: string;
    sort: string;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};
