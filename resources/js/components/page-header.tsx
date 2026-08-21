import type { ReactNode } from 'react';

type PageHeaderProps = {
    title: string;
    description?: string;
    /** Konten di kanan judul — biasanya tombol aksi (mis. "Tambah Produk"). */
    actions?: ReactNode;
};

/**
 * Kepala halaman back-office: garis manila pendek + judul serif — satu
 * keluarga dengan band etalase storefront dan halaman login.
 */
export function PageHeader({ title, description, actions }: PageHeaderProps) {
    return (
        <div className="flex flex-wrap items-end justify-between gap-4">
            <div>
                <span
                    aria-hidden
                    className="mb-3 block h-0.75 w-8 rounded-full bg-manila"
                />
                <h1 className="font-serif text-2xl tracking-tight">{title}</h1>
                {description ? (
                    <p className="mt-1.5 text-sm text-muted-foreground">
                        {description}
                    </p>
                ) : null}
            </div>
            {actions ? <div className="flex items-center gap-2">{actions}</div> : null}
        </div>
    );
}
