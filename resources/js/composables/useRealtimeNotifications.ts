/**
 * Subscribe to real-time notifications for the current user via Pusher.
 * Call onNotification when a new notification is broadcast.
 */
import { onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { getEcho } from '@/echo';

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
    data: {
        comment_id?: number;
        post_id?: number;
        excerpt?: string;
        is_reply?: boolean;
        is_reply_to_you?: boolean;
    } | null;
    read_at: string | null;
    created_at: string;
}

export function useRealtimeNotifications(onNotification: (payload: RealtimeNotificationPayload) => void): void {
    const page = usePage();
    const userId = page.props.auth?.user?.id as number | undefined;

    if (!userId) return;

    const Echo = getEcho();
    if (!Echo) return;

    const channel = Echo.private(`user.${userId}`);
    channel.listen('.NotificationSent', (e: { id?: number; type?: string; from_user?: unknown } & RealtimeNotificationPayload) => {
        const payload: RealtimeNotificationPayload = {
            id: e.id!,
            type: e.type!,
            from_user_id: e.from_user_id,
            from_user: e.from_user ?? null,
            notifiable_type: e.notifiable_type,
            notifiable_id: e.notifiable_id,
            data: e.data ?? null,
            read_at: e.read_at ?? null,
            created_at: e.created_at,
        };
        onNotification(payload);
    });

    onUnmounted(() => {
        Echo.leave(`user.${userId}`);
    });
}
