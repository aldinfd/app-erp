import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, update } from '@/routes/units';
import type { Unit } from '@/types';

type Props = {
    unit: Unit;
};

export default function UnitsEdit({ unit }: Props) {
    return (
        <>
            <Head title={`Edit Satuan — ${unit.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Edit Satuan</h1>

                <Form {...update.form({ unit: unit.id })} className="max-w-lg space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama Satuan</Label>
                                <Input id="name" name="name" required defaultValue={unit.name} />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="abbreviation">Singkatan</Label>
                                <Input id="abbreviation" name="abbreviation" required defaultValue={unit.abbreviation} />
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

UnitsEdit.layout = {
    breadcrumbs: [
        { title: 'Satuan', href: index.url() },
        { title: 'Edit' },
    ],
};
