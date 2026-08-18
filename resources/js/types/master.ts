/**
 * Bentuk props master data yang dikirim controller → halaman Inertia.
 * `Paginated<T>` mengikuti struktur paginator Laravel.
 */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type Category = {
    id: number;
    name: string;
    parent_id: number | null;
    parent?: { id: number; name: string } | null;
};

export type Unit = {
    id: number;
    name: string;
    abbreviation: string;
    allows_fraction: boolean;
};

export type Product = {
    id: number;
    sku: string;
    name: string;
    category_id: number | null;
    unit_id: number;
    cost_price: string;
    selling_price: string;
    stock_qty: string;
    reorder_point: string;
    image_url: string | null;
    is_active: boolean;
    category?: { id: number; name: string } | null;
    unit?: { id: number; name: string; abbreviation: string; allows_fraction: boolean } | null;
};

export type Customer = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
};

export type Vendor = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    is_active: boolean;
};

export type ChartOfAccount = {
    id: number;
    code: string;
    name: string;
    type: string;
    parent_id: number | null;
    is_postable: boolean;
    is_active: boolean;
    parent?: { id: number; code: string; name: string } | null;
};
