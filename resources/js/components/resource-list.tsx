import { Form } from '@inertiajs/react';
import { Archive, ArchiveRestore, LayoutGrid, Link2, List, Star, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
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
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import type { LinkView } from '@/hooks/use-link-view';
import { archive, bulk, destroy, favorite } from '@/routes/resources';

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
    favicon_url: string;
    is_favorite: boolean;
    is_archived: boolean;
};

function ResourceMark({ url }: { url: string }) {
    const [failed, setFailed] = useState(false);

    if (url === '' || failed) {
        return (
            <span className="bg-muted text-muted-foreground flex size-8 shrink-0 items-center justify-center rounded-md">
                <Link2 className="size-4" />
            </span>
        );
    }

    return (
        <span className="bg-muted flex size-8 shrink-0 items-center justify-center rounded-md">
            <img
                src={url}
                alt=""
                referrerPolicy="no-referrer"
                className="size-4"
                onError={() => setFailed(true)}
            />
        </span>
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
        </div>
    );
}

function ResourceActions({ resource }: { resource: LibraryResource }) {
    return (
        <div className="flex shrink-0 items-center">
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
                            className="flex flex-col gap-3 rounded-xl border p-4"
                        >
                            <div className="flex min-w-0 items-start gap-3">
                                <Checkbox
                                    checked={selected.includes(resource.id)}
                                    onCheckedChange={() => toggle(resource.id)}
                                    aria-label={`Selecteer ${resource.title}`}
                                    className="mt-1"
                                />
                                <ResourceMark url={resource.favicon_url} />
                                <ResourceBody resource={resource} />
                            </div>
                            <ResourceActions resource={resource} />
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
