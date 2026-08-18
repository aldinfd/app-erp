import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store } from '@/routes/vendors';

export default function VendorsCreate() {
    return (
        <>
            <Head title="Tambah Vendor" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Tambah Vendor</h1>

                <Form {...store.form()} className="max-w-lg space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama</Label>
                                <Input id="name" name="name" required autoFocus />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email (opsional)</Label>
                                <Input id="email" name="email" type="email" />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Telepon (opsional)</Label>
                                <Input id="phone" name="phone" />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address">Alamat (opsional)</Label>
                                <textarea
                                    id="address"
                                    name="address"
                                    rows={3}
                                    className="border-input bg-background rounded-md border px-3 py-2 text-sm"
                                />
                                <InputError message={errors.address} />
                            </div>

                            <div className="flex items-center space-x-3">
                                {/* Hidden 0 + checkbox 1 agar nilai tetap terkirim saat tidak dicentang. */}
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

VendorsCreate.layout = {
    breadcrumbs: [
        { title: 'Vendor', href: index.url() },
        { title: 'Tambah' },
    ],
};
