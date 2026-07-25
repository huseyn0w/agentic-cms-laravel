import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/Button';
import { TextField } from '@/components/TextField';
import { AuthLayout } from '@/layouts/AuthLayout';

export default function Register() {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        username: '',
        password: '',
        password_confirmation: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/register');
    }

    return (
        <AuthLayout title={t('registration.register_page_headline')} subtitle={t('registration.register')}>
            <Head title={t('registration.register')} />

            <form onSubmit={submit} className="space-y-5" noValidate>
                <TextField
                    name="name"
                    type="text"
                    label={t('registration.name')}
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    error={errors.name}
                    autoComplete="name"
                    autoFocus
                    required
                />
                <TextField
                    name="email"
                    type="email"
                    label={t('registration.email')}
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    autoComplete="email"
                    required
                />
                <TextField
                    name="username"
                    type="text"
                    label={t('registration.username')}
                    value={data.username}
                    onChange={(e) => setData('username', e.target.value)}
                    error={errors.username}
                    autoComplete="username"
                    required
                />
                <TextField
                    name="password"
                    type="password"
                    label={t('registration.password')}
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    autoComplete="new-password"
                    required
                />
                <TextField
                    name="password_confirmation"
                    type="password"
                    label={t('registration.confirm_password')}
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    error={errors.password_confirmation}
                    autoComplete="new-password"
                    required
                />

                <Button type="submit" variant="primary" loading={processing} className="w-full">
                    {t('registration.register_btn')}
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
