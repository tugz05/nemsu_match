<template>
  <EditorLayout title="Dashboard Overview">

    <!-- Welcome Banner -->
    <div class="welcome-banner">
      <h2>Welcome, {{ $page.props.auth.user.display_name }}! ✍️</h2>
      <p>Here's an overview of the NEMSU Match platform.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        </div>
        <div class="stat-value">{{ stats.total_users }}</div>
        <div class="stat-label">Total Users</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
        </div>
        <div class="stat-value">{{ stats.active_today }}</div>
        <div class="stat-label">Active Today</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
        </div>
        <div class="stat-value">{{ stats.total_announcements }}</div>
        <div class="stat-label">Total Announcements</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon teal">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2z"/></svg>
        </div>
        <div class="stat-value">{{ stats.total_posts }}</div>
        <div class="stat-label">Total Posts</div>
      </div>
    </div>

    <!-- Bottom Grid -->
    <div class="bottom-grid">
      <!-- Recent Users -->
      <div class="card">
        <div class="card-header"><h3>Recent Users</h3></div>
        <div class="card-body">
          <div v-for="user in recent_users" :key="user.id" class="user-row">
            <div class="user-ava">
              <img v-if="user.profile_picture" :src="user.profile_picture" :alt="user.display_name" />
              <span v-else>{{ initials(user.display_name) }}</span>
            </div>
            <div>
              <div class="uname">{{ user.display_name }}</div>
              <div class="uemail">{{ user.email }}</div>
            </div>
          </div>
          <div v-if="!recent_users.length" class="empty">No users yet.</div>
        </div>
      </div>

      <!-- Quick Access -->
      <div class="card">
        <div class="card-header"><h3>Quick Access</h3></div>
        <div class="card-body">
          <Link href="/editor/announcements" class="quick-link">
            <div class="ql-left">
              <div class="qi purple">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
              </div>
              <div>
                <div class="ql-title">Announcements</div>
                <div class="ql-sub">Create and manage announcements</div>
              </div>
            </div>
            <svg class="arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
          </Link>
          <Link href="/editor/social-feed" class="quick-link">
            <div class="ql-left">
              <div class="qi blue">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2z"/></svg>
              </div>
              <div>
                <div class="ql-title">Social Feed</div>
                <div class="ql-sub">Moderate user posts</div>
              </div>
            </div>
            <svg class="arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
          </Link>
        </div>
      </div>
    </div>

  </EditorLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import EditorLayout from './Layout/EditorLayout.vue'

defineProps({ stats: Object, recent_users: Array, recent_announcements: Array })
const initials = (name) => name ? name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) : 'U'
</script>

<style scoped>
.welcome-banner { background: linear-gradient(135deg, #7c3aed 0%, #a855f7 60%, #06b6d4 100%); border-radius: 16px; padding: 32px 36px; color: white; margin-bottom: 24px; }
.welcome-banner h2 { font-size: 24px; font-weight: 700; margin-bottom: 6px; }
.welcome-banner p  { opacity: 0.85; font-size: 14px; }

.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 22px; }
.stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
.stat-icon svg { width: 22px; height: 22px; }
.stat-icon.blue   { background: #dbeafe; color: #2563eb; }
.stat-icon.green  { background: #d1fae5; color: #059669; }
.stat-icon.purple { background: #ede9fe; color: #7c3aed; }
.stat-icon.teal   { background: #ccfbf1; color: #0d9488; }
.stat-value { font-size: 28px; font-weight: 700; color: #111827; line-height: 1; margin-bottom: 4px; }
.stat-label { font-size: 13px; color: #6b7280; font-weight: 500; }

.bottom-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
.card-header { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
.card-header h3 { font-size: 15px; font-weight: 600; color: #111827; }
.card-body { padding: 16px 20px; }

.user-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
.user-row:last-child { border-bottom: none; }
.user-ava { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #a855f7); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 13px; overflow: hidden; flex-shrink: 0; }
.user-ava img { width: 100%; height: 100%; object-fit: cover; }
.uname  { font-size: 14px; font-weight: 500; color: #111827; }
.uemail { font-size: 12px; color: #6b7280; }
.empty  { font-size: 14px; color: #9ca3af; text-align: center; padding: 20px 0; }

.quick-link { display: flex; align-items: center; justify-content: space-between; padding: 14px; border-radius: 10px; border: 1px solid #e5e7eb; text-decoration: none; color: #111827; transition: all 0.15s; margin-bottom: 10px; }
.quick-link:last-child { margin-bottom: 0; }
.quick-link:hover { background: #f5f3ff; border-color: #7c3aed; }
.ql-left { display: flex; align-items: center; gap: 12px; }
.qi { width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.qi svg { width: 18px; height: 18px; }
.qi.purple { background: #ede9fe; color: #7c3aed; }
.qi.blue   { background: #dbeafe; color: #2563eb; }
.ql-title { font-size: 14px; font-weight: 600; color: #111827; }
.ql-sub   { font-size: 12px; color: #6b7280; }
.arrow { width: 20px; height: 20px; color: #9ca3af; flex-shrink: 0; }
</style>