import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types/master';

type Props<T> = {
    items: Paginated<T>;
};

/**
 * Navigasi halaman sederhana untuk tabel master data.
 */
export function Pagination<T>({ items }: Props<T>) {
    if (items.last_page <= 1) {
        return null;
    }

    return (
        <div className="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm">
            <span className="text-muted-foreground">
                Menampilkan {items.from ?? 0}–{items.to ?? 0} dari {items.total} data
            </span>
            <div className="flex items-center gap-3">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={!items.prev_page_url}
                    onClick={() => items.prev_page_url && router.get(items.prev_page_url)}
                >
                    Sebelumnya
                </Button>
                <span className="font-mono text-xs text-muted-foreground">
                    Hal. {items.current_page} / {items.last_page}
                </span>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={!items.next_page_url}
                    onClick={() => items.next_page_url && router.get(items.next_page_url)}
                >
                    Berikutnya
                </Button>
            </div>
        </div>
    );
}
