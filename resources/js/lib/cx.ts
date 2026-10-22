/** Joins class names, skipping falsy ones: cx('a', isActive && 'b'). */
export function cx(...classes: (string | false | null | undefined)[]): string {
    return classes.filter(Boolean).join(' ');
}
