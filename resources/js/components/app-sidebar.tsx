import { Link, usePage } from '@inertiajs/react';
import { Archive, Clock, Code, Folders, Layers, LayoutGrid, Star } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { collectionIcon } from '@/components/collection-appearance';
import { dashboard } from '@/routes';
import { index as collectionsIndex, show as showCollection } from '@/routes/collections';
import { index as snippets } from '@/routes/snippets';
import type { NavItem } from '@/types';

const footerNavItems: NavItem[] = [];

function isCollectionsIndex(url: string): boolean {
    return url.split('?')[0] === '/collections';
}

function linkFilter(url: string): 'active' | 'all' | 'favorites' | 'archived' | 'recent' | null {
    const [path, query = ''] = url.split('?');

    if (path !== '/dashboard') {
        return null;
    }

    const links = new URLSearchParams(query).get('links');

    if (links === 'all' || links === 'favorites' || links === 'archived' || links === 'recent') {
        return links;
    }

    return 'active';
}

export function AppSidebar() {
    const { url, props } = usePage();
    const libraryNav = props.libraryNav;
    const filter = linkFilter(url);

    const mainNavItems: NavItem[] = [
        {
            title: 'Home',
            href: dashboard(),
            icon: LayoutGrid,
            isActive: filter === 'active',
        },
        {
            title: 'Alle links',
            href: dashboard.url({ query: { links: 'all' } }),
            icon: Layers,
            isActive: filter === 'all',
            badge: libraryNav?.linkCount,
        },
        {
            title: 'Favorieten',
            href: dashboard.url({ query: { links: 'favorites' } }),
            icon: Star,
            isActive: filter === 'favorites',
            badge: libraryNav?.favoriteCount,
        },
        {
            title: 'Recent geopend',
            href: dashboard.url({ query: { links: 'recent' } }),
            icon: Clock,
            isActive: filter === 'recent',
        },
        {
            title: 'Collecties',
            href: collectionsIndex(),
            icon: Folders,
            isActive: isCollectionsIndex(url),
        },
        {
            title: 'Archief',
            href: dashboard.url({ query: { links: 'archived' } }),
            icon: Archive,
            isActive: filter === 'archived',
        },
        {
            title: 'Snippets',
            href: snippets(),
            icon: Code,
        },
    ];

    const collectionItems: NavItem[] = (libraryNav?.collections ?? []).map(
        (collection) => ({
            title: collection.name,
            href: showCollection(collection.id),
            icon: collectionIcon(collection.icon),
            iconColor: collection.accent_color,
            badge: collection.resources_count,
        }),
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {collectionItems.length > 0 && (
                    <NavMain label="Jouw collecties" items={collectionItems} />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
