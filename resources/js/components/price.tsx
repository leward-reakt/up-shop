type PriceProps = {
    amount: number;
    currency?: string;
    className?: string;
};

export function Price({ amount, currency = 'PHP', className }: PriceProps) {
    const formatted = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency,
    }).format(amount / 100);

    return <span className={className}>{formatted}</span>;
}
