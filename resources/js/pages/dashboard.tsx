import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="rounded-xl border border-sidebar-border/70 border-dashed p-8 text-center dark:border-sidebar-border">
                    <p className="text-muted-foreground text-sm">
                        Devliberary workspace. Hier komen straks je
                        collecties, links en meer.
                    </p>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
