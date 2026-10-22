import type { ReactNode } from 'react';

export function PageTitle({ children }: { children: ReactNode }) {
    return <h1 className="text-2xl font-semibold text-navy sm:text-3xl">{children}</h1>;
}
