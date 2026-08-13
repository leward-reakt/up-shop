import { Link } from '@inertiajs/react';
import { toStorefrontHref } from '@/components/landing-pages/fashion-editorial/types';
import type { LandingPageSection } from '@/components/landing-pages/fashion-editorial/types';
import type { CatalogProduct } from '@/types';

type HeroSectionProps = {
    section: LandingPageSection;
    fallbackProduct?: CatalogProduct;
};

export function HeroSection({ section, fallbackProduct }: HeroSectionProps) {
    const imageUrl = section.image_url ?? fallbackProduct?.image_url;

    const imageAlt =
        section.image_alt ??
        fallbackProduct?.image_alt ??
        fallbackProduct?.name ??
        section.title ??
        'Storefront hero';

    const buttonHref = toStorefrontHref(section.button_url);

    return (
        <section className="relative isolate h-[calc(100svh-6rem)] max-h-[100svh] min-h-0 overflow-hidden bg-[#8b8177]">
            {imageUrl ? (
                <img
                    src={imageUrl}
                    alt={imageAlt}
                    loading="eager"
                    className="absolute inset-0 h-full w-full object-cover object-center"
                />
            ) : (
                <div className="absolute inset-0 bg-gradient-to-br from-[#a99e92] via-[#796f66] to-[#514b45]" />
            )}

            <div className="pointer-events-none absolute inset-0 bg-black/20" />
            <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/10" />

            <div className="relative z-10 mx-auto flex h-full max-w-[1600px] items-end justify-center px-5 pb-8 text-center sm:px-8 sm:pb-14 lg:px-14 lg:pb-20">
                <div className="max-w-3xl text-white">
                    {section.eyebrow && (
                        <p className="text-[10px] font-medium tracking-[0.24em] uppercase">
                            {section.eyebrow}
                        </p>
                    )}

                    {section.title && (
                        <h1 className="mt-4 font-serif text-[clamp(2.75rem,12vw,4.75rem)] leading-[0.95] font-normal tracking-[-0.035em] whitespace-pre-line sm:mt-6 sm:text-[clamp(4rem,7vw,6.5rem)]">
                            {section.title}
                        </h1>
                    )}

                    {section.body && (
                        <p className="mx-auto mt-4 max-w-xl text-sm leading-6 text-white/90 sm:mt-7 sm:text-base sm:leading-7">
                            {section.body}
                        </p>
                    )}

                    {section.button_label && buttonHref && (
                        <Link
                            href={buttonHref}
                            className="mt-6 inline-flex min-h-11 items-center justify-center border border-white bg-white px-7 text-[10px] font-medium tracking-[0.16em] text-neutral-950 uppercase transition duration-300 hover:bg-transparent hover:text-white sm:mt-9 sm:min-h-12"
                        >
                            {section.button_label}
                        </Link>
                    )}
                </div>
            </div>
        </section>
    );
}
