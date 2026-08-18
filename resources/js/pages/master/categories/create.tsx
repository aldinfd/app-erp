import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store } from '@/routes/categories';
import type { Category } from '@/types';

type Props = {
    categories: Category[];
};

export default function CategoriesCreate({ categories }: Props) {
    return (
        <>
            <Head title="Tambah Kategori" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Tambah Kategori</h1>

                <Form {...store.form()} className="max-w-lg space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama Kategori</Label>
                                <Input id="name" name="name" required autoFocus placeholder="mis. Pakaian" />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="parent_id">Kategori Induk (opsional)</Label>
                                <select
                                    id="parent_id"
                                    name="parent_id"
                                    defaultValue=""
                                    className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                                >
                                    <option value="">— Tanpa induk —</option>
                                    {categories.map((category) => (
                                        <option key={category.id} value={category.id}>
                                            {category.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.parent_id} />
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

CategoriesCreate.layout = {
    breadcrumbs: [
        { title: 'Kategori', href: index.url() },
        { title: 'Tambah' },
    ],
};
