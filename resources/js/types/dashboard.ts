/**
 * Props dashboard (Phase 7) — semua optional karena dikirim per-role:
 * widget gudang hanya untuk admin/staff_gudang, widget finance hanya
 * untuk admin/staff_finance (difilter di DashboardController).
 */
export type DashboardLowStockProduct = {
    id: number;
    sku: string;
    name: string;
    stock_qty: string;
    reorder_point: string;
    unit: { abbreviation: string; allows_fraction: boolean } | null;
};

export type DashboardLowStock = {
    count: number;
    products: DashboardLowStockProduct[];
};

export type DashboardMonthlySales = {
    total_orders: number;
    revenue: number;
};

export type DashboardPendingSales = {
    orders: number;
    invoices: number;
};

export type DashboardSalesChartPoint = {
    month: string; // 'YYYY-MM'
    revenue: number;
};
