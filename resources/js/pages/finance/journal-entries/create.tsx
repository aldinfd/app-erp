import * as React from 'react';
import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency } from '@/lib/utils';
import { index, store } from '@/routes/journal-entries';
import type { JournalAccountOption } from '@/types';

type Props = {
    accounts: JournalAccountOption[];
};

type LineRow = {
    key: number;
    accountId: string;
    debit: string;
    credit: string;
};

const selectClass = 'border-input bg-background h-9 rounded-md border px-3 text-sm';

let rowKey = 0;

export default function JournalEntriesCreate({ accounts }: Props) {
    const [lines, setLines] = React.useState<LineRow[]>([{ key: ++rowKey, accountId: '', debit: '', credit: '' }]);

    const totalDebit = lines.reduce((sum, line) => sum + (Number(line.debit) || 0), 0);
    const totalCredit = lines.reduce((sum, line) => sum + (Number(line.credit) || 0), 0);
    const balanced = totalDebit > 0 && Math.abs(totalDebit - totalCredit) < 0.005;

    function updateLine(key: number, patch: Partial<LineRow>) {
        setLines((rows) => rows.map((row) => (row.key === key ? { ...row, ...patch } : row)));
    }

    function addLine() {
        setLines((rows) => [...rows, { key: ++rowKey, accountId: '', debit: '', credit: '' }]);
    }

    function removeLine(key: number) {
        setLines((rows) => (rows.length > 1 ? rows.filter((row) => row.key !== key) : rows));
    }

    return (
        <>
            <Head title="Jurnal Manual" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Jurnal Manual</h1>

                <Form {...store.form()} className="max-w-3xl space-y-4">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="entry_date">Tanggal Jurnal</Label>
                                    <Input id="entry_date" name="entry_date" type="date" required defaultValue={new Date().toISOString().slice(0, 10)} />
                                    <InputError message={errors.entry_date} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="description">Deskripsi</Label>
                                    <Input id="description" name="description" required placeholder="mis. Koreksi pencatatan biaya kirim" />
                                    <InputError message={errors.description} />
                                </div>
                            </div>

                            {/* Baris jurnal: input dinamis dikirim sebagai lines[i][…]. */}
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label>Baris Jurnal</Label>
                                    <Button type="button" variant="outline" size="sm" onClick={addLine}>
                                        + Tambah Baris
                                    </Button>
                                </div>

                                {lines.map((row, index) => (
                                    <div key={row.key} className="grid grid-cols-12 items-start gap-2 rounded-lg border p-3">
                                        <div className="col-span-12 grid gap-1 sm:col-span-6">
                                            <select
                                                name={`lines[${index}][account_id]`}
                                                required
                                                value={row.accountId}
                                                onChange={(e) => updateLine(row.key, { accountId: e.target.value })}
                                                className={selectClass}
                                            >
                                                <option value="">— Pilih akun —</option>
                                                {accounts.map((account) => (
                                                    <option key={account.id} value={account.id}>
                                                        {account.code} — {account.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={errors[`lines.${index}.account_id`]} />
                                        </div>

                                        <div className="col-span-5 grid gap-1 sm:col-span-2">
                                            <Input
                                                name={`lines[${index}][debit]`}
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="Debit"
                                                value={row.debit}
                                                onChange={(e) => updateLine(row.key, { debit: e.target.value, credit: '' })}
                                            />
                                            <InputError message={errors[`lines.${index}.debit`]} />
                                        </div>

                                        <div className="col-span-5 grid gap-1 sm:col-span-2">
                                            <Input
                                                name={`lines[${index}][credit]`}
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="Kredit"
                                                value={row.credit}
                                                onChange={(e) => updateLine(row.key, { credit: e.target.value, debit: '' })}
                                            />
                                            <InputError message={errors[`lines.${index}.credit`]} />
                                        </div>

                                        <div className="col-span-2 flex items-center justify-end pt-1 sm:col-span-2">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => removeLine(row.key)}
                                                disabled={lines.length <= 1}
                                                aria-label="Hapus baris"
                                            >
                                                ✕
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="flex items-center justify-between rounded-lg border p-4 text-sm">
                                <span className={balanced ? 'text-emerald-600' : 'text-neutral-500'}>
                                    {balanced ? '✓ Jurnal balance (Σ debit = Σ kredit)' : 'Jurnal belum balance / masih kosong'}
                                </span>
                                <span className="tabular-nums">
                                    D {formatCurrency(totalDebit)} · K {formatCurrency(totalCredit)}
                                </span>
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing || !balanced}>
                                    Simpan Jurnal
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href={index.url()}>Batal</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

JournalEntriesCreate.layout = {
    breadcrumbs: [
        { title: 'Jurnal Umum', href: index.url() },
        { title: 'Jurnal Manual' },
    ],
};
