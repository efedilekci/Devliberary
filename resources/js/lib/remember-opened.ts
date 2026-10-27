import { opened } from '@/routes/resources';

function xsrfToken(): string {
    const row = document.cookie.split('; ').find((part) => part.startsWith('XSRF-TOKEN='));

    if (row === undefined) {
        return '';
    }

    return decodeURIComponent(row.slice('XSRF-TOKEN='.length));
}

export function rememberOpened(id: string): void {
    const token = xsrfToken();
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (token !== '') {
        headers['X-XSRF-TOKEN'] = token;
    }

    void fetch(opened.url(id), {
        method: 'POST',
        headers,
        credentials: 'same-origin',
        keepalive: true,
    }).catch(() => undefined);
}
