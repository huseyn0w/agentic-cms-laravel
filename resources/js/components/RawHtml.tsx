import { useEffect, useRef } from 'react';

/**
 * Injects a server-rendered HTML snippet and re-executes any <script> it
 * carries (innerHTML alone never runs scripts). Used for the captcha widget,
 * which is empty when no reCAPTCHA keys are configured.
 */
export function RawHtml({ html }: { html: string }) {
    const ref = useRef<HTMLDivElement>(null);
    useEffect(() => {
        const host = ref.current;
        if (!host) return;
        host.innerHTML = html;
        host.querySelectorAll('script').forEach((old) => {
            const script = document.createElement('script');
            [...old.attributes].forEach((attr) => script.setAttribute(attr.name, attr.value));
            script.textContent = old.textContent;
            old.replaceWith(script);
        });
    }, [html]);
    return <div ref={ref} />;
}
