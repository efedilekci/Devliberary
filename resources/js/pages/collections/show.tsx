import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { CollectionGlyph } from '@/components/collection-appearance';
import { CollectionDialog, DeleteCollectionDialog } from '@/components/collection-board';
import {
    LinkViewToggle,
    ResourceList,
    type LibraryResource,
} from '@/components/resource-list';
import { Button } from '@/components/ui/button';
import { useLinkView } from '@/hooks/use-link-view';
import { dashboard } from '@/routes';
import { index as collectionsIndex } from '@/routes/collections';

type CollectionItem = {
    id: string;
    name: string;
    description: string;
    icon: string;
    accent_color: string;
    is_pinned_to_home: boolean;
};

export default function CollectionShow({
    collection,
    resources,
}: {
    collection: CollectionItem;
    resources: LibraryResource[];
}) {
    const [linkView, setLinkView] = useLinkView();
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

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
                    <div className="flex items-start justify-between gap-3">
                        <h1 className="flex min-w-0 items-center gap-2 text-xl font-medium">
                            <CollectionGlyph
                                name={collection.icon}
                                color={collection.accent_color}
                                className="size-5 shrink-0"
                            />
                            <span className="truncate">{collection.name}</span>
                        </h1>
                        <div className="flex shrink-0 items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setEditOpen(true)}
                            >
                                Bewerken
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                onClick={() => setDeleteOpen(true)}
                            >
                                Verwijderen
                            </Button>
                        </div>
                    </div>
                    <CollectionDialog
                        open={editOpen}
                        collection={collection}
                        onOpenChange={setEditOpen}
                    />
                    <DeleteCollectionDialog
                        open={deleteOpen}
                        collection={collection}
                        onOpenChange={setDeleteOpen}
                    />
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
