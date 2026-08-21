import { Head, Link } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate } from '@/lib/utils';
import { index } from '@/routes/journal-entries';
import type { JournalEntry } from '@/types';
import { journalSourceLabels, journalSourceVariants } from '../source';

type Props = {
    entry: JournalEntry;
};

export default function JournalEntriesShow({ entry }: Props) {
    const lines = entry.lines ?? [];
    const totalDebit = lines.reduce((sum, line) => sum + Number(line.debit), 0);
    const totalCredit = lines.reduce((sum, line) => sum + Number(line.credit), 0);

    return (
        <>
            <Head title={`Jurnal ${entry.entry_number}`} />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader
                    title={entry.entry_number}
                    description="Jurnal Umum"
                    actions={
                        <Button asChild variant="outline">
                            <Link href={index.url()}>Kembali</Link>
                        </Button>
                    }
                />

                <div className="grid gap-2 rounded-xl border bg-card p-4 text-sm shadow-xs sm:grid-cols-2">
                    <div>
                        <span className="text-muted-foreground">Tanggal: </span>
                        {formatDate(entry.entry_date)}
                    </div>
                    <div>
                        <span className="text-muted-foreground">Sumber: </span>
                        <Badge variant={journalSourceVariants[entry.source]}>
                            {journalSourceLabels[entry.source]}
                        </Badge>
                    </div>
                    <div className="sm:col-span-2">
                        <span className="text-muted-foreground">Deskripsi: </span>
                        {entry.description}
                    </div>
                    {entry.poster && (
                        <div>
                            <span className="text-muted-foreground">Dibuat oleh: </span>
                            {entry.poster.name}
                        </div>
                    )}
                </div>

                <div className="overflow-x-auto rounded-xl border bg-card shadow-xs">
                    <table className="ledger-table w-full text-sm">
                        <thead className="text-left">
                            <tr>
                                <th className="px-4 py-2.5">Akun</th>
                                <th className="px-4 py-2.5 text-right">Debit</th>
                                <th className="px-4 py-2.5 text-right">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            {lines.map((line) => (
                                <tr key={line.id}>
                                    <td className="px-4 py-2.5">
                                        <span className="font-mono text-xs">{line.account?.code}</span> {line.account?.name}
                                    </td>
                                    <td className="px-4 py-2.5 text-right font-mono">
                                        {Number(line.debit) > 0 ? formatCurrency(line.debit) : '-'}
                                    </td>
                                    <td className="px-4 py-2.5 text-right font-mono">
                                        {Number(line.credit) > 0 ? formatCurrency(line.credit) : '-'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr className="border-t border-dashed bg-muted/60">
                                <td className="px-4 py-2.5 font-medium">Total</td>
                                <td className="px-4 py-2.5 text-right font-mono font-medium">{formatCurrency(totalDebit)}</td>
                                <td className="px-4 py-2.5 text-right font-mono font-medium">{formatCurrency(totalCredit)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </>
    );
}

JournalEntriesShow.layout = {
    breadcrumbs: [
        { title: 'Jurnal Umum', href: index.url() },
        { title: 'Detail Jurnal' },
    ],
};
