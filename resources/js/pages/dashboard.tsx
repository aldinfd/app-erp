import { Head, Link } from '@inertiajs/react';
import { SalesChart } from '@/components/dashboard/sales-chart';
import { StatTile } from '@/components/dashboard/stat-tile';
import { formatCurrency, formatQty } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as productsIndex } from '@/routes/products';
import { index as purchaseOrdersIndex } from '@/routes/purchase-orders';
import { incomeStatement as incomeStatementShow } from '@/routes/reports';
import { index as salesOrdersIndex } from '@/routes/sales-orders';
import type {
    DashboardLowStock,
    DashboardMonthlySales,
    DashboardPendingSales,
    DashboardSalesChartPoint,
} from '@/types';

type Props = {
    /** Semua props optional — dikirim per-role oleh DashboardController. */
    low_stock?: DashboardLowStock;
    po_waiting_goods?: number;
    monthly_sales?: DashboardMonthlySales;
    pending_sales?: DashboardPendingSales;
    po_waiting_payment?: number;
    sales_chart?: DashboardSalesChartPoint[];
};

export default function Dashboard({
    low_stock: lowStock,
    po_waiting_goods: poWaitingGoods,
    monthly_sales: monthlySales,
    pending_sales: pendingSales,
    po_waiting_payment: poWaitingPayment,
    sales_chart: salesChart,
}: Props) {
    const monthName = new Date().toLocaleDateString('id-ID', { month: 'long' });

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-lg font-semibold">Dashboard</h1>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {lowStock && (
                        <StatTile
                            label="Produk stok menipis"
                            value={String(lowStock.count)}
                            description="stok ≤ titik reorder"
                            href={productsIndex.url()}
                        />
                    )}
                    {monthlySales && (
                        <StatTile
                            label={`Penjualan ${monthName}`}
                            value={formatCurrency(monthlySales.revenue)}
                            description={`${monthlySales.total_orders} order bulan ini`}
                        />
                    )}
                    {pendingSales && (
                        <StatTile
                            label="Order tertunda"
                            value={String(pendingSales.orders)}
                            description="draft / menunggu pembayaran"
                            href={salesOrdersIndex.url()}
                        />
                    )}
                    {pendingSales && (
                        <StatTile
                            label="Invoice belum lunas"
                            value={String(pendingSales.invoices)}
                            description="unpaid / dibayar sebagian"
                            href={salesOrdersIndex.url()}
                        />
                    )}
                    {poWaitingGoods !== undefined && (
                        <StatTile
                            label="PO menunggu barang"
                            value={String(poWaitingGoods)}
                            description="sudah dipesan ke vendor"
                            href={purchaseOrdersIndex.url()}
                        />
                    )}
                    {poWaitingPayment !== undefined && (
                        <StatTile
                            label="PO menunggu pembayaran"
                            value={String(poWaitingPayment)}
                            description="invoice vendor belum lunas"
                            href={purchaseOrdersIndex.url()}
                        />
                    )}
                </div>

                {(salesChart || lowStock) && (
                    <div className="grid gap-4 lg:grid-cols-2">
                        {salesChart && (
                            <section
                                className={`rounded-lg border p-4 ${lowStock ? '' : 'lg:col-span-2'}`}
                            >
                                <div className="mb-4 flex items-start justify-between gap-4">
                                    <div>
                                        <h2 className="text-sm font-medium">Penjualan 6 bulan terakhir</h2>
                                        <p className="text-xs text-neutral-500">
                                            Revenue dari order berstatus paid per bulan
                                        </p>
                                    </div>
                                    <Link
                                        href={incomeStatementShow.url()}
                                        className="text-xs text-neutral-500 whitespace-nowrap hover:underline"
                                    >
                                        Lihat Laba Rugi
                                    </Link>
                                </div>
                                <SalesChart data={salesChart} />
                            </section>
                        )}

                        {lowStock && (
                            <section
                                className={`overflow-hidden rounded-lg border ${salesChart ? '' : 'lg:col-span-2'}`}
                            >
                                <div className="flex items-center justify-between bg-neutral-50 px-4 py-2 dark:bg-neutral-900">
                                    <h2 className="text-sm font-medium">
                                        Produk stok menipis ({lowStock.count})
                                    </h2>
                                    <Link
                                        href={productsIndex.url()}
                                        className="text-xs text-neutral-500 hover:underline"
                                    >
                                        Kelola produk
                                    </Link>
                                </div>
                                <table className="w-full text-sm">
                                    <thead className="text-left">
                                        <tr className="border-t">
                                            <th className="px-4 py-2 font-medium">Produk</th>
                                            <th className="px-4 py-2 text-right font-medium">Stok</th>
                                            <th className="px-4 py-2 text-right font-medium">Titik reorder</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {lowStock.products.map((product) => (
                                            <tr key={product.id} className="border-t">
                                                <td className="px-4 py-2">
                                                    <span className="font-mono text-xs">{product.sku}</span>{' '}
                                                    {product.name}
                                                </td>
                                                <td className="px-4 py-2 text-right tabular-nums">
                                                    {formatQty(product.stock_qty, product.unit?.allows_fraction)}{' '}
                                                    {product.unit?.abbreviation}
                                                </td>
                                                <td className="px-4 py-2 text-right tabular-nums">
                                                    {formatQty(product.reorder_point, product.unit?.allows_fraction)}{' '}
                                                    {product.unit?.abbreviation}
                                                </td>
                                            </tr>
                                        ))}
                                        {lowStock.products.length === 0 && (
                                            <tr>
                                                <td
                                                    colSpan={3}
                                                    className="px-4 py-4 text-center text-neutral-500"
                                                >
                                                    Tidak ada produk stok menipis.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                                {lowStock.count > lowStock.products.length && (
                                    <p className="border-t px-4 py-2 text-xs text-neutral-500">
                                        Menampilkan {lowStock.products.length} stok terendah dari{' '}
                                        {lowStock.count} produk.
                                    </p>
                                )}
                            </section>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
