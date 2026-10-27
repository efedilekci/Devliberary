import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            libraryNav: {
                linkCount: number;
                favoriteCount: number;
                collections: Array<{
                    id: string;
                    name: string;
                    icon: string;
                    accent_color: string;
                    resources_count: number;
                }>;
            } | null;
            [key: string]: unknown;
        };
    }
}
