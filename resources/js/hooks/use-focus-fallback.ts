import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { focusFallback, focusIsLost } from '@/lib/focus';

/**
 * When the same page refreshes and the focused element is gone, focus goes to the page's fallback instead of <body>,
 * so a keyboard user does not start again from the top. Effects closer to the element run first (children first).
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
