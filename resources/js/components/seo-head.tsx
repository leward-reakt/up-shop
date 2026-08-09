import { Head, usePage } from '@inertiajs/react';

type SeoSharedProps = {
    seo?: {
        base_url?: string;
    };
    store?: {
        logo_url?: string | null;
    };
};

type SeoHeadProps = {
    title: string;
    description?: string | null;
    canonicalPath: string;
    image?: string | null;
};

function toAbsoluteUrl(baseUrl: string, value: string): string {
    const normalizedValue = value.trim();

    if (/^https?:\/\//i.test(normalizedValue)) {
        return normalizedValue;
    }

    const normalizedBaseUrl = baseUrl.replace(/\/+$/, '');

    const normalizedPath = normalizedValue.startsWith('/')
        ? normalizedValue
        : `/${normalizedValue}`;

    return `${normalizedBaseUrl}${normalizedPath}`;
}

export function SeoHead({
    title,
    description,
    canonicalPath,
    image,
}: SeoHeadProps) {
    const page = usePage();

    const sharedProps = page.props as unknown as SeoSharedProps;

    const baseUrl = sharedProps.seo?.base_url ?? '';

    const canonicalUrl = toAbsoluteUrl(baseUrl, canonicalPath);

    const normalizedDescription = description?.trim() || null;

    const imageSource =
        image?.trim() || sharedProps.store?.logo_url?.trim() || null;

    const imageUrl = imageSource ? toAbsoluteUrl(baseUrl, imageSource) : null;

    return (
        <Head title={title}>
            {normalizedDescription && (
                <meta
                    head-key="description"
                    name="description"
                    content={normalizedDescription}
                />
            )}

            <link head-key="canonical" rel="canonical" href={canonicalUrl} />

            <meta head-key="og:title" property="og:title" content={title} />

            {normalizedDescription && (
                <meta
                    head-key="og:description"
                    property="og:description"
                    content={normalizedDescription}
                />
            )}

            <meta head-key="og:url" property="og:url" content={canonicalUrl} />

            {imageUrl && (
                <meta
                    head-key="og:image"
                    property="og:image"
                    content={imageUrl}
                />
            )}
        </Head>
    );
}
