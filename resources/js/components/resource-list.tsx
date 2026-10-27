import { Form, usePage } from '@inertiajs/react';
import { Archive, ArchiveRestore, LayoutGrid, Link2, List, Pencil, Star, Trash2 } from 'lucide-react';
import { useState, useEffect } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import type { LinkView } from '@/hooks/use-link-view';
import { archive, bulk, destroy, favorite, update } from '@/routes/resources';

export type BulkAction = 'favorite' | 'unfavorite' | 'archive' | 'restore' | 'delete';

const bulkLabels: Record<BulkAction, string> = {
    favorite: 'Favoriet maken',
    unfavorite: 'Favoriet weghalen',
    archive: 'Archiveren',
    restore: 'Terugzetten',
    delete: 'Verwijderen',
};

const defaultBulkActions: BulkAction[] = ['favorite', 'unfavorite', 'archive', 'delete'];

export type LibraryResource = {
    id: string;
    title: string;
    url: string;
    description?: string;
    favicon_url: string;
    preview_image_url?: string;
    tags?: string[];
    is_favorite: boolean;
    is_archived: boolean;
    collection_id?: string | null;
};

function hostLabel(url: string): string {
    try {
        const host = new URL(url.includes('://') ? url : `https://${url}`).hostname;

        return host.replace(/^www\./, '');
    } catch {
        return url;
    }
}

function ResourcePreview({ url }: { url: string }) {
    const [failed, setFailed] = useState(false);

    if (url === '' || failed) {
        return <div className="bg-muted h-36 w-full" />;
    }

    return (
        <img
            src={url}
            alt=""
            referrerPolicy="no-referrer"
            className="h-36 w-full object-cover"
            onError={() => setFailed(true)}
        />
    );
}

export function ResourceMark({ url, compact = false }: { url: string; compact?: boolean }) {
    const [failed, setFailed] = useState(false);
    const box = compact ? 'size-5 rounded-sm' : 'size-8 rounded-md';
    const icon = compact ? 'size-3' : 'size-4';

    if (url === '' || failed) {
        return (
            <span
                className={`bg-muted text-muted-foreground flex shrink-0 items-center justify-center ${box}`}
            >
                <Link2 className={icon} />
            </span>
        );
    }

    return (
        <span className={`bg-muted flex shrink-0 items-center justify-center ${box}`}>
            <img
                src={url}
                alt=""
                referrerPolicy="no-referrer"
                className={icon}
                onError={() => setFailed(true)}
            />
        </span>
    );
}

function ResourceTags({ tags }: { tags?: string[] }) {
    if (!tags || tags.length === 0) {
        return null;
    }

    return (
        <ul className="mt-1 flex flex-wrap gap-1">
            {tags.map((tag) => (
                <li
                    key={tag}
                    className="bg-muted text-muted-foreground rounded-md px-1.5 py-0.5 text-xs"
                >
                    {tag}
                </li>
            ))}
        </ul>
    );
}

function ResourceBody({ resource }: { resource: LibraryResource }) {
    return (
        <div className="min-w-0">
            <a
                href={resource.url}
                target="_blank"
                rel="noreferrer"
                className="font-medium hover:underline"
            >
                {resource.title}
            </a>
            <p className="text-muted-foreground truncate text-sm">{resource.url}</p>
            {resource.description ? (
                <p className="text-muted-foreground line-clamp-1 text-sm">{resource.description}</p>
            ) : null}
            <ResourceTags tags={resource.tags} />
        </div>
    );
}

export function EditLinkButton({
    resource,
    collectionId,
    className,
}: {
    resource: {
        id: string;
        title: string;
        url: string;
        tags?: string[];
        collection_id?: string | null;
    };
    collectionId?: string | null;
    className?: string;
}) {
    const collections = usePage().props.libraryNav?.collections ?? [];
    const selected =
        collectionId !== undefined ? collectionId : (resource.collection_id ?? null);
    const [open, setOpen] = useState(false);
    const options = [...collections].sort((a, b) => a.name.localeCompare(b.name, 'nl'));

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className={className}
                    aria-label="Link bewerken"
                >
                    <Pencil />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Link bewerken</DialogTitle>
                <DialogDescription>
                    Pas de titel, het adres, de tags of de collectie aan.
                </DialogDescription>
                <Form
                    key={`${resource.id}-${resource.title}-${resource.url}-${selected ?? ''}-${(resource.tags ?? []).join(',')}`}
                    {...update.form(resource.id)}
                    disableWhileProcessing
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`url-${resource.id}`}>Link</Label>
                                <Input
                                    id={`url-${resource.id}`}
                                    name="url"
                                    type="text"
                                    required
                                    defaultValue={resource.url}
                                    autoComplete="off"
                                />
                                <InputError message={errors.url} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`title-${resource.id}`}>Titel</Label>
                                <Input
                                    id={`title-${resource.id}`}
                                    name="title"
                                    type="text"
                                    required
                                    defaultValue={resource.title}
                                    autoComplete="off"
                                />
                                <InputError message={errors.title} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`tags-${resource.id}`}>Tags</Label>
                                <Input
                                    id={`tags-${resource.id}`}
                                    name="tags"
                                    type="text"
                                    defaultValue={(resource.tags ?? []).join(', ')}
                                    placeholder="react, documentatie"
                                    autoComplete="off"
                                />
                                <p className="text-muted-foreground text-xs">
                                    Scheid tags met een komma.
                                </p>
                                <InputError message={errors.tags} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`collection-${resource.id}`}>Collectie</Label>
                                <select
                                    id={`collection-${resource.id}`}
                                    name="collection_id"
                                    defaultValue={selected ?? ''}
                                    className="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                >
                                    <option value="">Geen collectie</option>
                                    {options.map((collection) => (
                                        <option key={collection.id} value={collection.id}>
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

function ResourceActions({ resource }: { resource: LibraryResource }) {
    return (
        <div className="flex shrink-0 items-center">
            <EditLinkButton resource={resource} />
            <Form {...favorite.form(resource.id)}>
                <Button
                    type="submit"
                    variant="ghost"
                    size="icon"
                    aria-label={
                        resource.is_favorite ? 'Favoriet weghalen' : 'Favoriet maken'
                    }
                >
                    <Star
                        className={resource.is_favorite ? 'fill-current' : undefined}
                    />
                </Button>
            </Form>
            <Form {...archive.form(resource.id)}>
                <Button
                    type="submit"
                    variant="ghost"
                    size="icon"
                    aria-label={resource.is_archived ? 'Terugzetten' : 'Archiveren'}
                >
                    {resource.is_archived ? <ArchiveRestore /> : <Archive />}
                </Button>
            </Form>
            <Dialog>
                <DialogTrigger asChild>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Link verwijderen"
                    >
                        <Trash2 />
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogTitle>Link verwijderen?</DialogTitle>
                    <DialogDescription>
                        {resource.title} verdwijnt uit je bibliotheek.
                    </DialogDescription>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Annuleren
                            </Button>
                        </DialogClose>
                        <Form {...destroy.form(resource.id)}>
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    Verwijderen
                                </Button>
                            )}
                        </Form>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

export function LinkViewToggle({
    view,
    onChange,
}: {
    view: LinkView;
    onChange: (view: LinkView) => void;
}) {
    return (
        <ToggleGroup
            type="single"
            variant="outline"
            size="sm"
            value={view}
            onValueChange={(value) => {
                if (value === 'list' || value === 'grid') {
                    onChange(value);
                }
            }}
            aria-label="Weergave"
        >
            <ToggleGroupItem value="list" aria-label="Lijst">
                <List />
            </ToggleGroupItem>
            <ToggleGroupItem value="grid" aria-label="Raster">
                <LayoutGrid />
            </ToggleGroupItem>
        </ToggleGroup>
    );
}

function BulkForm({
    action,
    ids,
    destructive = false,
}: {
    action: BulkAction;
    ids: string[];
    destructive?: boolean;
}) {
    return (
        <Form {...bulk.form()} disableWhileProcessing>
            {({ processing }) => (
                <>
                    {ids.map((id) => (
                        <input key={id} type="hidden" name="ids[]" value={id} />
                    ))}
                    <input type="hidden" name="action" value={action} />
                    <Button
                        type="submit"
                        variant={destructive ? 'destructive' : 'outline'}
                        size="sm"
                        disabled={processing}
                    >
                        {bulkLabels[action]}
                    </Button>
                </>
            )}
        </Form>
    );
}

function BulkToolbar({
    resources,
    selected,
    onSelectedChange,
    actions,
}: {
    resources: LibraryResource[];
    selected: string[];
    onSelectedChange: (ids: string[]) => void;
    actions: BulkAction[];
}) {
    const allSelected = resources.every((resource) => selected.includes(resource.id));
    const someSelected = selected.length > 0 && !allSelected;

    return (
        <div className="flex flex-wrap items-center gap-2">
            <Checkbox
                checked={allSelected ? true : someSelected ? 'indeterminate' : false}
                onCheckedChange={() =>
                    onSelectedChange(allSelected ? [] : resources.map((resource) => resource.id))
                }
                aria-label="Alles selecteren"
            />
            {selected.length > 0 && (
                <>
                    <span className="text-muted-foreground text-sm">
                        {selected.length === 1
                            ? '1 geselecteerd'
                            : `${selected.length} geselecteerd`}
                    </span>
                    {actions
                        .filter((action) => action !== 'delete')
                        .map((action) => (
                            <BulkForm key={action} action={action} ids={selected} />
                        ))}
                    {actions.includes('delete') && (
                        <Dialog>
                            <DialogTrigger asChild>
                                <Button type="button" variant="outline" size="sm">
                                    Verwijderen
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogTitle>Links verwijderen?</DialogTitle>
                                <DialogDescription>
                                    {selected.length === 1
                                        ? '1 link verdwijnt uit je bibliotheek.'
                                        : `${selected.length} links verdwijnen uit je bibliotheek.`}
                                </DialogDescription>
                                <DialogFooter>
                                    <DialogClose asChild>
                                        <Button type="button" variant="secondary">
                                            Annuleren
                                        </Button>
                                    </DialogClose>
                                    <BulkForm action="delete" ids={selected} destructive />
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    )}
                </>
            )}
        </div>
    );
}

export function ResourceList({
    resources,
    view = 'list',
    bulkActions = defaultBulkActions,
}: {
    resources: LibraryResource[];
    view?: LinkView;
    bulkActions?: BulkAction[];
}) {
    const [selected, setSelected] = useState<string[]>([]);

    useEffect(() => {
        const ids = new Set(resources.map((resource) => resource.id));

        setSelected((current) => {
            const next = current.filter((id) => ids.has(id));

            return next.length === current.length ? current : next;
        });
    }, [resources]);

    const toggle = (id: string) => {
        setSelected((current) =>
            current.includes(id) ? current.filter((item) => item !== id) : [...current, id],
        );
    };

    return (
        <div className="grid gap-3">
            <BulkToolbar
                resources={resources}
                selected={selected}
                onSelectedChange={setSelected}
                actions={bulkActions}
            />
            {view === 'grid' ? (
                <ul className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {resources.map((resource) => (
                        <li
                            key={resource.id}
                            className="flex flex-col overflow-hidden rounded-xl border"
                        >
                            <ResourcePreview url={resource.preview_image_url ?? ''} />
                            <div className="flex flex-1 flex-col gap-3 p-4">
                                <div className="flex min-w-0 items-start gap-3">
                                    <Checkbox
                                        checked={selected.includes(resource.id)}
                                        onCheckedChange={() => toggle(resource.id)}
                                        aria-label={`Selecteer ${resource.title}`}
                                        className="mt-1"
                                    />
                                    <div className="min-w-0">
                                        <a
                                            href={resource.url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="font-medium hover:underline"
                                        >
                                            {resource.title}
                                        </a>
                                        {resource.description ? (
                                            <p className="text-muted-foreground mt-1 line-clamp-2 text-sm">
                                                {resource.description}
                                            </p>
                                        ) : null}
                                        <ResourceTags tags={resource.tags} />
                                    </div>
                                </div>
                                <div className="mt-auto flex items-center justify-between gap-2">
                                    <div className="flex min-w-0 items-center gap-2">
                                        <ResourceMark url={resource.favicon_url} />
                                        <span className="text-muted-foreground truncate text-xs">
                                            {hostLabel(resource.url)}
                                        </span>
                                    </div>
                                    <ResourceActions resource={resource} />
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            ) : (
                <ul className="divide-y rounded-xl border">
                    {resources.map((resource) => (
                        <li
                            key={resource.id}
                            className="flex items-center justify-between gap-3 px-4 py-3"
                        >
                            <div className="flex min-w-0 items-center gap-3">
                                <Checkbox
                                    checked={selected.includes(resource.id)}
                                    onCheckedChange={() => toggle(resource.id)}
                                    aria-label={`Selecteer ${resource.title}`}
                                />
                                <ResourceMark url={resource.favicon_url} />
                                <ResourceBody resource={resource} />
                            </div>
                            <ResourceActions resource={resource} />
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
