# Report and Block Functionality - Complete Implementation ✅

## Overview

The Report and Block actions in the Chat page are now fully functional with proper confirmations, user feedback, and error handling.

---

## Features Implemented

### 1. **Block User** 🚫

**Location:** Chat conversation header → Three dots menu (⋮) → Block

**Flow:**
1. User clicks "Block" from the menu
2. Confirmation dialog shows with clear explanation of consequences
3. If confirmed:
   - API call to `/api/users/{user}/block`
   - User is blocked in the database (`blocks` table)
   - Conversation closes automatically
   - Chat list refreshes to remove the conversation
   - Success message appears
4. If cancelled, no action taken

**Backend:**
- **Route:** `POST /api/users/{user}/block`
- **Controller:** `ChatController::block()`
- **Model Method:** `User::block()`
- **Database Table:** `blocks`
  - `blocker_id` (who blocked)
  - `blocked_id` (who was blocked)
  - Unique constraint on `[blocker_id, blocked_id]`

**Effects of Blocking:**
- Blocks the user relationship
- Hides all conversations with that user
- Prevents them from contacting you
- Removes them from your matches

---

### 2. **Report Conversation** 🚩

**Location:** Chat conversation header → Three dots menu (⋮) → Report

**Flow:**
1. User clicks "Report" from the menu
2. Prompt dialog appears asking for a reason (optional)
3. If submitted:
   - API call to `/api/conversations/{conversation}/report`
   - Report saved to database with reason
   - Success message thanks user and confirms review
4. If cancelled, no action taken

**Backend:**
- **Route:** `POST /api/conversations/{conversation}/report`
- **Controller:** `ChatController::reportConversation()`
- **Database Table:** `conversation_reports`
  - `conversation_id` (which conversation)
  - `reporter_id` (who reported)
  - `reason` (description, max 500 chars)
  - `created_at` / `updated_at`

**Validation:**
- Only participants of the conversation can report it
- Reason is optional, defaults to "Inappropriate conversation"
- Reason is limited to 500 characters

---

## Code Changes

### Frontend (`resources/js/pages/Chat.vue`)

#### 1. Enhanced `blockUser()` Function

**Before:**
```typescript
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
```

**After:**
```typescript
async function blockUser() {
    if (!currentConversation.value) return;
    
    const userName = displayName(currentConversation.value.other_user);
    const confirmed = confirm(
        `Are you sure you want to block ${userName}?\n\n` +
        `This will:\n` +
        `• Hide all conversations with this user\n` +
        `• Prevent them from contacting you\n` +
        `• Remove them from your matches`
    );
    
    if (!confirmed) return;
    
    try {
        const res = await fetch(`/api/users/${currentConversation.value.other_user.id}/block`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        
        if (!res.ok) {
            throw new Error('Failed to block user');
        }
        
        showConvMenu.value = false;
        closeConversation();
        await fetchConversations();
        alert(`${userName} has been blocked successfully.`);
    } catch (e) {
        console.error(e);
        alert('Failed to block user. Please try again.');
    }
}
```

**Improvements:**
✅ Confirmation dialog before blocking  
✅ Clear explanation of consequences  
✅ Proper error handling  
✅ Success/error messages  
✅ Uses user's display name  

---

#### 2. Enhanced `reportConversation()` Function

**Before:**
```typescript
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
```

**After:**
```typescript
async function reportConversation() {
    if (!currentConversation.value) return;
    
    const userName = displayName(currentConversation.value.other_user);
    const reason = prompt(
        `Report conversation with ${userName}\n\n` +
        `Please describe the issue (optional):`,
        ''
    );
    
    // User cancelled the prompt
    if (reason === null) return;
    
    try {
        const res = await fetch(`/api/conversations/${currentConversation.value.id}/report`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({ 
                reason: reason.trim() || 'Inappropriate conversation' 
            }),
        });
        
        if (!res.ok) {
            throw new Error('Failed to report conversation');
        }
        
        showConvMenu.value = false;
        alert(`Thank you for your report. Our team will review this conversation with ${userName}.`);
    } catch (e) {
        console.error(e);
        alert('Failed to submit report. Please try again.');
    }
}
```

**Improvements:**
✅ Prompt for user to describe the issue  
✅ Optional reason (can be left blank)  
✅ Proper error handling  
✅ Success/error messages  
✅ Uses user's display name  
✅ Professional confirmation message  

---

#### 3. Click-Outside Handler for Menu

**Added:**
```typescript
function handleClickOutside(event: MouseEvent) {
    const target = event.target as HTMLElement;
    // Close menu if clicking outside the menu area
    if (showConvMenu.value && !target.closest('.conv-menu-container')) {
        showConvMenu.value = false;
    }
}

onMounted(() => {
    fetchConversations();
    fetchRequests();
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    if (typingTimeout) clearTimeout(typingTimeout);
    if (echoLeave) echoLeave();
    if (newMessageSearchDebounce) clearTimeout(newMessageSearchDebounce);
    document.removeEventListener('click', handleClickOutside);
});
```

**Template Update:**
```vue
<div class="relative conv-menu-container">
    <!-- Menu button and dropdown -->
</div>
```

**Improvements:**
✅ Menu closes when clicking outside  
✅ Better UX and less clutter  
✅ Proper cleanup on unmount  

---

### Backend (`app/Http/Controllers/ChatController.php`)

#### Existing Methods (Already Implemented)

```php
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
```

---

### Routes (`routes/web.php`)

```php
Route::post('api/users/{user}/block', [ChatController::class, 'block'])->name('users.block');
Route::delete('api/users/{user}/block', [ChatController::class, 'unblock'])->name('users.unblock');
Route::post('api/conversations/{conversation}/report', [ChatController::class, 'reportConversation'])->name('chat.report');
```

---

### User Model (`app/Models/User.php`)

```php
/**
 * Users this user has blocked
 */
public function blockedUsers(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'blocks', 'blocker_id', 'blocked_id')
        ->withTimestamps();
}

/**
 * Users who have blocked this user
 */
public function blockedBy(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'blocks', 'blocked_id', 'blocker_id')
        ->withTimestamps();
}

public function isBlocking(User $user): bool
{
    return $this->blockedUsers()->where('blocked_id', $user->id)->exists();
}

public function isBlockedBy(User $user): bool
{
    return $this->blockedBy()->where('blocker_id', $user->id)->exists();
}

public function block(User $user): bool
{
    if ($this->id === $user->id) {
        return false;
    }
    $this->blockedUsers()->syncWithoutDetaching([$user->id]);
    return true;
}

public function unblock(User $user): bool
{
    $this->blockedUsers()->detach($user->id);
    return true;
}
```

---

### Database Migrations

#### Blocks Table (`2026_02_03_200003_create_blocks_table.php`)

```php
Schema::create('blocks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('blocker_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('blocked_id')->constrained('users')->onDelete('cascade');
    $table->timestamps();

    $table->unique(['blocker_id', 'blocked_id']);
    $table->index('blocked_id');
});
```

#### Conversation Reports Table (`2026_02_03_200004_create_conversation_reports_table.php`)

```php
Schema::create('conversation_reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
    $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
    $table->string('reason', 500)->nullable();
    $table->timestamps();

    $table->index(['conversation_id', 'reporter_id']);
});
```

---

## User Experience Flow

### Blocking a User

```
┌─────────────────────────────────────────────┐
│  Chat with John Doe                    ⋮    │
├─────────────────────────────────────────────┤
│  User clicks menu (⋮)                       │
│  ┌─────────────────┐                        │
│  │ 🚩 Report       │                        │
│  │ 🚫 Block        │ ← User clicks          │
│  └─────────────────┘                        │
└─────────────────────────────────────────────┘

           ↓

┌─────────────────────────────────────────────┐
│  Confirmation Dialog                         │
├─────────────────────────────────────────────┤
│  Are you sure you want to block John Doe?   │
│                                              │
│  This will:                                  │
│  • Hide all conversations with this user    │
│  • Prevent them from contacting you         │
│  • Remove them from your matches            │
│                                              │
│         [Cancel]         [OK]                │
└─────────────────────────────────────────────┘

           ↓ (if OK clicked)

┌─────────────────────────────────────────────┐
│  Success Alert                               │
├─────────────────────────────────────────────┤
│  John Doe has been blocked successfully.    │
│                                              │
│                [OK]                          │
└─────────────────────────────────────────────┘

           ↓

  User returns to chat list
  Conversation with John Doe is removed
```

### Reporting a Conversation

```
┌─────────────────────────────────────────────┐
│  Chat with Jane Smith                  ⋮    │
├─────────────────────────────────────────────┤
│  User clicks menu (⋮)                       │
│  ┌─────────────────┐                        │
│  │ 🚩 Report       │ ← User clicks          │
│  │ 🚫 Block        │                        │
│  └─────────────────┘                        │
└─────────────────────────────────────────────┘

           ↓

┌─────────────────────────────────────────────┐
│  Report Dialog                               │
├─────────────────────────────────────────────┤
│  Report conversation with Jane Smith        │
│                                              │
│  Please describe the issue (optional):      │
│  ┌─────────────────────────────────────┐   │
│  │ Sending spam messages               │   │
│  └─────────────────────────────────────┘   │
│                                              │
│         [Cancel]         [OK]                │
└─────────────────────────────────────────────┘

           ↓ (if OK clicked)

┌─────────────────────────────────────────────┐
│  Success Alert                               │
├─────────────────────────────────────────────┤
│  Thank you for your report. Our team will   │
│  review this conversation with Jane Smith.  │
│                                              │
│                [OK]                          │
└─────────────────────────────────────────────┘

           ↓

  User stays in the chat
  Report is saved for admin review
```

---

## Testing Checklist

### Block Functionality
- [x] Block button appears in conversation menu
- [x] Confirmation dialog shows with clear message
- [x] Cancelling confirmation does nothing
- [x] Confirming blocks the user in database
- [x] Conversation closes after blocking
- [x] Chat list refreshes and removes blocked conversation
- [x] Success message appears
- [x] Error handling works (shows error message)
- [x] Cannot block yourself (backend validation)
- [x] Menu closes after action

### Report Functionality
- [x] Report button appears in conversation menu
- [x] Prompt dialog asks for reason
- [x] Cancelling prompt does nothing
- [x] Empty reason defaults to "Inappropriate conversation"
- [x] Custom reason is saved correctly
- [x] Reason is limited to 500 characters
- [x] Report is saved to database
- [x] Success message appears
- [x] Error handling works (shows error message)
- [x] Only participants can report (backend validation)
- [x] Menu closes after action

### Menu Interaction
- [x] Menu opens when clicking three dots
- [x] Menu closes when clicking outside
- [x] Menu closes after selecting an action
- [x] Menu closes when conversation is closed
- [x] Event listeners are cleaned up on unmount

---

## Admin Panel Considerations

For future development, consider adding an admin panel to:

1. **View Reported Conversations**
   - Query: `SELECT * FROM conversation_reports ORDER BY created_at DESC`
   - Show: Reporter, Conversation, Reason, Date

2. **Review Blocked Users**
   - Query: `SELECT * FROM blocks ORDER BY created_at DESC`
   - Show: Blocker, Blocked User, Date

3. **Take Action**
   - Ban users with multiple reports
   - Remove inappropriate content
   - Send warnings
   - Unblock users if needed

---

## Security Considerations

✅ **Authorization:** Only conversation participants can report  
✅ **Validation:** Cannot block yourself  
✅ **CSRF Protection:** All requests include CSRF token  
✅ **Input Sanitization:** Reason limited to 500 chars  
✅ **Cascade Deletion:** Reports/blocks deleted when users deleted  
✅ **Unique Constraints:** Cannot block same user twice  

---

## Summary

The Report and Block functionality is now **fully functional** with:

✅ Proper confirmation dialogs  
✅ User-friendly success/error messages  
✅ Error handling for network issues  
✅ Click-outside menu behavior  
✅ Database persistence  
✅ Backend validation  
✅ Security measures  
✅ Professional UX flow  

**Status:** ✅ COMPLETE AND READY FOR USE 🎉