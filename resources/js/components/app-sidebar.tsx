import { Link, usePage } from '@inertiajs/react';
import {
    BookOpenText,
    Boxes,
    ClipboardCheck,
    FileBarChart,
    History,
    Landmark,
    LayoutGrid,
    NotebookText,
    Package,
    ReceiptText,
    Ruler,
    Tag,
    TrendingUp,
    Truck,
    UserSquare,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as categoriesIndex } from '@/routes/categories';
import { index as chartOfAccountsIndex } from '@/routes/chart-of-accounts';
import { index as customersIndex } from '@/routes/customers';
import { index as generalLedgerIndex } from '@/routes/general-ledger';
import { index as journalEntriesIndex } from '@/routes/journal-entries';
import { index as productsIndex } from '@/routes/products';
import { index as purchaseOrdersIndex } from '@/routes/purchase-orders';
import { incomeStatement as incomeStatementIndex, sales as salesReportIndex, stockCard as stockCardIndex } from '@/routes/reports';
import { index as salesOrdersIndex } from '@/routes/sales-orders';
import { index as stockMovementsIndex } from '@/routes/stock-movements';
import { index as stockOpnameIndex } from '@/routes/stock-opname';
import { index as unitsIndex } from '@/routes/units';
import { index as vendorsIndex } from '@/routes/vendors';
import type { NavItem } from '@/types';

type NavGroup = {
    label: string;
    items: NavItem[];
};

/*
 | Menu dikelompokkan per modul agar tiap role cepat menemukan bagiannya.
 | Item tanpa `roles` tampil untuk semua role internal.
 */
const navGroups: NavGroup[] = [
    {
        label: 'Utama',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ],
    },
    {
        label: 'Master Data',
        items: [
            {
                title: 'Produk',
                href: productsIndex.url(),
                icon: Package,
                roles: ['admin', 'staff_gudang'],
            },
            {
                title: 'Kategori',
                href: categoriesIndex.url(),
                icon: Tag,
                roles: ['admin', 'staff_gudang'],
            },
            {
                title: 'Satuan',
                href: unitsIndex.url(),
                icon: Ruler,
                roles: ['admin', 'staff_gudang'],
            },
            {
                title: 'Customer',
                href: customersIndex.url(),
                icon: Users,
                roles: ['admin', 'staff_finance'],
            },
            {
                title: 'Vendor',
                href: vendorsIndex.url(),
                icon: UserSquare,
                roles: ['admin', 'staff_finance'],
            },
            {
                title: 'Chart of Accounts',
                href: chartOfAccountsIndex.url(),
                icon: Landmark,
                roles: ['admin', 'staff_finance'],
            },
        ],
    },
    {
        label: 'Inventory',
        items: [
            {
                title: 'Riwayat Stok',
                href: stockMovementsIndex.url(),
                icon: History,
                roles: ['admin', 'staff_gudang'],
            },
            {
                title: 'Stock Opname',
                href: stockOpnameIndex.url(),
                icon: ClipboardCheck,
                roles: ['admin', 'staff_gudang'],
            },
        ],
    },
    {
        label: 'Transaksi',
        items: [
            {
                title: 'Sales Order',
                href: salesOrdersIndex.url(),
                icon: ReceiptText,
                roles: ['admin', 'staff_finance'],
            },
            {
                title: 'Purchase Order',
                href: purchaseOrdersIndex.url(),
                icon: Truck,
                // Gudang kelola PO; finance buka PO untuk catat invoice/pembayaran.
                roles: ['admin', 'staff_gudang', 'staff_finance'],
            },
        ],
    },
    {
        label: 'Keuangan',
        items: [
            {
                title: 'Jurnal Umum',
                href: journalEntriesIndex.url(),
                icon: NotebookText,
                roles: ['admin', 'staff_finance'],
            },
            {
                title: 'Buku Besar',
                href: generalLedgerIndex.url(),
                icon: BookOpenText,
                roles: ['admin', 'staff_finance'],
            },
            {
                title: 'Laporan Keuangan',
                href: incomeStatementIndex.url(),
                icon: FileBarChart,
                roles: ['admin', 'staff_finance'],
            },
        ],
    },
    {
        label: 'Laporan',
        items: [
            {
                title: 'Laporan Penjualan',
                href: salesReportIndex.url(),
                icon: TrendingUp,
                roles: ['admin', 'staff_finance'],
            },
            {
                title: 'Kartu Stok',
                href: stockCardIndex.url(),
                icon: Boxes,
                roles: ['admin', 'staff_finance'],
            },
        ],
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;

    // Saring item per role; kelompok yang kosong disembunyikan seluruhnya.
    const visibleGroups = navGroups
        .map((group) => ({
            label: group.label,
            items: group.items.filter(
                (item) =>
                    !item.roles || item.roles.some((role) => auth.roles.includes(role)),
            ),
        }))
        .filter((group) => group.items.length > 0);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {visibleGroups.map((group) => (
                    <NavMain key={group.label} label={group.label} items={group.items} />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
