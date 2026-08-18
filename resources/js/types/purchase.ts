import type { Paginated } from './master';

/**
 * Bentuk props modul purchase yang dikirim controller → halaman Inertia.
 * Uang tetap string decimal (konvensi project); format tampilan via
 * formatCurrency.
 */

export type PurchaseOrderStatus = 'draft' | 'ordered' | 'received' | 'paid' | 'cancelled';

export type VendorInvoiceStatus = 'unpaid' | 'partial' | 'paid' | 'void';

export type VendorPaymentMethod = 'bank_transfer' | 'cash';

/** Opsi vendor untuk form PO ( hanya vendor aktif). */
export type PurchaseVendorOption = {
    id: number;
    name: string;
};

/** Opsi produk untuk form PO (harga beli terakhir jadi default unit_cost). */
export type PurchaseProductOption = {
    id: number;
    sku: string;
    name: string;
    cost_price: string;
    unit: { abbreviation: string; allows_fraction: boolean } | null;
};

export type PurchaseOrderListItem = {
    id: number;
    po_number: string;
    order_date: string;
    expected_date: string | null;
    status: PurchaseOrderStatus;
    grand_total: string;
    vendor: { id: number; name: string } | null;
};

export type PurchaseOrderItemRow = {
    id: number;
    qty: string;
    unit_cost: string;
    subtotal: string;
    product: {
        id: number;
        sku: string;
        name: string;
        unit?: { abbreviation: string; allows_fraction: boolean } | null;
    } | null;
};

export type PurchaseOrderDetail = PurchaseOrderListItem & {
    subtotal: string;
    tax: string;
    notes: string | null;
    vendor: { id: number; name: string; email: string | null; phone: string | null; address: string | null } | null;
    items: PurchaseOrderItemRow[];
    vendorInvoice: {
        id: number;
        vendor_invoice_number: string;
        invoice_date: string;
        due_date: string | null;
        amount: string;
        amount_paid: string;
        status: VendorInvoiceStatus;
        payments: Array<{
            id: number;
            amount: string;
            method: VendorPaymentMethod;
            reference_no: string | null;
            paid_at: string;
            notes: string | null;
        }>;
    } | null;
};

export type PaginatedPurchaseOrders = Paginated<PurchaseOrderListItem>;
