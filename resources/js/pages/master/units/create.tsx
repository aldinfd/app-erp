import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store } from '@/routes/units';

export default function UnitsCreate() {
    return (
        <>
            <Head title="Tambah Satuan" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Tambah Satuan</h1>

                <Form {...store.form()} className="max-w-lg space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama Satuan</Label>
                                <Input id="name" name="name" required autoFocus placeholder="mis. Kilogram" />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="abbreviation">Singkatan</Label>
                                <Input id="abbreviation" name="abbreviation" required placeholder="mis. kg" />
                                <InputError message={errors.abbreviation} />
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

UnitsCreate.layout = {
    breadcrumbs: [
        { title: 'Satuan', href: index.url() },
        { title: 'Tambah' },
    ],
};
