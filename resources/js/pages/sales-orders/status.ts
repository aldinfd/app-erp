import type { InvoiceStatus, PaymentStatus, SalesOrderStatus } from '@/types/sales';

/**
 * Label + warna badge status modul Sales — dipakai bersama halaman
 * index dan detail.
 */

type BadgeVariant = 'default' | 'secondary' | 'destructive' | 'outline';

export const salesOrderStatusLabels: Record<SalesOrderStatus, string> = {
    draft: 'Draft',
    confirmed: 'Menunggu Pembayaran',
    paid: 'Dibayar',
    cancelled: 'Dibatalkan',
};

export const salesOrderStatusVariants: Record<SalesOrderStatus, BadgeVariant> = {
    draft: 'outline',
    confirmed: 'secondary',
    paid: 'default',
    cancelled: 'destructive',
};

export const invoiceStatusLabels: Record<InvoiceStatus, string> = {
    unpaid: 'Belum Dibayar',
    partial: 'Dibayar Sebagian',
    paid: 'Lunas',
    void: 'Void',
};

export const invoiceStatusVariants: Record<InvoiceStatus, BadgeVariant> = {
    unpaid: 'secondary',
    partial: 'outline',
    paid: 'default',
    void: 'destructive',
};

export const paymentStatusLabels: Record<PaymentStatus, string> = {
    pending: 'Pending',
    settlement: 'Settlement',
    capture: 'Capture',
    deny: 'Ditolak',
    expire: 'Kedaluwarsa',
    cancel: 'Dibatalkan',
    refund: 'Refund',
};

export const paymentMethodLabels: Record<string, string> = {
    midtrans: 'Midtrans',
    bank_transfer: 'Transfer Bank',
    cash: 'Tunai',
};
