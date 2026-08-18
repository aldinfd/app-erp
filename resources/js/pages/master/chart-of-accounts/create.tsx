import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store } from '@/routes/chart-of-accounts';
import type { ChartOfAccount } from '@/types';

type Props = {
    accounts: ChartOfAccount[];
    types: string[];
};

const TYPE_LABELS: Record<string, string> = {
    asset: 'Aset',
    liability: 'Liabilitas',
    equity: 'Ekuitas',
    revenue: 'Pendapatan',
    expense: 'Beban',
};

const selectClass = 'border-input bg-background h-9 rounded-md border px-3 text-sm';

export default function ChartOfAccountsCreate({ accounts, types }: Props) {
    return (
        <>
            <Head title="Tambah Akun" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Tambah Akun</h1>

                <Form {...store.form()} className="max-w-lg space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="code">Kode Akun</Label>
                                <Input id="code" name="code" required autoFocus placeholder="mis. 1-1100" />
                                <InputError message={errors.code} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama Akun</Label>
                                <Input id="name" name="name" required placeholder="mis. Kas Kecil" />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="type">Tipe</Label>
                                <select id="type" name="type" required defaultValue="" className={selectClass}>
                                    <option value="">— Pilih tipe —</option>
                                    {types.map((type) => (
                                        <option key={type} value={type}>
                                            {TYPE_LABELS[type] ?? type}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.type} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="parent_id">Akun Induk (opsional)</Label>
                                <select id="parent_id" name="parent_id" defaultValue="" className={selectClass}>
                                    <option value="">— Tanpa induk —</option>
                                    {accounts.map((account) => (
                                        <option key={account.id} value={account.id}>
                                            {account.code} — {account.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.parent_id} />
                            </div>

                            <div className="flex items-center space-x-3">
                                {/* Hidden 0 + checkbox 1 agar nilai tetap terkirim saat tidak dicentang. */}
                                <input type="hidden" name="is_postable" value="0" />
                                <Checkbox id="is_postable" name="is_postable" value="1" defaultChecked />
                                <Label htmlFor="is_postable">Bisa di-jurnal (postable)</Label>
                            </div>

                            <div className="flex items-center space-x-3">
                                <input type="hidden" name="is_active" value="0" />
                                <Checkbox id="is_active" name="is_active" value="1" defaultChecked />
                                <Label htmlFor="is_active">Aktif</Label>
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Simpan
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

ChartOfAccountsCreate.layout = {
    breadcrumbs: [
        { title: 'Chart of Accounts', href: index.url() },
        { title: 'Tambah' },
    ],
};
