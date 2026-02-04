/**
 * Returns the image src for a user's profile picture.
 * Supports both full URLs (e.g. from seed API) and storage paths.
 */
export function profilePictureSrc(pathOrUrl: string | null | undefined): string {
    if (pathOrUrl == null || pathOrUrl === '') return '';
    if (pathOrUrl.startsWith('http://') || pathOrUrl.startsWith('https://')) {
        return pathOrUrl;
    }
    return `/storage/${pathOrUrl}`;
}
