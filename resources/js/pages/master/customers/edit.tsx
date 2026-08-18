import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, update } from '@/routes/customers';
import type { Customer } from '@/types';

type Props = {
    customer: Customer;
};

export default function CustomersEdit({ customer }: Props) {
    return (
        <>
            <Head title={`Edit Customer — ${customer.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Edit Customer</h1>

                <Form {...update.form({ customer: customer.id })} className="max-w-lg space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama</Label>
                                <Input id="name" name="name" required defaultValue={customer.name} />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email (opsional)</Label>
                                <Input id="email" name="email" type="email" defaultValue={customer.email ?? ''} />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Telepon (opsional)</Label>
                                <Input id="phone" name="phone" defaultValue={customer.phone ?? ''} />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address">Alamat (opsional)</Label>
                                <textarea
                                    id="address"
                                    name="address"
                                    rows={3}
                                    defaultValue={customer.address ?? ''}
                                    className="border-input bg-background rounded-md border px-3 py-2 text-sm"
                                />
                                <InputError message={errors.address} />
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

CustomersEdit.layout = {
    breadcrumbs: [
        { title: 'Customer', href: index.url() },
        { title: 'Edit' },
    ],
};
