import { Head } from '@inertiajs/react';
import { CollectionGlyph } from '@/components/collection-appearance';
import {
    LinkViewToggle,
    ResourceList,
    type LibraryResource,
} from '@/components/resource-list';
import { useLinkView } from '@/hooks/use-link-view';
import { dashboard } from '@/routes';
import { index as collectionsIndex } from '@/routes/collections';

type CollectionItem = {
    id: string;
    name: string;
    description: string;
    icon: string;
    accent_color: string;
};

export default function CollectionShow({
    collection,
    resources,
}: {
    collection: CollectionItem;
    resources: LibraryResource[];
}) {
    const [linkView, setLinkView] = useLinkView();

    return (
        <>
            <Head title={collection.name} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div
                    className="rounded-xl border p-4"
                    style={{
                        borderTopColor: collection.accent_color,
                        borderTopWidth: 3,
                    }}
                >
                    <h1 className="flex items-center gap-2 text-xl font-medium">
                        <CollectionGlyph
                            name={collection.icon}
                            color={collection.accent_color}
                            className="size-5 shrink-0"
                        />
                        {collection.name}
                    </h1>
                    {collection.description !== '' && (
                        <p className="text-muted-foreground mt-1 text-sm">
                            {collection.description}
                        </p>
                    )}
                </div>
                {resources.length > 0 && (
                    <div className="flex justify-end">
                        <LinkViewToggle view={linkView} onChange={setLinkView} />
                    </div>
                )}
                {resources.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        Nog geen links in deze collectie.
                    </p>
                ) : (
                    <ResourceList resources={resources} view={linkView} />
                )}
            </div>
        </>
    );
}

CollectionShow.layout = {
    breadcrumbs: [
        {
            title: 'Home',
            href: dashboard(),
        },
        {
            title: 'Collecties',
            href: collectionsIndex(),
        },
    ],
};
