import type { JournalSource } from '@/types/finance';

/**
 * Label + warna badge sumber jurnal — dipakai halaman jurnal umum & detail.
 */

type BadgeVariant = 'default' | 'secondary' | 'destructive' | 'outline';

export const journalSourceLabels: Record<JournalSource, string> = {
    sales_payment: 'Pembayaran Penjualan',
    purchase_received: 'Penerimaan Pembelian',
    purchase_payment: 'Pembayaran Vendor',
    manual: 'Manual',
};

export const journalSourceVariants: Record<JournalSource, BadgeVariant> = {
    sales_payment: 'default',
    purchase_received: 'secondary',
    purchase_payment: 'secondary',
    manual: 'outline',
};
