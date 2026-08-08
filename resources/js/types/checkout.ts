import type { AccountAddress } from './account';

export type CheckoutItem = {
    product_id: number;
    name: string;
    slug: string;
    quantity: number;
    unit_price: number;
    line_total: number;
    image_url: string | null;
};

export type CheckoutTotals = {
    subtotal: number;
    discount_total: number;
    discount_code: string | null;
    shipping_total: number;
    tax_total: number;
    grand_total: number;
};

export type CheckoutOption = {
    value: string;
    label: string;
};

export type CheckoutCustomer = {
    name: string;
    email: string;
    phone: string;
};

export type CheckoutAddress = AccountAddress;

export type CheckoutFormData = {
    customer_name: string;
    customer_email: string;
    customer_phone: string;

    shipping_address_id: number | null;
    shipping_address_line_1: string;
    shipping_address_line_2: string;
    shipping_city: string;
    shipping_province: string;
    shipping_postal_code: string;

    shipping_method: string;
    payment_method: string;

    customer_notes: string;
};

export type CheckoutOrderItem = {
    product_name: string;
    sku: string;
    quantity: number;
    unit_price: number;
    subtotal: number;
};

export type CheckoutOrder = {
    order_number: string;

    customer_name: string;
    customer_email: string;

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

    payment_reference: string | null;

    items: CheckoutOrderItem[];

    subtotal: number;
    discount_total: number;
    discount_code: string | null;
    shipping_total: number;
    tax_total: number;
    grand_total: number;

    created_at: string | null;
};
