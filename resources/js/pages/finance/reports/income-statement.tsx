import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/utils';
import { balanceSheet } from '@/routes/reports';
import { excel as incomeStatementExcel } from '@/routes/reports/income-statement';
import { pdf as incomeStatementPdf } from '@/routes/reports/income-statement';
import { incomeStatement } from '@/routes/reports';
import type { ReportRow } from '@/types';

type Props = {
    report: {
        from: string;
        to: string;
        revenues: ReportRow[];
        expenses: ReportRow[];
        total_revenue: number;
        total_expense: number;
        net_income: number;
    };
};

/** Baris section laporan: daftar akun + baris total. */
function ReportSection({ title, rows, total }: { title: string; rows: ReportRow[]; total: number }) {
    return (
        <div className="overflow-hidden rounded-xl border bg-card shadow-xs">
            <div className="bg-muted/60 px-4 py-3 font-mono text-[10px] tracking-[0.14em] uppercase text-muted-foreground">
                {title}
            </div>
            <table className="ledger-table w-full text-sm">
                <tbody>
                    {rows.map((row) => (
                        <tr key={row.code}>
                            <td className="px-4 py-2.5">
                                <span className="font-mono text-xs">{row.code}</span> {row.name}
                            </td>
                            <td className="px-4 py-2.5 text-right font-mono">{formatCurrency(row.amount)}</td>
                        </tr>
                    ))}
                    {rows.length === 0 && (
                        <tr>
                            <td colSpan={2} className="px-4 py-4 text-center text-muted-foreground">
                                Tidak ada data.
                            </td>
                        </tr>
                    )}
                </tbody>
                <tfoot>
                    <tr className="border-t border-dashed bg-muted/60">
                        <td className="px-4 py-2.5 font-medium">Total {title}</td>
                        <td className="px-4 py-2.5 text-right font-mono font-medium">{formatCurrency(total)}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    );
}

export default function IncomeStatement({ report }: Props) {
    const [from, setFrom] = React.useState(report.from);
    const [to, setTo] = React.useState(report.to);

    function applyFilter(event: React.FormEvent) {
        event.preventDefault();
        router.get(incomeStatement.url(), { from: from || undefined, to: to || undefined }, { preserveState: true });
    }

    const profitable = report.net_income >= 0;

    return (
        <>
            <Head title="Laba Rugi" />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader
                    title="Laba Rugi"
                    description="Pendapatan dikurangi beban untuk satu periode."
                    actions={
                        <>
                            {/* <a> polos agar browser mengunduh file, bukan visit Inertia. */}
                            <Button asChild variant="outline">
                                <a
                                    href={incomeStatementPdf.url({
                                        query: { from: report.from, to: report.to },
                                    })}
                                >
                                    Export PDF
                                </a>
                            </Button>
                            <Button asChild variant="outline">
                                <a
                                    href={incomeStatementExcel.url({
                                        query: { from: report.from, to: report.to },
                                    })}
                                >
                                    Export Excel
                                </a>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={balanceSheet.url()}>Lihat Neraca</Link>
                            </Button>
                        </>
                    }
                />

                <form onSubmit={applyFilter} className="flex flex-wrap items-center gap-2">
                    <Input type="date" name="from" value={from} onChange={(e) => setFrom(e.target.value)} className="w-40" aria-label="Dari tanggal" />
                    <span className="text-sm text-muted-foreground">s.d.</span>
                    <Input type="date" name="to" value={to} onChange={(e) => setTo(e.target.value)} className="w-40" aria-label="Sampai tanggal" />
                    <Button type="submit" variant="outline">
                        Tampilkan
                    </Button>
                </form>

                <p className="text-sm text-muted-foreground">
                    Periode {formatDate(report.from)} – {formatDate(report.to)}
                </p>

                <ReportSection title="Pendapatan" rows={report.revenues} total={report.total_revenue} />
                <ReportSection title="Beban" rows={report.expenses} total={report.total_expense} />

                <div className="flex items-center justify-between rounded-lg border border-dashed p-4 text-sm">
                    <span className="font-medium">{profitable ? 'Laba Bersih' : 'Rugi Bersih'}</span>
                    <span className={`font-mono font-semibold ${profitable ? 'text-emerald-600' : 'text-red-600'}`}>
                        {formatCurrency(Math.abs(report.net_income))}
                    </span>
                </div>
            </div>
        </>
    );
}

IncomeStatement.layout = {
    breadcrumbs: [{ title: 'Laba Rugi', href: incomeStatement.url() }],
};
