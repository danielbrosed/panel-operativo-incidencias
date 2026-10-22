import { Button } from '@/components/ui/button';
import { FieldError } from '@/components/ui/field';
import type { StatusChangeTarget } from '@/hooks/use-status-change';
import type { Transition } from '@/types/incident';

interface StatusActionsProps {
    incident: StatusChangeTarget;
    size?: 'sm' | 'md';
    busy?: boolean;
    error?: string;
    onChoose: (incident: StatusChangeTarget, transition: Transition) => void;
}

/** One button per transition the server allows: the UI has no copy of the state machine. */
export function StatusActions({ incident, size = 'sm', busy = false, error, onChoose }: StatusActionsProps) {
    return (
        <div className="flex flex-col gap-2">
            {/* useStatusChange finds the buttons by this attribute to put focus back on them. */}
            <div data-status-actions={incident.id} className="flex flex-wrap gap-2">
                {incident.transitions.map((transition) => (
                    <Button
                        key={transition.to.value}
                        variant="secondary"
                        size={size}
                        aria-disabled={busy || undefined}
                        onClick={() => onChoose(incident, transition)}
                    >
                        {transition.action_label}
                    </Button>
                ))}
            </div>
            {error && <FieldError role="alert">{error}</FieldError>}
        </div>
    );
}
