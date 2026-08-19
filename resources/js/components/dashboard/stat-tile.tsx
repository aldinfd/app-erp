import { Link } from '@inertiajs/react';

type StatTileProps = {
    label: string;
    value: string;
    description?: string;
    href?: string;
};

/**
 * Kartu angka ringkasan (stat tile) untuk baris KPI dashboard. Bila diberi
 * `href`, kartu jadi tautan ke halaman terkait (list produk/PO/order).
 */
export function StatTile({ label, value, description, href }: StatTileProps) {
    const content = (
        <>
            <p className="text-sm text-neutral-500">{label}</p>
            <p className="mt-1 text-2xl font-semibold">{value}</p>
            {description ? <p className="mt-1 text-xs text-neutral-500">{description}</p> : null}
        </>
    );

    if (href) {
        return (
            <Link
                href={href}
                className="block rounded-lg border p-4 transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900"
            >
                {content}
            </Link>
        );
    }

    return <div className="rounded-lg border p-4">{content}</div>;
}
