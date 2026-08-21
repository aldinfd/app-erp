import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/utils';
import { index, create, show } from '@/routes/journal-entries';
import type { JournalSource, PaginatedJournalEntries } from '@/types';
import { journalSourceLabels, journalSourceVariants } from '../source';

type Props = {
    entries: PaginatedJournalEntries;
    sources: JournalSource[];
    filters: { q?: string; from?: string; to?: string; source?: string };
};

/** Total debit satu entry — dipakai kolom nominal di tabel. */
function entryTotal(lines: { debit: string }[] | undefined): number {
    return (lines ?? []).reduce((sum, line) => sum + Number(line.debit), 0);
}

/** Gaya <select> natif — selaras dengan fokus manila komponen Input. */
const selectClass =
    'border-input bg-card h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30';

export default function JournalEntriesIndex({ entries, sources, filters }: Props) {
    const [q, setQ] = React.useState(filters.q ?? '');
    const [from, setFrom] = React.useState(filters.from ?? '');
    const [to, setTo] = React.useState(filters.to ?? '');
    const [source, setSource] = React.useState(filters.source ?? '');

    function applyFilter(event: React.FormEvent) {
        event.preventDefault();
        router.get(
            index.url(),
            {
                q: q || undefined,
                from: from || undefined,
                to: to || undefined,
                source: source || undefined,
            },
            { preserveState: true },
        );
    }

    return (
        <>
            <Head title="Jurnal Umum" />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader
                    title="Jurnal Umum"
                    description="Semua catatan keuangan — otomatis dari transaksi atau input manual."
                    actions={
                        <Button asChild>
                            <Link href={create.url()}>+ Jurnal Manual</Link>
                        </Button>
                    }
                />

                <form onSubmit={applyFilter} className="flex flex-wrap items-center gap-2">
                    <Input
                        name="q"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari nomor jurnal / deskripsi…"
                        className="max-w-xs"
                    />
                    <Input type="date" name="from" value={from} onChange={(e) => setFrom(e.target.value)} className="w-40" aria-label="Dari tanggal" />
                    <Input type="date" name="to" value={to} onChange={(e) => setTo(e.target.value)} className="w-40" aria-label="Sampai tanggal" />
                    <select
                        name="source"
                        value={source}
                        onChange={(e) => setSource(e.target.value)}
                        className={selectClass}
                    >
                        <option value="">Semua sumber</option>
                        {sources.map((source) => (
                            <option key={source} value={source}>
                                {journalSourceLabels[source]}
                            </option>
                        ))}
                    </select>
                    <Button type="submit" variant="outline">
                        Filter
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-xl border bg-card shadow-xs">
                    <table className="ledger-table w-full text-sm">
                        <thead className="text-left">
                            <tr>
                                <th className="px-4 py-2.5">Nomor Jurnal</th>
                                <th className="px-4 py-2.5">Tanggal</th>
                                <th className="px-4 py-2.5">Deskripsi</th>
                                <th className="px-4 py-2.5">Sumber</th>
                                <th className="px-4 py-2.5 text-right">Total Debit</th>
                                <th className="px-4 py-2.5">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {entries.data.map((entry) => (
                                <tr key={entry.id}>
                                    <td className="px-4 py-2.5 font-mono text-xs font-medium">{entry.entry_number}</td>
                                    <td className="px-4 py-2.5 whitespace-nowrap">{formatDate(entry.entry_date)}</td>
                                    <td className="px-4 py-2.5">{entry.description}</td>
                                    <td className="px-4 py-2.5">
                                        <Badge variant={journalSourceVariants[entry.source]}>
                                            {journalSourceLabels[entry.source]}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-2.5 text-right font-mono whitespace-nowrap">
                                        {formatCurrency(entryTotal(entry.lines))}
                                    </td>
                                    <td className="px-4 py-2.5">
                                        <Button asChild variant="outline" size="sm">
                                            <Link href={show.url({ journal_entry: entry.id })}>Detail</Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                            {entries.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                        Tidak ada jurnal.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination items={entries} />
            </div>
        </>
    );
}

JournalEntriesIndex.layout = {
    breadcrumbs: [{ title: 'Jurnal Umum', href: index.url() }],
};
