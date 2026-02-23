/**
 * Browser (native) notifications: permission, preference, and title/body for realtime payloads.
 */
import { ref, shallowRef } from 'vue';

const STORAGE_KEY = 'browser_notifications_enabled';

export interface RealtimeNotificationPayload {
    id: number;
    type: string;
    from_user_id: number;
    from_user: {
        id: number;
        display_name?: string;
        fullname?: string;
        profile_picture?: string | null;
    } | null;
    notifiable_type: string;
    notifiable_id: number | null;
    data: Record<string, unknown> | null;
    read_at: string | null;
    created_at: string;
}

export function useBrowserNotifications() {
    const isSupported = typeof window !== 'undefined' && 'Notification' in window;
    const permission = shallowRef<NotificationPermission>(
        isSupported ? Notification.permission : 'denied'
    );
    const isEnabled = ref(
        isSupported && localStorage.getItem(STORAGE_KEY) === '1'
    );

    function refreshPermission() {
        if (isSupported) {
            permission.value = Notification.permission;
            // If user revoked permission in browser, clear the "enabled" preference so UI stays in sync
            if (Notification.permission === 'denied' && isEnabled.value) {
                localStorage.removeItem(STORAGE_KEY);
                isEnabled.value = false;
            }
        }
    }

    function setEnabled(value: boolean) {
        if (isSupported) {
            if (value) {
                localStorage.setItem(STORAGE_KEY, '1');
            } else {
                localStorage.removeItem(STORAGE_KEY);
            }
            isEnabled.value = value;
        }
    }

    async function requestPermission(): Promise<NotificationPermission> {
        if (!isSupported) return 'denied';
        const result = await Notification.requestPermission();
        permission.value = result;
        if (result === 'granted') {
            setEnabled(true);
        }
        return result;
    }

    return {
        isSupported,
        permission,
        isEnabled,
        setEnabled,
        requestPermission,
        refreshPermission,
    };
}

/**
 * Build title and body for the native Notification from a realtime payload.
 */
export function getNotificationTitleAndBody(
    payload: RealtimeNotificationPayload
): { title: string; body: string } {
    const name =
        payload.from_user?.display_name ||
        payload.from_user?.fullname ||
        'Someone';
    const d = payload.data ?? {};

    switch (payload.type) {
        case 'message': {
            const excerpt = (d.excerpt as string) || 'New message';
            return {
                title: name,
                body: excerpt,
            };
        }
        case 'comment':
            return {
                title: 'New comment',
                body: `${name} commented on your post`,
            };
        case 'like':
            return {
                title: 'New like',
                body: `${name} liked your post`,
            };
        case 'follow':
            return {
                title: 'New follower',
                body: `${name} started following you`,
            };
        case 'comment_like':
            return {
                title: 'Comment liked',
                body: `${name} liked your comment`,
            };
        case 'message_request':
            return {
                title: 'Message request',
                body: `${name} wants to message you`,
            };
        case 'message_request_accepted':
            return {
                title: 'Request accepted',
                body: `${name} accepted your message request`,
            };
        case 'match_dating':
            return {
                title: 'Heart match',
                body: `${name} sent you a heart match`,
            };
        case 'match_friend':
            return {
                title: 'Smile match',
                body: `${name} sent you a smile match`,
            };
        case 'match_study_buddy':
            return {
                title: 'Study buddy',
                body: `${name} wants to be your study buddy`,
            };
        case 'mutual_match':
            return {
                title: "It's a match!",
                body: `You and ${name} matched`,
            };
        case 'high_compatibility_match': {
            const score = d.compatibility_score as number | undefined;
            const pct = score != null ? `${score}%` : '70%+';
            return {
                title: 'High match',
                body: `${name} has you as a ${pct} match!`,
            };
        }
        case 'nearby_match':
            return {
                title: 'Match nearby',
                body: `Hey! ${name} is nearby—say hi or plan to meet up!`,
            };
        case 'test':
            return {
                title: 'NEMSU Match - Test',
                body: 'This is a test notification from Pusher. Browser notifications are working.',
            };
        default:
            return {
                title: 'NEMSU Match',
                body: `${name} interacted with you`,
            };
    }
}

/**
 * Show a native browser notification if permission granted and preference enabled.
 * Call from a global listener when document is hidden (user on another tab or minimized).
 */
export function showBrowserNotificationIfAllowed(
    payload: RealtimeNotificationPayload,
    appName = 'NEMSU Match'
): void {
    if (typeof window === 'undefined' || !('Notification' in window)) return;
    if (Notification.permission !== 'granted') return;
    if (localStorage.getItem(STORAGE_KEY) !== '1') return;
    // Show when tab is in background; also show test notifications when focused so superadmin can verify
    if (!document.hidden && payload.type !== 'test') return;

    const { title, body } = getNotificationTitleAndBody(payload);
    const iconPath = payload.from_user?.profile_picture as string | undefined;
    const icon = iconPath
        ? `${window.location.origin}/storage/${iconPath.replace(/^\//, '')}`
        : undefined;
    const notification = new Notification(title, {
        body,
        icon,
        tag: `notification-${payload.id}`,
        requireInteraction: false,
    });
    notification.onclick = () => {
        window.focus();
        notification.close();
        if (payload.type === 'message' || payload.type === 'message_request' || payload.type === 'message_request_accepted') {
            const convId = payload.notifiable_type === 'conversation' ? payload.notifiable_id : (payload.data?.conversation_id as number);
            if (convId) {
                window.location.href = `${window.location.origin}/chat?conversation=${convId}`;
            } else {
                window.location.href = `${window.location.origin}/chat`;
            }
        } else if (payload.type === 'mutual_match' || payload.type === 'nearby_match') {
            window.location.href = `${window.location.origin}/like-you?tab=matches&show_match=${payload.from_user_id}`;
        } else if (payload.type === 'match_dating' || payload.type === 'match_friend' || payload.type === 'match_study_buddy') {
            window.location.href = `${window.location.origin}/like-you?tab=match_back`;
        } else if (payload.type === 'follow') {
            window.location.href = `${window.location.origin}/profile/${payload.from_user_id}`;
        } else {
            window.location.href = `${window.location.origin}/notifications`;
        }
    };
}
