import type { PurchaseOrderStatus, VendorInvoiceStatus } from '@/types/purchase';

/**
 * Label + warna badge status modul Purchase — dipakai bersama halaman
 * index dan detail.
 */

type BadgeVariant = 'default' | 'secondary' | 'destructive' | 'outline';

export const purchaseOrderStatusLabels: Record<PurchaseOrderStatus, string> = {
    draft: 'Draft',
    ordered: 'Dipesan',
    received: 'Diterima',
    paid: 'Dibayar',
    cancelled: 'Dibatalkan',
};

export const purchaseOrderStatusVariants: Record<PurchaseOrderStatus, BadgeVariant> = {
    draft: 'outline',
    ordered: 'secondary',
    received: 'secondary',
    paid: 'default',
    cancelled: 'destructive',
};

export const vendorInvoiceStatusLabels: Record<VendorInvoiceStatus, string> = {
    unpaid: 'Belum Dibayar',
    partial: 'Dibayar Sebagian',
    paid: 'Lunas',
    void: 'Void',
};

export const vendorInvoiceStatusVariants: Record<VendorInvoiceStatus, BadgeVariant> = {
    unpaid: 'secondary',
    partial: 'outline',
    paid: 'default',
    void: 'destructive',
};

export const vendorPaymentMethodLabels: Record<string, string> = {
    bank_transfer: 'Transfer Bank',
    cash: 'Tunai',
};
