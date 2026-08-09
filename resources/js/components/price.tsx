import { usePage } from '@inertiajs/react';

type PriceProps = {
    amount: number;
    currency?: string;
    className?: string;
};

type PriceSharedProps = {
    store?: {
        currency?: string;
    };
};

function normalizeCurrency(currency: string | undefined): string {
    const normalized = currency?.trim().toUpperCase();

    return normalized && /^[A-Z]{3}$/.test(normalized) ? normalized : 'PHP';
}

export function Price({ amount, currency, className }: PriceProps) {
    const page = usePage();

    const sharedProps = page.props as unknown as PriceSharedProps;

    const resolvedCurrency = normalizeCurrency(
        currency ?? sharedProps.store?.currency,
    );

    const formatted = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: resolvedCurrency,
    }).format(amount / 100);

    return <span className={className}>{formatted}</span>;
}
