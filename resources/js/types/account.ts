export type AccountAddress = {
    id: number;
    label: string | null;
    recipient_name: string;
    email: string | null;
    phone: string;
    address_line_1: string;
    address_line_2: string | null;
    city: string;
    province: string;
    postal_code: string;
    country: string;
    is_default: boolean;
};

export type AccountOrderSummary = {
    id: number;
    order_number: string;
    grand_total: number;
    payment_status: string;
    payment_status_label: string;
    order_status: string;
    order_status_label: string;
    created_at: string | null;
};

export type AccountOrderItem = {
    id: number;
    product_name: string;
    sku: string;
    quantity: number;
    unit_price: number;
    subtotal: number;
};

export type AccountPayment = {
    method: string;
    method_label: string;
    status: string;
    status_label: string;
    amount: number;
    reference: string | null;
    paid_at: string | null;
};

export type AccountOrderDetails = {
    id: number;
    order_number: string;

    customer_name: string;
    customer_email: string;
    customer_phone: string;

    shipping_address: {
        address_line_1: string;
        address_line_2: string | null;
        city: string;
        province: string;
        postal_code: string;
        country: string;
    };

    shipping_method: string;
    shipping_method_label: string;

    payment_method: string;
    payment_method_label: string;

    payment_status: string;
    payment_status_label: string;

    order_status: string;
    order_status_label: string;

    items: AccountOrderItem[];
    payment: AccountPayment | null;

    subtotal: number;
    discount_total: number;
    discount_code: string | null;
    shipping_total: number;
    tax_total: number;
    grand_total: number;

    customer_notes: string | null;
    created_at: string | null;
};

export type AccountDashboardSummary = {
    total_orders: number;
    active_orders: number;
    completed_orders: number;
};
