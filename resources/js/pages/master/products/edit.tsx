import * as React from 'react';
import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, update } from '@/routes/products';
import { formatQty } from '@/lib/utils';
import type { Category, Product, Unit } from '@/types';

type Props = {
    product: Product;
    categories: Category[];
    units: Unit[];
};

const selectClass =
    'border-input bg-card h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30';

export default function ProductsEdit({ product, categories, units }: Props) {
    const [unitId, setUnitId] = React.useState(String(product.unit_id));

    // Reorder point hanya boleh pecahan bila satuannya bisa pecahan (mis. kg).
    const selectedUnit = units.find((unit) => String(unit.id) === unitId);
    const reorderStep = selectedUnit?.allows_fraction ? '0.01' : '1';

    return (
        <>
            <Head title={`Edit Produk — ${product.name}`} />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader title="Edit Produk" description={product.name} />

                <Form {...update.form({ product: product.id })} className="max-w-lg space-y-5 rounded-xl border bg-card p-6 shadow-xs">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="sku">SKU</Label>
                                <Input id="sku" name="sku" required defaultValue={product.sku} />
                                <InputError message={errors.sku} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama Produk</Label>
                                <Input id="name" name="name" required defaultValue={product.name} />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="category_id">Kategori (opsional)</Label>
                                <select
                                    id="category_id"
                                    name="category_id"
                                    defaultValue={product.category_id ?? ''}
                                    className={selectClass}
                                >
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
                                    <Input
                                        id="cost_price"
                                        name="cost_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        defaultValue={product.cost_price}
                                    />
                                    <InputError message={errors.cost_price} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="selling_price">Harga Jual</Label>
                                    <Input
                                        id="selling_price"
                                        name="selling_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        defaultValue={product.selling_price}
                                    />
                                    <InputError message={errors.selling_price} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="reorder_point">Reorder Point</Label>
                                <Input
                                    id="reorder_point"
                                    name="reorder_point"
                                    type="number"
                                    min="0"
                                    step={reorderStep}
                                    defaultValue={formatQty(product.reorder_point, product.unit?.allows_fraction)}
                                />
                                <InputError message={errors.reorder_point} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="image">Ganti Gambar Produk (opsional, maks 2MB)</Label>
                                {product.image_url && (
                                    <img src={product.image_url} alt={product.name} className="h-24 w-24 rounded-md object-cover" />
                                )}
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
                                <Checkbox
                                    id="is_active"
                                    name="is_active"
                                    value="1"
                                    defaultChecked={product.is_active}
                                />
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

ProductsEdit.layout = {
    breadcrumbs: [
        { title: 'Produk', href: index.url() },
        { title: 'Edit' },
    ],
};
