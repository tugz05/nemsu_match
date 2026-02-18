<template>
  <EditorLayout title="User Profile">

    <div class="page-header">
      <div class="header-left">
        <Link href="/editor/users" class="back-btn">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
          Back to Users
        </Link>
        <h2>User Profile</h2>
        <p>View and manage this user's account</p>
      </div>
      <div class="status-badges">
        <span v-if="user.is_banned" class="badge badge-red">Banned</span>
        <span v-else-if="user.is_suspended" class="badge badge-orange">Suspended</span>
        <span v-else class="badge badge-green">Active</span>
        <span v-if="user.is_verified_student" class="badge badge-blue">✓ Verified Student</span>
        <span v-else class="badge badge-gray">Unverified</span>
      </div>
    </div>

    <!-- Profile Card -->
    <div class="card profile-card mb">
      <div class="profile-top">
        <div class="profile-ava">
          <img v-if="user.avatar" :src="user.avatar" />
          <span v-else>{{ initials(user.name) }}</span>
        </div>
        <div class="profile-info">
          <h3 class="profile-name">{{ user.name }}</h3>
          <p class="profile-email">{{ user.email }}</p>
          <p class="profile-joined">Joined {{ formatDate(user.created_at) }}</p>
        </div>
        <div class="profile-actions">
          <button v-if="!user.is_verified_student" @click="verifyStudent" class="btn btn-blue">Verify Student</button>
          <button v-else @click="unverifyStudent" class="btn btn-outline">Remove Verification</button>

          <button v-if="!user.is_suspended && !user.is_banned" @click="openSuspendModal" class="btn btn-warn">Suspend</button>
          <button v-else-if="user.is_suspended && !user.is_banned" @click="unsuspend" class="btn btn-success">Unsuspend</button>

          <button v-if="!user.is_banned" @click="openBanModal" class="btn btn-danger">Ban User</button>
        </div>
      </div>

      <!-- Suspension Info -->
      <div v-if="user.is_suspended && user.suspension_reason" class="suspension-info">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <div>
          <strong>{{ user.is_banned ? 'Ban' : 'Suspension' }} Reason:</strong>
          {{ user.suspension_reason }}
          <span v-if="user.suspended_at" class="muted"> · {{ formatDate(user.suspended_at) }}</span>
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid mb">
      <div class="stat-card">
        <div class="stat-icon purple">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ user.total_matches }}</div>
          <div class="stat-label">Total Matches</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ user.matches_this_week }}</div>
          <div class="stat-label">Matches This Week</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ user.total_posts }}</div>
          <div class="stat-label">Total Posts</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ user.reports_against }}</div>
          <div class="stat-label">Reports Against</div>
        </div>
      </div>
    </div>

    <!-- Suspend Modal -->
    <Teleport to="body">
      <div v-if="suspendModal" class="modal-overlay" @click.self="suspendModal = false">
        <div class="modal">
          <div class="modal-header">
            <h3>Suspend {{ user.name }}</h3>
            <button @click="suspendModal = false" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
          </div>
          <div class="modal-body">
            <p class="modal-text">Provide a reason for suspending <strong>{{ user.name }}</strong>.</p>
            <textarea v-model="actionReason" rows="3" placeholder="Reason for suspension..." class="modal-textarea"></textarea>
          </div>
          <div class="modal-footer">
            <button @click="suspendModal = false" class="btn btn-outline">Cancel</button>
            <button @click="confirmSuspend" :disabled="!actionReason" class="btn btn-warn">Suspend User</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Ban Modal -->
    <Teleport to="body">
      <div v-if="banModal" class="modal-overlay" @click.self="banModal = false">
        <div class="modal">
          <div class="modal-header">
            <h3>Ban {{ user.name }}</h3>
            <button @click="banModal = false" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
          </div>
          <div class="modal-body">
            <p class="modal-text danger">This will <strong>permanently ban</strong> {{ user.name }} from the platform.</p>
            <textarea v-model="actionReason" rows="3" placeholder="Reason for ban..." class="modal-textarea"></textarea>
          </div>
          <div class="modal-footer">
            <button @click="banModal = false" class="btn btn-outline">Cancel</button>
            <button @click="confirmBan" :disabled="!actionReason" class="btn btn-danger">Ban User</button>
          </div>
        </div>
      </div>
    </Teleport>

  </EditorLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import EditorLayout from './Layout/EditorLayout.vue'

const props = defineProps({ user: Object })

const suspendModal = ref(false)
const banModal     = ref(false)
const actionReason = ref('')

const verifyStudent   = () => router.post(`/editor/users/${props.user.id}/verify-student`)
const unverifyStudent = () => router.post(`/editor/users/${props.user.id}/unverify-student`)
const unsuspend       = () => router.post(`/editor/users/${props.user.id}/unsuspend`)

const openSuspendModal = () => { actionReason.value = ''; suspendModal.value = true }
const openBanModal     = () => { actionReason.value = ''; banModal.value = true }

const confirmSuspend = () => router.post(`/editor/users/${props.user.id}/suspend`, { reason: actionReason.value }, { onSuccess: () => { suspendModal.value = false } })
const confirmBan     = () => router.post(`/editor/users/${props.user.id}/ban`,     { reason: actionReason.value }, { onSuccess: () => { banModal.value = false } })

const initials   = (name) => name ? name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) : 'U'
const formatDate = (d)    => new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; }
.header-left { display: flex; flex-direction: column; gap: 4px; }
.page-header h2 { font-size: 20px; font-weight: 700; color: #111827; }
.page-header p  { font-size: 14px; color: #6b7280; margin-top: 2px; }
.status-badges { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.back-btn { display: inline-flex; align-items: center; gap: 4px; font-size: 13px; color: #6b7280; text-decoration: none; margin-bottom: 4px; transition: color 0.15s; }
.back-btn:hover { color: #7c3aed; }
.back-btn svg { width: 16px; height: 16px; }
.mb { margin-bottom: 16px; }

.card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }

/* Profile Card */
.profile-card { padding: 24px; }
.profile-top { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.profile-ava { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #a855f7); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 20px; overflow: hidden; flex-shrink: 0; }
.profile-ava img { width: 100%; height: 100%; object-fit: cover; }
.profile-info { flex: 1; }
.profile-name  { font-size: 18px; font-weight: 700; color: #111827; }
.profile-email { font-size: 14px; color: #6b7280; margin-top: 2px; }
.profile-joined { font-size: 13px; color: #9ca3af; margin-top: 2px; }
.profile-actions { display: flex; gap: 8px; flex-wrap: wrap; }

.suspension-info { display: flex; align-items: flex-start; gap: 10px; margin-top: 16px; padding: 12px 16px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; font-size: 14px; color: #92400e; }
.suspension-info svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; fill: #f97316; }
.muted { color: #9ca3af; }

/* Stats Grid */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
.stat-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 18px; display: flex; align-items: center; gap: 14px; }
.stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-icon svg { width: 22px; height: 22px; }
.stat-icon.purple { background: #ede9fe; color: #7c3aed; fill: #7c3aed; }
.stat-icon.blue   { background: #dbeafe; color: #1d4ed8; fill: #1d4ed8; }
.stat-icon.green  { background: #d1fae5; color: #059669; fill: #059669; }
.stat-icon.red    { background: #fee2e2; color: #dc2626; fill: #dc2626; }
.stat-value { font-size: 22px; font-weight: 700; color: #111827; }
.stat-label { font-size: 12px; color: #6b7280; margin-top: 2px; }

/* Badges */
.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; }
.badge-gray   { background: #f3f4f6; color: #6b7280; }
.badge-green  { background: #d1fae5; color: #059669; }
.badge-orange { background: #ffedd5; color: #c2410c; }
.badge-red    { background: #fee2e2; color: #dc2626; }
.badge-blue   { background: #dbeafe; color: #1d4ed8; }

/* Buttons */
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; transition: all 0.15s; text-decoration: none; }
.btn-outline { background: white; border: 1px solid #e5e7eb; color: #374151; }
.btn-outline:hover { background: #f9fafb; }
.btn-blue    { background: #dbeafe; color: #1d4ed8; }
.btn-blue:hover { background: #bfdbfe; }
.btn-warn    { background: #f97316; color: white; }
.btn-warn:hover { background: #ea580c; }
.btn-success { background: #059669; color: white; }
.btn-success:hover { background: #047857; }
.btn-danger  { background: #dc2626; color: white; }
.btn-danger:hover { background: #b91c1c; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal { background: white; border-radius: 16px; width: 100%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); animation: slideUp 0.2s ease; }
@keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; }
.modal-header h3 { font-size: 16px; font-weight: 600; }
.modal-close { background: none; border: none; cursor: pointer; color: #6b7280; border-radius: 6px; padding: 4px; display: flex; }
.modal-close svg { width: 18px; height: 18px; }
.modal-body { padding: 20px 24px; display: flex; flex-direction: column; gap: 12px; }
.modal-text { color: #6b7280; font-size: 14px; }
.modal-text.danger { color: #dc2626; }
.modal-text strong { color: #111827; }
.modal-textarea { padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; resize: vertical; outline: none; font-family: inherit; }
.modal-textarea:focus { border-color: #7c3aed; }
.modal-footer { display: flex; gap: 10px; justify-content: flex-end; padding: 16px 24px; border-top: 1px solid #e5e7eb; }
</style>