// A page marks the element that takes focus when the focused one disappears with `data-focus-fallback` and
// `tabIndex={-1}` (the incident list, for instance).

/** Whether the element is rendered. Safari before 17.4 has no checkVisibility(), and calling it there throws. */
export function isShown(element: Element): boolean {
    return typeof element.checkVisibility === 'function'
        ? element.checkVisibility()
        : element.getClientRects().length > 0;
}

/** True when focus has fallen to the page body or sits on something that is no longer shown. */
export function focusIsLost(): boolean {
    const active = document.activeElement;

    return active === null || active === document.body || !isShown(active);
}

/** Moves focus to the page's fallback target, without scrolling. */
export function focusFallback(): void {
    document.querySelector<HTMLElement>('[data-focus-fallback]')?.focus({ preventScroll: true });
}
