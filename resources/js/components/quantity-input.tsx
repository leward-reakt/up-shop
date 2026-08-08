type QuantityInputProps = {
    value: number;
    max: number;
    disabled?: boolean;
    onChange: (quantity: number) => void;
};

export function QuantityInput({
    value,
    max,
    disabled = false,
    onChange,
}: QuantityInputProps) {
    const safeMax = Math.max(1, max);

    const normalize = (quantity: number) => {
        if (!Number.isFinite(quantity)) {
            return 1;
        }

        return Math.min(safeMax, Math.max(1, Math.trunc(quantity)));
    };

    return (
        <div className="inline-flex items-center overflow-hidden rounded-lg border bg-white">
            <button
                type="button"
                disabled={disabled || value <= 1}
                onClick={() => onChange(normalize(value - 1))}
                className="h-10 w-10 border-r text-lg hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-40"
                aria-label="Decrease quantity"
            >
                −
            </button>

            <input
                type="number"
                min={1}
                max={safeMax}
                value={value}
                disabled={disabled}
                onChange={(event) =>
                    onChange(normalize(Number(event.target.value)))
                }
                className="h-10 w-16 border-0 bg-transparent text-center outline-none"
                aria-label="Quantity"
            />

            <button
                type="button"
                disabled={disabled || value >= safeMax}
                onClick={() => onChange(normalize(value + 1))}
                className="h-10 w-10 border-l text-lg hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-40"
                aria-label="Increase quantity"
            >
                +
            </button>
        </div>
    );
}
