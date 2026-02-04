/**
 * Returns a function to get the CSRF token from the document meta tag.
 * Use for fetch/axios requests that require Laravel CSRF protection.
 */
export function useCsrfToken(): () => string {
    return () =>
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}
