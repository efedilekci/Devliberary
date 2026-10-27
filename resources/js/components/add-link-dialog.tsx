import { Form } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { preview, store as storeResource } from '@/routes/resources';

type PreviewStatus = 'idle' | 'loading' | 'found' | 'empty';

function looksLikeUrl(value: string): boolean {
    const trimmed = value.trim();

    return trimmed.length >= 4 && trimmed.includes('.') && !/\s/.test(trimmed);
}

function csrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export function AddLinkDialog({
    collections,
    open,
    onOpenChange,
    collectionId,
}: {
    collections: Array<{ id: string; name: string }>;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    collectionId: string | null;
}) {
    const collectionOptions = [...collections].sort((a, b) =>
        a.name.localeCompare(b.name, 'nl'),
    );
    const [url, setUrl] = useState('');
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [previewImage, setPreviewImage] = useState('');
    const [status, setStatus] = useState<PreviewStatus>('idle');
    const titleTouched = useRef(false);
    const descriptionTouched = useRef(false);
    const suggestedTitle = useRef('');
    const titleValue = useRef('');
    titleValue.current = title;

    useEffect(() => {
        if (!open) {
            return;
        }

        setUrl('');
        setTitle('');
        setDescription('');
        setPreviewImage('');
        setStatus('idle');
        titleTouched.current = false;
        descriptionTouched.current = false;
        suggestedTitle.current = '';
    }, [open, collectionId]);

    useEffect(() => {
        if (!open || !looksLikeUrl(url)) {
            setStatus('idle');
            setPreviewImage('');

            if (!titleTouched.current) {
                setTitle('');
                suggestedTitle.current = '';
            }

            if (!descriptionTouched.current) {
                setDescription('');
            }

            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(() => {
            setStatus('loading');

            void fetch(preview.url(), {
                method: 'post',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ url: url.trim() }),
                signal: controller.signal,
            })
                .then(async (response) => {
                    if (!response.ok) {
                        setStatus('empty');
                        setPreviewImage('');

                        return;
                    }

                    const data = (await response.json()) as {
                        title?: string;
                        description?: string;
                        preview_image_url?: string;
                    };
                    const nextTitle = data.title ?? '';

                    if (!titleTouched.current || titleValue.current === suggestedTitle.current) {
                        setTitle(nextTitle);
                        suggestedTitle.current = nextTitle;
                        titleTouched.current = false;
                    }

                    if (!descriptionTouched.current) {
                        setDescription(data.description ?? '');
                    }

                    setPreviewImage(data.preview_image_url ?? '');
                    setStatus(nextTitle === '' ? 'empty' : 'found');
                })
                .catch((error: unknown) => {
                    if (error instanceof DOMException && error.name === 'AbortError') {
                        return;
                    }

                    setStatus('empty');
                    setPreviewImage('');
                });
        }, 500);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [open, url]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Link toevoegen</DialogTitle>
                    <DialogDescription>
                        Plak een adres. De titel wordt opgehaald.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    key={collectionId ?? 'geen'}
                    {...storeResource.form()}
                    resetOnSuccess
                    disableWhileProcessing
                    onSuccess={() => onOpenChange(false)}
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
                                    value={url}
                                    placeholder="https://voorbeeld.nl"
                                    autoComplete="off"
                                    onChange={(event) => setUrl(event.target.value)}
                                />
                                {status === 'loading' && (
                                    <p className="text-muted-foreground flex items-center gap-2 text-xs">
                                        <Spinner />
                                        Titel ophalen...
                                    </p>
                                )}
                                {status === 'found' && (
                                    <p className="text-muted-foreground text-xs">
                                        Titel gevonden. Je kunt hem nog aanpassen.
                                    </p>
                                )}
                                {status === 'empty' && (
                                    <p className="text-muted-foreground text-xs">
                                        Geen titel gevonden. Vul er zelf een in.
                                    </p>
                                )}
                                <InputError message={errors.url} />
                            </div>
                            {previewImage !== '' && status === 'found' && (
                                <img
                                    src={previewImage}
                                    alt=""
                                    referrerPolicy="no-referrer"
                                    className="h-32 w-full rounded-md object-cover"
                                />
                            )}
                            <div className="grid gap-2">
                                <Label htmlFor="title">Titel</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    type="text"
                                    value={title}
                                    placeholder={status === 'loading' ? 'Wordt opgehaald' : 'Optioneel'}
                                    autoComplete="off"
                                    onChange={(event) => {
                                        titleTouched.current = true;
                                        setTitle(event.target.value);
                                    }}
                                />
                                <InputError message={errors.title} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">Beschrijving</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows={3}
                                    value={description}
                                    placeholder="Optioneel"
                                    className="border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                                    onChange={(event) => {
                                        descriptionTouched.current = true;
                                        setDescription(event.target.value);
                                    }}
                                />
                                <InputError message={errors.description} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="tags">Tags</Label>
                                <Input
                                    id="tags"
                                    name="tags"
                                    type="text"
                                    placeholder="react, documentatie"
                                    autoComplete="off"
                                />
                                <p className="text-muted-foreground text-xs">
                                    Scheid tags met een komma.
                                </p>
                                <InputError message={errors.tags} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="collection_id">Collectie</Label>
                                <select
                                    id="collection_id"
                                    name="collection_id"
                                    defaultValue={collectionId ?? ''}
                                    className="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                >
                                    <option value="">Geen collectie</option>
                                    {collectionOptions.map((collection) => (
                                        <option key={collection.id} value={collection.id}>
                                            {collection.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.collection_id} />
                            </div>
                            <div className="flex items-center gap-2">
                                <input type="hidden" name="show_on_home" value="0" />
                                <input
                                    id="link-show-on-home"
                                    name="show_on_home"
                                    type="checkbox"
                                    value="1"
                                    defaultChecked
                                    className="accent-primary size-4"
                                />
                                <Label htmlFor="link-show-on-home">Tonen op home</Label>
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
