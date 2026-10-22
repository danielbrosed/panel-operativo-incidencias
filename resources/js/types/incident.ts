/*
 * Shapes sent by the server. Each one mirrors a PHP class; change both together:
 *  - Incident        ← app/Http/Resources/IncidentResource.php
 *  - IncidentDetail  ← app/Http/Resources/IncidentDetailResource.php
 *  - TimelineEntry   ← app/Http/Resources/TimelineEntryResource.php
 *  - value unions    ← app/Enums/IncidentStatus.php and IncidentPriority.php
 */

export type IncidentStatusValue = 'open' | 'in_review' | 'blocked' | 'resolved';
export type IncidentPriorityValue = 'low' | 'medium' | 'high';

export interface Option<Value extends string = string> {
    value: Value;
    label: string;
}

export interface PriorityOption extends Option<IncidentPriorityValue> {
    impact: string;
    sla_hours: number;
}

/** A transition the server allows from the incident's current status. */
export interface Transition {
    to: Option<IncidentStatusValue>;
    action_label: string;
    requires_reason: boolean;
    requires_confirmation: boolean;
}

export interface Incident {
    id: number;
    title: string;
    store: string | null;
    status: Option<IncidentStatusValue>;
    priority: Option<IncidentPriorityValue> & { impact: string };
    requester_name: string;
    assigned_to: string | null;
    created_at: string;
    due_at: string;
    is_overdue: boolean;
    flagged_for_review: boolean;
    transitions: Transition[];
    links: { self: string; status: string };
}

export interface IncidentDetail extends Omit<Incident, 'links'> {
    description: string;
    updated_at: string;
    links: { self: string; status: string; comments: string };
}

export interface TimelineLogEntry {
    type: 'log';
    id: number;
    action: Option;
    old_value: string | null;
    new_value: string | null;
    old_label: string | null;
    new_label: string | null;
    user_name: string;
    reason: string | null;
    detail: string | null;
    inferred: boolean;
    created_at: string;
    voided: { at: string | null; reason: string | null } | null;
}

export interface TimelineCommentEntry {
    type: 'comment';
    id: number;
    author_name: string;
    body: string;
    created_at: string;
}

export type TimelineEntry = TimelineLogEntry | TimelineCommentEntry;

export interface IncidentFilters {
    status: IncidentStatusValue | null;
    priority: IncidentPriorityValue | null;
    q: string | null;
}

export type StatusCounts = Record<IncidentStatusValue, number>;

/** Laravel's paginated resource collection. */
export interface Paginated<Item> {
    data: Item[];
    links: { first: string | null; last: string | null; prev: string | null; next: string | null };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: { url: string | null; label: string; active: boolean }[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
}

export interface IncidentIndexProps {
    incidents: Paginated<Incident>;
    counts: StatusCounts;
    filters: IncidentFilters;
    options: {
        statuses: Option<IncidentStatusValue>[];
        priorities: PriorityOption[];
        operators: string[];
    };
    urls: { index: string; store: string };
}

export interface IncidentShowProps {
    incident: IncidentDetail;
    timeline: TimelineEntry[];
    urls: { index: string };
}
