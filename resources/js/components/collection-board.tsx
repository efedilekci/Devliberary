import { Form, Link } from '@inertiajs/react';
import { MoreHorizontal, Plus, Star } from 'lucide-react';
import { useEffect, useState } from 'react';
import { AddLinkDialog } from '@/components/add-link-dialog';
import {
    CollectionGlyph,
    ColorSwatches,
    IconPicker,
} from '@/components/collection-appearance';
import InputError from '@/components/input-error';
import { EditLinkButton, ResourceMark } from '@/components/resource-list';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    collapse as collapseCollection,
    move as moveCollection,
    pin as pinCollection,
    show as showCollection,
    store as storeCollection,
} from '@/routes/collections';
import { favorite as favoriteResource } from '@/routes/resources';

export type CollectionLink = {
    id: string;
    title: string;
    url: string;
    favicon_url: string;
    is_favorite: boolean;
    show_on_home: boolean;
    tags?: string[];
};

export type CollectionItem = {
    id: string;
    name: string;
    description: string;
    icon: string;
    accent_color: string;
    resources_count: number;
    is_pinned_to_home: boolean;
    is_collapsed: boolean;
    links: CollectionLink[];
};

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
        <article className="bg-card/60 flex min-w-0 flex-col overflow-hidden rounded-xl border">
            <header className="flex items-center gap-2 px-3 py-2.5">
                <CollectionGlyph name={collection.icon} color={collection.accent_color} />
                <Link
                    href={showCollection(collection.id)}
                    className="min-w-0 flex-1 truncate text-sm font-medium"
                >
                    {collection.name}
                </Link>
                <div className="flex shrink-0 items-center">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-7"
                        aria-label="Link aan deze collectie toevoegen"
                        onClick={() => onAddLink(collection.id)}
                    >
                        <Plus />
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="size-7"
                                aria-label="Collectie-opties"
                            >
                                <MoreHorizontal />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <Form {...pinCollection.form(collection.id)}>
                                <DropdownMenuItem asChild>
                                    <button type="submit" className="w-full">
                                        {collection.is_pinned_to_home
                                            ? 'Niet meer op home'
                                            : 'Op home zetten'}
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
                <ul className="grid min-w-0 gap-0.5 px-1.5 pb-2">
                    {collection.links.length === 0 ? (
                        <li className="text-muted-foreground px-2 py-2 text-sm">Nog geen links.</li>
                    ) : (
                        collection.links.map((link) => (
                            <li
                                key={link.id}
                                className="group flex min-w-0 items-center gap-2 rounded-md px-1.5 py-1"
                            >
                                <ResourceMark url={link.favicon_url} compact />
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
                                    className="size-7 shrink-0 sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100"
                                />
                                <Form {...favoriteResource.form(link.id)}>
                                    <Button
                                        type="submit"
                                        variant="ghost"
                                        size="icon"
                                        className="size-7 shrink-0"
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

export function CollectionDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [icon, setIcon] = useState('folder');
    const [color, setColor] = useState('#F97316');
    const [formKey, setFormKey] = useState(0);

    useEffect(() => {
        if (!open) {
            return;
        }

        setIcon('folder');
        setColor('#F97316');
        setFormKey((key) => key + 1);
    }, [open]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Collectie maken</DialogTitle>
                    <DialogDescription>
                        Geef de collectie een naam, een icoon en een kleur.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    key={formKey}
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
                                    placeholder="Bijvoorbeeld Design"
                                    autoComplete="off"
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">Beschrijving</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows={3}
                                    maxLength={500}
                                    placeholder="Wat hoort hier?"
                                    className="border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                                />
                                <InputError message={errors.description} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="icon" className="md:hidden">
                                        Icoon
                                    </Label>
                                    <Label htmlFor="icon-desktop" className="hidden md:inline">
                                        Icoon
                                    </Label>
                                    <IconPicker value={icon} color={color} onChange={setIcon} />
                                    <InputError message={errors.icon} />
                                </div>
                                <div className="grid content-start gap-2">
                                    <Label>Kleur</Label>
                                    <ColorSwatches value={color} onChange={setColor} />
                                    <InputError message={errors.accent_color} />
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <input type="hidden" name="show_on_home" value="0" />
                                <input
                                    id="show_on_home"
                                    name="show_on_home"
                                    type="checkbox"
                                    value="1"
                                    defaultChecked
                                    className="accent-primary size-4"
                                />
                                <Label htmlFor="show_on_home">Tonen op home</Label>
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

function groupPlace(collection: CollectionItem, collections: CollectionItem[]): {
    index: number;
    total: number;
} {
    const group = collections.filter(
        (item) => item.is_pinned_to_home === collection.is_pinned_to_home,
    );

    return {
        index: group.findIndex((item) => item.id === collection.id),
        total: group.length,
    };
}

export function CollectionBoard({
    collections,
    uncollected,
    place,
}: {
    collections: CollectionItem[];
    uncollected: CollectionLink[];
    place: 'home' | 'collections';
}) {
    const onHome = place === 'home';
    const [filter, setFilter] = useState('');
    const [linkOpen, setLinkOpen] = useState(false);
    const [linkCollectionId, setLinkCollectionId] = useState<string | null>(null);
    const [collectionOpen, setCollectionOpen] = useState(false);
    const needle = onHome ? '' : filter.trim().toLocaleLowerCase('nl');
    const pool = onHome
        ? collections.filter((collection) => collection.is_pinned_to_home)
        : collections;
    const visible =
        needle === ''
            ? pool
            : pool.filter((collection) =>
                  collection.name.toLocaleLowerCase('nl').includes(needle),
              );
    const loose = onHome
        ? uncollected.filter((link) => link.show_on_home)
        : uncollected;

    return (
        <section className="grid gap-4">
            {!onHome && (
                <div className="flex flex-wrap items-end justify-between gap-3 border-b">
                    <h2 className="border-primary border-b-2 px-1 pb-2 text-sm font-medium">
                        Jouw collecties
                    </h2>
                    <div className="mb-2">
                        <label htmlFor="collection-filter" className="sr-only">
                            Filter collecties
                        </label>
                        <Input
                            id="collection-filter"
                            type="search"
                            value={filter}
                            onChange={(event) => setFilter(event.target.value)}
                            placeholder="Filter collecties"
                            className="h-8 w-44"
                        />
                    </div>
                </div>
            )}
            <AddLinkDialog
                collections={collections}
                open={linkOpen}
                collectionId={linkCollectionId}
                onOpenChange={setLinkOpen}
            />
            <CollectionDialog open={collectionOpen} onOpenChange={setCollectionOpen} />
            {onHome && visible.length === 0 && (
                <p className="text-muted-foreground text-sm">Nog geen collecties op home.</p>
            )}
            {needle !== '' && visible.length === 0 ? (
                <p className="text-muted-foreground text-sm">Geen collecties gevonden.</p>
            ) : (
                <div className="grid items-start gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {visible.map((collection) => {
                        const spot = groupPlace(collection, collections);
                        const card = onHome
                            ? {
                                  ...collection,
                                  links: collection.links.filter((link) => link.show_on_home),
                                  resources_count: collection.links.filter((link) => link.show_on_home)
                                      .length,
                              }
                            : collection;

                        return (
                            <CollectionCard
                                key={collection.id}
                                collection={card}
                                index={spot.index}
                                total={spot.total}
                                onAddLink={(collectionId) => {
                                    setLinkCollectionId(collectionId);
                                    setLinkOpen(true);
                                }}
                            />
                        );
                    })}
                    {loose.length > 0 && needle === '' && (
                        <article className="bg-card/60 flex min-w-0 flex-col overflow-hidden rounded-xl border">
                            <header className="flex items-center gap-2 px-3 py-2.5">
                                <span className="bg-muted-foreground/40 size-2.5 shrink-0 rounded-[3px]" />
                                <h3 className="min-w-0 flex-1 truncate text-sm font-medium">
                                    Zonder collectie
                                </h3>
                                <span className="text-muted-foreground text-xs">
                                    {linkLabel(loose.length)}
                                </span>
                            </header>
                            <ul className="grid min-w-0 gap-0.5 px-1.5 pb-2">
                                {loose.map((link) => (
                                    <li
                                        key={link.id}
                                        className="group flex min-w-0 items-center gap-2 rounded-md px-1.5 py-1"
                                    >
                                        <ResourceMark url={link.favicon_url} compact />
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
                                            className="size-7 shrink-0 sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100"
                                        />
                                        <Form {...favoriteResource.form(link.id)}>
                                            <Button
                                                type="submit"
                                                variant="ghost"
                                                size="icon"
                                                className="size-7 shrink-0"
                                                aria-label={
                                                    link.is_favorite
                                                        ? 'Favoriet weghalen'
                                                        : 'Favoriet maken'
                                                }
                                            >
                                                <Star
                                                    className={
                                                        link.is_favorite ? 'fill-current' : undefined
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
                        className="text-muted-foreground hover:bg-muted/40 flex min-h-36 flex-col items-center justify-center gap-1 rounded-xl border border-dashed p-4 text-center"
                    >
                        <span className="text-foreground text-sm font-medium">
                            Nog ruimte voor een collectie.
                        </span>
                        <span className="text-sm">Maak een collectie.</span>
                    </button>
                </div>
            )}
        </section>
    );
}
