/**
 * Bentuk props modul Reporting & Export (plan Phase 8) — laporan penjualan
 * dan kartu stok. Laba rugi & neraca memakai types di finance.ts.
 */

export type SalesReportOrder = {
    order_number: string;
    order_date: string;
    customer_name: string;
    status: string;
    subtotal: number;
    tax: number;
    shipping: number;
    grand_total: number;
};

export type SalesReport = {
    from: string;
    to: string;
    orders: SalesReportOrder[];
    total_orders: number;
    total_subtotal: number;
    total_tax: number;
    total_shipping: number;
    total_grand_total: number;
};

export type StockCardLine = {
    date: string;
    type: string;
    qty: number;
    balance: number;
    reference: string | null;
    note: string | null;
    user: string | null;
};

export type StockCard = {
    product: {
        id: number;
        sku: string;
        name: string;
        unit: string;
        allows_fraction: boolean;
    };
    from: string;
    to: string;
    opening: number;
    closing: number;
    total_in: number;
    total_out: number;
    lines: StockCardLine[];
};

export type ProductOption = {
    id: number;
    sku: string;
    name: string;
};
