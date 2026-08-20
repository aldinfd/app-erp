import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

const STEPS = ['Keranjang', 'Checkout', 'Pembayaran'];

/**
 * Penanda langkah belanja — urutan nyata alur customer, bukan dekorasi.
 * `current` 0-index: 0 = di keranjang, 1 = checkout, 2 = pembayaran.
 */
export default function CheckoutSteps({ current }: { current: number }) {
    return (
        <ol className="flex flex-wrap items-center gap-x-3 gap-y-2 font-mono text-[11px] tracking-[0.16em] uppercase">
            {STEPS.map((label, index) => {
                const isDone = index < current;
                const isCurrent = index === current;

                return (
                    <li
                        key={label}
                        className="flex items-center gap-3"
                        aria-current={isCurrent ? 'step' : undefined}
                    >
                        <span
                            className={cn(
                                'flex items-center gap-1.5',
                                isCurrent && 'font-semibold text-ink dark:text-foreground',
                                isDone && 'text-muted-foreground',
                                !isDone && !isCurrent && 'text-muted-foreground',
                            )}
                        >
                            <span
                                aria-hidden
                                className={cn(
                                    'flex size-5 items-center justify-center rounded-full border text-[10px]',
                                    isCurrent && 'border-ink bg-ink text-white dark:border-foreground dark:bg-foreground dark:text-background',
                                    isDone && 'border-ink/30 text-ink dark:border-border dark:text-muted-foreground',
                                    !isDone && !isCurrent && 'border-muted-foreground/40',
                                )}
                            >
                                {isDone ? <Check className="size-3" /> : index + 1}
                            </span>
                            {label}
                        </span>
                        {index < STEPS.length - 1 && (
                            <span
                                aria-hidden
                                className="w-6 border-t border-dotted border-muted-foreground/40"
                            />
                        )}
                    </li>
                );
            })}
        </ol>
    );
}
