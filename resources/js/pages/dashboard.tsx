import { Form, Head, Link } from '@inertiajs/react';
import { ArrowDown, ArrowUp, FoldVertical, FolderPlus, Pin, Plus, UnfoldVertical } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { ResourceList, type LibraryResource } from '@/components/resource-list';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import {
    collapse as collapseCollection,
    move as moveCollection,
    pin as pinCollection,
    show as showCollection,
    store as storeCollection,
} from '@/routes/collections';
import { store as storeResource } from '@/routes/resources';

type CollectionItem = {
    id: string;
    name: string;
    description: string;
    accent_color: string;
    resources_count: number;
    is_pinned_to_home: boolean;
    is_collapsed: boolean;
};

type ResourceItem = LibraryResource;

function CollectionCard({
    collection,
    index,
    total,
}: {
    collection: CollectionItem;
    index: number;
    total: number;
}) {
    return (
        <article
            className="flex items-start justify-between gap-3 rounded-xl border p-4"
            style={{
                borderTopColor: collection.accent_color,
                borderTopWidth: 3,
            }}
        >
            <Link href={showCollection(collection.id)} className="min-w-0">
                <h3 className="font-medium">{collection.name}</h3>
                {!collection.is_collapsed && (
                    <p className="text-muted-foreground mt-1 text-sm">
                        {collection.resources_count}{' '}
                        {collection.resources_count === 1 ? 'link' : 'links'}
                    </p>
                )}
            </Link>
            <div className="flex shrink-0 items-center">
                <Form {...pinCollection.form(collection.id)}>
                    <Button
                        type="submit"
                        variant="ghost"
                        size="icon"
                        aria-label={
                            collection.is_pinned_to_home
                                ? 'Losmaken van het dashboard'
                                : 'Vastzetten op het dashboard'
                        }
                    >
                        <Pin
                            className={
                                collection.is_pinned_to_home
                                    ? 'fill-current'
                                    : undefined
                            }
                        />
                    </Button>
                </Form>
                <Form {...collapseCollection.form(collection.id)}>
                    <Button
                        type="submit"
                        variant="ghost"
                        size="icon"
                        aria-label={
                            collection.is_collapsed ? 'Uitklappen' : 'Inklappen'
                        }
                    >
                        {collection.is_collapsed ? (
                            <UnfoldVertical />
                        ) : (
                            <FoldVertical />
                        )}
                    </Button>
                </Form>
                <Form {...moveCollection.form(collection.id)}>
                    <input type="hidden" name="direction" value="up" />
                    <Button
                        type="submit"
                        variant="ghost"
                        size="icon"
                        aria-label="Omhoog"
                        disabled={index === 0}
                    >
                        <ArrowUp />
                    </Button>
                </Form>
                <Form {...moveCollection.form(collection.id)}>
                    <input type="hidden" name="direction" value="down" />
                    <Button
                        type="submit"
                        variant="ghost"
                        size="icon"
                        aria-label="Omlaag"
                        disabled={index === total - 1}
                    >
                        <ArrowDown />
                    </Button>
                </Form>
            </div>
        </article>
    );
}

function LinkDialog({ collections }: { collections: CollectionItem[] }) {
    const [open, setOpen] = useState(false);
    const collectionOptions = [...collections].sort((a, b) =>
        a.name.localeCompare(b.name, 'nl'),
    );

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button">
                    <Plus />
                    Link toevoegen
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Link toevoegen</DialogTitle>
                    <DialogDescription>
                        Plak een adres. De titel kun je leeg laten.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...storeResource.form()}
                    resetOnSuccess
                    disableWhileProcessing
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
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
                                    {collectionOptions.map((collection) => (
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
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Annuleren
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Opslaan
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function CollectionDialog() {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button" variant="outline">
                    <FolderPlus />
                    Collectie maken
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Collectie maken</DialogTitle>
                    <DialogDescription>
                        Geef de collectie een naam.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...storeCollection.form()}
                    resetOnSuccess
                    disableWhileProcessing
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
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
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Annuleren
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Collectie aanmaken
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function Dashboard({
    collections,
    resources,
    linkFilter,
}: {
    collections: CollectionItem[];
    resources: ResourceItem[];
    linkFilter: 'active' | 'favorites' | 'archived';
}) {
    const pinned = collections.filter((collection) => collection.is_pinned_to_home);
    const unpinned = collections.filter(
        (collection) => !collection.is_pinned_to_home,
    );
    const showGroupLabels = pinned.length > 0 && unpinned.length > 0;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap gap-2">
                    <LinkDialog collections={collections} />
                    <CollectionDialog />
                </div>

                <section className="grid gap-3">
                    <h2 className="text-sm font-medium">Collecties</h2>
                    {collections.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            Nog geen collecties.
                        </p>
                    ) : (
                        <div className="grid gap-6">
                            {pinned.length > 0 && (
                                <div className="grid gap-3">
                                    {showGroupLabels && (
                                        <h3 className="text-muted-foreground text-sm">
                                            Op het dashboard
                                        </h3>
                                    )}
                                    {pinned.map((collection, index) => (
                                        <CollectionCard
                                            key={collection.id}
                                            collection={collection}
                                            index={index}
                                            total={pinned.length}
                                        />
                                    ))}
                                </div>
                            )}
                            {unpinned.length > 0 && (
                                <div className="grid gap-3">
                                    <h3 className="text-muted-foreground text-sm">
                                        Niet op het dashboard
                                    </h3>
                                    {unpinned.map((collection, index) => (
                                        <CollectionCard
                                            key={collection.id}
                                            collection={collection}
                                            index={index}
                                            total={unpinned.length}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                </section>

                <section className="grid gap-3">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-sm font-medium">Links</h2>
                        <div className="flex gap-3">
                            {(
                                [
                                    ['active', 'Alles', dashboard()],
                                    [
                                        'favorites',
                                        'Favorieten',
                                        dashboard.url({
                                            query: { links: 'favorites' },
                                        }),
                                    ],
                                    [
                                        'archived',
                                        'Archief',
                                        dashboard.url({
                                            query: { links: 'archived' },
                                        }),
                                    ],
                                ] as const
                            ).map(([key, label, href]) => (
                                <Link
                                    key={key}
                                    href={href}
                                    aria-current={
                                        linkFilter === key ? 'page' : undefined
                                    }
                                    className={
                                        linkFilter === key
                                            ? 'text-sm font-medium'
                                            : 'text-muted-foreground text-sm'
                                    }
                                >
                                    {label}
                                </Link>
                            ))}
                        </div>
                    </div>
                    {resources.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            {linkFilter === 'favorites'
                                ? 'Nog geen favorieten.'
                                : linkFilter === 'archived'
                                  ? 'Het archief is leeg.'
                                  : 'Nog geen links.'}
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
