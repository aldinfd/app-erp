import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

type ClientErrors = {
    email?: string;
    password?: string;
};

/**
 * Validasi sisi klien dengan pesan Indonesia — menggantikan gelembung
 * validasi bawaan browser (form memakai noValidate). Error dari server
 * (mis. kredensial salah) tetap tampil di slot inline yang sama.
 */
function validateLogin(email: string, password: string): ClientErrors {
    const errors: ClientErrors = {};

    if (!email.trim()) {
        errors.email = 'Email wajib diisi.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) {
        errors.email = 'Format email tidak valid — contoh: nama@toko.com.';
    }

    if (!password) {
        errors.password = 'Password wajib diisi.';
    }

    return errors;
}

export default function Login({ status, canResetPassword }: Props) {
    const [clientErrors, setClientErrors] = useState<ClientErrors>({});

    /** Hapus error klien field tertentu saat user mulai mengetik ulang. */
    const clearClientError = (field: keyof ClientErrors) => {
        setClientErrors((prev) =>
            prev[field] ? { ...prev, [field]: undefined } : prev,
        );
    };

    return (
        <>
            <Head title="Masuk" />

            <PasskeyVerify
                label="Masuk dengan passkey"
                separator="Atau lanjutkan dengan email"
            />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                noValidate
                onBefore={(visit) => {
                    const next = validateLogin(
                        String(visit.data.email ?? ''),
                        String(visit.data.password ?? ''),
                    );
                    setClientErrors(next);
                    return Object.keys(next).length === 0;
                }}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => {
                    const emailError = clientErrors.email ?? errors.email;
                    const passwordError =
                        clientErrors.password ?? errors.password;

                    return (
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="email"
                                    className="font-mono text-[11px] font-medium tracking-[0.14em] text-muted-foreground uppercase"
                                >
                                    Email
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="nama@toko.com"
                                    aria-invalid={emailError ? true : undefined}
                                    onInput={() => clearClientError('email')}
                                    className={cn(
                                        'h-11 rounded-lg transition-shadow focus-visible:border-manila/60 focus-visible:ring-manila/25',
                                        emailError &&
                                            'border-red-600/60 focus-visible:border-red-600/60 focus-visible:ring-red-500/20 dark:border-red-400/60 dark:focus-visible:border-red-400/60 dark:focus-visible:ring-red-400/20',
                                    )}
                                />
                                <InputError message={emailError} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label
                                        htmlFor="password"
                                        className="font-mono text-[11px] font-medium tracking-[0.14em] text-muted-foreground uppercase"
                                    >
                                        Password
                                    </Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-sm"
                                            tabIndex={5}
                                        >
                                            Lupa password?
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="••••••••"
                                    aria-invalid={
                                        passwordError ? true : undefined
                                    }
                                    onInput={() =>
                                        clearClientError('password')
                                    }
                                    className={cn(
                                        'h-11 rounded-lg transition-shadow focus-visible:border-manila/60 focus-visible:ring-manila/25',
                                        passwordError &&
                                            'border-red-600/60 focus-visible:border-red-600/60 focus-visible:ring-red-500/20 dark:border-red-400/60 dark:focus-visible:border-red-400/60 dark:focus-visible:ring-red-400/20',
                                    )}
                                />
                                <InputError message={passwordError} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">Ingat saya</Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 h-11 w-full rounded-lg bg-ink text-white shadow-[0_10px_24px_-10px_rgba(0,0,0,0.45)] transition-all hover:bg-ledger hover:shadow-[0_12px_28px_-10px_rgba(0,0,0,0.5)] focus-visible:ring-manila/40 active:translate-y-px"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Masuk ke back-office
                            </Button>
                        </div>
                    );
                }}
            </Form>

            {status && (
                <div className="mt-4 text-center text-sm font-medium text-green-700 dark:text-green-400">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Masuk ke back-office',
    description:
        'Kelola stok, penjualan, pembelian, dan keuangan toko dari satu tempat.',
};
