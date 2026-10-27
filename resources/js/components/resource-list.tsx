import { Form } from '@inertiajs/react';
import { Archive, ArchiveRestore, LayoutGrid, Link2, List, Star, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
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
import { archive, destroy, favorite } from '@/routes/resources';

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

export function ResourceList({
    resources,
    view = 'list',
}: {
    resources: LibraryResource[];
    view?: LinkView;
}) {
    if (view === 'grid') {
        return (
            <ul className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                {resources.map((resource) => (
                    <li
                        key={resource.id}
                        className="flex flex-col gap-3 rounded-xl border p-4"
                    >
                        <div className="flex min-w-0 items-start gap-3">
                            <ResourceMark url={resource.favicon_url} />
                            <ResourceBody resource={resource} />
                        </div>
                        <ResourceActions resource={resource} />
                    </li>
                ))}
            </ul>
        );
    }

    return (
        <ul className="divide-y rounded-xl border">
            {resources.map((resource) => (
                <li
                    key={resource.id}
                    className="flex items-center justify-between gap-3 px-4 py-3"
                >
                    <div className="flex min-w-0 items-center gap-3">
                        <ResourceMark url={resource.favicon_url} />
                        <ResourceBody resource={resource} />
                    </div>
                    <ResourceActions resource={resource} />
                </li>
            ))}
        </ul>
    );
}
