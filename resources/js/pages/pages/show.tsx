import { Head } from '@inertiajs/react';
import StorefrontLayout from '@/layouts/storefront-layout';

type ContentPage = {
    title: string;
    slug: string;
    content: string;
    meta_title: string;
    meta_description: string;
};

type ContentPageProps = {
    contentPage: ContentPage;
};

export default function ContentPageShow({ contentPage }: ContentPageProps) {
    return (
        <StorefrontLayout>
            <Head title={contentPage.meta_title} />

            <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
                <article className="rounded-xl border bg-white p-6 sm:p-8 lg:p-10">
                    <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                        {contentPage.title}
                    </h1>

                    {contentPage.content ? (
                        <div className="mt-8 leading-7 whitespace-pre-line text-neutral-700">
                            {contentPage.content}
                        </div>
                    ) : (
                        <p className="mt-8 text-neutral-500">
                            Content is currently unavailable.
                        </p>
                    )}
                </article>
            </div>
        </StorefrontLayout>
    );
}
