import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/utils';
import { balanceSheet, incomeStatement } from '@/routes/reports';
import { excel as balanceSheetExcel } from '@/routes/reports/balance-sheet';
import { pdf as balanceSheetPdf } from '@/routes/reports/balance-sheet';
import type { ReportRow } from '@/types';

type Props = {
    report: {
        as_of: string;
        assets: ReportRow[];
        liabilities: ReportRow[];
        equity: ReportRow[];
        current_earnings: number;
        total_assets: number;
        total_liabilities: number;
        total_equity: number;
    };
};

function ReportSection({ title, rows, total }: { title: string; rows: ReportRow[]; total: number }) {
    return (
        <div className="overflow-hidden rounded-lg border">
            <div className="bg-neutral-50 px-4 py-2 text-sm font-medium dark:bg-neutral-900">{title}</div>
            <table className="w-full text-sm">
                <tbody>
                    {rows.map((row) => (
                        <tr key={row.code} className="border-t">
                            <td className="px-4 py-2">
                                <span className="font-mono text-xs">{row.code}</span> {row.name}
                            </td>
                            <td className="px-4 py-2 text-right tabular-nums">{formatCurrency(row.amount)}</td>
                        </tr>
                    ))}
                    {rows.length === 0 && (
                        <tr>
                            <td colSpan={2} className="px-4 py-4 text-center text-neutral-500">
                                Tidak ada data.
                            </td>
                        </tr>
                    )}
                </tbody>
                <tfoot>
                    <tr className="border-t bg-neutral-50 dark:bg-neutral-900">
                        <td className="px-4 py-2 font-medium">Total {title}</td>
                        <td className="px-4 py-2 text-right font-medium tabular-nums">{formatCurrency(total)}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    );
}

export default function BalanceSheet({ report }: Props) {
    const [asOf, setAsOf] = React.useState(report.as_of);

    function applyFilter(event: React.FormEvent) {
        event.preventDefault();
        router.get(balanceSheet.url(), { as_of: asOf || undefined }, { preserveState: true });
    }

    const totalLiabilitiesAndEquity = report.total_liabilities + report.total_equity + report.current_earnings;
    const balanced = Math.abs(report.total_assets - totalLiabilitiesAndEquity) < 0.005;

    return (
        <>
            <Head title="Neraca" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-lg font-semibold">Neraca</h1>
                    <div className="flex items-center gap-2">
                        {/* <a> polos agar browser mengunduh file, bukan visit Inertia. */}
                        <Button asChild variant="outline">
                            <a href={balanceSheetPdf.url({ query: { as_of: report.as_of } })}>Export PDF</a>
                        </Button>
                        <Button asChild variant="outline">
                            <a href={balanceSheetExcel.url({ query: { as_of: report.as_of } })}>Export Excel</a>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={incomeStatement.url()}>Lihat Laba Rugi</Link>
                        </Button>
                    </div>
                </div>

                <form onSubmit={applyFilter} className="flex flex-wrap items-center gap-2">
                    <span className="text-sm text-neutral-500">Per tanggal</span>
                    <Input type="date" name="as_of" value={asOf} onChange={(e) => setAsOf(e.target.value)} className="w-40" aria-label="Per tanggal" />
                    <Button type="submit" variant="outline">
                        Tampilkan
                    </Button>
                </form>

                <p className="text-sm text-neutral-500">Posisi keuangan per {formatDate(report.as_of)}</p>

                <ReportSection title="Aset" rows={report.assets} total={report.total_assets} />

                <ReportSection title="Liabilitas" rows={report.liabilities} total={report.total_liabilities} />

                <div className="overflow-hidden rounded-lg border">
                    <div className="bg-neutral-50 px-4 py-2 text-sm font-medium dark:bg-neutral-900">Ekuitas</div>
                    <table className="w-full text-sm">
                        <tbody>
                            {report.equity.map((row) => (
                                <tr key={row.code} className="border-t">
                                    <td className="px-4 py-2">
                                        <span className="font-mono text-xs">{row.code}</span> {row.name}
                                    </td>
                                    <td className="px-4 py-2 text-right tabular-nums">{formatCurrency(row.amount)}</td>
                                </tr>
                            ))}
                            <tr className="border-t">
                                <td className="px-4 py-2">Laba Tahun Berjalan</td>
                                <td className="px-4 py-2 text-right tabular-nums">{formatCurrency(report.current_earnings)}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr className="border-t bg-neutral-50 dark:bg-neutral-900">
                                <td className="px-4 py-2 font-medium">Total Ekuitas</td>
                                <td className="px-4 py-2 text-right font-medium tabular-nums">
                                    {formatCurrency(report.total_equity + report.current_earnings)}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div className="flex items-center justify-between rounded-lg border p-4 text-sm">
                    <span className="font-medium">Liabilitas + Ekuitas</span>
                    <span className="font-semibold tabular-nums">{formatCurrency(totalLiabilitiesAndEquity)}</span>
                </div>

                <p className={`text-sm ${balanced ? 'text-emerald-600' : 'text-red-600'}`}>
                    {balanced
                        ? '✓ Neraca balance: Aset = Liabilitas + Ekuitas'
                        : 'Neraca belum balance — periksa jurnal manual.'}
                </p>
            </div>
        </>
    );
}

BalanceSheet.layout = {
    breadcrumbs: [{ title: 'Neraca', href: balanceSheet.url() }],
};
