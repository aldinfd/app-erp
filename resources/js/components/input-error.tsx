import type { HTMLAttributes } from 'react';
import { CircleAlert } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Pesan error validasi inline — tampil tepat di bawah field yang salah,
 * dengan ikon dan animasi masuk singkat agar mudah terlihat.
 */
export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p
            {...props}
            role="alert"
            className={cn(
                'flex items-center gap-1.5 text-sm font-medium text-red-600 motion-safe:animate-in motion-safe:fade-in-0 motion-safe:slide-in-from-top-1 dark:text-red-400',
                className,
            )}
        >
            <CircleAlert aria-hidden className="size-3.5 shrink-0" />
            {message}
        </p>
    ) : null;
}
