import type { SVGAttributes } from 'react';
import { Boxes } from 'lucide-react';

/**
 * Ikon logo aplikasi — tumpukan kotak (modul stok/penjualan/keuangan
 * yang terintegrasi menjadi satu sistem ERP).
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return <Boxes aria-hidden {...props} />;
}
