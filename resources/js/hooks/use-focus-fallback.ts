import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { focusFallback, focusIsLost } from '@/lib/focus';

/**
 * When the same page refreshes (a filter, another page of the list, a status change, a new incident), the element
 * that had focus may be gone: its row left the list or its button was replaced. Focus would drop to <body> and a
 * keyboard user would start again from the top, so it goes to the page's fallback target instead. Moving to another
 * page leaves focus alone, as a full page load would. Components that can put focus somewhere closer do it in their
 * own effects, which run before this one (children first).
 */
export function useFocusFallback() {
    const page = usePage();
    const previous = useRef(page);

    useEffect(() => {
        const before = previous.current;
        previous.current = page;
        if (before !== page && before.component === page.component && focusIsLost()) {
            focusFallback();
        }
    }, [page]);
}
