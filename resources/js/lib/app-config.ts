/** From VITE_APP_NAME, which .env copies from APP_NAME; the fallback is the only other place the name is written. */
export const APP_NAME = import.meta.env.VITE_APP_NAME || 'Panel operativo de incidencias';

/**
 * A visit only shows as loading after this long, so a fast response does not flicker. The progress bar and the
 * list's dimming wait the same; Tailwind needs the class written out in full to generate it.
 */
export const LOADING_DELAY_MS = 150;
export const LOADING_DELAY_CLASS = 'delay-150';
