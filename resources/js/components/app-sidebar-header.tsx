import { router, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

function searchQuery(url: string): string {
    const query = url.split('?')[1] ?? '';

    return new URLSearchParams(query).get('q') ?? '';
}

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { url } = usePage();
    const query = searchQuery(url);

    function search(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const value = new FormData(event.currentTarget).get('q');
        const term = typeof value === 'string' ? value.trim() : '';

        router.get(dashboard.url(), term === '' ? {} : { q: term }, {
            preserveScroll: true,
        });
    }

    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex min-w-0 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <form
                onSubmit={search}
                className="ml-auto flex min-w-0 flex-1 items-center gap-2 sm:max-w-xs sm:flex-none"
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
        </header>
    );
}
