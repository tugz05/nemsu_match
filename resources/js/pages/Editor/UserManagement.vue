<template>
  <EditorLayout title="User Management">

    <div class="page-header">
      <div>
        <h2>User Management</h2>
        <p>View, suspend, ban, and verify student accounts</p>
      </div>
      <div class="total-badge">{{ users.total }} Total Users</div>
    </div>

    <!-- Filters -->
    <div class="card mb filters-card">
      <input v-model="search" @input="applyFilters" type="text" placeholder="Search name or email..." class="search-input" />
      <select v-model="filterStatus" @change="applyFilters" class="filter-select">
        <option value="">All Users</option>
        <option value="suspended">Suspended</option>
        <option value="banned">Banned</option>
        <option value="verified">Verified Students</option>
        <option value="unverified">Unverified</option>
      </select>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Status</th>
              <th>Verification</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(user, i) in users.data" :key="user.id">
              <td class="muted">{{ i + 1 }}</td>
              <td>
                <div class="user-cell">
                  <div class="user-ava">
                    <img v-if="user.avatar" :src="user.avatar" />
                    <span v-else>{{ initials(user.name) }}</span>
                  </div>
                  <div>
                    <div class="uname">{{ user.name }}</div>
                    <div class="uemail">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span v-if="user.is_banned" class="badge badge-red">Banned</span>
                <span v-else-if="user.is_suspended" class="badge badge-orange">Suspended</span>
                <span v-else class="badge badge-green">Active</span>
              </td>
              <td>
                <span v-if="user.is_verified_student" class="badge badge-blue">✓ Verified</span>
                <span v-else class="badge badge-gray">Unverified</span>
              </td>
              <td class="muted">{{ formatDate(user.created_at) }}</td>
              <td>
                <div class="action-btns">
<Link :href="`/editor/users/${user.id}`" class="btn-sm btn-gray">View</Link>
                  <button v-if="!user.is_verified_student" @click="verifyStudent(user)" class="btn-sm btn-blue">Verify</button>
                  <button v-else @click="unverifyStudent(user)" class="btn-sm btn-gray">Unverify</button>

                  <button v-if="!user.is_suspended && !user.is_banned" @click="openSuspendModal(user)" class="btn-sm btn-warn">Suspend</button>
                  <button v-else-if="user.is_suspended && !user.is_banned" @click="unsuspend(user)" class="btn-sm btn-success">Unsuspend</button>

                  <button v-if="!user.is_banned" @click="openBanModal(user)" class="btn-sm btn-danger">Ban</button>
                </div>
              </td>
            </tr>
            <tr v-if="!users.data.length">
              <td colspan="6" class="empty-row">No users found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="users.last_page > 1" class="pagination">
        <Link v-for="link in users.links" :key="link.label"
          :href="link.url ?? '#'"
          v-html="link.label"
          class="page-btn"
          :class="{ active: link.active }"
        />
      </div>
    </div>

    <!-- Suspend Modal -->
    <Teleport to="body">
      <div v-if="suspendModal" class="modal-overlay" @click.self="suspendModal = false">
        <div class="modal">
          <div class="modal-header">
            <h3>Suspend {{ suspendTarget?.name }}</h3>
            <button @click="suspendModal = false" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
          </div>
          <div class="modal-body">
            <p class="modal-text">Provide a reason for suspending <strong>{{ suspendTarget?.name }}</strong>.</p>
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
            <h3>Ban {{ banTarget?.name }}</h3>
            <button @click="banModal = false" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
          </div>
          <div class="modal-body">
            <p class="modal-text danger">This will <strong>permanently ban</strong> {{ banTarget?.name }} from the platform.</p>
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

const props = defineProps({ users: Object, filters: Object })

const search        = ref(props.filters?.search ?? '')
const filterStatus  = ref(props.filters?.filter ?? '')
const suspendModal  = ref(false)
const banModal      = ref(false)
const suspendTarget = ref(null)
const banTarget     = ref(null)
const actionReason  = ref('')

const applyFilters    = () => router.get('/editor/users', { search: search.value, filter: filterStatus.value }, { preserveState: true, replace: true })
const verifyStudent   = (u) => router.post(`/editor/users/${u.id}/verify-student`)
const unverifyStudent = (u) => router.post(`/editor/users/${u.id}/unverify-student`)
const unsuspend       = (u) => router.post(`/editor/users/${u.id}/unsuspend`)
const openSuspendModal = (u) => { suspendTarget.value = u; actionReason.value = ''; suspendModal.value = true }
const openBanModal     = (u) => { banTarget.value = u;     actionReason.value = ''; banModal.value = true }

const confirmSuspend  = () => router.post(`/editor/users/${suspendTarget.value.id}/suspend`, { reason: actionReason.value }, { onSuccess: () => { suspendModal.value = false } })
const confirmBan      = () => router.post(`/editor/users/${banTarget.value.id}/ban`, { reason: actionReason.value }, { onSuccess: () => { banModal.value = false } })

const initials   = (name) => name ? name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) : 'U'
const formatDate = (d) => new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.page-header h2 { font-size: 20px; font-weight: 700; color: #111827; }
.page-header p  { font-size: 14px; color: #6b7280; margin-top: 2px; }
.total-badge { background: #ede9fe; color: #5b21b6; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }
.mb { margin-bottom: 16px; }

.card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
.filters-card { padding: 14px 16px; display: flex; gap: 12px; align-items: center; }
.search-input { flex: 1; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; }
.search-input:focus { border-color: #7c3aed; }
.filter-select { padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; background: white; }

.table-wrapper { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead th { text-align: left; padding: 11px 16px; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
tbody td { padding: 13px 16px; font-size: 14px; border-bottom: 1px solid #f3f4f6; color: #111827; vertical-align: middle; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: #fafafa; }
.muted { color: #9ca3af !important; font-size: 13px !important; }
.empty-row { text-align: center; padding: 40px !important; color: #9ca3af; font-size: 14px; }

.user-cell { display: flex; align-items: center; gap: 10px; }
.user-ava { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #a855f7); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 12px; overflow: hidden; flex-shrink: 0; }
.user-ava img { width: 100%; height: 100%; object-fit: cover; }
.uname  { font-size: 14px; font-weight: 500; }
.uemail { font-size: 12px; color: #9ca3af; }

.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; }
.badge-gray   { background: #f3f4f6; color: #6b7280; }
.badge-green  { background: #d1fae5; color: #059669; }
.badge-orange { background: #ffedd5; color: #c2410c; }
.badge-red    { background: #fee2e2; color: #dc2626; }
.badge-blue   { background: #dbeafe; color: #1d4ed8; }

.action-btns { display: flex; gap: 6px; flex-wrap: wrap; }
.btn-sm { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; border: none; transition: all 0.15s; text-decoration: none; display: inline-flex; align-items: center; }
.btn-gray    { background: #f3f4f6; color: #374151; }
.btn-gray:hover { background: #e5e7eb; }
.btn-blue    { background: #dbeafe; color: #1d4ed8; }
.btn-blue:hover { background: #bfdbfe; }
.btn-warn    { background: #ffedd5; color: #c2410c; }
.btn-warn:hover { background: #fed7aa; }
.btn-success { background: #d1fae5; color: #059669; }
.btn-success:hover { background: #a7f3d0; }
.btn-danger  { background: #fee2e2; color: #dc2626; }
.btn-danger:hover { background: #fecaca; }

.pagination { display: flex; gap: 4px; padding: 12px 16px; border-top: 1px solid #e5e7eb; }
.page-btn { padding: 5px 10px; border-radius: 6px; border: 1px solid #e5e7eb; font-size: 13px; color: #374151; text-decoration: none; transition: all 0.15s; }
.page-btn:hover { background: #f3f4f6; }
.page-btn.active { background: #7c3aed; color: white; border-color: #7c3aed; }

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
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.15s; }
.btn-outline { background: white; border: 1px solid #e5e7eb; color: #374151; }
.btn-outline:hover { background: #f9fafb; }
.btn-warn   { background: #f97316; color: white; }
.btn-warn:hover { background: #ea580c; }
.btn-danger { background: #dc2626; color: white; }
.btn-danger:hover { background: #b91c1c; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
</style>