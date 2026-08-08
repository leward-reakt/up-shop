export type CartLineItem = {
    product_id: number;
    name: string;
    slug: string;
    sku: string;
    price: number;
    quantity: number;
    stock_quantity: number;
    line_total: number;
    image_url: string | null;
    image_alt: string | null;
    is_product_visible: boolean;
    can_update_quantity: boolean;
    is_available: boolean;
    availability_message: string | null;
};

export type CartTotals = {
    subtotal: number;
    discount_total: number;
    shipping_total: number;
    grand_total: number;
    discount_code: string | null;
    discount_error: string | null;
};
