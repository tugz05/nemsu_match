# Message Request System - Current Structure & Changes

## ✅ What Already Exists

### Backend Infrastructure
1. **MessageRequest Model** (`app/Models/MessageRequest.php`)
   - Has STATUS_PENDING, STATUS_ACCEPTED, STATUS_DECLINED
   - Stores: from_user_id, to_user_id, body, status
   - Relationships: fromUser(), toUser()

2. **UserMatch Model** (`app/Models/UserMatch.php`)
   - Stores mutual matches (users who liked each other)
   - Has `areMatched(userId1, userId2)` method to check if two users are matched
   - One row per pair with normalized IDs (smaller in user_id, larger in matched_user_id)

3. **ChatController Methods** (FULLY IMPLEMENTED)
   - `sendMessageToUser()` - Sends message or creates message request
   - `messageRequests()` - Lists pending requests
   - `acceptRequest()` - Accepts request, creates conversation
   - `declineRequest()` - Declines request

4. **Routes** (`routes/web.php`)
   - POST `/api/conversations/send` → sendMessageToUser
   - GET `/api/message-requests` → messageRequests
   - POST `/api/message-requests/{id}/accept` → acceptRequest
   - POST `/api/message-requests/{id}/decline` → declineRequest

### Frontend (Chat.vue)
- Message request UI already exists
- Accept/Decline buttons working
- Fetches and displays pending requests
- Sends message requests when needed

## ⚠️ Current Issue

The `canMessage()` method in ChatController checks if users **follow** each other:

```php
private function canMessage(User $other): bool
{
    // ...
    return $me->isFollowing($other) || $other->isFollowing($me);
}
```

## ✅ Solution: Change to Match-Based Messaging

Update `canMessage()` to check if users have **matched** (mutually liked):

```php
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
```

## How It Will Work After Change

### Scenario 1: Users Are Matched (Mutual Likes)
1. User A likes User B with heart/smile/study
2. User B likes User A back
3. They become matched → `UserMatch` record created
4. When either clicks "Message":
   - `canMessage()` returns true
   - Direct message sent
   - Conversation created immediately
   - No message request needed ✅

### Scenario 2: Users Are NOT Matched
1. User A likes User B
2. User B hasn't liked back (or ignored)
3. When User A clicks "Message":
   - `canMessage()` returns false
   - Message request created instead
   - User B sees it in "Message Requests" tab
   - User B can accept or decline
   - If accepted → conversation created with that first message

## Additional Consideration: Following System

The current code also checks conversation list against following:

```php
// In index() method
if (! $followingIds->has($other->id) && ! $followerIds->has($other->id)) {
    continue;
}
```

We should also update this to check matches instead of following for consistency.

## Files That Need Changes

1. ✅ **app/Http/Controllers/ChatController.php**
   - Line ~31-43: `canMessage()` method
   - Line ~72-74: Conversation list filtering in `index()` method

2. ✅ **No frontend changes needed** - already supports message requests!

## Testing Checklist

After making the change:
- [ ] Matched users can send direct messages immediately
- [ ] Non-matched users send message requests
- [ ] Message requests appear in recipient's "Requests" tab
- [ ] Accept button creates conversation with original message
- [ ] Decline button hides the request
- [ ] Blocked users cannot send messages at all
- [ ] Conversation list shows all conversations with matches
- [ ] Notifications work for message requests