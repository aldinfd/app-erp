import { Head, Link } from '@inertiajs/react';
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
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="text-lg font-semibold">{entry.entry_number}</h1>
                    <Button asChild variant="outline">
                        <Link href={index.url()}>Kembali</Link>
                    </Button>
                </div>

                <div className="grid gap-2 rounded-lg border p-4 text-sm sm:grid-cols-2">
                    <div>
                        <span className="text-neutral-500">Tanggal: </span>
                        {formatDate(entry.entry_date)}
                    </div>
                    <div>
                        <span className="text-neutral-500">Sumber: </span>
                        <Badge variant={journalSourceVariants[entry.source]}>
                            {journalSourceLabels[entry.source]}
                        </Badge>
                    </div>
                    <div className="sm:col-span-2">
                        <span className="text-neutral-500">Deskripsi: </span>
                        {entry.description}
                    </div>
                    {entry.poster && (
                        <div>
                            <span className="text-neutral-500">Dibuat oleh: </span>
                            {entry.poster.name}
                        </div>
                    )}
                </div>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                            <tr>
                                <th className="px-4 py-2 font-medium">Akun</th>
                                <th className="px-4 py-2 font-medium text-right">Debit</th>
                                <th className="px-4 py-2 font-medium text-right">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            {lines.map((line) => (
                                <tr key={line.id} className="border-t">
                                    <td className="px-4 py-2">
                                        <span className="font-mono text-xs">{line.account?.code}</span> {line.account?.name}
                                    </td>
                                    <td className="px-4 py-2 text-right tabular-nums">
                                        {Number(line.debit) > 0 ? formatCurrency(line.debit) : '-'}
                                    </td>
                                    <td className="px-4 py-2 text-right tabular-nums">
                                        {Number(line.credit) > 0 ? formatCurrency(line.credit) : '-'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr className="border-t bg-neutral-50 dark:bg-neutral-900">
                                <td className="px-4 py-2 font-medium">Total</td>
                                <td className="px-4 py-2 text-right font-medium tabular-nums">{formatCurrency(totalDebit)}</td>
                                <td className="px-4 py-2 text-right font-medium tabular-nums">{formatCurrency(totalCredit)}</td>
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
