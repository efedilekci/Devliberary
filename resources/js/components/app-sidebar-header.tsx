import { router, usePage } from '@inertiajs/react';
import { FolderPlus, Plus } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AddLinkDialog } from '@/components/add-link-dialog';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { CollectionDialog } from '@/components/collection-board';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { show as showCollection } from '@/routes/collections';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

function searchQuery(url: string): string {
    const query = url.split('?')[1] ?? '';

    return new URLSearchParams(query).get('q') ?? '';
}

function collectionIdFromUrl(url: string): string | null {
    const match = url.split('?')[0].match(/^\/collections\/([^/]+)$/);

    return match ? match[1] : null;
}

function linkFilterFromUrl(url: string): string | null {
    const [path, query = ''] = url.split('?');

    if (path !== '/dashboard') {
        return null;
    }

    const links = new URLSearchParams(query).get('links');

    if (links === 'all' || links === 'favorites' || links === 'archived' || links === 'recent') {
        return links;
    }

    return null;
}

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { url, props } = usePage();
    const query = searchQuery(url);
    const [linkOpen, setLinkOpen] = useState(false);
    const [collectionOpen, setCollectionOpen] = useState(false);
    const collections = props.libraryNav?.collections ?? [];
    const linkCollectionId = collectionIdFromUrl(url);

    function search(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const value = new FormData(event.currentTarget).get('q');
        const term = typeof value === 'string' ? value.trim() : '';

        if (linkCollectionId) {
            router.get(
                showCollection.url(linkCollectionId),
                term === '' ? {} : { q: term },
                { preserveScroll: true },
            );

            return;
        }

        const links = linkFilterFromUrl(url);
        const params: Record<string, string> = {};

        if (links !== null) {
            params.links = links;
        }

        if (term !== '') {
            params.q = term;
        }

        router.get(dashboard.url(), params, { preserveScroll: true });
    }

    return (
        <header className="flex min-h-16 shrink-0 flex-wrap items-center gap-2 border-b border-sidebar-border/50 px-6 py-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:min-h-12 md:px-4">
            <div className="flex min-w-0 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="ml-auto flex flex-wrap items-center justify-end gap-2">
                <Button type="button" variant="outline" size="sm" onClick={() => setLinkOpen(true)}>
                    <Plus />
                    Link toevoegen
                </Button>
                <Button type="button" size="sm" onClick={() => setCollectionOpen(true)}>
                    <FolderPlus />
                    Collectie maken
                </Button>
                <AddLinkDialog
                    collections={collections}
                    open={linkOpen}
                    collectionId={linkCollectionId}
                    onOpenChange={setLinkOpen}
                />
                <CollectionDialog open={collectionOpen} onOpenChange={setCollectionOpen} />
                <form
                    onSubmit={search}
                    className="flex min-w-0 items-center gap-2 sm:max-w-xs"
                >
                <label htmlFor="library-search" className="sr-only">
                    Zoek links
                </label>
                <Input
                    id="library-search"
                    name="q"
                    key={query}
                    type="search"
                    defaultValue={query}
                    placeholder="Zoek links"
                    className="h-8"
                    maxLength={200}
                />
                <Button type="submit" variant="secondary" size="sm">
                    Zoeken
                </Button>
            </form>
            </div>
        </header>
    );
}
