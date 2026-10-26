import { Head } from '@inertiajs/react';
import {
    ResourceList,
    type LibraryResource,
} from '@/components/resource-list';
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
                {resources.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        Nog geen links in deze collectie.
                    </p>
                ) : (
                    <ResourceList resources={resources} />
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
