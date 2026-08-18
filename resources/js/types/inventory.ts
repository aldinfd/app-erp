import type { Paginated } from './master';

/**
 * Bentuk props modul inventory yang dikirim controller → halaman Inertia.
 */

export type StockMovementType = 'in' | 'out' | 'adjust';

export type StockMovement = {
    id: number;
    product: {
        id: number;
        sku: string;
        name: string;
        unit?: { id: number; abbreviation: string; allows_fraction: boolean } | null;
    } | null;
    user: { id: number; name: string } | null;
    type: StockMovementType;
    qty: string;
    before_qty: string;
    after_qty: string;
    reference_type: string | null;
    reference_id: number | null;
    note: string | null;
    created_at: string;
};

export type PaginatedMovements = Paginated<StockMovement>;

export type OpnameProduct = {
    id: number;
    sku: string;
    name: string;
    stock_qty: string;
    reorder_point: string;
    is_active: boolean;
    unit?: { id: number; name: string; abbreviation: string; allows_fraction: boolean } | null;
};
