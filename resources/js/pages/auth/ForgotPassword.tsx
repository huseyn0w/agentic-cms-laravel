import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/Button';
import { TextField } from '@/components/TextField';
import { AuthLayout } from '@/layouts/AuthLayout';

interface ForgotPasswordProps {
    status?: string | null;
}

export default function ForgotPassword({ status }: ForgotPasswordProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/password/email');
    }

    return (
        <AuthLayout title={t('custom-passwords.reset_page_headline')} subtitle={t('custom-passwords.reset_password')}>
            <Head title={t('custom-passwords.reset_password')} />

            {status && (
                <p role="status" className="mb-6 rounded-md border border-success bg-success-bg p-4 text-sm text-success">
                    {status}
                </p>
            )}

            <form onSubmit={submit} className="space-y-5" noValidate>
                <TextField
                    name="email"
                    type="email"
                    label={t('custom-passwords.email')}
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    autoComplete="email"
                    autoFocus
                    required
                />

                <Button type="submit" variant="primary" loading={processing} className="w-full">
                    {t('custom-passwords.send_password_link')}
                </Button>
            </form>

            <div className="mt-6 text-center text-sm text-muted">
                <Link href="/login" className="font-medium text-primary transition hover:text-primary-hover">
                    {t('login.login')}
                </Link>
            </div>
        </AuthLayout>
    );
}
