import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/Button';
import { GoogleButton } from '@/components/GoogleButton';
import { TextField } from '@/components/TextField';
import { AuthLayout } from '@/layouts/AuthLayout';

interface LoginProps {
    canResetPassword: boolean;
    membershipEnabled: boolean;
    status?: string | null;
}

export default function Login({ canResetPassword, membershipEnabled, status }: LoginProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/login');
    }

    return (
        <AuthLayout title={t('login.login_page_headline')} subtitle={t('login.login')}>
            <Head title={t('login.login')} />

            {status && (
                <p role="status" className="mb-6 rounded-md border border-success bg-success-bg p-4 text-sm text-success">
                    {status}
                </p>
            )}

            <GoogleButton>{t('login.login_with')} Google</GoogleButton>

            <div className="my-6 flex items-center gap-3 text-xs uppercase tracking-wider text-subtle">
                <span className="h-px flex-1 bg-border" />
                <span>{t('login.or')}</span>
                <span className="h-px flex-1 bg-border" />
            </div>

            <form onSubmit={submit} className="space-y-5" noValidate>
                <TextField
                    name="email"
                    type="text"
                    label={t('login.username_or_email')}
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    autoComplete="email"
                    autoFocus
                    required
                    data-testid="login-username"
                />
                <TextField
                    name="password"
                    type="password"
                    label={t('login.password')}
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    autoComplete="current-password"
                    required
                    data-testid="login-password"
                />

                <label htmlFor="remember" className="flex cursor-pointer items-center gap-2.5 text-sm text-muted">
                    <input
                        id="remember"
                        type="checkbox"
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                        className="rounded border-strong text-primary focus:ring-ring"
                    />
                    {t('login.remember_me')}
                </label>

                <div className="flex items-center justify-between gap-4 pt-1">
                    <Button type="submit" variant="primary" loading={processing} data-testid="login-submit">
                        {t('login.login')}
                    </Button>
                    {canResetPassword && (
                        <Link href="/password/reset" className="text-sm font-medium text-muted transition hover:text-primary">
                            {t('login.forgot_password')}
                        </Link>
                    )}
                </div>
            </form>

            {membershipEnabled && (
                <div className="mt-6 text-center text-sm text-muted">
                    <Link href="/register" className="font-medium text-primary transition hover:text-primary-hover">
                        {t('registration.register')}
                    </Link>
                </div>
            )}
        </AuthLayout>
    );
}
