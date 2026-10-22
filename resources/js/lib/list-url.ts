const KEY = 'incidents:list-url';

/** Remembers the list's URL (filters and page) for the detail page's "Volver al listado". */
export function rememberListUrl(url: string): void {
    try {
        sessionStorage.setItem(KEY, url);
    } catch {
        // Storage blocked: the link falls back to the plain list.
    }
}

/** The last list URL seen in this tab, as long as it is same-origin and still points at the list; `fallback` otherwise. */
export function lastListUrl(fallback: string): string {
    try {
        const stored = sessionStorage.getItem(KEY);
        const listPath = new URL(fallback, location.origin).pathname;
        const candidate = stored !== null ? new URL(stored, location.origin) : null;
        if (candidate !== null && candidate.origin === location.origin && candidate.pathname === listPath) {
            // The parsed URL, not the stored text: a relative value would resolve against the detail page's path.
            return candidate.pathname + candidate.search;
        }
    } catch {
        // Storage blocked or a malformed value.
    }

    return fallback;
}
