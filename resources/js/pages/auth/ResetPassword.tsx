import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/Button';
import { TextField } from '@/components/TextField';
import { AuthLayout } from '@/layouts/AuthLayout';

interface ResetPasswordProps {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/password/reset');
    }

    return (
        <AuthLayout title={t('custom-passwords.reset_page_headline')} subtitle={t('custom-passwords.reset_password')}>
            <Head title={t('custom-passwords.reset_password')} />

            <form onSubmit={submit} className="space-y-5" noValidate>
                <TextField
                    name="email"
                    type="email"
                    label={t('custom-passwords.email')}
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    autoComplete="email"
                    required
                />
                <TextField
                    name="password"
                    type="password"
                    label={t('custom-passwords.password')}
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    autoComplete="new-password"
                    autoFocus
                    required
                />
                <TextField
                    name="password_confirmation"
                    type="password"
                    label={t('custom-passwords.confirm_password')}
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    error={errors.password_confirmation}
                    autoComplete="new-password"
                    required
                />

                <Button type="submit" variant="primary" loading={processing} className="w-full">
                    {t('custom-passwords.reset_password_btn')}
                </Button>
            </form>
        </AuthLayout>
    );
}
