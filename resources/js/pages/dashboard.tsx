import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { ResourceList, type LibraryResource } from '@/components/resource-list';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { show as showCollection, store as storeCollection } from '@/routes/collections';
import { store as storeResource } from '@/routes/resources';

type CollectionItem = {
    id: string;
    name: string;
    description: string;
    accent_color: string;
    resources_count: number;
};

type ResourceItem = LibraryResource;

export default function Dashboard({
    collections,
    resources,
}: {
    collections: CollectionItem[];
    resources: ResourceItem[];
}) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="grid gap-4 lg:grid-cols-2">
                    <section className="rounded-xl border p-4">
                        <h2 className="mb-4 text-sm font-medium">Link toevoegen</h2>
                        <Form
                            {...storeResource.form()}
                            resetOnSuccess
                            disableWhileProcessing
                            className="grid gap-3"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="url">Link</Label>
                                        <Input
                                            id="url"
                                            name="url"
                                            type="text"
                                            required
                                            placeholder="https://voorbeeld.nl"
                                            autoComplete="off"
                                        />
                                        <InputError message={errors.url} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="title">Titel</Label>
                                        <Input
                                            id="title"
                                            name="title"
                                            type="text"
                                            placeholder="Optioneel"
                                            autoComplete="off"
                                        />
                                        <InputError message={errors.title} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="collection_id">Collectie</Label>
                                        <select
                                            id="collection_id"
                                            name="collection_id"
                                            defaultValue=""
                                            className="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                        >
                                            <option value="">Geen collectie</option>
                                            {collections.map((collection) => (
                                                <option
                                                    key={collection.id}
                                                    value={collection.id}
                                                >
                                                    {collection.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.collection_id} />
                                    </div>
                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        Opslaan
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>

                    <section className="rounded-xl border p-4">
                        <h2 className="mb-4 text-sm font-medium">Collectie maken</h2>
                        <Form
                            {...storeCollection.form()}
                            resetOnSuccess
                            disableWhileProcessing
                            className="grid gap-3"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Naam</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            type="text"
                                            required
                                            placeholder="Bijvoorbeeld Laravel"
                                            autoComplete="off"
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        Collectie aanmaken
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>
                </div>

                <section className="grid gap-3">
                    <h2 className="text-sm font-medium">Collecties</h2>
                    {collections.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            Nog geen collecties.
                        </p>
                    ) : (
                        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            {collections.map((collection) => (
                                <Link
                                    key={collection.id}
                                    href={showCollection(collection.id)}
                                    className="rounded-xl border p-4"
                                    style={{
                                        borderTopColor: collection.accent_color,
                                        borderTopWidth: 3,
                                    }}
                                >
                                    <h3 className="font-medium">{collection.name}</h3>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        {collection.resources_count}{' '}
                                        {collection.resources_count === 1
                                            ? 'link'
                                            : 'links'}
                                    </p>
                                </Link>
                            ))}
                        </div>
                    )}
                </section>

                <section className="grid gap-3">
                    <h2 className="text-sm font-medium">Links</h2>
                    {resources.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            Nog geen links.
                        </p>
                    ) : (
                        <ResourceList resources={resources} />
                    )}
                </section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
