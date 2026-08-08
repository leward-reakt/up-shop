type QuantityInputProps = {
    value: number;
    max: number;
    disabled?: boolean;
    variant?: 'default' | 'fashion-editorial';
    onChange: (quantity: number) => void;
};

export function QuantityInput({
    value,
    max,
    disabled = false,
    variant = 'default',
    onChange,
}: QuantityInputProps) {
    const safeMax = Math.max(1, max);

    const isFashionEditorial = variant === 'fashion-editorial';

    const normalize = (quantity: number) => {
        if (!Number.isFinite(quantity)) {
            return 1;
        }

        return Math.min(safeMax, Math.max(1, Math.trunc(quantity)));
    };

    return (
        <div
            className={
                isFashionEditorial
                    ? 'inline-flex items-center border border-neutral-300 bg-transparent'
                    : 'inline-flex items-center overflow-hidden rounded-lg border bg-white'
            }
        >
            <button
                type="button"
                disabled={disabled || value <= 1}
                onClick={() => onChange(normalize(value - 1))}
                className={
                    isFashionEditorial
                        ? 'h-10 w-10 border-r border-neutral-300 text-base transition hover:bg-[#ebe6df] disabled:cursor-not-allowed disabled:opacity-40'
                        : 'h-10 w-10 border-r text-lg hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-40'
                }
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
                className={
                    isFashionEditorial
                        ? 'h-10 w-14 border-0 bg-transparent text-center text-sm outline-none'
                        : 'h-10 w-16 border-0 bg-transparent text-center outline-none'
                }
                aria-label="Quantity"
            />

            <button
                type="button"
                disabled={disabled || value >= safeMax}
                onClick={() => onChange(normalize(value + 1))}
                className={
                    isFashionEditorial
                        ? 'h-10 w-10 border-l border-neutral-300 text-base transition hover:bg-[#ebe6df] disabled:cursor-not-allowed disabled:opacity-40'
                        : 'h-10 w-10 border-l text-lg hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-40'
                }
                aria-label="Increase quantity"
            >
                +
            </button>
        </div>
    );
}
