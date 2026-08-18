import type { Paginated } from './master';

/**
 * Bentuk props modul sales yang dikirim controller → halaman Inertia.
 * Uang tetap string decimal (konvensi project); format tampilan via
 * formatCurrency.
 */

export type SalesOrderStatus = 'draft' | 'confirmed' | 'paid' | 'cancelled';

export type InvoiceStatus = 'unpaid' | 'partial' | 'paid' | 'void';

export type PaymentStatus = 'pending' | 'settlement' | 'capture' | 'deny' | 'expire' | 'cancel' | 'refund';

/** Item katalog/checkout storefront. */
export type CatalogProduct = {
    id: number;
    sku: string;
    name: string;
    selling_price: string;
    stock_qty: string;
    image_url: string | null;
    unit?: { id: number; name: string; abbreviation: string; allows_fraction: boolean } | null;
};

/** Item yang dikirim ke server saat checkout. */
export type CheckoutLine = {
    product_id: number;
    qty: number;
};

/** Snapshot item keranjang di localStorage (display saja — server validasi ulang). */
export type StoredCartItem = {
    product_id: number;
    sku: string;
    name: string;
    price: number;
    qty: number;
    unit_abbreviation: string;
    allows_fraction: boolean;
    image_url: string | null;
};

export type SalesOrderListItem = {
    id: number;
    order_number: string;
    order_date: string;
    status: SalesOrderStatus;
    grand_total: string;
    customer: { id: number; name: string; email: string | null } | null;
};

export type SalesOrderItemRow = {
    id: number;
    qty: string;
    unit_price: string;
    subtotal: string;
    product: {
        id: number;
        sku: string;
        name: string;
        unit?: { abbreviation: string; allows_fraction: boolean } | null;
    } | null;
};

export type SalesOrderDetail = {
    id: number;
    order_number: string;
    order_date: string;
    status: SalesOrderStatus;
    subtotal: string;
    tax: string;
    shipping: string;
    grand_total: string;
    notes: string | null;
    customer: { id: number; name: string; email: string | null; phone: string | null; address: string | null } | null;
    items: SalesOrderItemRow[];
    invoice: {
        id: number;
        invoice_number: string;
        issued_date: string;
        amount: string;
        amount_paid: string;
        status: InvoiceStatus;
    } | null;
    payments: Array<{
        id: number;
        amount: string;
        method: string;
        gateway: string | null;
        gateway_ref: string | null;
        status: PaymentStatus;
        paid_at: string | null;
    }>;
};

export type PaginatedSalesOrders = Paginated<SalesOrderListItem>;
