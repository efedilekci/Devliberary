import type { MouseEvent, ReactNode } from 'react';
import { rememberOpened } from '@/lib/remember-opened';

export function linkHref(url: string): string {
    const trimmed = url.trim();

    if (trimmed === '' || trimmed.startsWith('//') || /^[a-z][a-z0-9+.-]*:\/\//i.test(trimmed)) {
        return trimmed;
    }

    return `https://${trimmed}`;
}

export function OpenLink({
    href,
    resourceId,
    className,
    children,
}: {
    href: string;
    resourceId: string;
    className?: string;
    children: ReactNode;
}) {
    function track(event: MouseEvent<HTMLAnchorElement>): void {
        if (event.button !== 0 && event.button !== 1) {
            return;
        }

        rememberOpened(resourceId);
    }

    return (
        <a
            href={linkHref(href)}
            target="_blank"
            rel="noreferrer"
            className={className}
            onClick={track}
            onAuxClick={track}
        >
            {children}
        </a>
    );
}
