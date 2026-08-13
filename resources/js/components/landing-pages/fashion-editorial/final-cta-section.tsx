import { Link } from '@inertiajs/react';

type FinalCtaVariables = {
    eyebrow?: string | null;
    label?: string | null;
    heading?: string | null;
    title?: string | null;
    description?: string | null;
    button_label?: string | null;
    button_text?: string | null;
    button_url?: string | null;
};

type FinalCtaSectionProps = {
    variables?: FinalCtaVariables | null;
};

export function FinalCtaSection({ variables }: FinalCtaSectionProps) {
    /*
     * Landing-page configuration may be missing for existing or partially
     * configured stores. Keep the storefront usable with safe defaults.
     */
    const eyebrow =
        variables?.eyebrow?.trim() ||
        variables?.label?.trim() ||
        'The complete wardrobe';

    const heading =
        variables?.heading?.trim() ||
        variables?.title?.trim() ||
        'Discover the collection.';

    const description =
        variables?.description?.trim() ||
        'Modern essentials and refined statement pieces designed to create an elegant, considered wardrobe.';

    const buttonLabel =
        variables?.button_label?.trim() ||
        variables?.button_text?.trim() ||
        'Shop all';

    const buttonUrl = variables?.button_url?.trim() || '/shop';

    return (
        <section className="border-t border-neutral-200 bg-[#eee8e1] px-5 py-20 sm:px-8 sm:py-24 lg:px-14 lg:py-28">
            <div className="mx-auto max-w-3xl text-center">
                <p className="text-[10px] font-medium tracking-[0.2em] text-neutral-500 uppercase">
                    {eyebrow}
                </p>

                <h2 className="mt-5 font-serif text-4xl leading-tight tracking-[-0.025em] sm:text-5xl lg:text-6xl">
                    {heading}
                </h2>

                <p className="mx-auto mt-5 max-w-xl text-sm leading-7 text-neutral-600">
                    {description}
                </p>

                <Link
                    href={buttonUrl}
                    className="mt-9 inline-flex min-h-12 items-center justify-center border border-neutral-950 px-7 text-[10px] font-medium tracking-[0.16em] uppercase transition duration-300 hover:bg-neutral-950 hover:text-white"
                >
                    {buttonLabel}
                </Link>
            </div>
        </section>
    );
}
