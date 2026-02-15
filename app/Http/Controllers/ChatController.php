<?php

namespace App\Http\Controllers;

use App\Events\MessageRead as MessageReadEvent;
use App\Events\MessageSent;
use App\Events\NotificationSent;
use App\Events\TypingIndicator;
use App\Models\Block;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageRequest;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserMatch;
use App\Services\ChatContentModeration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /** Ids of users the current user has blocked or is blocked by */
    private function blockedUserIds(): array
    {
        $id = Auth::id();
        $blocked = Block::where('blocker_id', $id)->pluck('blocked_id')->all();
        $blockedBy = Block::where('blocked_id', $id)->pluck('blocker_id')->all();

        return array_values(array_unique(array_merge($blocked, $blockedBy)));
    }

    /** Whether current user can message/see the other (matched and not blocked) */
    private function canMessage(User $other): bool
    {
        $me = Auth::user();
        if ($me->id === $other->id) {
            return false;
        }
        $blocked = $this->blockedUserIds();
        if (in_array((int) $other->id, $blocked, true)) {
            return false;
        }

        // Check if users are matched (mutually liked each other)
        return UserMatch::areMatched($me->id, $other->id);
    }

    /**
     * List conversations: all conversations with messages (matched users or message requests).
     */
    public function index(Request $request)
    {
        $blocked = $this->blockedUserIds();
        $me = Auth::user();

        $conversations = Conversation::query()
            ->where(function ($q) use ($me): void {
                $q->where('user1_id', $me->id)->orWhere('user2_id', $me->id);
            })
            ->whereNotIn('user1_id', $blocked)
            ->whereNotIn('user2_id', $blocked)
            ->has('messages') // Only show conversations that have at least one message
            ->with(['user1', 'user2', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->orderByDesc('updated_at')
            ->get();

        $list = [];
        foreach ($conversations as $c) {
            // Determine the other user
            $otherId = ((int) $c->user1_id === $me->id) ? $c->user2_id : $c->user1_id;
            $other = ((int) $c->user1_id === $me->id) ? $c->user2 : $c->user1;

            if (! $other || in_array((int) $otherId, $blocked, true)) {
                continue;
            }
            // Show all conversations (matched or message requests)
            // If conversation exists, both parties should see it

            $lastMessage = $c->messages->first(); // Already loaded via with()
            $unreadCount = $c->messages()
                ->where('sender_id', '!=', $me->id)
                ->whereNull('read_at')
                ->count();

            // Check if this is a pending message request
            $pendingRequest = MessageRequest::where(function ($q) use ($me, $other): void {
                $q->where('from_user_id', $me->id)->where('to_user_id', $other->id)
                    ->orWhere('from_user_id', $other->id)->where('to_user_id', $me->id);
            })
                ->where('status', MessageRequest::STATUS_PENDING)
                ->first();

            $list[] = [
                'id' => $c->id,
                'other_user' => [
                    'id' => $other->id,
                    'display_name' => $other->display_name,
                    'fullname' => $other->fullname,
                    'profile_picture' => $other->profile_picture,
                    'is_online' => $other->isOnline(),
                    'is_workspace_verified' => (bool) $other->is_workspace_verified,
                ],
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'sender_id' => $lastMessage->sender_id,
                    'body' => \Str::limit($lastMessage->body, 60),
                    'read_at' => $lastMessage->read_at?->toIso8601String(),
                    'created_at' => $lastMessage->created_at->toIso8601String(),
                ] : null,
                'unread_count' => $unreadCount,
                'updated_at' => $c->updated_at->toIso8601String(),
                'is_pending_request' => $pendingRequest !== null,
                'pending_request_from_me' => $pendingRequest && $pendingRequest->from_user_id === $me->id,
            ];
        }

        return response()->json(['data' => $list]);
    }

    /**
     * Total count of unread messages for the current user (across all conversations).
     */
    public function unreadCount(Request $request)
    {
        $me = Auth::id();
        $blocked = $this->blockedUserIds();

        $count = Message::query()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $me)
            ->whereHas('conversation', function ($q) use ($me, $blocked): void {
                $q->where(function ($q) use ($me): void {
                    $q->where('user1_id', $me)->orWhere('user2_id', $me);
                })
                    ->whereNotIn('user1_id', $blocked)
                    ->whereNotIn('user2_id', $blocked);
            })
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get a single conversation by ID (for opening from link).
     */
    public function show(Conversation $conversation)
    {
        $me = Auth::user();
        if ($conversation->user1_id !== $me->id && $conversation->user2_id !== $me->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if (in_array((int) $conversation->user1_id, $this->blockedUserIds(), true) || in_array((int) $conversation->user2_id, $this->blockedUserIds(), true)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation->load(['user1:id,display_name,fullname,profile_picture,last_seen_at', 'user2:id,display_name,fullname,profile_picture,last_seen_at']);
        $other = $conversation->otherUser($me->id);

        return response()->json([
            'id' => $conversation->id,
            'other_user' => [
                'id' => $other->id,
                'display_name' => $other->display_name,
                'fullname' => $other->fullname,
                'profile_picture' => $other->profile_picture,
                'is_online' => $other->isOnline(),
                'is_workspace_verified' => (bool) $other->is_workspace_verified,
            ],
        ]);
    }

    /**
     * Get or create a conversation with another user. Allowed only if follow relation exists.
     */
    public function store(Request $request)
    {
        $request->validate(['user_id' => 'required|integer|exists:users,id']);
        $other = User::findOrFail($request->user_id);

        if (! $this->canMessage($other)) {
            return response()->json([
                'message' => 'You can only message users you have matched with. Send a message request instead.',
                'user' => [
                    'id' => $other->id,
                    'display_name' => $other->display_name,
                    'fullname' => $other->fullname,
                    'profile_picture' => $other->profile_picture,
                ],
            ], 403);
        }

        $conversation = Conversation::between(Auth::id(), $other->id);
        $conversation->load(['user1:id,display_name,fullname,profile_picture', 'user2:id,display_name,fullname,profile_picture']);

        $otherUser = $conversation->otherUser(Auth::id());

        return response()->json([
            'id' => $conversation->id,
            'other_user' => [
                'id' => $otherUser->id,
                'display_name' => $otherUser->display_name,
                'fullname' => $otherUser->fullname,
                'profile_picture' => $otherUser->profile_picture,
                'is_workspace_verified' => (bool) $otherUser->is_workspace_verified,
            ],
        ]);
    }

    /**
     * Get messages in a conversation (paginated).
     */
    public function messages(Request $request, Conversation $conversation)
    {
        $me = Auth::user();
        if ($conversation->user1_id !== $me->id && $conversation->user2_id !== $me->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if (in_array((int) $conversation->user1_id, $this->blockedUserIds(), true) || in_array((int) $conversation->user2_id, $this->blockedUserIds(), true)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = min((int) $request->input('per_page', 30), 50);
        $messages = $conversation->messages()
            ->with('sender:id,display_name,fullname,profile_picture')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $items = $messages->getCollection()->map(function (Message $m) {
            return [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'sender' => $m->sender ? [
                    'id' => $m->sender->id,
                    'display_name' => $m->sender->display_name,
                    'fullname' => $m->sender->fullname,
                    'profile_picture' => $m->sender->profile_picture,
                ] : null,
                'body' => $m->body,
                'read_at' => $m->read_at?->toIso8601String(),
                'created_at' => $m->created_at->toIso8601String(),
            ];
        })->values()->all();

        return response()->json([
            'data' => $items,
            'current_page' => $messages->currentPage(),
            'last_page' => $messages->lastPage(),
            'per_page' => $messages->perPage(),
        ]);
    }

    /**
     * Send a message in an existing conversation.
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $request->validate(['body' => 'required|string|max:2000']);

        $moderation = ChatContentModeration::fromConfig()->check($request->body);
        if (! $moderation['allowed']) {
            return response()->json(['message' => $moderation['reason']], 422);
        }

        $me = Auth::user();
        if ($conversation->user1_id !== $me->id && $conversation->user2_id !== $me->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if (in_array((int) $conversation->user1_id, $this->blockedUserIds(), true) || in_array((int) $conversation->user2_id, $this->blockedUserIds(), true)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $me->id,
            'body' => $request->body,
        ]);
        $message->load('sender:id,display_name,fullname,profile_picture');
        broadcast(new MessageSent($message));

        $other = $conversation->otherUser($me->id);
        $notif = Notification::notify($other->id, 'message', $me->id, 'conversation', $conversation->id, [
            'message_id' => $message->id,
            'excerpt' => \Str::limit($request->body, 80),
        ]);
        if ($notif) {
            broadcast(new NotificationSent($notif));
        }

        return response()->json(['message' => $message], 201);
    }

    /**
     * Send first message to a user: creates conversation if follow relation, else message request.
     */
    public function sendMessageToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'body' => 'required|string|max:2000',
        ]);
        $other = User::findOrFail($request->user_id);
        $me = Auth::user();
        if ($me->id === $other->id) {
            return response()->json(['message' => 'Cannot message yourself'], 422);
        }
        if (in_array((int) $other->id, $this->blockedUserIds(), true)) {
            return response()->json(['message' => 'Cannot message this user'], 403);
        }

        $existingConv = Conversation::where(function ($q) use ($me, $other): void {
            $q->where('user1_id', $me->id)->where('user2_id', $other->id)
                ->orWhere('user1_id', $other->id)->where('user2_id', $me->id);
        })->first();

        if ($existingConv) {
            return $this->sendMessage($request, $existingConv);
        }

        if ($this->canMessage($other)) {
            $conversation = Conversation::between($me->id, $other->id);

            return $this->sendMessage($request, $conversation);
        }

        $existingRequest = MessageRequest::where('from_user_id', $me->id)
            ->where('to_user_id', $other->id)
            ->where('status', MessageRequest::STATUS_PENDING)
            ->first();
        if ($existingRequest) {
            // Find the existing conversation
            $existingConversation = Conversation::where(function ($q) use ($me, $other): void {
                $q->where('user1_id', $me->id)->where('user2_id', $other->id)
                    ->orWhere('user1_id', $other->id)->where('user2_id', $me->id);
            })->first();

            if ($existingConversation) {
                $existingConversation->load(['user1:id,display_name,fullname,profile_picture,last_seen_at', 'user2:id,display_name,fullname,profile_picture,last_seen_at']);
                $otherUser = $existingConversation->otherUser($me->id);

                return response()->json([
                    'message' => 'You already have a pending request to this user',
                    'conversation_id' => $existingConversation->id,
                    'conversation' => [
                        'id' => $existingConversation->id,
                        'other_user' => [
                            'id' => $otherUser->id,
                            'display_name' => $otherUser->display_name,
                            'fullname' => $otherUser->fullname,
                            'profile_picture' => $otherUser->profile_picture,
                            'is_online' => $otherUser->isOnline(),
                        ],
                        'updated_at' => $existingConversation->updated_at->toIso8601String(),
                    ],
                ], 200); // Return 200 with conversation data
            }

            return response()->json(['message' => 'You already have a pending request to this user'], 422);
        }

        $moderation = ChatContentModeration::fromConfig()->check($request->body);
        if (! $moderation['allowed']) {
            return response()->json(['message' => $moderation['reason']], 422);
        }

        // Create conversation immediately so sender can see it in their chat list
        $conversation = Conversation::between($me->id, $other->id);
        $conversation->touch(); // Ensure updated_at is current

        // Create the message request
        $req = MessageRequest::create([
            'from_user_id' => $me->id,
            'to_user_id' => $other->id,
            'body' => $request->body,
            'status' => MessageRequest::STATUS_PENDING,
        ]);
        $req->load('fromUser:id,display_name,fullname,profile_picture');

        // Add message to conversation so it appears in sender's chat list
        $message = $conversation->messages()->create([
            'sender_id' => $me->id,
            'body' => $request->body,
        ]);
        $message->load('sender:id,display_name,fullname,profile_picture');

        $conversation->load(['user1:id,display_name,fullname,profile_picture,last_seen_at', 'user2:id,display_name,fullname,profile_picture,last_seen_at']);

        $notif = Notification::notify($other->id, 'message_request', $me->id, 'message_request', $req->id, [
            'excerpt' => \Str::limit($request->body, 80),
        ]);
        if ($notif) {
            broadcast(new NotificationSent($notif));
        }

        $otherUser = $conversation->otherUser($me->id);

        return response()->json([
            'message_request' => $req,
            'message' => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'body' => $message->body,
                'read_at' => $message->read_at,
                'created_at' => $message->created_at->toIso8601String(),
                'conversation_id' => $conversation->id,
                'sender' => [
                    'id' => $me->id,
                    'display_name' => $me->display_name,
                    'fullname' => $me->fullname,
                    'profile_picture' => $me->profile_picture,
                ],
            ],
            'conversation_id' => $conversation->id,
            'conversation' => [
                'id' => $conversation->id,
                'other_user' => [
                    'id' => $otherUser->id,
                    'display_name' => $otherUser->display_name,
                    'fullname' => $otherUser->fullname,
                    'profile_picture' => $otherUser->profile_picture,
                    'is_online' => $otherUser->isOnline(),
                    'is_workspace_verified' => (bool) $otherUser->is_workspace_verified,
                ],
                'updated_at' => $conversation->updated_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Mark messages in a conversation as read.
     */
    public function markRead(Request $request, Conversation $conversation)
    {
        $me = Auth::user();
        if ($conversation->user1_id !== $me->id && $conversation->user2_id !== $me->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messageIds = $request->input('message_ids', []);
        if (is_array($messageIds) && count($messageIds) > 0) {
            $updated = $conversation->messages()
                ->whereIn('id', $messageIds)
                ->where('sender_id', '!=', $me->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
            $readIds = $conversation->messages()->whereIn('id', $messageIds)->whereNotNull('read_at')->pluck('id')->all();
            if (count($readIds) > 0) {
                broadcast(new MessageReadEvent($conversation->id, $me->id, $readIds));
            }
        } else {
            $conversation->messages()
                ->where('sender_id', '!=', $me->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
            $readIds = $conversation->messages()->where('sender_id', '!=', $me->id)->pluck('id')->all();
            if (count($readIds) > 0) {
                broadcast(new MessageReadEvent($conversation->id, $me->id, $readIds));
            }
        }

        return response()->json(['read' => true]);
    }

    /**
     * Broadcast typing indicator (client can call this on keydown and debounce stop after 2s).
     */
    public function typing(Request $request, Conversation $conversation)
    {
        $me = Auth::user();
        if ($conversation->user1_id !== $me->id && $conversation->user2_id !== $me->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $typing = (bool) $request->input('typing', true);
        broadcast(new TypingIndicator($conversation->id, $me->id, $typing));

        return response()->json(['typing' => $typing]);
    }

    /**
     * List pending message requests (received by current user).
     */
    public function messageRequests()
    {
        $list = MessageRequest::where('to_user_id', Auth::id())
            ->where('status', MessageRequest::STATUS_PENDING)
            ->with('fromUser:id,display_name,fullname,profile_picture,last_seen_at')
            ->latest()
            ->get();

        $data = $list->map(function (MessageRequest $r) {
            return [
                'id' => $r->id,
                'from_user' => $r->fromUser ? [
                    'id' => $r->fromUser->id,
                    'display_name' => $r->fromUser->display_name,
                    'fullname' => $r->fromUser->fullname,
                    'profile_picture' => $r->fromUser->profile_picture,
                    'is_online' => $r->fromUser->isOnline(),
                    'is_workspace_verified' => (bool) $r->fromUser->is_workspace_verified,
                ] : null,
                'body' => $r->body,
                'status' => $r->status,
                'created_at' => $r->created_at->toIso8601String(),
            ];
        })->all();

        return response()->json(['data' => $data]);
    }

    /**
     * Accept a message request: mark as accepted, notify sender.
     * (Conversation and message already created when request was sent)
     */
    public function acceptRequest(MessageRequest $messageRequest)
    {
        if ($messageRequest->to_user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($messageRequest->status !== MessageRequest::STATUS_PENDING) {
            return response()->json(['message' => 'Request already handled'], 422);
        }

        $messageRequest->update(['status' => MessageRequest::STATUS_ACCEPTED]);

        // Conversation already exists from when request was sent
        $conversation = Conversation::where(function ($q) use ($messageRequest): void {
            $q->where('user1_id', $messageRequest->from_user_id)->where('user2_id', $messageRequest->to_user_id)
                ->orWhere('user1_id', $messageRequest->to_user_id)->where('user2_id', $messageRequest->from_user_id);
        })->firstOrFail();

        $notif = Notification::notify($messageRequest->from_user_id, 'message_request_accepted', Auth::id(), 'conversation', $conversation->id, []);
        if ($notif) {
            broadcast(new NotificationSent($notif));
        }

        $other = $conversation->otherUser(Auth::id());

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'other_user' => [
                    'id' => $other->id,
                    'display_name' => $other->display_name,
                    'fullname' => $other->fullname,
                    'profile_picture' => $other->profile_picture,
                    'is_online' => $other->isOnline(),
                ],
            ],
        ]);
    }

    /**
     * Decline a message request.
     */
    public function declineRequest(MessageRequest $messageRequest)
    {
        if ($messageRequest->to_user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($messageRequest->status !== MessageRequest::STATUS_PENDING) {
            return response()->json(['message' => 'Request already handled'], 422);
        }
        $messageRequest->update(['status' => MessageRequest::STATUS_DECLINED]);

        return response()->json(['declined' => true]);
    }

    /**
     * Block a user (no new messages, hide conversation from list).
     */
    public function block(User $user)
    {
        $me = Auth::user();
        if ($me->id === $user->id) {
            return response()->json(['message' => 'Cannot block yourself'], 422);
        }
        $me->block($user);

        return response()->json(['blocked' => true]);
    }

    /**
     * Unblock a user.
     */
    public function unblock(User $user)
    {
        Auth::user()->unblock($user);

        return response()->json(['unblocked' => true]);
    }

    /**
     * Report a conversation (safety).
     */
    public function reportConversation(Request $request, Conversation $conversation)
    {
        $me = Auth::user();
        if ($conversation->user1_id !== $me->id && $conversation->user2_id !== $me->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reason = $request->input('reason', '');
        \DB::table('conversation_reports')->insert([
            'conversation_id' => $conversation->id,
            'reporter_id' => $me->id,
            'reason' => \Str::limit($reason, 500),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['reported' => true]);
    }
}
