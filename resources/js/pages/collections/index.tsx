import { Head } from '@inertiajs/react';
import {
    CollectionBoard,
    type CollectionItem,
    type CollectionLink,
} from '@/components/collection-board';
import { index as collectionsIndex } from '@/routes/collections';

export default function CollectionsIndex({
    collections,
    uncollected,
}: {
    collections: CollectionItem[];
    uncollected: CollectionLink[];
}) {
    return (
        <>
            <Head title="Collecties" />
            <div className="flex flex-1 flex-col p-4">
                <CollectionBoard
                    collections={collections}
                    uncollected={uncollected}
                    place="collections"
                />
            </div>
        </>
    );
}

CollectionsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Collecties',
            href: collectionsIndex(),
        },
    ],
};
