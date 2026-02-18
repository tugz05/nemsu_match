<template>
  <div class="editor-app">
    <aside class="sidebar">
      <div class="sidebar-logo">
        <div class="logo-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
          </svg>
        </div>
        <div class="logo-text">
          <span class="logo-name">NEMSU</span>
          <span class="logo-role">EDITOR PANEL</span>
        </div>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-label">MAIN MENU</div>

        <Link href="/editor/dashboard" :prefetch="false" class="nav-item" :class="{ active: $page.url.startsWith('/editor/dashboard') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
          Dashboard
          <svg v-if="$page.url.startsWith('/editor/dashboard')" class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        </Link>

        <Link href="/editor/announcements" :prefetch="false" class="nav-item" :class="{ active: $page.url.startsWith('/editor/announcements') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
          Announcements
          <svg v-if="$page.url.startsWith('/editor/announcements')" class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        </Link>

        <Link href="/editor/social-feed" :prefetch="false" class="nav-item" :class="{ active: $page.url.startsWith('/editor/social-feed') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2z"/></svg>
          Social Feed
          <svg v-if="$page.url.startsWith('/editor/social-feed')" class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        </Link>

        <div class="nav-label mt">MANAGEMENT</div>

        <Link href="/editor/users" :prefetch="false" class="nav-item" :class="{ active: $page.url.startsWith('/editor/users') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
          User Management
          <svg v-if="$page.url.startsWith('/editor/users')" class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        </Link>

        <Link href="/editor/reports" :prefetch="false" class="nav-item" :class="{ active: $page.url.startsWith('/editor/reports') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/></svg>
          Reports
          <svg v-if="$page.url.startsWith('/editor/reports')" class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        </Link>

        <div class="nav-label mt">INSIGHTS</div>

        <Link href="/editor/analytics" :prefetch="false" class="nav-item" :class="{ active: $page.url.startsWith('/editor/analytics') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M5 9.2h3V19H5zM10.6 5h2.8v14h-2.8zm5.6 8H19v6h-2.8z"/></svg>
          Analytics
          <svg v-if="$page.url.startsWith('/editor/analytics')" class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        </Link>

        <Link href="/editor/audit-log" :prefetch="false" class="nav-item" :class="{ active: $page.url.startsWith('/editor/audit-log') }">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
          Audit Log
          <svg v-if="$page.url.startsWith('/editor/audit-log')" class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        </Link>
      </nav>

      <div class="sidebar-footer">
        <div class="user-card">
          <div class="user-ava">
            <img v-if="$page.props.auth.user.profile_picture" :src="$page.props.auth.user.profile_picture" alt="avatar" />
            <span v-else>{{ initials($page.props.auth.user.display_name) }}</span>
          </div>
          <div class="user-details">
            <div class="user-name">{{ $page.props.auth.user.display_name }}</div>
            <div class="user-email">{{ $page.props.auth.user.email }}</div>
          </div>
        </div>
        <Link href="/admin/logout" method="post" as="button" class="sign-out-btn">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
          Sign Out
        </Link>
      </div>
    </aside>

    <div class="main-content">
      <div class="topbar">
        <h1 class="page-title">{{ title }}</h1>
        <div class="topbar-actions">
          <Link href="/" class="btn btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Back to App
          </Link>
          <Link href="/admin/logout" method="post" as="button" class="btn btn-logout">Logout</Link>
        </div>
      </div>

      <div class="page-content">
        <div v-if="$page.props.flash?.success" class="alert alert-success">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
          {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash?.error" class="alert alert-error">{{ $page.props.flash.error }}</div>
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({ title: String })

const initials = (name) => {
  if (!name) return 'ED'
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}
</script>

<style scoped>
* { box-sizing: border-box; margin: 0; padding: 0; }
.editor-app { display: flex; min-height: 100vh; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #f9fafb; }
.sidebar { width: 260px; background: #ffffff; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; overflow-y: auto; }
.sidebar-logo { display: flex; align-items: center; gap: 12px; padding: 22px 20px 18px; border-bottom: 1px solid #e5e7eb; }
.logo-icon { width: 42px; height: 42px; background: linear-gradient(135deg, #7c3aed, #a855f7); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; }
.logo-icon svg { width: 20px; height: 20px; }
.logo-name { display: block; font-size: 16px; font-weight: 700; color: #7c3aed; letter-spacing: 0.5px; }
.logo-role { display: block; font-size: 10px; color: #9ca3af; font-weight: 600; letter-spacing: 1px; }
.sidebar-nav { padding: 16px 12px; flex: 1; }
.nav-label { font-size: 11px; font-weight: 600; color: #9ca3af; letter-spacing: 1px; padding: 0 8px; margin-bottom: 8px; margin-top: 8px; }
.nav-label.mt { margin-top: 20px; }
.nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; color: #6b7280; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.15s; margin-bottom: 2px; }
.nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }
.nav-item:hover { background: #f5f3ff; color: #7c3aed; }
.nav-item.active { background: linear-gradient(135deg, #7c3aed, #a855f7); color: white; }
.chevron { margin-left: auto; width: 18px !important; height: 18px !important; }
.sidebar-footer { padding: 16px; border-top: 1px solid #e5e7eb; }
.user-card { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 8px; margin-bottom: 8px; }
.user-ava { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #a855f7); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 13px; overflow: hidden; flex-shrink: 0; }
.user-ava img { width: 100%; height: 100%; object-fit: cover; }
.user-name { font-size: 13px; font-weight: 600; color: #111827; }
.user-email { font-size: 11px; color: #6b7280; }
.sign-out-btn { display: flex; align-items: center; gap: 8px; width: 100%; padding: 9px 12px; background: #fef2f2; color: #dc2626; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; transition: background 0.15s; }
.sign-out-btn svg { width: 16px; height: 16px; }
.sign-out-btn:hover { background: #fee2e2; }
.main-content { margin-left: 260px; flex: 1; min-height: 100vh; display: flex; flex-direction: column; }
.topbar { background: white; border-bottom: 1px solid #e5e7eb; padding: 14px 32px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
.page-title { font-size: 18px; font-weight: 600; color: #111827; }
.topbar-actions { display: flex; align-items: center; gap: 10px; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; border: none; transition: all 0.15s; }
.btn svg { width: 16px; height: 16px; }
.btn-outline { background: white; border: 1px solid #e5e7eb; color: #374151; }
.btn-outline:hover { background: #f9fafb; }
.btn-logout { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.btn-logout:hover { background: #fee2e2; }
.page-content { padding: 28px 32px; flex: 1; }
.alert { display: flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
.alert svg { width: 16px; height: 16px; flex-shrink: 0; }
.alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>