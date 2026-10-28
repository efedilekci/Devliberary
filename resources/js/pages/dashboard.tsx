import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    CollectionBoard,
    type CollectionItem,
    type CollectionLink,
} from '@/components/collection-board';
import { LinkSortSelect, LinkViewToggle, ResourceList, type LibraryResource } from '@/components/resource-list';
import { useLinkView } from '@/hooks/use-link-view';
import { dashboard } from '@/routes';

type ResourceItem = LibraryResource;

export default function Dashboard({
    collections,
    uncollected,
    resources,
    linkFilter,
    search,
    sort,
    page,
    hasMore,
}: {
    collections: CollectionItem[];
    uncollected: CollectionLink[];
    resources: ResourceItem[];
    linkFilter: 'active' | 'all' | 'favorites' | 'archived' | 'recent';
    search: string;
    sort: 'newest' | 'oldest' | 'title' | 'opened';
    page: number;
    hasMore: boolean;
}) {
    const [linkView, setLinkView] = useLinkView();
    const sortOptions =
        linkFilter === 'recent'
            ? [
                  { value: 'opened', label: 'Geopend' },
                  { value: 'newest', label: 'Nieuwste' },
                  { value: 'oldest', label: 'Oudste' },
                  { value: 'title', label: 'Titel' },
              ]
            : [
                  { value: 'newest', label: 'Nieuwste' },
                  { value: 'oldest', label: 'Oudste' },
                  { value: 'title', label: 'Titel' },
              ];

    function changeSort(next: string): void {
        const params: Record<string, string> = {};
        const defaultSort = linkFilter === 'recent' ? 'opened' : 'newest';

        if (linkFilter !== 'active') {
            params.links = linkFilter;
        }

        if (search !== '') {
            params.q = search;
        }

        if (next !== defaultSort) {
            params.sort = next;
        }

        router.get(dashboard.url(), params, { preserveScroll: true, preserveState: true });
    }

    function loadMore(): void {
        const params: Record<string, string> = { page: String(page + 1) };
        const defaultSort = linkFilter === 'recent' ? 'opened' : 'newest';

        if (linkFilter !== 'active') {
            params.links = linkFilter;
        }

        if (search !== '') {
            params.q = search;
        }

        if (sort !== defaultSort) {
            params.sort = sort;
        }

        router.get(dashboard.url(), params, { preserveScroll: true, preserveState: true });
    }
    const pageTitle =
        search !== ''
            ? 'Zoekresultaten'
            : {
                  active: 'Home',
                  all: 'Alle links',
                  favorites: 'Favorieten',
                  archived: 'Archief',
                  recent: 'Recent geopend',
              }[linkFilter];
    const showBoard = linkFilter === 'active' && search === '';

    return (
        <>
            <Head title={pageTitle} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                {showBoard ? (
                <CollectionBoard
                    collections={collections}
                    uncollected={uncollected}
                    place="home"
                />
                ) : (
                    <>
                        <div className="flex justify-end gap-2">
                            <LinkSortSelect value={sort} options={sortOptions} onChange={changeSort} />
                            <LinkViewToggle view={linkView} onChange={setLinkView} />
                        </div>
                        <section className="grid gap-3">
                            <h2 className="text-sm font-medium">{pageTitle}</h2>
                            {resources.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    {search !== ''
                                        ? 'Geen links gevonden.'
                                        : linkFilter === 'favorites'
                                          ? 'Nog geen favorieten.'
                                          : linkFilter === 'archived'
                                            ? 'Het archief is leeg.'
                                            : linkFilter === 'recent'
                                              ? 'Nog niets geopend.'
                                              : 'Nog geen links.'}
                                </p>
                            ) : (
                                <ResourceList
                                    resources={resources}
                                    view={linkView}
                                    bulkActions={
                                        linkFilter === 'archived'
                                            ? ['restore', 'delete']
                                            : ['favorite', 'unfavorite', 'archive', 'delete']
                                    }
                                />
                            )}
                            {hasMore && (
                                <Button type="button" variant="outline" onClick={loadMore}>
                                    Meer laden
                                </Button>
                            )}
                        </section>
                    </>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Home',
            href: dashboard(),
        },
    ],
};
