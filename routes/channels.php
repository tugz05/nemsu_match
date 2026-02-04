<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('user.{id}', function (User $user, int $id): bool {
    return (int) $user->id === $id;
});

Broadcast::channel('conversation.{id}', function (User $user, int $id): bool {
    $conversation = Conversation::find($id);
    if (! $conversation) {
        return false;
    }

    return (int) $conversation->user1_id === (int) $user->id || (int) $conversation->user2_id === (int) $user->id;
});
