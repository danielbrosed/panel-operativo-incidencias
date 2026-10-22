import { Head } from '@inertiajs/react';
import type { IncidentShowProps } from '@/types/incident';

export default function IncidentShow({ incident }: IncidentShowProps) {
    return (
        <>
            <Head title={`#${incident.id} ${incident.title}`} />
            <h1 className="text-2xl font-medium text-navy">{incident.title}</h1>
        </>
    );
}
