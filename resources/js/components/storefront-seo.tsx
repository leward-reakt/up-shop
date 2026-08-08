import { Head, usePage } from '@inertiajs/react';

type ProductSeoData = {
    name: string;
    slug: string;
    description: string | null;
    image_url: string | null;
    meta_title: string | null;
    meta_description: string | null;
};

type ContentPageSeoData = {
    title: string;
    slug: string;
    content: string;
    meta_title: string;
    meta_description: string;
};

type StorefrontSeoProps = {
    store?: {
        name?: string;
    };

    seo?: {
        base_url?: string;
        indexing_enabled?: boolean;
    };

    product?: ProductSeoData;

    contentPage?: ContentPageSeoData;
};

function absoluteUrl(baseUrl: string, value: string): string {
    if (/^https?:\/\//i.test(value)) {
        return value;
    }

    const normalizedBaseUrl = baseUrl.replace(/\/+$/, '');
    const normalizedPath = value.startsWith('/') ? value : `/${value}`;

    return `${normalizedBaseUrl}${normalizedPath}`;
}

function normalizeDescription(value: string | null | undefined): string | null {
    if (!value) {
        return null;
    }

    const description = value.replace(/\s+/g, ' ').trim();

    if (!description) {
        return null;
    }

    return description.slice(0, 160);
}

export function StorefrontSeo() {
    const inertiaPage = usePage();

    const props = inertiaPage.props as unknown as StorefrontSeoProps;

    const component = inertiaPage.component;

    const storeName = props.store?.name || 'Up Shop';

    const baseUrl = props.seo?.base_url || '';

    const indexingEnabled = Boolean(props.seo?.indexing_enabled);

    const privateCommercePage =
        component.startsWith('cart/') || component.startsWith('checkout/');

    const robots =
        indexingEnabled && !privateCommercePage
            ? 'index, follow'
            : 'noindex, nofollow';

    let title = storeName;
    let description: string | null = null;
    let canonicalPath: string | null = null;
    let imageUrl: string | null = null;
    let type = 'website';

    switch (component) {
        case 'home':
            title = storeName;
            description =
                'Browse featured products and shop online from Up Shop.';
            canonicalPath = '/';
            break;

        case 'shop/index':
            title = 'Shop';
            description =
                'Browse available products, categories, prices, and current stock.';
            canonicalPath = '/shop';
            break;

        case 'shop/show':
            if (props.product) {
                title = props.product.meta_title?.trim() || props.product.name;

                description =
                    normalizeDescription(props.product.meta_description) ||
                    normalizeDescription(props.product.description) ||
                    `Shop ${props.product.name} at ${storeName}.`;

                canonicalPath = `/products/${props.product.slug}`;
                imageUrl = props.product.image_url;
                type = 'product';
            }

            break;

        case 'pages/show':
            if (props.contentPage) {
                title =
                    props.contentPage.meta_title?.trim() ||
                    props.contentPage.title;

                description =
                    normalizeDescription(props.contentPage.meta_description) ||
                    normalizeDescription(props.contentPage.content) ||
                    `Learn more about ${props.contentPage.title} at ${storeName}.`;

                canonicalPath = `/${props.contentPage.slug}`;
            }

            break;

        default:
            break;
    }

    const canonicalUrl = canonicalPath
        ? absoluteUrl(baseUrl, canonicalPath)
        : null;

    const absoluteImageUrl = imageUrl ? absoluteUrl(baseUrl, imageUrl) : null;

    const openGraphTitle =
        title === storeName ? storeName : `${title} - ${storeName}`;

    return (
        <Head>
            <meta head-key="robots" name="robots" content={robots} />

            {description ? (
                <meta
                    head-key="description"
                    name="description"
                    content={description}
                />
            ) : null}

            {canonicalUrl ? (
                <link
                    head-key="canonical"
                    rel="canonical"
                    href={canonicalUrl}
                />
            ) : null}

            {canonicalUrl ? (
                <meta head-key="og:type" property="og:type" content={type} />
            ) : null}

            {canonicalUrl ? (
                <meta
                    head-key="og:site_name"
                    property="og:site_name"
                    content={storeName}
                />
            ) : null}

            {canonicalUrl ? (
                <meta
                    head-key="og:title"
                    property="og:title"
                    content={openGraphTitle}
                />
            ) : null}

            {canonicalUrl && description ? (
                <meta
                    head-key="og:description"
                    property="og:description"
                    content={description}
                />
            ) : null}

            {canonicalUrl ? (
                <meta
                    head-key="og:url"
                    property="og:url"
                    content={canonicalUrl}
                />
            ) : null}

            {canonicalUrl && absoluteImageUrl ? (
                <meta
                    head-key="og:image"
                    property="og:image"
                    content={absoluteImageUrl}
                />
            ) : null}
        </Head>
    );
}
