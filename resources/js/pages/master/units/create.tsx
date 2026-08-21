import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store } from '@/routes/units';

export default function UnitsCreate() {
    return (
        <>
            <Head title="Tambah Satuan" />
            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <PageHeader title="Tambah Satuan" />

                <Form {...store.form()} className="max-w-lg space-y-5 rounded-xl border bg-card p-6 shadow-xs">
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

                            <div className="flex items-center space-x-3">
                                {/* Hidden 0 + checkbox 1 agar nilai tetap terkirim saat tidak dicentang. */}
                                <input type="hidden" name="allows_fraction" value="0" />
                                <Checkbox id="allows_fraction" name="allows_fraction" value="1" />
                                <Label htmlFor="allows_fraction">Boleh pecahan (mis. kg)</Label>
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
