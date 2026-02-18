<template>
  <EditorLayout title="Audit Log">

    <div class="page-header">
      <div>
        <h2>Audit Log</h2>
        <p>Track all editor actions across the platform</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb filters-card">
      <select v-model="filterAction" @change="applyFilters" class="filter-select">
        <option value="">All Actions</option>
        <option value="created">Created</option>
        <option value="updated">Updated</option>
        <option value="deleted">Deleted</option>
        <option value="suspended">Suspended</option>
        <option value="unsuspended">Unsuspended</option>
        <option value="banned">Banned</option>
        <option value="verified_student">Verified Student</option>
        <option value="reviewed_report">Reviewed Report</option>
      </select>
      <select v-model="filterType" @change="applyFilters" class="filter-select">
        <option value="">All Types</option>
        <option value="announcement">Announcement</option>
        <option value="user">User</option>
        <option value="content_report">Report</option>
      </select>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Editor</th>
              <th>Action</th>
              <th>Target</th>
              <th>IP Address</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(log, i) in logs.data" :key="log.id">
              <td class="muted">{{ i + 1 }}</td>
              <td>
                <div class="uname">{{ log.editor_name }}</div>
                <div class="uemail">{{ log.editor_email }}</div>
              </td>
              <td><span :class="['badge', actionBadge(log.action)]">{{ log.action.replace(/_/g, ' ') }}</span></td>
              <td class="muted capitalize">{{ log.target_type }}{{ log.target_id ? ' #' + log.target_id : '' }}</td>
              <td class="muted">{{ log.ip_address ?? '—' }}</td>
              <td class="muted">{{ formatDate(log.created_at) }}</td>
            </tr>
            <tr v-if="!logs.data.length">
              <td colspan="6" class="empty-row">No audit logs yet.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="logs.last_page > 1" class="pagination">
        <Link v-for="link in logs.links" :key="link.label"
          :href="link.url ?? '#'"
          v-html="link.label"
          class="page-btn"
          :class="{ active: link.active }"
        />
      </div>
    </div>

  </EditorLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import EditorLayout from './Layout/EditorLayout.vue'

const props = defineProps({ logs: Object, filters: Object })

const filterAction = ref(props.filters?.action ?? '')
const filterType   = ref(props.filters?.type   ?? '')

const applyFilters = () => router.get('/editor/audit-log', { action: filterAction.value, type: filterType.value }, { preserveState: true, replace: true })

const actionBadge = (a) => {
  if (['created'].includes(a))                              return 'badge-green'
  if (['updated', 'activated', 'deactivated'].includes(a)) return 'badge-blue'
  if (['deleted', 'banned'].includes(a))                   return 'badge-red'
  if (['suspended'].includes(a))                           return 'badge-orange'
  if (['verified_student', 'unsuspended'].includes(a))     return 'badge-teal'
  return 'badge-gray'
}

const formatDate = (d) => new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.page-header h2 { font-size: 20px; font-weight: 700; color: #111827; }
.page-header p  { font-size: 14px; color: #6b7280; margin-top: 2px; }
.mb { margin-bottom: 16px; }

.card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
.filters-card { padding: 14px 16px; display: flex; gap: 12px; }
.filter-select { padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; background: white; }

.table-wrapper { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead th { text-align: left; padding: 11px 16px; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
tbody td { padding: 13px 16px; font-size: 14px; border-bottom: 1px solid #f3f4f6; color: #111827; vertical-align: middle; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: #fafafa; }
.muted { color: #9ca3af !important; font-size: 13px !important; }
.capitalize { text-transform: capitalize; }
.uname  { font-size: 14px; font-weight: 500; }
.uemail { font-size: 12px; color: #9ca3af; }
.empty-row { text-align: center; padding: 40px !important; color: #9ca3af; font-size: 14px; }

.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; text-transform: capitalize; }
.badge-gray   { background: #f3f4f6; color: #6b7280; }
.badge-green  { background: #d1fae5; color: #059669; }
.badge-blue   { background: #dbeafe; color: #1d4ed8; }
.badge-red    { background: #fee2e2; color: #dc2626; }
.badge-orange { background: #ffedd5; color: #c2410c; }
.badge-teal   { background: #ccfbf1; color: #0d9488; }

.pagination { display: flex; gap: 4px; padding: 12px 16px; border-top: 1px solid #e5e7eb; }
.page-btn { padding: 5px 10px; border-radius: 6px; border: 1px solid #e5e7eb; font-size: 13px; color: #374151; text-decoration: none; transition: all 0.15s; }
.page-btn:hover { background: #f3f4f6; }
.page-btn.active { background: #7c3aed; color: white; border-color: #7c3aed; }
</style>