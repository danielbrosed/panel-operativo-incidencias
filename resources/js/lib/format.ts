// The operations team works on Spanish time, whatever the browser's time zone.
const TIME_ZONE = 'Europe/Madrid';

const dateTime = new Intl.DateTimeFormat('es-ES', { dateStyle: 'medium', timeStyle: 'short', timeZone: TIME_ZONE });
const relative = new Intl.RelativeTimeFormat('es-ES', { numeric: 'auto', style: 'short' });

const UNITS: [Intl.RelativeTimeFormatUnit, number][] = [
    ['day', 86_400],
    ['hour', 3_600],
    ['minute', 60],
];

/** "22 oct 2026, 13:45" */
export function formatDateTime(iso: string): string {
    return dateTime.format(new Date(iso));
}

/** "hace 3 h", "dentro de 2 h", "ayer"; "ahora" under a minute. */
export function formatRelative(iso: string, now: number = Date.now()): string {
    const seconds = (new Date(iso).getTime() - now) / 1000;
    const match = UNITS.find(([, size]) => Math.abs(seconds) >= size);

    return match ? relative.format(Math.trunc(seconds / match[1]), match[0]) : 'ahora';
}
