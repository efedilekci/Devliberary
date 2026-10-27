import InputError from '@/components/input-error';
import { cn } from '@/lib/utils';
import { CheckIcon, ChevronDownIcon } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';

export function CollectionChecks({
    collections,
    selectedIds,
    lockedIds = [],
    idPrefix,
    error,
}: {
    collections: Array<{ id: string; name: string }>;
    selectedIds: string[];
    lockedIds?: string[];
    idPrefix: string;
    error?: string;
}) {
    const options = [...collections].sort((a, b) => a.name.localeCompare(b.name, 'nl'));
    const [selected, setSelected] = useState(selectedIds);
    const [open, setOpen] = useState(false);
    const rootRef = useRef<HTMLDivElement>(null);
    const listId = useId();
    const names = options.filter((option) => selected.includes(option.id)).map((option) => option.name);

    useEffect(() => {
        if (!open) {
            return;
        }

        function onPointerDown(event: PointerEvent): void {
            if (!rootRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        document.addEventListener('pointerdown', onPointerDown);

        return () => document.removeEventListener('pointerdown', onPointerDown);
    }, [open]);

    function toggle(id: string): void {
        if (lockedIds.includes(id)) {
            return;
        }

        setSelected((current) =>
            current.includes(id) ? current.filter((item) => item !== id) : [...current, id],
        );
    }

    return (
        <div ref={rootRef} className="grid gap-2">
            <label htmlFor={idPrefix} className="text-sm leading-none font-medium">
                Collecties
            </label>
            <input type="hidden" name="collection_ids[]" value="" />
            {selected.map((id) => (
                <input key={id} type="hidden" name="collection_ids[]" value={id} />
            ))}
            {options.length === 0 ? (
                <p className="text-muted-foreground text-sm">Nog geen collecties.</p>
            ) : (
                <div className="grid gap-1">
                    <button
                        id={idPrefix}
                        type="button"
                        aria-expanded={open}
                        aria-controls={listId}
                        className="border-input focus-visible:border-ring focus-visible:ring-ring/50 dark:bg-input/30 dark:hover:bg-input/50 flex h-9 w-full items-center justify-between gap-2 rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                        onClick={() => setOpen((current) => !current)}
                        onKeyDown={(event) => {
                            if (event.key === 'Escape' && open) {
                                event.preventDefault();
                                event.stopPropagation();
                                setOpen(false);
                            }
                        }}
                    >
                        <span className={cn('truncate', names.length === 0 && 'text-muted-foreground')}>
                            {names.length === 0 ? 'Geen collectie' : names.join(', ')}
                        </span>
                        <ChevronDownIcon className={cn('size-4 shrink-0 opacity-50', open && 'rotate-180')} />
                    </button>
                    {open ? (
                        <div
                            id={listId}
                            role="listbox"
                            aria-multiselectable="true"
                            className="bg-popover max-h-40 overflow-y-auto rounded-md border p-1 shadow-md"
                            onKeyDown={(event) => {
                                if (event.key === 'Escape') {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    setOpen(false);
                                }
                            }}
                        >
                            {options.map((collection) => {
                                const checked = selected.includes(collection.id);
                                const locked = lockedIds.includes(collection.id);

                                return (
                                    <button
                                        key={collection.id}
                                        type="button"
                                        role="option"
                                        aria-selected={checked}
                                        aria-disabled={locked}
                                        className={cn(
                                            'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm',
                                            locked ? 'cursor-default' : 'hover:bg-accent',
                                        )}
                                        onClick={() => toggle(collection.id)}
                                    >
                                        <span className="flex-1 truncate">{collection.name}</span>
                                        {checked ? <CheckIcon className="text-primary size-4" /> : null}
                                    </button>
                                );
                            })}
                        </div>
                    ) : null}
                </div>
            )}
            <InputError message={error} />
        </div>
    );
}
