import { Copy } from 'lucide-react';
import { toast } from 'sonner';
import { linkHref } from '@/components/open-link';
import { Button } from '@/components/ui/button';

async function copyAddress(url: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(linkHref(url));
        toast.success('Adres gekopieerd.');
    } catch {
        toast.error('Het adres kon niet gekopieerd worden.');
    }
}

export function CopyLinkButton({ url, className }: { url: string; className?: string }) {
    return (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            className={className}
            aria-label="Adres kopiëren"
            onClick={() => {
                void copyAddress(url);
            }}
        >
            <Copy />
        </Button>
    );
}
