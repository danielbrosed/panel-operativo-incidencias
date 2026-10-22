import type { ReactNode } from 'react';
import { ActorSwitcher } from '@/components/actor-switcher';
import { FlashToaster } from '@/components/flash-toaster';

export default function AppLayout({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-dvh">
            <div className="h-1.5 bg-brand-gradient" aria-hidden="true" />
            <header className="border-b border-line bg-white">
                <div className="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-5">
                        <img src="/brand/optima-retail.svg" alt="Optima Retail" className="h-7 w-auto self-start" />
                        <div className="sm:border-l sm:border-line sm:pl-5">
                            <p className="font-semibold text-navy">Panel operativo de incidencias</p>
                            <p className="max-w-xl text-sm text-muted">
                                Consulta y opera las incidencias de mantenimiento de las tiendas: prioridad, estado,
                                responsable e historial de cada una.
                            </p>
                        </div>
                    </div>
                    <ActorSwitcher />
                </div>
            </header>
            <main className="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 sm:py-8">{children}</main>
            <FlashToaster />
        </div>
    );
}
