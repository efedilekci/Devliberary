import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Library } from 'lucide-react';
import ParticleDrift from '@/components/ui/particle-drift';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

export default function Welcome() {
    const { auth, name } = usePage().props;

    return (
        <>
            <Head title="Home" />
            <div className="relative min-h-screen overflow-hidden bg-[#030509] text-white">
                <div
                    className="pointer-events-none fixed inset-0 z-0"
                    aria-hidden
                >
                    <ParticleDrift
                        mode="auto"
                        className="h-full w-full"
                        density={1.1}
                        length={1.05}
                    />
                </div>

                <div
                    className="pointer-events-none absolute inset-0 z-[1] bg-[#030509]/55"
                    aria-hidden
                />

                <div className="relative z-10 flex min-h-screen flex-col">
                    <header className="flex items-center justify-between px-6 py-5 md:px-10">
                        <div className="flex items-center gap-2.5">
                            <div className="flex size-9 items-center justify-center rounded-lg border border-white/10 bg-white/5">
                                <Library
                                    className="size-5 text-[#60A5FA]"
                                    strokeWidth={1.5}
                                />
                            </div>
                            <span className="text-sm font-semibold tracking-tight">
                                {name}
                            </span>
                        </div>
                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Button
                                    asChild
                                    variant="secondary"
                                    className="border-white/10 bg-white/10 text-white hover:bg-white/15"
                                >
                                    <Link href={dashboard()} prefetch>
                                        Dashboard
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button
                                        asChild
                                        variant="ghost"
                                        className="text-white/80 hover:bg-white/10 hover:text-white"
                                    >
                                        <Link href={login()} prefetch>
                                            Inloggen
                                        </Link>
                                    </Button>
                                    <Button
                                        asChild
                                        className="bg-[#60A5FA] text-[#030509] hover:bg-blue-300"
                                    >
                                        <Link href={register()} prefetch>
                                            Registreren
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </header>

                    <main className="flex flex-1 flex-col justify-center px-6 pb-16 md:px-10 lg:px-16">
                        <div className="mx-auto w-full max-w-3xl">
                            <h1 className="mb-6 text-4xl leading-[1.05] font-light tracking-tight md:text-6xl lg:text-7xl">
                                Links, snippets en prompts,{' '}
                                <span className="text-white/90">
                                    overzichtelijk op één plek.
                                </span>
                            </h1>
                            <p className="mb-10 max-w-xl text-lg leading-relaxed font-light text-[#9CA3AF]">
                                Devliberary is je self-hosted workspace voor
                                bookmarks, collecties, code en AI-instructies.
                                Alles blijft van jou.
                            </p>
                            <div className="flex flex-wrap items-center gap-3">
                                {auth.user ? (
                                    <Button
                                        asChild
                                        size="lg"
                                        className="rounded-full bg-[#60A5FA] px-8 text-[#030509] hover:bg-blue-300"
                                    >
                                        <Link href={dashboard()} prefetch>
                                            Naar dashboard
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button
                                            asChild
                                            size="lg"
                                            className="rounded-full bg-[#60A5FA] px-8 text-[#030509] hover:bg-blue-300"
                                        >
                                            <Link href={register()} prefetch>
                                                Registreren
                                                <ArrowRight className="size-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            asChild
                                            size="lg"
                                            variant="outline"
                                            className="rounded-full border-white/15 bg-transparent text-white hover:bg-white/10"
                                        >
                                            <Link href={login()} prefetch>
                                                Ik heb al een account
                                            </Link>
                                        </Button>
                                    </>
                                )}
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
