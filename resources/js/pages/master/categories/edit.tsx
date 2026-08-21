import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, update } from '@/routes/categories';
import type { Category } from '@/types';

type Props = {
    category: Category;
    categories: Category[];
};

/** Gaya <select> natif — selaras dengan fokus manila komponen Input. */
const selectClass =
    'border-input bg-card h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30';

export default function CategoriesEdit({ category, categories }: Props) {
    return (
        <>
            <Head title={`Edit Kategori — ${category.name}`} />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader title="Edit Kategori" description={category.name} />

                <Form {...update.form({ category: category.id })} className="max-w-lg space-y-5 rounded-xl border bg-card p-6 shadow-xs">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama Kategori</Label>
                                <Input id="name" name="name" required defaultValue={category.name} />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="parent_id">Kategori Induk (opsional)</Label>
                                <select
                                    id="parent_id"
                                    name="parent_id"
                                    defaultValue={category.parent_id ?? ''}
                                    className={selectClass}
                                >
                                    <option value="">— Tanpa induk —</option>
                                    {categories
                                        .filter((option) => option.id !== category.id)
                                        .map((option) => (
                                            <option key={option.id} value={option.id}>
                                                {option.name}
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

CategoriesEdit.layout = {
    breadcrumbs: [
        { title: 'Kategori', href: index.url() },
        { title: 'Edit' },
    ],
};
