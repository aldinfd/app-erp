import * as React from 'react';
import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store } from '@/routes/products';
import type { Category, Unit } from '@/types';

type Props = {
    categories: Category[];
    units: Unit[];
};

/** Gaya <select> natif — selaras dengan fokus manila komponen Input. */
const selectClass =
    'border-input bg-card h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30';

export default function ProductsCreate({ categories, units }: Props) {
    const [unitId, setUnitId] = React.useState('');

    // Reorder point hanya boleh pecahan bila satuannya bisa pecahan (mis. kg).
    const selectedUnit = units.find((unit) => String(unit.id) === unitId);
    const reorderStep = selectedUnit?.allows_fraction ? '0.01' : '1';

    return (
        <>
            <Head title="Tambah Produk" />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader title="Tambah Produk" />

                <Form {...store.form()} className="max-w-lg space-y-5 rounded-xl border bg-card p-6 shadow-xs">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="sku">SKU</Label>
                                <Input id="sku" name="sku" required autoFocus placeholder="mis. SKU-PK-003" />
                                <InputError message={errors.sku} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama Produk</Label>
                                <Input id="name" name="name" required />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="category_id">Kategori (opsional)</Label>
                                <select id="category_id" name="category_id" defaultValue="" className={selectClass}>
                                    <option value="">— Tanpa kategori —</option>
                                    {categories.map((category) => (
                                        <option key={category.id} value={category.id}>
                                            {category.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.category_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="unit_id">Satuan</Label>
                                <select id="unit_id" name="unit_id" required value={unitId} onChange={(e) => setUnitId(e.target.value)} className={selectClass}>
                                    <option value="">— Pilih satuan —</option>
                                    {units.map((unit) => (
                                        <option key={unit.id} value={unit.id}>
                                            {unit.name} ({unit.abbreviation})
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.unit_id} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="cost_price">Harga Beli</Label>
                                    <Input id="cost_price" name="cost_price" type="number" min="0" step="0.01" />
                                    <InputError message={errors.cost_price} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="selling_price">Harga Jual</Label>
                                    <Input id="selling_price" name="selling_price" type="number" min="0" step="0.01" />
                                    <InputError message={errors.selling_price} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="reorder_point">Reorder Point</Label>
                                <Input id="reorder_point" name="reorder_point" type="number" min="0" step={reorderStep} />
                                <InputError message={errors.reorder_point} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="image">Gambar Produk (opsional, maks 2MB)</Label>
                                <input
                                    id="image"
                                    name="image"
                                    type="file"
                                    accept="image/*"
                                    className="border-input bg-card h-9 w-full rounded-md border px-3 py-1.5 text-sm shadow-xs file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-0.5 file:text-sm"
                                />
                                <InputError message={errors.image} />
                            </div>

                            <div className="flex items-center space-x-3">
                                {/* Hidden 0 + checkbox 1 agar nilai tetap terkirim saat tidak dicentang. */}
                                <input type="hidden" name="is_active" value="0" />
                                <Checkbox id="is_active" name="is_active" value="1" defaultChecked />
                                <Label htmlFor="is_active">Aktif (tampil di katalog)</Label>
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

ProductsCreate.layout = {
    breadcrumbs: [
        { title: 'Produk', href: index.url() },
        { title: 'Tambah' },
    ],
};
