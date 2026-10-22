import type { ReactNode } from 'react';

export default function AppLayout({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-dvh">
            <div className="h-1.5 bg-brand-gradient" aria-hidden="true" />
            <header className="border-b border-line bg-white">
                <div className="mx-auto flex max-w-7xl items-center gap-4 px-4 py-4 sm:px-6">
                    <img src="/brand/optima-retail.svg" alt="Optima Retail" className="h-7 w-auto" />
                </div>
            </header>
            <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6">{children}</main>
        </div>
    );
}
