import { Link } from '@inertiajs/react';

type StatTileProps = {
    label: string;
    value: string;
    description?: string;
    href?: string;
};

/**
 * Kartu angka ringkasan (stat tile) untuk baris KPI dashboard — label cap
 * mono, angka mono besar, pembatas dashed ala struk. Bila diberi `href`,
 * kartu jadi tautan ke halaman terkait (list produk/PO/order).
 */
export function StatTile({ label, value, description, href }: StatTileProps) {
    const content = (
        <>
            <p className="font-mono text-[10px] tracking-[0.14em] text-muted-foreground uppercase">
                {label}
            </p>
            <p className="mt-2 font-mono text-2xl font-semibold tracking-tight">
                {value}
            </p>
            {description ? (
                <p className="mt-2 border-t border-dashed pt-2 text-xs text-muted-foreground">
                    {description}
                </p>
            ) : null}
        </>
    );

    const className =
        'block rounded-xl border bg-card p-4 shadow-xs transition-colors hover:border-ink/30 dark:hover:border-border';

    if (href) {
        return (
            <Link href={href} className={className}>
                {content}
            </Link>
        );
    }

    return (
        <div className={className}>
            {content}
        </div>
    );
}
