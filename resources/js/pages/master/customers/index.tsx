import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Pagination } from '@/components/master/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, destroy, edit, index } from '@/routes/customers';
import type { Customer, Paginated } from '@/types';

type Props = {
    customers: Paginated<Customer>;
    filters: { q?: string };
};

export default function CustomersIndex({ customers, filters }: Props) {
    const [q, setQ] = React.useState(filters.q ?? '');

    function applySearch(event: React.FormEvent) {
        event.preventDefault();
        router.get(index.url(), { q: q || undefined }, { preserveState: true });
    }

    function handleDelete(customer: Customer) {
        if (confirm(`Hapus customer "${customer.name}"?`)) {
            router.delete(destroy.url({ customer: customer.id }));
        }
    }

    return (
        <>
            <Head title="Customer" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="text-lg font-semibold">Customer</h1>
                    <Button asChild>
                        <Link href={create.url()}>Tambah Customer</Link>
                    </Button>
                </div>

                <form onSubmit={applySearch} className="flex max-w-sm gap-2">
                    <Input
                        name="q"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari nama / email / telepon…"
                    />
                    <Button type="submit" variant="outline">
                        Cari
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-neutral-50 text-left dark:bg-neutral-900">
                            <tr>
                                <th className="px-4 py-2 font-medium">Nama</th>
                                <th className="px-4 py-2 font-medium">Email</th>
                                <th className="px-4 py-2 font-medium">Telepon</th>
                                <th className="px-4 py-2 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {customers.data.map((customer) => (
                                <tr key={customer.id} className="border-t">
                                    <td className="px-4 py-2">{customer.name}</td>
                                    <td className="px-4 py-2">{customer.email ?? '-'}</td>
                                    <td className="px-4 py-2">{customer.phone ?? '-'}</td>
                                    <td className="px-4 py-2">
                                        <div className="flex gap-2">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={edit.url({ customer: customer.id })}>Edit</Link>
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => handleDelete(customer)}
                                            >
                                                Hapus
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {customers.data.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="px-4 py-8 text-center text-neutral-500">
                                        Tidak ada customer.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination items={customers} />
            </div>
        </>
    );
}

CustomersIndex.layout = {
    breadcrumbs: [{ title: 'Customer', href: index.url() }],
};
