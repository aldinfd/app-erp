import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/**
 * Format angka qty stok/reorder point: bilangan bulat tanpa desimal kecuali
 * satuannya boleh pecahan (mis. kg) — ikut flag allows_fraction dari unit.
 */
export function formatQty(value: string | number, allowsFraction = false): string {
    const num = Number(value);

    return allowsFraction ? num.toFixed(2) : String(Math.round(num));
}

/**
 * Format uang Rupiah tanpa desimal (IDR tidak pakai sen; harga seeder
 * rupiah bulat). Props tetap string decimal — konversi hanya untuk tampilan.
 */
export function formatCurrency(value: string | number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(value));
}

/**
 * Format tanggal ISO (hasil cast date/datetime Eloquent) ke "18 Agu 2026".
 */
export function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
