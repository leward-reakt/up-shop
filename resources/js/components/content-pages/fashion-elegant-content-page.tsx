import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { CatalogCategory } from '@/types';

type ContentPage = {
    title: string;
    slug: string;
    content: string;
    meta_title: string;
    meta_description: string;
};

type StorefrontStore = {
    name?: string;
    email?: string | null;
    contact_number?: string | null;
    business_address?: string | null;
};

type FashionElegantContentPageProps = {
    contentPage: ContentPage;
    store?: StorefrontStore;
    navigationCategories: CatalogCategory[];
};

type RelatedLink = {
    label: string;
    href: string;
    slug: string;
};

const clientServicesLinks: RelatedLink[] = [
    {
        label: 'Contact us',
        href: '/contact',
        slug: 'contact',
    },
    {
        label: 'Shipping',
        href: '/shipping-policy',
        slug: 'shipping-policy',
    },
    {
        label: 'Returns',
        href: '/return-refund-policy',
        slug: 'return-refund-policy',
    },
];

const legalLinks: RelatedLink[] = [
    {
        label: 'Terms & Conditions',
        href: '/terms-and-conditions',
        slug: 'terms-and-conditions',
    },
    {
        label: 'Privacy Policy',
        href: '/privacy-policy',
        slug: 'privacy-policy',
    },
];

function pageEyebrow(slug: string): string {
    if (slug === 'about') {
        return 'Our story';
    }

    if (
        slug === 'contact' ||
        slug === 'shipping-policy' ||
        slug === 'return-refund-policy'
    ) {
        return 'Client services';
    }

    return 'Legal';
}

function contentParagraphs(content: string): string[] {
    if (!content.trim()) {
        return [];
    }

    return content
        .split(/\n{2,}/)
        .map((paragraph) => paragraph.trim())
        .filter((paragraph) => paragraph !== '');
}

function ContentCopy({ content }: { content: string }) {
    const paragraphs = contentParagraphs(content);

    if (paragraphs.length === 0) {
        return (
            <p className="text-sm leading-7 text-neutral-500">
                Content is currently unavailable.
            </p>
        );
    }

    return (
        <div className="space-y-6">
            {paragraphs.map((paragraph, index) => (
                <p
                    key={index}
                    className="text-sm leading-7 whitespace-pre-line text-neutral-700 sm:text-base sm:leading-8"
                >
                    {paragraph}
                </p>
            ))}
        </div>
    );
}

function ContentPageHero({
    title,
    eyebrow,
}: {
    title: string;
    eyebrow: string;
}) {
    return (
        <section className="border-b border-neutral-300 bg-[#f8f6f1]">
            <div className="mx-auto max-w-[1600px] px-5 py-16 sm:px-8 sm:py-20 lg:px-14 lg:py-24">
                <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                    {eyebrow}
                </p>

                <h1 className="mt-5 max-w-4xl font-serif text-5xl leading-none tracking-[-0.035em] sm:text-6xl lg:text-7xl">
                    {title}
                </h1>
            </div>
        </section>
    );
}

function AboutContent({
    content,
    storeName,
}: {
    content: string;
    storeName: string;
}) {
    return (
        <section className="bg-[#f8f6f1]">
            <div className="mx-auto max-w-[1600px] px-5 py-12 sm:px-8 sm:py-16 lg:px-14 lg:py-20">
                <div className="grid border-y border-neutral-300 lg:grid-cols-2">
                    <div className="flex min-h-[320px] items-end bg-[#eee8e1] p-8 sm:min-h-[400px] sm:p-10 lg:min-h-[520px] lg:p-14">
                        <div>
                            <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                                Our story
                            </p>

                            <p className="mt-5 max-w-md font-serif text-4xl leading-[1.05] tracking-[-0.03em] sm:text-5xl">
                                {storeName}
                            </p>
                        </div>
                    </div>

                    <article className="border-t border-neutral-300 px-0 py-10 sm:py-12 lg:border-t-0 lg:border-l lg:px-12 lg:py-14 xl:px-16 xl:py-16">
                        <p className="mb-8 text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                            About
                        </p>

                        <ContentCopy content={content} />
                    </article>
                </div>
            </div>
        </section>
    );
}

function ContactDetails({ store }: { store?: StorefrontStore }) {
    const hasContactDetails = Boolean(
        store?.email || store?.contact_number || store?.business_address,
    );

    if (!hasContactDetails) {
        return (
            <p className="text-sm leading-7 text-neutral-500">
                Contact details are currently unavailable.
            </p>
        );
    }

    return (
        <div className="space-y-8">
            {store?.email && (
                <div>
                    <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                        Email
                    </p>

                    <a
                        href={`mailto:${store.email}`}
                        className="mt-2 inline-block text-sm underline decoration-neutral-400 underline-offset-4 transition-opacity hover:opacity-60"
                    >
                        {store.email}
                    </a>
                </div>
            )}

            {store?.contact_number && (
                <div>
                    <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                        Telephone
                    </p>

                    <a
                        href={`tel:${store.contact_number}`}
                        className="mt-2 inline-block text-sm underline decoration-neutral-400 underline-offset-4 transition-opacity hover:opacity-60"
                    >
                        {store.contact_number}
                    </a>
                </div>
            )}

            {store?.business_address && (
                <div>
                    <p className="text-[9px] font-medium tracking-[0.16em] text-neutral-500 uppercase">
                        Address
                    </p>

                    <p className="mt-2 max-w-sm text-sm leading-7 text-neutral-700">
                        {store.business_address}
                    </p>
                </div>
            )}
        </div>
    );
}

function ContactContent({
    content,
    store,
}: {
    content: string;
    store?: StorefrontStore;
}) {
    return (
        <section className="bg-[#f8f6f1]">
            <div className="mx-auto grid max-w-[1600px] px-5 py-12 sm:px-8 sm:py-16 lg:grid-cols-[minmax(0,1fr)_420px] lg:px-14 lg:py-20">
                <article className="border-b border-neutral-300 pb-12 lg:border-r lg:border-b-0 lg:pr-16 lg:pb-0">
                    <p className="mb-8 text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                        How can we help?
                    </p>

                    <div className="max-w-3xl">
                        <ContentCopy content={content} />
                    </div>
                </article>

                <aside className="pt-12 lg:pt-0 lg:pl-12">
                    <p className="mb-8 text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                        Contact details
                    </p>

                    <ContactDetails store={store} />
                </aside>
            </div>
        </section>
    );
}

function PolicyNavigation({
    currentSlug,
    label,
    links,
}: {
    currentSlug: string;
    label: string;
    links: RelatedLink[];
}) {
    return (
        <aside>
            <div className="lg:sticky lg:top-10">
                <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                    {label}
                </p>

                <nav
                    aria-label={`${label} navigation`}
                    className="mt-6 grid border-t border-neutral-300"
                >
                    {links.map((link) => {
                        const isCurrent = link.slug === currentSlug;

                        return (
                            <Link
                                key={link.slug}
                                href={link.href}
                                aria-current={isCurrent ? 'page' : undefined}
                                className={`border-b border-neutral-300 py-4 text-xs tracking-[0.08em] transition-opacity hover:opacity-60 ${
                                    isCurrent
                                        ? 'font-medium text-neutral-950'
                                        : 'text-neutral-600'
                                }`}
                            >
                                {link.label}
                            </Link>
                        );
                    })}
                </nav>
            </div>
        </aside>
    );
}

function PolicyContent({ contentPage }: { contentPage: ContentPage }) {
    const isLegal =
        contentPage.slug === 'privacy-policy' ||
        contentPage.slug === 'terms-and-conditions';

    const links = isLegal ? legalLinks : clientServicesLinks;

    const label = isLegal ? 'Legal' : 'Client services';

    return (
        <section className="bg-[#f8f6f1]">
            <div className="mx-auto grid max-w-[1600px] gap-12 px-5 py-12 sm:px-8 sm:py-16 lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-20 lg:px-14 lg:py-20">
                <PolicyNavigation
                    currentSlug={contentPage.slug}
                    label={label}
                    links={links}
                />

                <article className="max-w-3xl">
                    <ContentCopy content={contentPage.content} />
                </article>
            </div>
        </section>
    );
}

function CollectionCta() {
    return (
        <section className="border-t border-neutral-300 bg-[#eee8e1]">
            <div className="mx-auto max-w-[1600px] px-5 py-16 text-center sm:px-8 sm:py-20 lg:px-14 lg:py-24">
                <h2 className="font-serif text-4xl leading-tight tracking-[-0.03em] sm:text-5xl">
                    Discover the collection.
                </h2>

                <Link
                    href="/shop"
                    className="mt-8 inline-flex min-h-12 items-center justify-center border border-neutral-950 px-7 text-[10px] font-medium tracking-[0.16em] uppercase transition duration-300 hover:bg-neutral-950 hover:text-white"
                >
                    Shop all
                </Link>
            </div>
        </section>
    );
}

export default function FashionElegantContentPage({
    contentPage,
    store,
    navigationCategories,
}: FashionElegantContentPageProps) {
    const eyebrow = pageEyebrow(contentPage.slug);

    const storeName = store?.name || 'Up Shop';

    return (
        <StorefrontLayout
            variant="fashion-editorial"
            navigationCategories={navigationCategories}
        >
            <Head title={contentPage.meta_title} />

            <ContentPageHero title={contentPage.title} eyebrow={eyebrow} />

            {contentPage.slug === 'about' ? (
                <AboutContent
                    content={contentPage.content}
                    storeName={storeName}
                />
            ) : contentPage.slug === 'contact' ? (
                <ContactContent content={contentPage.content} store={store} />
            ) : (
                <PolicyContent contentPage={contentPage} />
            )}

            <CollectionCta />
        </StorefrontLayout>
    );
}
