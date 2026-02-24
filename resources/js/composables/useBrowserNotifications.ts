/**
 * Browser (native) notifications: permission, preference, and title/body for realtime payloads.
 * On iOS: Web Notifications only work in iOS 16.4+ and when the app is added to Home Screen (PWA).
 */
import { ref, shallowRef, computed } from 'vue';

const STORAGE_KEY = 'browser_notifications_enabled';

/** Detect iOS (iPhone, iPad, iPod) via userAgent only — avoid false positives on touchscreen Macs/PCs. */
export function isIOSUserAgent(): boolean {
    if (typeof navigator === 'undefined') return false;
    return /iPad|iPhone|iPod/.test(navigator.userAgent);
}

/** True when running as standalone (e.g. added to home screen). */
export function isStandalone(): boolean {
    if (typeof window === 'undefined') return false;
    const w = window as Window & { standalone?: boolean };
    return !!w.navigator?.standalone || (w.matchMedia?.('(display-mode: standalone)')?.matches ?? false);
}

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
    const hasNotificationAPI = typeof window !== 'undefined' && 'Notification' in window;
    const ios = typeof window !== 'undefined' ? isIOSUserAgent() : false;
    /** Show the toggle whenever the browser has the Notification API (non‑iOS and iOS both get the switch). */
    const isSupported = hasNotificationAPI;
    const isIOS = ref(ios);
    /** On iOS, notifications only work when app is added to Home Screen; show hint when not standalone. */
    const needsPWAHint = computed(() => ios && !isStandalone());
    const permission = shallowRef<NotificationPermission>(
        hasNotificationAPI ? Notification.permission : 'denied'
    );
    const isEnabled = ref(
        hasNotificationAPI && localStorage.getItem(STORAGE_KEY) === '1'
    );

    function refreshPermission() {
        if (hasNotificationAPI) {
            permission.value = Notification.permission;
            // If user revoked permission in browser, clear the "enabled" preference so UI stays in sync
            if (Notification.permission === 'denied' && isEnabled.value) {
                localStorage.removeItem(STORAGE_KEY);
                isEnabled.value = false;
            }
        }
    }

    function setEnabled(value: boolean) {
        // Always update ref so the toggle UI updates
        isEnabled.value = value;
        if (hasNotificationAPI) {
            if (value) {
                localStorage.setItem(STORAGE_KEY, '1');
            } else {
                localStorage.removeItem(STORAGE_KEY);
            }
        }
    }

    async function requestPermission(): Promise<NotificationPermission> {
        if (!hasNotificationAPI) return 'denied';
        const result = await Notification.requestPermission();
        permission.value = result;
        if (result === 'granted') {
            setEnabled(true);
        }
        return result;
    }

    return {
        isSupported,
        isIOS,
        needsPWAHint,
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
            // Regular chat message: show sender name and message excerpt
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
        case 'nearby_heart_tap':
            return {
                title: 'Someone tapped your heart',
                body: 'Open Find Your Match to tap back.',
            };
        case 'test':
            return {
                title: 'NEMSU Match - Test',
                body: 'This is a test notification from Pusher. Browser notifications are working.',
            };
        default:
            return {
                title: 'NEMSU Match',
                body: payload.from_user
                    ? `${name} sent you a notification`
                    : (payload.data?.excerpt as string) || 'You have a new notification',
            };
    }
}

/**
 * Show a native browser notification for every received notification when permission and preference are enabled.
 * Includes: message chat, message requests, matches, follows, nearby tap, etc.
 * All notification data is used to build title/body; shown regardless of tab focus.
 */
export function showBrowserNotificationIfAllowed(
    payload: RealtimeNotificationPayload,
    appName = 'NEMSU Match'
): void {
    if (typeof window === 'undefined' || !('Notification' in window)) return;
    if (Notification.permission !== 'granted') return;
    if (localStorage.getItem(STORAGE_KEY) !== '1') return;

    const { title, body } = getNotificationTitleAndBody(payload);
    // Anonymous types (e.g. nearby_heart_tap): do not use sender's profile as icon
    const iconPath =
        payload.type === 'nearby_heart_tap'
            ? undefined
            : (payload.from_user?.profile_picture as string | undefined);
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
        // Message chat, message requests, and request accepted: open Chat (with conversation when available)
        if (payload.type === 'message' || payload.type === 'message_request' || payload.type === 'message_request_accepted') {
            const convId =
                payload.notifiable_type === 'conversation'
                    ? Number(payload.notifiable_id)
                    : (payload.data?.conversation_id != null ? Number(payload.data.conversation_id) : null);
            if (convId && !Number.isNaN(convId)) {
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
        } else if (payload.type === 'nearby_heart_tap') {
            window.location.href = `${window.location.origin}/find-your-match?show_tap_back=1`;
        } else {
            window.location.href = `${window.location.origin}/notifications`;
        }
    };
}
