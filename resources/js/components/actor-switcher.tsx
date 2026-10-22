import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Select } from '@/components/ui/form-controls';

/**
 * "Actuando como": there is no login in this exercise, so the operator picks who they are. The server keeps it in
 * the session and signs every status change and comment with it.
 */
export function ActorSwitcher() {
    const { actor } = usePage().props;
    // The name being saved, shown until the response brings it back as actor.current.
    const [saving, setSaving] = useState<string | null>(null);

    return (
        <div className="flex items-center gap-3">
            <label htmlFor="actor" className="text-sm font-medium whitespace-nowrap text-muted">
                Actuando como
            </label>
            <Select
                id="actor"
                value={saving ?? actor.current}
                onChange={(event) => {
                    const name = event.target.value;
                    setSaving(name);
                    router.put(
                        actor.update_url,
                        { name },
                        { preserveScroll: true, preserveState: true, onFinish: () => setSaving(null) },
                    );
                }}
                className="w-48"
            >
                {actor.options.map((name) => (
                    <option key={name}>{name}</option>
                ))}
            </Select>
        </div>
    );
}
