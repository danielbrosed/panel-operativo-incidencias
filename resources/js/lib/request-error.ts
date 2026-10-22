/**
 * An expired session (419) or the rate limit (429) comes back as a validation error on the key "request", which is
 * not a field of any form: the form treats it as a failure and keeps what was typed.
 */
export function requestError(errors: object): string | undefined {
    return 'request' in errors && typeof errors.request === 'string' ? errors.request : undefined;
}
