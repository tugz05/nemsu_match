<?php

namespace App\Http\Controllers;

use App\Models\AnonymousChatMessage;
use App\Models\AnonymousChatRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AnonymousChatController extends Controller
{
    /**
     * GET /api/anonymous-chat/rooms
     * List anonymous chat rooms for the current user. No profile data — fully anonymous.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $rooms = AnonymousChatRoom::query()
            ->where(function ($q) use ($user) {
                $q->where('user1_id', $user->id)->orWhere('user2_id', $user->id);
            })
            ->with(['messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->orderByDesc('updated_at')
            ->get();

        foreach ($rooms as $room) {
            $room->unread_count = AnonymousChatMessage::query()
                ->where('anonymous_chat_room_id', $room->id)
                ->where('user_id', '!=', $user->id)
                ->whereNull('read_at')
                ->count();
        }

        $list = [];
        foreach ($rooms as $room) {
            if (empty($room->display_name)) {
                $room->update(['display_name' => AnonymousChatRoom::getRandomDisplayNameForPair($room->user1_id, $room->user2_id)]);
                $room->refresh();
            }
            $lastMessage = $room->messages->first();
            $list[] = [
                'id' => $room->id,
                'display_name' => $room->display_name ?? 'Anonymous match',
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'sender_id' => $lastMessage->user_id,
                    'body' => Str::limit($lastMessage->body, 80),
                    'created_at' => $lastMessage->created_at->toIso8601String(),
                ] : null,
                'unread_count' => (int) $room->unread_count,
                'updated_at' => $room->updated_at->toIso8601String(),
            ];
        }

        return response()->json(['data' => $list]);
    }

    /**
     * GET /api/anonymous-chat/rooms/{room}/messages
     * Get messages for a room. Sender only as "you" or "them" (no profile unless revealed).
     * Includes room display_name and reveal state; other_user when both have agreed to reveal.
     */
    public function messages(Request $request, AnonymousChatRoom $room)
    {
        $user = Auth::user();
        if (! $room->hasUser($user->id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $messages = $room->messages()->orderBy('created_at')->get();

        $items = [];
        foreach ($messages as $m) {
            $items[] = [
                'id' => $m->id,
                'user_id' => $m->user_id,
                'is_me' => (int) $m->user_id === (int) $user->id,
                'body' => $m->body,
                'read_at' => $m->read_at?->toIso8601String(),
                'created_at' => $m->created_at->toIso8601String(),
            ];
        }

        // Mark as read
        $room->messages()->where('user_id', '!=', $user->id)->whereNull('read_at')->update(['read_at' => now()]);

        $meAgreed = $room->hasUserAgreedToReveal($user->id);
        $otherUser = $room->getOtherUser($user->id);
        $themAgreed = $otherUser ? $room->hasUserAgreedToReveal($otherUser->id) : false;
        $identitiesRevealed = $room->isRevealed();

        $roomPayload = [
            'display_name' => $room->display_name ?? 'Anonymous match',
            'me_agreed_to_reveal' => $meAgreed,
            'them_agreed_to_reveal' => $themAgreed,
            'identities_revealed' => $identitiesRevealed,
            'other_user' => null,
        ];

        if ($identitiesRevealed && $otherUser) {
            $roomPayload['other_user'] = [
                'id' => $otherUser->id,
                'display_name' => $otherUser->display_name ?? $otherUser->fullname ?? 'User',
                'fullname' => $otherUser->fullname ?? '',
                'profile_picture' => $otherUser->profile_picture,
                'profile_url' => '/profile/' . $otherUser->id,
            ];
        }

        return response()->json(['data' => $items, 'room' => $roomPayload]);
    }

    /**
     * POST /api/anonymous-chat/rooms/{room}/messages
     * Send a message in an anonymous room. No profile exposed.
     */
    public function sendMessage(Request $request, AnonymousChatRoom $room)
    {
        $request->validate(['body' => 'required|string|max:2000']);
        $user = Auth::user();
        if (! $room->hasUser($user->id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $message = AnonymousChatMessage::create([
            'anonymous_chat_room_id' => $room->id,
            'user_id' => $user->id,
            'body' => $request->input('body'),
        ]);

        $room->touch();

        return response()->json([
            'data' => [
                'id' => $message->id,
                'user_id' => $message->user_id,
                'is_me' => true,
                'body' => $message->body,
                'read_at' => null,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * POST /api/anonymous-chat/rooms/{room}/agree-reveal
     * Current user agrees to reveal identity. When both agree, identities are revealed in the room.
     */
    public function agreeReveal(Request $request, AnonymousChatRoom $room)
    {
        $user = Auth::user();
        if (! $room->hasUser($user->id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($room->user1_id === $user->id) {
            $room->update(['user1_agreed_to_reveal' => true]);
        } else {
            $room->update(['user2_agreed_to_reveal' => true]);
        }

        $room->refresh();
        $otherUser = $room->getOtherUser($user->id);
        $identitiesRevealed = $room->isRevealed();

        $payload = [
            'me_agreed_to_reveal' => true,
            'them_agreed_to_reveal' => $room->hasUserAgreedToReveal($otherUser?->id ?? 0),
            'identities_revealed' => $identitiesRevealed,
            'other_user' => null,
        ];

        if ($identitiesRevealed && $otherUser) {
            $payload['other_user'] = [
                'id' => $otherUser->id,
                'display_name' => $otherUser->display_name ?? $otherUser->fullname ?? 'User',
                'fullname' => $otherUser->fullname ?? '',
                'profile_picture' => $otherUser->profile_picture,
                'profile_url' => '/profile/' . $otherUser->id,
            ];
        }

        return response()->json($payload);
    }

    /**
     * GET /api/anonymous-chat/rooms/unread-count
     * Total unread count for the anonymous chat tab badge.
     */
    public function unreadCount(Request $request)
    {
        $user = Auth::user();
        $roomIds = AnonymousChatRoom::query()
            ->where(function ($q) use ($user) {
                $q->where('user1_id', $user->id)->orWhere('user2_id', $user->id);
            })
            ->pluck('id');
        $count = AnonymousChatMessage::query()
            ->whereIn('anonymous_chat_room_id', $roomIds)
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}
