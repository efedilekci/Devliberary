import { useEffect, useState } from 'react';

export type LinkView = 'list' | 'grid';

const storageKey = 'devliberary-link-view';

export function useLinkView(): [LinkView, (view: LinkView) => void] {
    const [view, setView] = useState<LinkView>('list');

    useEffect(() => {
        const stored = window.localStorage.getItem(storageKey);

        if (stored === 'list' || stored === 'grid') {
            setView(stored);
        }
    }, []);

    const update = (next: LinkView) => {
        setView(next);
        window.localStorage.setItem(storageKey, next);
    };

    return [view, update];
}
