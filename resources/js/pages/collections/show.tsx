import { Head, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { AddLinkDialog } from '@/components/add-link-dialog';
import {
    LinkViewToggle,
    ResourceList,
    type LibraryResource,
} from '@/components/resource-list';
import { Button } from '@/components/ui/button';
import { useLinkView } from '@/hooks/use-link-view';
import { dashboard } from '@/routes';

type CollectionItem = {
    id: string;
    name: string;
    description: string;
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
    const [linkOpen, setLinkOpen] = useState(false);
    const collections = usePage().props.libraryNav?.collections ?? [];

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
                    <h1 className="text-xl font-medium">{collection.name}</h1>
                    {collection.description !== '' && (
                        <p className="text-muted-foreground mt-1 text-sm">
                            {collection.description}
                        </p>
                    )}
                </div>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Button type="button" onClick={() => setLinkOpen(true)}>
                        <Plus />
                        Link toevoegen
                    </Button>
                    <AddLinkDialog
                        collections={collections}
                        open={linkOpen}
                        collectionId={collection.id}
                        onOpenChange={setLinkOpen}
                    />
                    {resources.length > 0 && (
                        <LinkViewToggle view={linkView} onChange={setLinkView} />
                    )}
                </div>
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
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Collectie',
            href: dashboard(),
        },
    ],
};
