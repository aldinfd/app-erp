import { Link, usePage } from '@inertiajs/react';
import { BookOpen, ClipboardCheck, FolderGit2, History, Landmark, LayoutGrid, Package, ReceiptText, Ruler, Tag, UserSquare, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
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
import { index as productsIndex } from '@/routes/products';
import { index as salesOrdersIndex } from '@/routes/sales-orders';
import { index as stockMovementsIndex } from '@/routes/stock-movements';
import { index as stockOpnameIndex } from '@/routes/stock-opname';
import { index as unitsIndex } from '@/routes/units';
import { index as vendorsIndex } from '@/routes/vendors';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
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
    {
        title: 'Sales Order',
        href: salesOrdersIndex.url(),
        icon: ReceiptText,
        roles: ['admin', 'staff_finance'],
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
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;

    // Menu tanpa `roles` tampil untuk semua role internal;
    // menu lain (Phase 2+) cukup isi `roles: ['admin', ...]`.
    const visibleNavItems = mainNavItems.filter(
        (item) =>
            !item.roles || item.roles.some((role) => auth.roles.includes(role)),
    );

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
                <NavMain items={visibleNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
