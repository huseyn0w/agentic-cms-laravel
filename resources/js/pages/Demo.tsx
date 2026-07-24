import { Head, usePage } from '@inertiajs/react';
import type { SharedProps } from '@/lib/types';

interface DemoProps {
    message: string;
}

// Phase 0 smoke page: proves the Inertia + React pipeline renders and that
// shared props (locale/auth) reach the client. Removed once real pages land.
export default function Demo({ message }: DemoProps) {
    const { locale, auth } = usePage<SharedProps & DemoProps>().props;

    return (
        <>
            <Head title="Inertia demo" />
            <main>
                <h1>{message}</h1>
                <p>Locale: {locale.current}</p>
                <p>Authenticated: {auth.user ? auth.user.name : 'guest'}</p>
            </main>
        </>
    );
}
