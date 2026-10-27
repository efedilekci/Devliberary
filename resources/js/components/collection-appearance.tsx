import {
    Bookmark,
    BookOpen,
    Box,
    Check,
    Code,
    Cpu,
    Database,
    FileText,
    Folder,
    Globe,
    Heart,
    Image,
    Layers,
    Lightbulb,
    Palette,
    Rocket,
    Star,
    Terminal,
    Wrench,
    type LucideIcon,
} from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export const collectionIcons: Array<{ value: string; label: string; icon: LucideIcon }> = [
    { value: 'folder', label: 'Map', icon: Folder },
    { value: 'bookmark', label: 'Bladwijzer', icon: Bookmark },
    { value: 'star', label: 'Ster', icon: Star },
    { value: 'heart', label: 'Hart', icon: Heart },
    { value: 'code', label: 'Code', icon: Code },
    { value: 'terminal', label: 'Terminal', icon: Terminal },
    { value: 'book', label: 'Boek', icon: BookOpen },
    { value: 'lightbulb', label: 'Lamp', icon: Lightbulb },
    { value: 'palette', label: 'Palet', icon: Palette },
    { value: 'wrench', label: 'Gereedschap', icon: Wrench },
    { value: 'globe', label: 'Wereld', icon: Globe },
    { value: 'layers', label: 'Lagen', icon: Layers },
    { value: 'box', label: 'Doos', icon: Box },
    { value: 'image', label: 'Afbeelding', icon: Image },
    { value: 'file-text', label: 'Notitie', icon: FileText },
    { value: 'cpu', label: 'Processor', icon: Cpu },
    { value: 'database', label: 'Database', icon: Database },
    { value: 'rocket', label: 'Raket', icon: Rocket },
];

export const collectionColors: Array<{ value: string; label: string }> = [
    { value: '#F97316', label: 'Oranje' },
    { value: '#EAB308', label: 'Geel' },
    { value: '#22C55E', label: 'Groen' },
    { value: '#14B8A6', label: 'Turquoise' },
    { value: '#3B82F6', label: 'Blauw' },
    { value: '#A855F7', label: 'Paars' },
    { value: '#EC4899', label: 'Roze' },
    { value: '#9CA3AF', label: 'Grijs' },
];

export function collectionIcon(name: string): LucideIcon {
    return collectionIcons.find((item) => item.value === name)?.icon ?? Folder;
}

export function CollectionGlyph({
    name,
    color,
    className = 'size-4 shrink-0',
}: {
    name: string;
    color: string;
    className?: string;
}) {
    const Icon = collectionIcon(name);

    return <Icon className={className} style={{ color }} />;
}

export function IconPicker({
    value,
    color,
    onChange,
}: {
    value: string;
    color: string;
    onChange: (icon: string) => void;
}) {
    const label = collectionIcons.find((item) => item.value === value)?.label ?? 'Map';

    return (
        <div className="flex items-center gap-2">
            <input type="hidden" name="icon" value={value} />
            <CollectionGlyph name={value} color={color} className="size-4 shrink-0 md:hidden" />
            <select
                id="icon"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm md:hidden"
            >
                {collectionIcons.map((item) => (
                    <option key={item.value} value={item.value}>
                        {item.label}
                    </option>
                ))}
            </select>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger id="icon-desktop" className="hidden w-full md:flex">
                    <CollectionGlyph name={value} color={color} />
                    <SelectValue>{label}</SelectValue>
                </SelectTrigger>
                <SelectContent align="start" side="bottom" className="max-h-56">
                    {collectionIcons.map((item) => {
                        const Icon = item.icon;

                        return (
                            <SelectItem key={item.value} value={item.value}>
                                <Icon className="size-4" style={{ color }} />
                                {item.label}
                            </SelectItem>
                        );
                    })}
                </SelectContent>
            </Select>
        </div>
    );
}

export function ColorSwatches({
    value,
    onChange,
}: {
    value: string;
    onChange: (color: string) => void;
}) {
    return (
        <div className="flex flex-wrap items-center gap-1.5" role="radiogroup" aria-label="Kleur">
            {collectionColors.map((color) => {
                const selected = value.toUpperCase() === color.value.toUpperCase();
                const light = color.value === '#EAB308' || color.value === '#9CA3AF';

                return (
                    <label key={color.value} className="cursor-pointer">
                        <input
                            type="radio"
                            name="accent_color"
                            value={color.value}
                            checked={selected}
                            onChange={() => onChange(color.value)}
                            className="sr-only"
                        />
                        <span
                            className="flex size-5 items-center justify-center rounded-full"
                            style={{
                                backgroundColor: color.value,
                                boxShadow: selected ? `0 0 0 2px var(--background), 0 0 0 4px ${color.value}` : undefined,
                            }}
                        >
                            {selected && (
                                <Check className={light ? 'size-3.5 text-neutral-900' : 'size-3.5 text-white'} />
                            )}
                            <span className="sr-only">{color.label}</span>
                        </span>
                    </label>
                );
            })}
        </div>
    );
}
