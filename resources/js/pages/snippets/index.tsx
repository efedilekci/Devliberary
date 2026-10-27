import { Form, Head } from '@inertiajs/react';
import { Check, Copy, Plus, Star, Trash2 } from 'lucide-react';
import { useState } from 'react';
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
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { destroy, favorite, index as snippetsIndex, store } from '@/routes/snippets';

type SnippetItem = {
    id: string;
    title: string;
    code: string;
    language: string;
    is_favorite: boolean;
};

type LanguageOption = {
    value: string;
    label: string;
};

function CopyButton({ code }: { code: string }) {
    const [copied, setCopied] = useState(false);

    return (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            aria-label={copied ? 'Gekopieerd' : 'Kopiëren'}
            onClick={() => {
                navigator.clipboard
                    .writeText(code)
                    .then(() => {
                        setCopied(true);
                        window.setTimeout(() => setCopied(false), 1500);
                    })
                    .catch(() => undefined);
            }}
        >
            {copied ? <Check /> : <Copy />}
        </Button>
    );
}

function SnippetDialog({ languages }: { languages: LanguageOption[] }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button">
                    <Plus />
                    Snippet toevoegen
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Snippet toevoegen</DialogTitle>
                    <DialogDescription>
                        Bewaar een stuk code met een titel en een taal.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...store.form()}
                    resetOnSuccess
                    disableWhileProcessing
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">Titel</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    type="text"
                                    required
                                    placeholder="Bijvoorbeeld Query helpers"
                                    autoComplete="off"
                                />
                                <InputError message={errors.title} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="language">Taal</Label>
                                <select
                                    id="language"
                                    name="language"
                                    defaultValue="text"
                                    className="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                >
                                    {languages.map((language) => (
                                        <option key={language.value} value={language.value}>
                                            {language.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.language} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="code">Code</Label>
                                <textarea
                                    id="code"
                                    name="code"
                                    required
                                    rows={8}
                                    spellCheck={false}
                                    className="border-input w-full rounded-md border bg-transparent px-3 py-2 font-mono text-sm"
                                />
                                <InputError message={errors.code} />
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

export default function Snippets({
    snippets,
    languages,
}: {
    snippets: SnippetItem[];
    languages: LanguageOption[];
}) {
    const labels = new Map(languages.map((language) => [language.value, language.label]));

    return (
        <>
            <Head title="Snippets" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <SnippetDialog languages={languages} />
                {snippets.length === 0 ? (
                    <p className="text-muted-foreground text-sm">Nog geen snippets.</p>
                ) : (
                    <ul className="grid gap-3">
                        {snippets.map((snippet) => (
                            <li key={snippet.id} className="grid gap-3 rounded-xl border p-4">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <h2 className="font-medium">{snippet.title}</h2>
                                        <p className="text-muted-foreground text-sm">
                                            {labels.get(snippet.language) ?? snippet.language}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 items-center">
                                        <CopyButton code={snippet.code} />
                                        <Form {...favorite.form(snippet.id)}>
                                            <Button
                                                type="submit"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={
                                                    snippet.is_favorite
                                                        ? 'Favoriet weghalen'
                                                        : 'Favoriet maken'
                                                }
                                            >
                                                <Star
                                                    className={
                                                        snippet.is_favorite
                                                            ? 'fill-current'
                                                            : undefined
                                                    }
                                                />
                                            </Button>
                                        </Form>
                                        <Dialog>
                                            <DialogTrigger asChild>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label="Snippet verwijderen"
                                                >
                                                    <Trash2 />
                                                </Button>
                                            </DialogTrigger>
                                            <DialogContent>
                                                <DialogTitle>Snippet verwijderen?</DialogTitle>
                                                <DialogDescription>
                                                    {snippet.title} verdwijnt uit je bibliotheek.
                                                </DialogDescription>
                                                <DialogFooter>
                                                    <DialogClose asChild>
                                                        <Button type="button" variant="secondary">
                                                            Annuleren
                                                        </Button>
                                                    </DialogClose>
                                                    <Form {...destroy.form(snippet.id)}>
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
                                </div>
                                <pre className="bg-muted max-h-64 overflow-auto rounded-md p-3 font-mono text-sm whitespace-pre-wrap">
                                    {snippet.code}
                                </pre>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

Snippets.layout = {
    breadcrumbs: [
        {
            title: 'Snippets',
            href: snippetsIndex(),
        },
    ],
};
