import { Link } from '@inertiajs/react';
import { toStorefrontHref } from '@/components/landing-pages/fashion-editorial/types';
import type { LandingPageSection } from '@/components/landing-pages/fashion-editorial/types';
import type { CatalogProduct } from '@/types';

type StorySectionProps = {
    section: LandingPageSection;
    fallbackProduct?: CatalogProduct;
};

export function StorySection({ section, fallbackProduct }: StorySectionProps) {
    const imageUrl = section.image_url ?? fallbackProduct?.image_url;

    const imageAlt =
        section.image_alt ??
        fallbackProduct?.image_alt ??
        fallbackProduct?.name ??
        section.title ??
        'Storefront story';

    const buttonHref = toStorefrontHref(section.button_url);

    return (
        <section className="bg-[#f1ece5]">
            <div className="mx-auto grid max-w-[1600px] lg:grid-cols-2">
                <div className="relative min-h-[480px] overflow-hidden bg-[#d6cec5] sm:min-h-[580px] lg:min-h-[680px]">
                    {imageUrl ? (
                        <img
                            src={imageUrl}
                            alt={imageAlt}
                            loading="lazy"
                            className="absolute inset-0 h-full w-full object-cover"
                        />
                    ) : (
                        <div className="absolute inset-0 bg-gradient-to-br from-[#ded5cb] via-[#c7baad] to-[#a89a8e]" />
                    )}
                </div>

                <div className="flex items-center px-6 py-20 sm:px-12 sm:py-24 lg:px-20">
                    <div className="max-w-lg">
                        {section.eyebrow && (
                            <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                                {section.eyebrow}
                            </p>
                        )}

                        {section.title && (
                            <h2 className="mt-5 font-serif text-4xl leading-[1.08] tracking-[-0.025em] whitespace-pre-line sm:text-5xl lg:text-6xl">
                                {section.title}
                            </h2>
                        )}

                        {section.body && (
                            <p className="mt-7 max-w-md text-sm leading-7 text-neutral-600 sm:text-base">
                                {section.body}
                            </p>
                        )}

                        {section.button_label && buttonHref && (
                            <Link
                                href={buttonHref}
                                className="mt-9 inline-flex min-h-11 items-center border-b border-neutral-950 text-[10px] font-medium tracking-[0.15em] uppercase transition-opacity hover:opacity-60"
                            >
                                {section.button_label}
                            </Link>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}
