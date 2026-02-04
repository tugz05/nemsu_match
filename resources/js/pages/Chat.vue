<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, Send, MessageCircle, UserPlus, Check, X, MoreVertical, Ban, Flag, Search, PencilLine, Camera, SlidersHorizontal, Paperclip, Smile, Mic } from 'lucide-vue-next';
import { useCsrfToken } from '@/composables/useCsrfToken';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import { BottomNav } from '@/components/feed';
import { getEcho } from '@/echo';

interface OtherUser {
    id: number;
    display_name: string;
    fullname: string;
    profile_picture: string | null;
    is_online?: boolean;
}

interface ConversationItem {
    id: number;
    other_user: OtherUser;
    last_message: { id: number; sender_id: number; body: string; read_at: string | null; created_at: string } | null;
    unread_count: number;
    updated_at: string;
}

interface MessageItem {
    id: number;
    sender_id: number;
    sender: OtherUser | null;
    body: string;
    read_at: string | null;
    created_at: string;
}

interface MessageRequestItem {
    id: number;
    from_user: OtherUser | null;
    body: string;
    status: string;
    created_at: string;
}

const props = defineProps<{ conversationId?: string | null; userId?: string | null }>();

const getCsrfToken = useCsrfToken();
const conversations = ref<ConversationItem[]>([]);
const requests = ref<MessageRequestItem[]>([]);
const messages = ref<MessageItem[]>([]);
const loadingConversations = ref(true);
const loadingRequests = ref(false);
const loadingMessages = ref(false);
const activeTab = ref<'chats' | 'requests'>('chats');
const selectedConversationId = ref<number | null>(null);
const currentConversation = ref<{ id: number; other_user: OtherUser } | null>(null);
const messagePage = ref(1);
const lastMessagePage = ref(1);
const newMessageBody = ref('');
const sending = ref(false);
const typingUserId = ref<number | null>(null);
const showConvMenu = ref(false);
/** Search in chat list (filter by name / last message) */
const chatListSearch = ref('');
/** Search within open conversation messages */
const messageSearchQuery = ref('');
const showMessageSearchInput = ref(false);
/** New message flow: search users and compose to specific user */
const showNewMessage = ref(false);
const newMessageUserSearch = ref('');
const newMessageUserResults = ref<OtherUser[]>([]);
const newMessageUserLoading = ref(false);
const selectedUserForNewMessage = ref<OtherUser | null>(null);
const newMessageComposeBody = ref('');
const sendingNewMessage = ref(false);
let typingTimeout: ReturnType<typeof setTimeout> | null = null;
let echoLeave: (() => void) | null = null;
let newMessageSearchDebounce: ReturnType<typeof setTimeout> | null = null;

const page = usePage();
const currentUserId = computed(() => (page.props.auth?.user as { id?: number } | undefined)?.id ?? 0);

/** Filter conversations by name or last message body */
const filteredConversations = computed(() => {
    const q = chatListSearch.value.trim().toLowerCase();
    if (!q) return conversations.value;
    return conversations.value.filter((c) => {
        const name = displayName(c.other_user).toLowerCase();
        const last = c.last_message?.body?.toLowerCase() ?? '';
        return name.includes(q) || last.includes(q);
    });
});

/** Filter messages in current conversation by body */
const filteredMessages = computed(() => {
    const q = messageSearchQuery.value.trim().toLowerCase();
    if (!q) return messages.value;
    return messages.value.filter((m) => m.body.toLowerCase().includes(q));
});

function onMessageSearchBlur() {
    if (!messageSearchQuery.value.trim()) showMessageSearchInput.value = false;
}

function toggleMessageSearchInput() {
    showMessageSearchInput.value = !showMessageSearchInput.value;
    if (!showMessageSearchInput.value) messageSearchQuery.value = '';
}

/** Date label for message grouping: "Today Jun 14", "Yesterday", or "Mon Jun 10" */
function messageDateLabel(dateStr: string): string {
    const d = new Date(dateStr);
    const now = new Date();
    const sameDay = d.getDate() === now.getDate() && d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
    if (sameDay) return `Today ${d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);
    const isYesterday = d.getDate() === yesterday.getDate() && d.getMonth() === yesterday.getMonth() && d.getFullYear() === yesterday.getFullYear();
    if (isYesterday) return 'Yesterday';
    return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
}

/** Group messages by date for separators */
const messagesWithDateGroups = computed(() => {
    const list = filteredMessages.value;
    if (list.length === 0) return [];
    const groups: { dateLabel: string; messages: MessageItem[] }[] = [];
    let currentLabel = '';
    let currentGroup: MessageItem[] = [];
    for (const m of list) {
        const label = messageDateLabel(m.created_at);
        if (label !== currentLabel) {
            if (currentGroup.length > 0) {
                groups.push({ dateLabel: currentLabel, messages: currentGroup });
                currentGroup = [];
            }
            currentLabel = label;
        }
        currentGroup.push(m);
    }
    if (currentGroup.length > 0) groups.push({ dateLabel: currentLabel, messages: currentGroup });
    return groups;
});

function displayName(u: OtherUser | null): string {
    return u?.display_name || u?.fullname || 'User';
}

async function fetchConversations() {
    loadingConversations.value = true;
    try {
        const res = await fetch('/api/conversations', {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            conversations.value = data.data ?? [];
        }
    } catch (e) {
        console.error(e);
    } finally {
        loadingConversations.value = false;
    }
}

async function fetchRequests() {
    loadingRequests.value = true;
    try {
        const res = await fetch('/api/message-requests', {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            requests.value = data.data ?? [];
        }
    } catch (e) {
        console.error(e);
    } finally {
        loadingRequests.value = false;
    }
}

async function fetchNewMessageUserSearch() {
    const q = newMessageUserSearch.value.trim();
    if (!q) {
        newMessageUserResults.value = [];
        return;
    }
    newMessageUserLoading.value = true;
    try {
        const res = await fetch(`/api/users/search?${new URLSearchParams({ q })}`, {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            newMessageUserResults.value = data.data ?? [];
        } else {
            newMessageUserResults.value = [];
        }
    } catch (e) {
        newMessageUserResults.value = [];
    } finally {
        newMessageUserLoading.value = false;
    }
}

function openNewMessage() {
    showNewMessage.value = true;
    selectedUserForNewMessage.value = null;
    newMessageUserSearch.value = '';
    newMessageUserResults.value = [];
    newMessageComposeBody.value = '';
}

function closeNewMessage() {
    showNewMessage.value = false;
    selectedUserForNewMessage.value = null;
    newMessageUserSearch.value = '';
    newMessageUserResults.value = [];
    newMessageComposeBody.value = '';
}

function selectUserToMessage(user: OtherUser) {
    const existing = conversations.value.find((c) => c.other_user.id === user.id);
    if (existing) {
        closeNewMessage();
        openConversation(existing);
        return;
    }
    selectedUserForNewMessage.value = user;
    newMessageComposeBody.value = '';
}

async function sendNewMessageToUser() {
    const user = selectedUserForNewMessage.value;
    const body = newMessageComposeBody.value.trim();
    if (!user || !body || sendingNewMessage.value) return;
    sendingNewMessage.value = true;
    try {
        const res = await fetch('/api/conversations/send', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({ user_id: user.id, body }),
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok) {
            if (data.message?.conversation_id != null) {
                closeNewMessage();
                await fetchConversations();
                const conv = conversations.value.find((c) => c.id === data.message.conversation_id);
                if (conv) {
                    openConversation(conv);
                } else {
                    currentConversation.value = { id: data.message.conversation_id, other_user: user };
                    selectedConversationId.value = data.message.conversation_id;
                    messages.value = [data.message];
                    messagePage.value = 1;
                    lastMessagePage.value = 1;
                    markRead(data.message.conversation_id);
                    subscribeToConversation(data.message.conversation_id);
                }
            } else if (data.message_request) {
                closeNewMessage();
                await fetchRequests();
                alert('Message request sent. They will see it in their requests.');
            }
        } else {
            alert(data.message || 'Could not send message.');
        }
    } catch (e) {
        console.error(e);
        alert('Could not send message.');
    } finally {
        sendingNewMessage.value = false;
    }
}

async function fetchMessages(conversationId: number, page = 1) {
    loadingMessages.value = true;
    try {
        const res = await fetch(`/api/conversations/${conversationId}/messages?page=${page}&per_page=30`, {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            const list = data.data ?? [];
            if (page === 1) {
                messages.value = list.reverse();
                messagePage.value = 1;
                lastMessagePage.value = data.last_page ?? 1;
            } else {
                messages.value = [...list.reverse(), ...messages.value];
            }
        }
    } catch (e) {
        console.error(e);
    } finally {
        loadingMessages.value = false;
    }
}

function openConversation(conv: ConversationItem) {
    selectedConversationId.value = conv.id;
    currentConversation.value = { id: conv.id, other_user: conv.other_user };
    messages.value = [];
    fetchMessages(conv.id);
    markRead(conv.id);
    subscribeToConversation(conv.id);
}

function closeConversation() {
    selectedConversationId.value = null;
    currentConversation.value = null;
    messages.value = [];
    if (echoLeave) {
        echoLeave();
        echoLeave = null;
    }
}

function subscribeToConversation(conversationId: number) {
    if (echoLeave) echoLeave();
    const Echo = getEcho();
    if (!Echo) return;
    const channel = Echo.private(`conversation.${conversationId}`);
    channel.listen('.MessageSent', (e: MessageItem) => {
        if (selectedConversationId.value === conversationId) {
            messages.value = [...messages.value, e];
        }
        fetchConversations();
    });
    channel.listen('.MessageRead', (e: { reader_id: number; message_ids: number[] }) => {
        if (selectedConversationId.value !== conversationId) return;
        messages.value = messages.value.map((m) =>
            e.message_ids.includes(m.id) ? { ...m, read_at: new Date().toISOString() } : m,
        );
    });
    channel.listen('.TypingIndicator', (e: { user_id: number; typing: boolean }) => {
        if (selectedConversationId.value !== conversationId) return;
        typingUserId.value = e.typing ? e.user_id : null;
    });
    echoLeave = () => Echo.leave(`conversation.${conversationId}`);
}

async function markRead(conversationId: number) {
    try {
        await fetch(`/api/conversations/${conversationId}/read`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({}),
        });
        const conv = conversations.value.find((c) => c.id === conversationId);
        if (conv) conv.unread_count = 0;
    } catch (e) {
        console.error(e);
    }
}

function sendTyping(conversationId: number, typing: boolean) {
    fetch(`/api/conversations/${conversationId}/typing`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            Accept: 'application/json',
        },
        body: JSON.stringify({ typing }),
    }).catch(() => {});
}

function onMessageInput() {
    if (!currentConversation.value) return;
    sendTyping(currentConversation.value.id, true);
    if (typingTimeout) clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => {
        sendTyping(currentConversation.value!.id, false);
        typingTimeout = null;
    }, 2000);
}

async function sendMessage() {
    const body = newMessageBody.value.trim();
    if (!body || !currentConversation.value || sending.value) return;
    sending.value = true;
    newMessageBody.value = '';
    try {
        const res = await fetch(`/api/conversations/${currentConversation.value.id}/messages`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({ body }),
        });
        if (res.ok) {
            const data = await res.json();
            messages.value = [...messages.value, data.message];
            fetchConversations();
        }
    } catch (e) {
        console.error(e);
    } finally {
        sending.value = false;
    }
}

async function acceptRequest(req: MessageRequestItem) {
    try {
        const res = await fetch(`/api/message-requests/${req.id}/accept`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            requests.value = requests.value.filter((r) => r.id !== req.id);
            await fetchConversations();
            if (data.conversation) {
                openConversation({
                    id: data.conversation.id,
                    other_user: data.conversation.other_user,
                    last_message: null,
                    unread_count: 0,
                    updated_at: new Date().toISOString(),
                });
            }
        }
    } catch (e) {
        console.error(e);
    }
}

async function declineRequest(req: MessageRequestItem) {
    try {
        await fetch(`/api/message-requests/${req.id}/decline`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        requests.value = requests.value.filter((r) => r.id !== req.id);
    } catch (e) {
        console.error(e);
    }
}

async function blockUser() {
    if (!currentConversation.value) return;
    try {
        await fetch(`/api/users/${currentConversation.value.other_user.id}/block`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        showConvMenu.value = false;
        closeConversation();
        fetchConversations();
    } catch (e) {
        console.error(e);
    }
}

async function reportConversation() {
    if (!currentConversation.value) return;
    try {
        await fetch(`/api/conversations/${currentConversation.value.id}/report`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({ reason: 'Inappropriate conversation' }),
        });
        showConvMenu.value = false;
    } catch (e) {
        console.error(e);
    }
}

function timeAgo(dateStr: string): string {
    const d = new Date(dateStr);
    const now = new Date();
    const sec = Math.floor((now.getTime() - d.getTime()) / 1000);
    if (sec < 60) return 'now';
    if (sec < 3600) return `${Math.floor(sec / 60)}m`;
    if (sec < 86400) return `${Math.floor(sec / 3600)}h`;
    return d.toLocaleDateString();
}

function goBack() {
    if (showNewMessage.value) {
        closeNewMessage();
        return;
    }
    if (currentConversation.value) closeConversation();
    else router.visit('/home');
}

watch(newMessageUserSearch, () => {
    if (newMessageSearchDebounce) clearTimeout(newMessageSearchDebounce);
    newMessageSearchDebounce = setTimeout(() => fetchNewMessageUserSearch(), 300);
});

watch(
    () => [props.conversationId, props.userId],
    async ([convId, userId]) => {
        if (userId) {
            const num = parseInt(userId as string, 10);
            if (!num || isNaN(num)) return;
            try {
                const res = await fetch('/api/conversations', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ user_id: num }),
                });
                if (res.ok) {
                    const data = await res.json();
                    openConversation({
                        id: data.id,
                        other_user: data.other_user,
                        last_message: null,
                        unread_count: 0,
                        updated_at: new Date().toISOString(),
                    });
                }
            } catch (e) {
                console.error(e);
            }
            return;
        }
        const id = convId;
        const num = id ? parseInt(id, 10) : null;
        if (!num || isNaN(num)) return;
        const conv = conversations.value.find((c) => c.id === num);
        if (conv) {
            openConversation(conv);
            return;
        }
        try {
            const res = await fetch(`/api/conversations/${num}`, {
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
            });
            if (res.ok) {
                const data = await res.json();
                currentConversation.value = { id: data.id, other_user: data.other_user };
                selectedConversationId.value = data.id;
                fetchMessages(data.id);
                markRead(data.id);
                subscribeToConversation(data.id);
            }
        } catch (e) {
            console.error(e);
        }
    },
    { immediate: true },
);

onMounted(() => {
    fetchConversations();
    fetchRequests();
});

onUnmounted(() => {
    if (typingTimeout) clearTimeout(typingTimeout);
    if (echoLeave) echoLeave();
    if (newMessageSearchDebounce) clearTimeout(newMessageSearchDebounce);
});
</script>

<template>
    <div class="min-h-screen bg-white pb-20 flex flex-col">
        <Head title="Chat - NEMSU Match" />

        <!-- Header: only show when on list or new-message (not inside a conversation) -->
        <div v-if="!currentConversation" class="sticky top-0 z-40 bg-white border-b border-gray-200">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center gap-2">
                <button type="button" @click="goBack" class="p-2 -ml-2 rounded-full hover:bg-gray-100 shrink-0" aria-label="Back">
                    <ChevronLeft class="w-6 h-6 text-gray-700" />
                </button>
                <h1 class="text-lg font-bold text-gray-900 flex-1 text-center">Messages</h1>
                <div v-if="!showNewMessage" class="flex items-center gap-1 shrink-0 w-20 justify-end">
                    <button type="button" class="p-2 rounded-full hover:bg-gray-100" aria-label="Camera">
                        <Camera class="w-6 h-6 text-gray-700" />
                    </button>
                    <button type="button" @click="openNewMessage" class="p-2 rounded-full hover:bg-gray-100" aria-label="New message">
                        <PencilLine class="w-6 h-6 text-gray-700" />
                    </button>
                </div>
            </div>
            <!-- Search bar + filter (list view only) -->
            <div v-if="!showNewMessage" class="max-w-2xl mx-auto px-4 pb-3 flex items-center gap-2">
                <div class="relative flex-1 min-w-0">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" />
                    <input
                        v-model="chatListSearch"
                        type="text"
                        placeholder="Search"
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-100 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>
                <button type="button" class="p-2.5 bg-gray-100 rounded-xl hover:bg-gray-200 shrink-0" aria-label="Filter">
                    <SlidersHorizontal class="w-5 h-5 text-gray-600" />
                </button>
            </div>
            <!-- Pill tabs: Chats | Requests -->
            <div v-if="!showNewMessage" class="max-w-2xl mx-auto px-4 pb-3 flex gap-2">
                <button
                    type="button"
                    class="px-4 py-2 rounded-full text-sm font-semibold transition-colors"
                    :class="activeTab === 'chats' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    @click="activeTab = 'chats'"
                >
                    Chats
                </button>
                <button
                    type="button"
                    class="px-4 py-2 rounded-full text-sm font-semibold transition-colors relative"
                    :class="activeTab === 'requests' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    @click="activeTab = 'requests'; fetchRequests()"
                >
                    Requests
                    <span v-if="requests.length > 0" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center">
                        {{ requests.length > 99 ? '99+' : requests.length }}
                    </span>
                </button>
            </div>
        </div>

        <!-- New message: search user + compose -->
        <div v-if="showNewMessage" class="flex-1 flex flex-col overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <p class="text-sm font-semibold text-gray-700 mb-2">New message</p>
                <input
                    v-if="!selectedUserForNewMessage"
                    v-model="newMessageUserSearch"
                    type="text"
                    placeholder="Search for someone to message..."
                    class="w-full px-4 py-2.5 bg-gray-100 rounded-full text-sm outline-none focus:ring-2 focus:ring-blue-500"
                />
                <div v-else class="flex items-center gap-3">
                    <button type="button" @click="selectedUserForNewMessage = null" class="p-1 rounded-full hover:bg-gray-100">
                        <ChevronLeft class="w-5 h-5 text-gray-600" />
                    </button>
                    <div class="w-10 h-10 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100 flex-shrink-0">
                        <img
                            v-if="selectedUserForNewMessage.profile_picture"
                            :src="profilePictureSrc(selectedUserForNewMessage.profile_picture)"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold">
                            {{ displayName(selectedUserForNewMessage).charAt(0).toUpperCase() }}
                        </div>
                    </div>
                    <p class="font-semibold text-gray-900 truncate flex-1">{{ displayName(selectedUserForNewMessage) }}</p>
                </div>
            </div>
            <div v-if="!selectedUserForNewMessage" class="flex-1 overflow-y-auto">
                <div v-if="newMessageUserLoading" class="flex justify-center py-8">
                    <div class="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin" />
                </div>
                <p v-else-if="newMessageUserSearch.trim() && newMessageUserResults.length === 0" class="py-6 text-center text-gray-500 text-sm">No users found</p>
                <p v-else-if="!newMessageUserSearch.trim()" class="py-6 text-center text-gray-500 text-sm">Search by name to start a conversation</p>
                <ul v-else class="divide-y divide-gray-100">
                    <li
                        v-for="u in newMessageUserResults"
                        :key="u.id"
                        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer"
                        @click="selectUserToMessage(u)"
                    >
                        <div class="w-12 h-12 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100 flex-shrink-0">
                            <img v-if="u.profile_picture" :src="profilePictureSrc(u.profile_picture)" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-lg">
                                {{ displayName(u).charAt(0).toUpperCase() }}
                            </div>
                        </div>
                        <p class="font-semibold text-gray-900 truncate flex-1">{{ displayName(u) }}</p>
                    </li>
                </ul>
            </div>
            <div v-else class="flex-1 flex flex-col p-4">
                <textarea
                    v-model="newMessageComposeBody"
                    placeholder="Write a message..."
                    rows="4"
                    class="w-full px-4 py-3 bg-gray-100 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                />
                <button
                    type="button"
                    @click="sendNewMessageToUser"
                    :disabled="!newMessageComposeBody.trim() || sendingNewMessage"
                    class="mt-3 px-4 py-2.5 bg-blue-600 text-white rounded-full font-semibold text-sm disabled:opacity-50"
                >
                    {{ sendingNewMessage ? 'Sending...' : 'Send' }}
                </button>
            </div>
        </div>

        <!-- List view -->
        <div v-else-if="!currentConversation" class="flex-1 overflow-y-auto min-h-0">
            <div v-if="activeTab === 'chats'" class="max-w-2xl mx-auto px-4">
                <div v-if="loadingConversations" class="flex justify-center py-16">
                    <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
                </div>
                <div v-else-if="conversations.length === 0" class="py-16 text-center text-gray-500">
                    <MessageCircle class="w-14 h-14 mx-auto mb-4 text-gray-300" />
                    <p class="font-medium text-gray-700">No chats yet</p>
                    <p class="text-sm mt-1 text-gray-500">Only people you follow or who follow you appear here.</p>
                </div>
                <div v-else-if="filteredConversations.length === 0" class="py-12 text-center text-gray-500 text-sm">No conversations match your search</div>
                <ul v-else class="divide-y divide-gray-100 -mx-4">
                    <li
                        v-for="c in filteredConversations"
                        :key="c.id"
                        class="flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50 cursor-pointer active:bg-gray-100 transition-colors"
                        @click="openConversation(c)"
                    >
                        <div class="relative w-12 h-12 flex-shrink-0">
                            <div class="w-12 h-12 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100">
                                <img
                                    v-if="c.other_user.profile_picture"
                                    :src="profilePictureSrc(c.other_user.profile_picture)"
                                    :alt="displayName(c.other_user)"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-lg">
                                    {{ displayName(c.other_user).charAt(0).toUpperCase() }}
                                </div>
                            </div>
                            <!-- Single status dot: blue = unread, green = online (read), gray = offline (read) -->
                            <span
                                class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white shrink-0"
                                :class="c.unread_count > 0 ? 'bg-blue-600' : (c.other_user.is_online ? 'bg-green-500' : 'bg-gray-300')"
                                :title="c.unread_count > 0 ? 'Unread' : (c.other_user.is_online ? 'Online' : 'Offline')"
                            />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 truncate">{{ displayName(c.other_user) }}</p>
                            <p class="text-sm truncate mt-0.5" :class="c.unread_count > 0 ? 'text-gray-700 font-medium' : 'text-gray-500'">
                                {{ c.last_message ? c.last_message.body : 'No messages yet' }}
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <p class="text-xs text-gray-500 whitespace-nowrap">
                                {{ c.last_message ? timeAgo(c.last_message.created_at) : '—' }}
                            </p>
                            <span
                                v-if="c.unread_count > 0"
                                class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0"
                            >
                                {{ c.unread_count > 99 ? '99+' : c.unread_count }}
                            </span>
                            <span v-else class="w-6 h-6 flex-shrink-0" aria-hidden="true" />
                        </div>
                    </li>
                </ul>
            </div>
            <div v-else class="max-w-2xl mx-auto px-4">
                <div v-if="loadingRequests" class="flex justify-center py-16">
                    <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
                </div>
                <div v-else-if="requests.length === 0" class="py-16 text-center text-gray-500">
                    <UserPlus class="w-14 h-14 mx-auto mb-4 text-gray-300" />
                    <p class="font-medium text-gray-700">No message requests</p>
                </div>
                <ul v-else class="divide-y divide-gray-100 -mx-4">
                    <li v-for="r in requests" :key="r.id" class="flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="w-12 h-12 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100 flex-shrink-0">
                                <img
                                    v-if="r.from_user?.profile_picture"
                                    :src="profilePictureSrc(r.from_user?.profile_picture)"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-lg">
                                    {{ displayName(r.from_user).charAt(0).toUpperCase() }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 truncate">{{ displayName(r.from_user) }}</p>
                                <p class="text-sm text-gray-500 truncate mt-0.5">{{ r.body }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <button
                                type="button"
                                class="p-2 rounded-full bg-blue-600 text-white hover:bg-blue-700 transition-colors"
                                @click="acceptRequest(r)"
                                aria-label="Accept"
                            >
                                <Check class="w-5 h-5" />
                            </button>
                            <button
                                type="button"
                                class="p-2 rounded-full bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors"
                                @click="declineRequest(r)"
                                aria-label="Decline"
                            >
                                <X class="w-5 h-5" />
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Conversation view -->
        <template v-else>
            <div class="flex-1 flex flex-col min-h-0">
                <!-- Header: single row — back | avatar + name + status | actions -->
                <header class="sticky top-0 z-40 bg-white border-b border-gray-200 shrink-0">
                    <div class="max-w-2xl mx-auto px-4 py-3 flex items-center gap-3">
                        <button type="button" @click="closeConversation" class="p-2 -ml-2 rounded-full hover:bg-gray-100 shrink-0" aria-label="Back">
                            <ChevronLeft class="w-5 h-5 text-gray-700" />
                        </button>
                        <div class="flex-1 min-w-0 flex items-center gap-3">
                            <div class="relative w-10 h-10 flex-shrink-0">
                                <div class="w-10 h-10 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100">
                                    <img
                                        v-if="currentConversation.other_user.profile_picture"
                                        :src="profilePictureSrc(currentConversation.other_user.profile_picture)"
                                        class="w-full h-full object-cover"
                                        :alt="displayName(currentConversation.other_user)"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-sm">
                                        {{ displayName(currentConversation.other_user).charAt(0).toUpperCase() }}
                                    </div>
                                </div>
                                <span
                                    class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-white"
                                    :class="currentConversation.other_user.is_online ? 'bg-green-500' : 'bg-gray-400'"
                                    :title="currentConversation.other_user.is_online ? 'Online' : 'Offline'"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900 truncate">{{ displayName(currentConversation.other_user) }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ typingUserId === currentConversation.other_user.id ? 'typing...' : (currentConversation.other_user.is_online ? 'Online' : 'Offline') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center shrink-0">
                            <div v-if="showMessageSearchInput" class="min-w-0 max-w-[100px] mx-1">
                                <input
                                    v-model="messageSearchQuery"
                                    type="text"
                                    placeholder="Search..."
                                    class="w-full px-2 py-1.5 text-sm bg-gray-100 rounded-lg outline-none focus:ring-2 focus:ring-blue-500"
                                    @blur="onMessageSearchBlur"
                                />
                            </div>
                            <button type="button" @click="toggleMessageSearchInput" class="p-2 rounded-full hover:bg-gray-100" :class="(showMessageSearchInput || messageSearchQuery) ? 'text-blue-600' : 'text-gray-600'" aria-label="Search">
                                <Search class="w-5 h-5" />
                            </button>
                            <div class="relative">
                                <button type="button" @click="showConvMenu = !showConvMenu" class="p-2 rounded-full hover:bg-gray-100 text-gray-600" aria-label="Options">
                                    <MoreVertical class="w-5 h-5" />
                                </button>
                                <div v-if="showConvMenu" class="absolute right-0 top-full mt-1 w-48 bg-white rounded-xl shadow-lg border border-gray-200 py-1 z-50">
                                    <button type="button" class="w-full px-4 py-2 text-left text-sm flex items-center gap-2 text-gray-700 hover:bg-gray-50" @click="reportConversation">
                                        <Flag class="w-4 h-4" /> Report
                                    </button>
                                    <button type="button" class="w-full px-4 py-2 text-left text-sm flex items-center gap-2 text-red-600 hover:bg-red-50" @click="blockUser">
                                        <Ban class="w-4 h-4" /> Block
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>
                <!-- Messages: scrollable, with padding so content stays above fixed input bar -->
                <div class="flex-1 overflow-y-auto min-h-0 px-4 py-4 pb-[88px]">
                    <div v-if="messageSearchQuery.trim() && filteredMessages.length === 0" class="py-12 text-center text-gray-500 text-sm">No messages match your search</div>
                    <div v-else-if="loadingMessages && messages.length === 0" class="flex justify-center py-12">
                        <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin" />
                    </div>
                    <template v-else>
                        <div v-for="group in messagesWithDateGroups" :key="group.dateLabel" class="mb-6">
                            <p class="text-center text-xs text-gray-500 py-3">{{ group.dateLabel }}</p>
                            <div class="space-y-3">
                                <div
                                    v-for="m in group.messages"
                                    :key="m.id"
                                    class="flex flex-col gap-0.5"
                                    :class="m.sender_id === currentUserId ? 'items-end' : 'items-start'"
                                >
                                    <div
                                        class="max-w-[85%] rounded-2xl px-4 py-2.5 shadow-sm"
                                        :class="m.sender_id === currentUserId ? 'bg-blue-600 text-white rounded-br-md' : 'bg-gray-100 text-gray-900 rounded-bl-md'"
                                    >
                                        <p class="text-sm whitespace-pre-wrap break-words">{{ m.body }}</p>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5 max-w-[85%] truncate" :class="m.sender_id === currentUserId ? 'text-right' : 'text-left'">
                                        {{ timeAgo(m.created_at) }}{{ m.sender_id === currentUserId && m.read_at ? ' · Read' : '' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <!-- Typing indicator -->
                        <div
                            v-if="!messageSearchQuery.trim() && currentConversation && typingUserId === currentConversation.other_user.id"
                            class="flex justify-start mt-1"
                        >
                            <div class="rounded-2xl rounded-bl-md bg-gray-100 text-gray-900 px-4 py-3 flex items-center gap-1 shadow-sm">
                                <span class="flex gap-1" aria-hidden="true">
                                    <span class="w-2 h-2 rounded-full bg-gray-500 animate-bounce" style="animation-delay: 0ms" />
                                    <span class="w-2 h-2 rounded-full bg-gray-500 animate-bounce" style="animation-delay: 150ms" />
                                    <span class="w-2 h-2 rounded-full bg-gray-500 animate-bounce" style="animation-delay: 300ms" />
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <!-- Fixed input bar at bottom (above BottomNav) -->
            <div class="fixed bottom-20 left-0 right-0 z-30 bg-white border-t border-gray-200 shadow-[0_-2px_10px_rgba(0,0,0,0.05)]">
                <div class="max-w-2xl mx-auto px-4 py-3 flex items-center gap-2">
                    <button type="button" class="p-2.5 rounded-full hover:bg-gray-100 shrink-0 text-gray-500 transition-colors" aria-label="Emoji">
                        <Smile class="w-5 h-5" />
                    </button>
                    <input
                        v-model="newMessageBody"
                        type="text"
                        placeholder="Enter Text"
                        class="flex-1 min-w-0 px-4 py-2.5 bg-gray-100 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-colors"
                        @keydown.enter.prevent="sendMessage"
                        @input="onMessageInput"
                    />
                    <template v-if="newMessageBody.trim()">
                        <button
                            type="button"
                            @click="sendMessage"
                            :disabled="sending"
                            class="p-2.5 bg-blue-600 text-white rounded-full shrink-0 hover:bg-blue-700 disabled:opacity-50 transition-colors"
                            aria-label="Send"
                        >
                            <Send class="w-5 h-5" />
                        </button>
                    </template>
                    <template v-else>
                        <button type="button" class="p-2.5 rounded-full hover:bg-gray-100 shrink-0 text-gray-500 transition-colors" aria-label="Voice">
                            <Mic class="w-5 h-5" />
                        </button>
                    </template>
                </div>
            </div>
        </template>

        <BottomNav active-tab="chat" />
    </div>
</template>
