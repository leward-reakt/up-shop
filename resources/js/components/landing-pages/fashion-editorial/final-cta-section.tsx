import { Link } from '@inertiajs/react';
import { toStorefrontHref } from '@/components/landing-pages/fashion-editorial/types';
import type { LandingPageSection } from '@/components/landing-pages/fashion-editorial/types';

type FinalCtaSectionProps = {
    section: LandingPageSection;
};

export function FinalCtaSection({ section }: FinalCtaSectionProps) {
    const buttonHref = toStorefrontHref(section.button_url);

    return (
        <section className="border-t border-neutral-200 bg-[#eee8e1] px-5 py-20 sm:px-8 sm:py-24 lg:px-14 lg:py-28">
            <div className="mx-auto max-w-3xl text-center">
                {section.eyebrow && (
                    <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                        {section.eyebrow}
                    </p>
                )}

                {section.title && (
                    <h2 className="mt-5 font-serif text-4xl leading-tight tracking-[-0.025em] whitespace-pre-line sm:text-5xl lg:text-6xl">
                        {section.title}
                    </h2>
                )}

                {section.body && (
                    <p className="mx-auto mt-5 max-w-xl text-sm leading-7 text-neutral-600">
                        {section.body}
                    </p>
                )}

                {section.button_label && buttonHref && (
                    <Link
                        href={buttonHref}
                        className="mt-9 inline-flex min-h-12 items-center justify-center border border-neutral-950 px-7 text-[10px] font-medium tracking-[0.16em] uppercase transition duration-300 hover:bg-neutral-950 hover:text-white"
                    >
                        {section.button_label}
                    </Link>
                )}
            </div>
        </section>
    );
}
