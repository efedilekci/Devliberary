import { Form, Head, Link } from '@inertiajs/react';
import { FolderPlus, MoreHorizontal, Plus, Star } from 'lucide-react';
import { useState } from 'react';
import { AddLinkDialog } from '@/components/add-link-dialog';
import InputError from '@/components/input-error';
import { EditLinkButton, LinkViewToggle, ResourceList, ResourceMark, type LibraryResource } from '@/components/resource-list';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import { useLinkView } from '@/hooks/use-link-view';
import { dashboard } from '@/routes';
import {
    collapse as collapseCollection,
    move as moveCollection,
    pin as pinCollection,
    show as showCollection,
    store as storeCollection,
} from '@/routes/collections';
import { favorite as favoriteResource } from '@/routes/resources';

type CollectionLink = {
    id: string;
    title: string;
    url: string;
    favicon_url: string;
    is_favorite: boolean;
    tags?: string[];
};

type CollectionItem = {
    id: string;
    name: string;
    description: string;
    accent_color: string;
    resources_count: number;
    is_pinned_to_home: boolean;
    is_collapsed: boolean;
    links: CollectionLink[];
};

type ResourceItem = LibraryResource;

function CollectionCard({
    collection,
    index,
    total,
    onAddLink,
}: {
    collection: CollectionItem;
    index: number;
    total: number;
    onAddLink: (collectionId: string) => void;
}) {
    const hidden = Math.max(collection.resources_count - collection.links.length, 0);

    return (
        <article
            className="flex min-h-40 flex-col rounded-xl border"
            style={{
                borderTopColor: collection.accent_color,
                borderTopWidth: 3,
            }}
        >
            <header className="flex items-start justify-between gap-2 px-3 pt-3">
                <Link href={showCollection(collection.id)} className="min-w-0">
                    <h3 className="font-medium">{collection.name}</h3>
                    <p className="text-muted-foreground text-sm">
                        {collection.resources_count}{' '}
                        {collection.resources_count === 1 ? 'link' : 'links'}
                    </p>
                </Link>
                <div className="flex shrink-0 items-center">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Link aan deze collectie toevoegen"
                        onClick={() => onAddLink(collection.id)}
                    >
                        <Plus />
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button type="button" variant="ghost" size="icon" aria-label="Collectie-opties">
                                <MoreHorizontal />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <Form {...pinCollection.form(collection.id)}>
                                <DropdownMenuItem asChild>
                                    <button type="submit" className="w-full">
                                        {collection.is_pinned_to_home
                                            ? 'Losmaken van het dashboard'
                                            : 'Vastzetten op het dashboard'}
                                    </button>
                                </DropdownMenuItem>
                            </Form>
                            <Form {...collapseCollection.form(collection.id)}>
                                <DropdownMenuItem asChild>
                                    <button type="submit" className="w-full">
                                        {collection.is_collapsed ? 'Uitklappen' : 'Inklappen'}
                                    </button>
                                </DropdownMenuItem>
                            </Form>
                            <Form {...moveCollection.form(collection.id)}>
                                <input type="hidden" name="direction" value="up" />
                                <DropdownMenuItem asChild disabled={index === 0}>
                                    <button type="submit" className="w-full" disabled={index === 0}>
                                        Omhoog
                                    </button>
                                </DropdownMenuItem>
                            </Form>
                            <Form {...moveCollection.form(collection.id)}>
                                <input type="hidden" name="direction" value="down" />
                                <DropdownMenuItem asChild disabled={index === total - 1}>
                                    <button type="submit" className="w-full" disabled={index === total - 1}>
                                        Omlaag
                                    </button>
                                </DropdownMenuItem>
                            </Form>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>
            {!collection.is_collapsed && (
                <ul className="grid gap-0.5 p-2">
                    {collection.links.length === 0 ? (
                        <li className="text-muted-foreground px-2 py-3 text-sm">Nog geen links.</li>
                    ) : (
                        collection.links.map((link) => (
                            <li key={link.id} className="flex items-center gap-2 rounded-md px-2 py-1.5">
                                <ResourceMark url={link.favicon_url} />
                                <a
                                    href={link.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="min-w-0 flex-1 truncate text-sm hover:underline"
                                >
                                    {link.title}
                                </a>
                                <EditLinkButton
                                    resource={link}
                                    collectionId={collection.id}
                                    className="size-7"
                                />
                                <Form {...favoriteResource.form(link.id)}>
                                    <Button
                                        type="submit"
                                        variant="ghost"
                                        size="icon"
                                        className="size-7"
                                        aria-label={
                                            link.is_favorite ? 'Favoriet weghalen' : 'Favoriet maken'
                                        }
                                    >
                                        <Star
                                            className={link.is_favorite ? 'fill-current' : undefined}
                                        />
                                    </Button>
                                </Form>
                            </li>
                        ))
                    )}
                    {hidden > 0 && (
                        <li className="px-2 py-1">
                            <Link
                                href={showCollection(collection.id)}
                                className="text-muted-foreground text-sm hover:underline"
                            >
                                Nog {hidden} {hidden === 1 ? 'link' : 'links'}
                            </Link>
                        </li>
                    )}
                </ul>
            )}
        </article>
    );
}

function CollectionDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
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
                    onSuccess={() => onOpenChange(false)}
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

function linkLabel(count: number): string {
    return `${count} ${count === 1 ? 'link' : 'links'}`;
}

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
    const pinned = collections.filter((collection) => collection.is_pinned_to_home);
    const unpinned = collections.filter(
        (collection) => !collection.is_pinned_to_home,
    );
    const showGroupLabels = pinned.length > 0 && unpinned.length > 0;
    const [linkView, setLinkView] = useLinkView();
    const [linkOpen, setLinkOpen] = useState(false);
    const [linkCollectionId, setLinkCollectionId] = useState<string | null>(null);
    const [collectionOpen, setCollectionOpen] = useState(false);
    const pageTitle =
        search !== ''
            ? 'Zoekresultaten'
            : {
                  active: 'Dashboard',
                  all: 'Alle links',
                  favorites: 'Favorieten',
                  archived: 'Archief',
              }[linkFilter];

    return (
        <>
            <Head title={pageTitle} />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            onClick={() => {
                                setLinkCollectionId(null);
                                setLinkOpen(true);
                            }}
                        >
                            <Plus />
                            Link toevoegen
                        </Button>
                        <AddLinkDialog
                            collections={collections}
                            open={linkOpen}
                            collectionId={linkCollectionId}
                            onOpenChange={setLinkOpen}
                        />
                        <CollectionDialog
                            open={collectionOpen}
                            onOpenChange={setCollectionOpen}
                        />
                    </div>
                    {(linkFilter !== 'active' || search !== '') && (
                        <LinkViewToggle view={linkView} onChange={setLinkView} />
                    )}
                </div>

                {linkFilter === 'active' && search === '' ? (
                    <section className="grid gap-3">
                        <h2 className="text-sm font-medium">Collecties</h2>
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {showGroupLabels && (
                                <h3 className="text-muted-foreground text-sm sm:col-span-2 xl:col-span-3">
                                    Op het dashboard
                                </h3>
                            )}
                            {pinned.map((collection, index) => (
                                <CollectionCard
                                    key={collection.id}
                                    collection={collection}
                                    index={index}
                                    total={pinned.length}
                                    onAddLink={(collectionId) => {
                                        setLinkCollectionId(collectionId);
                                        setLinkOpen(true);
                                    }}
                                />
                            ))}
                            {showGroupLabels && (
                                <h3 className="text-muted-foreground text-sm sm:col-span-2 xl:col-span-3">
                                    Niet op het dashboard
                                </h3>
                            )}
                            {unpinned.map((collection, index) => (
                                <CollectionCard
                                    key={collection.id}
                                    collection={collection}
                                    index={index}
                                    total={unpinned.length}
                                    onAddLink={(collectionId) => {
                                        setLinkCollectionId(collectionId);
                                        setLinkOpen(true);
                                    }}
                                />
                            ))}
                            {uncollected.length > 0 && (
                                <article className="flex min-h-40 flex-col rounded-xl border">
                                    <header className="px-3 pt-3">
                                        <h3 className="font-medium">Zonder collectie</h3>
                                        <p className="text-muted-foreground text-sm">
                                            {linkLabel(uncollected.length)}
                                        </p>
                                    </header>
                                    <ul className="grid gap-0.5 p-2">
                                        {uncollected.map((link) => (
                                            <li
                                                key={link.id}
                                                className="flex items-center gap-2 rounded-md px-2 py-1.5"
                                            >
                                                <ResourceMark url={link.favicon_url} />
                                                <a
                                                    href={link.url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="min-w-0 flex-1 truncate text-sm hover:underline"
                                                >
                                                    {link.title}
                                                </a>
                                                <EditLinkButton
                                                    resource={link}
                                                    collectionId={null}
                                                    className="size-7"
                                                />
                                                <Form {...favoriteResource.form(link.id)}>
                                                    <Button
                                                        type="submit"
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-7"
                                                        aria-label={
                                                            link.is_favorite
                                                                ? 'Favoriet weghalen'
                                                                : 'Favoriet maken'
                                                        }
                                                    >
                                                        <Star
                                                            className={
                                                                link.is_favorite
                                                                    ? 'fill-current'
                                                                    : undefined
                                                            }
                                                        />
                                                    </Button>
                                                </Form>
                                            </li>
                                        ))}
                                    </ul>
                                </article>
                            )}
                            <button
                                type="button"
                                onClick={() => setCollectionOpen(true)}
                                className="text-muted-foreground hover:bg-muted/40 flex min-h-40 flex-col items-center justify-center gap-1 rounded-xl border border-dashed p-4 text-center"
                            >
                                <span className="text-foreground text-sm font-medium">
                                    Nog ruimte voor een collectie.
                                </span>
                                <span className="text-sm">Maak een collectie.</span>
                            </button>
                        </div>
                    </section>
                ) : (
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
                )}
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
