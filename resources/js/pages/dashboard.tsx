import { Head } from '@inertiajs/react';
import {
    CollectionBoard,
    type CollectionItem,
    type CollectionLink,
} from '@/components/collection-board';
import { LinkViewToggle, ResourceList, type LibraryResource } from '@/components/resource-list';
import { useLinkView } from '@/hooks/use-link-view';
import { dashboard } from '@/routes';

type ResourceItem = LibraryResource;

export default function Dashboard({
    collections,
    uncollected,
    resources,
    linkFilter,
    search,
}: {
    collections: CollectionItem[];
    uncollected: CollectionLink[];
    resources: ResourceItem[];
    linkFilter: 'active' | 'all' | 'favorites' | 'archived';
    search: string;
}) {
    const [linkView, setLinkView] = useLinkView();
    const pageTitle =
        search !== ''
            ? 'Zoekresultaten'
            : {
                  active: 'Home',
                  all: 'Alle links',
                  favorites: 'Favorieten',
                  archived: 'Archief',
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
                        <div className="flex justify-end">
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
