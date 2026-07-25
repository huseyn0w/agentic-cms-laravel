import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AuthLayout } from '@/layouts/AuthLayout';

interface VerifyEmailProps {
    status?: 'resent' | null;
}

export default function VerifyEmail({ status }: VerifyEmailProps) {
    const { t } = useTranslation();
    const { post, processing } = useForm({});

    function resend(e: FormEvent) {
        e.preventDefault();
        post('/email/resend');
    }

    return (
        <AuthLayout title={t('email.verify_page_headline')}>
            <Head title={t('email.verify_page_headline')} />

            {status === 'resent' && (
                <p role="status" className="mb-6 rounded-md border border-success bg-success-bg p-4 text-sm text-success">
                    {t('email.fresh_link')}
                </p>
            )}

            <form onSubmit={resend}>
                <p className="text-sm text-muted">
                    {t('email.check_email')} {t('email.not_receive_email')},{' '}
                    <button
                        type="submit"
                        disabled={processing}
                        className="font-medium text-primary transition hover:text-primary-hover disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {t('email.request_other_email')}
                    </button>
                    .
                </p>
            </form>
        </AuthLayout>
    );
}
