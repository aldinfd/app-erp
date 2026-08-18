import * as React from 'react';

import { Alert, AlertDescription } from '@/components/ui/alert';
import { usePage } from '@inertiajs/react';

/**
 * Notifikasi sekali-tampil (session flash) hasil aksi CRUD di back-office.
 */
export function FlashMessages() {
    const { flash } = usePage().props;
    const [visible, setVisible] = React.useState(true);

    React.useEffect(() => {
        setVisible(true);
    }, [flash.success, flash.error]);

    if (!visible || (!flash.success && !flash.error)) {
        return null;
    }

    return (
        <Alert variant={flash.error ? 'destructive' : 'default'} className="mx-4 mt-4">
            <AlertDescription>{flash.error ?? flash.success}</AlertDescription>
        </Alert>
    );
}
