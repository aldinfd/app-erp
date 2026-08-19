import * as React from 'react';
import { formatCurrency } from '@/lib/utils';
import type { DashboardSalesChartPoint } from '@/types';

type SalesChartProps = {
    data: DashboardSalesChartPoint[];
};

/** Label singkat bulan ('Agu') dari kunci 'YYYY-MM'. */
function monthLabel(month: string): string {
    return new Date(`${month}-01T00:00:00`).toLocaleDateString('id-ID', { month: 'short' });
}

/** Label bulan + tahun ('Agu 2026') untuk tooltip & tabel sr-only. */
function monthYearLabel(month: string): string {
    return new Date(`${month}-01T00:00:00`).toLocaleDateString('id-ID', {
        month: 'short',
        year: 'numeric',
    });
}

/** Tick sumbu Y versi ringkas: Rp 500 rb / Rp 1,5 jt. */
function formatCompactCurrency(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        notation: 'compact',
        maximumFractionDigits: 1,
    }).format(value);
}

/**
 * Batas atas skala dibulatkan ke angka "mulus" (1/2/5 × 10^p) supaya
 * tick sumbu-Y (0, setengah, maksimum) berupa angka bulat.
 */
function niceMax(value: number): number {
    const base = 10 ** Math.floor(Math.log10(value));

    for (const step of [1, 2, 5, 10]) {
        if (step * base >= value) {
            return step * base;
        }
    }

    return 10 * base;
}

/**
 * Grafik batang revenue per bulan (6 bulan terakhir) — komponen ringan
 * tanpa library chart. Satu series: warna biru gelap di mode terang,
 * biru terang di mode gelap (lolos kontras 3:1 di kedua permukaan).
 * Nilai tiap bulan tersedia via tooltip hover/fokus dan tabel sr-only.
 */
export function SalesChart({ data }: SalesChartProps) {
    const [activeIndex, setActiveIndex] = React.useState<number | null>(null);

    const maxRevenue = Math.max(...data.map((point) => point.revenue));
    const lastIndex = data.length - 1;

    if (maxRevenue <= 0) {
        return (
            <p className="flex h-56 items-center justify-center text-sm text-neutral-500">
                Belum ada penjualan 6 bulan terakhir.
            </p>
        );
    }

    const scale = niceMax(maxRevenue);

    return (
        <div>
            <div className="relative h-56">
                <span className="absolute top-0 left-0 -translate-y-1/2 text-xs text-neutral-500">
                    {formatCompactCurrency(scale)}
                </span>
                <span className="absolute top-1/2 left-0 -translate-y-1/2 text-xs text-neutral-500">
                    {formatCompactCurrency(scale / 2)}
                </span>
                <span className="absolute bottom-0 left-0 translate-y-1/2 text-xs text-neutral-500">0</span>

                <div className="absolute inset-y-0 right-0 left-12">
                    <div className="absolute top-0 right-0 left-0 h-px bg-neutral-200 dark:bg-neutral-800" />
                    <div className="absolute top-1/2 right-0 left-0 h-px bg-neutral-200 dark:bg-neutral-800" />
                    <div className="absolute right-0 bottom-0 left-0 h-px bg-neutral-300 dark:bg-neutral-700" />

                    <div className="absolute inset-0 flex items-end gap-2">
                        {data.map((point, index) => {
                            const heightPercent = Math.round((point.revenue / scale) * 100);
                            const isActive = activeIndex === index;

                            return (
                                <button
                                    key={point.month}
                                    type="button"
                                    className="group relative flex h-full flex-1 cursor-pointer flex-col items-center justify-end bg-transparent p-0"
                                    aria-label={`${monthYearLabel(point.month)}: ${formatCurrency(point.revenue)}`}
                                    onMouseEnter={() => setActiveIndex(index)}
                                    onMouseLeave={() => setActiveIndex(null)}
                                    onFocus={() => setActiveIndex(index)}
                                    onBlur={() => setActiveIndex(null)}
                                >
                                    {isActive && (
                                        <span className="pointer-events-none absolute left-1/2 z-10 -translate-x-1/2 rounded-md border bg-background px-2 py-1 text-xs whitespace-nowrap shadow-sm"
                                            style={{ bottom: `calc(${heightPercent}% + 8px)` }}
                                        >
                                            <span className="font-medium">{formatCurrency(point.revenue)}</span>{' '}
                                            <span className="text-neutral-500">{monthYearLabel(point.month)}</span>
                                        </span>
                                    )}

                                    {!isActive && index === lastIndex && (
                                        <span className="pointer-events-none absolute left-1/2 -translate-x-1/2 text-xs font-medium whitespace-nowrap"
                                            style={{ bottom: `calc(${heightPercent}% + 4px)` }}
                                        >
                                            {formatCurrency(point.revenue)}
                                        </span>
                                    )}

                                    <span
                                        className={`w-6 max-w-full rounded-t bg-[#2a78d6] dark:bg-[#3987e5] ${isActive ? 'brightness-110' : ''}`}
                                        style={{ height: `${heightPercent}%` }}
                                    />
                                </button>
                            );
                        })}
                    </div>
                </div>
            </div>

            <div className="mt-2 flex gap-2 pl-12">
                {data.map((point) => (
                    <span key={point.month} className="flex-1 text-center text-xs text-neutral-500">
                        {monthLabel(point.month)}
                    </span>
                ))}
            </div>

            <table className="sr-only">
                <caption>Penjualan per bulan, 6 bulan terakhir</caption>
                <thead>
                    <tr>
                        <th scope="col">Bulan</th>
                        <th scope="col">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    {data.map((point) => (
                        <tr key={point.month}>
                            <td>{monthYearLabel(point.month)}</td>
                            <td>{formatCurrency(point.revenue)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
