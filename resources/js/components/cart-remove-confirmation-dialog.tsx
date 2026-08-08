import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type CartRemoveConfirmationDialogProps = {
    open: boolean;
    itemCount: number;
    itemName?: string;
    processing?: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
};

export function CartRemoveConfirmationDialog({
    open,
    itemCount,
    itemName,
    processing = false,
    onOpenChange,
    onConfirm,
}: CartRemoveConfirmationDialogProps) {
    const isSingleItem = itemCount === 1;

    const title =
        isSingleItem && itemName
            ? `Remove ${itemName}?`
            : `Remove ${itemCount} items?`;

    const description = isSingleItem
        ? 'Are you sure you want to remove this item from your cart?'
        : `Are you sure you want to remove these ${itemCount} items from your cart?`;

    const confirmLabel = isSingleItem
        ? 'Remove item'
        : `Remove ${itemCount} items`;

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                if (!processing) {
                    onOpenChange(nextOpen);
                }
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>

                    <DialogDescription>
                        {description} This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <DialogClose asChild>
                        <button
                            type="button"
                            disabled={processing}
                            className="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Cancel
                        </button>
                    </DialogClose>

                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={processing}
                        className="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {processing ? 'Removing...' : confirmLabel}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
