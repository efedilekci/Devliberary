import { Form } from '@inertiajs/react';
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
import { store as storeResource } from '@/routes/resources';

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

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Link toevoegen</DialogTitle>
                    <DialogDescription>
                        Plak een adres. De titel kun je leeg laten.
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
