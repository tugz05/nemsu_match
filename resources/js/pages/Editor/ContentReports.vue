<template>
  <EditorLayout title="Content Reports">

    <div class="page-header">
      <div>
        <h2>Content Reports</h2>
        <p>Review and resolve user-submitted reports</p>
      </div>
      <div class="total-badge">{{ stats.pending }} Pending</div>
    </div>

    <!-- Stats -->
    <div class="stats-row mb">
      <div class="mini-stat yellow">
        <div class="ms-value">{{ stats.pending }}</div>
        <div class="ms-label">Pending</div>
      </div>
      <div class="mini-stat blue">
        <div class="ms-value">{{ stats.reviewed }}</div>
        <div class="ms-label">Reviewed</div>
      </div>
      <div class="mini-stat green">
        <div class="ms-value">{{ stats.resolved }}</div>
        <div class="ms-label">Resolved</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb filters-card">
      <select v-model="filterStatus" @change="applyFilters" class="filter-select">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="reviewed">Reviewed</option>
        <option value="resolved">Resolved</option>
        <option value="dismissed">Dismissed</option>
      </select>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Reporter</th>
              <th>Target</th>
              <th>Reason</th>
              <th>Status</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(report, i) in reports.data" :key="report.id">
              <td class="muted">{{ i + 1 }}</td>
              <td>
                <div class="uname">{{ report.reporter_name }}</div>
                <div class="uemail">{{ report.reporter_email }}</div>
              </td>
              <td class="muted">{{ report.reportable_type }} #{{ report.reportable_id }}</td>
              <td><span class="badge badge-red">{{ report.reason_label }}</span></td>
              <td><span :class="['badge', statusBadge(report.status)]">{{ report.status }}</span></td>
              <td class="muted">{{ formatDate(report.created_at) }}</td>
              <td>
                <button v-if="report.status === 'pending'" @click="openReviewModal(report)" class="btn-sm btn-purple">Review</button>
                <span v-else class="muted">Done</span>
              </td>
            </tr>
            <tr v-if="!reports.data.length">
              <td colspan="7" class="empty-row">No reports found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="reports.last_page > 1" class="pagination">
        <Link v-for="link in reports.links" :key="link.label"
          :href="link.url ?? '#'"
          v-html="link.label"
          class="page-btn"
          :class="{ active: link.active }"
        />
      </div>
    </div>

    <!-- Review Modal -->
    <Teleport to="body">
      <div v-if="reviewModal" class="modal-overlay" @click.self="reviewModal = false">
        <div class="modal">
          <div class="modal-header">
            <h3>Review Report</h3>
            <button @click="reviewModal = false" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
          </div>
          <div class="modal-body">
            <p class="modal-text"><strong>{{ reviewTarget?.reason_label }}</strong> reported by {{ reviewTarget?.reporter_name }}</p>
            <select v-model="reviewForm.status" class="modal-select">
              <option value="reviewed">Mark as Reviewed</option>
              <option value="resolved">Resolved (Action taken)</option>
              <option value="dismissed">Dismissed (No action needed)</option>
            </select>
            <textarea v-model="reviewForm.review_notes" rows="3" placeholder="Review notes (optional)..." class="modal-textarea"></textarea>
          </div>
          <div class="modal-footer">
            <button @click="reviewModal = false" class="btn btn-outline">Cancel</button>
            <button @click="submitReview" class="btn btn-primary">Submit Review</button>
          </div>
        </div>
      </div>
    </Teleport>

  </EditorLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import EditorLayout from './Layout/EditorLayout.vue'

const props = defineProps({ reports: Object, filters: Object, stats: Object, reasons: Object })

const filterStatus = ref(props.filters?.status ?? '')
const reviewModal  = ref(false)
const reviewTarget = ref(null)
const reviewForm   = useForm({ status: 'reviewed', review_notes: '' })

const applyFilters = () => router.get('/editor/reports', { status: filterStatus.value }, { preserveState: true, replace: true })
const openReviewModal = (r) => { reviewTarget.value = r; reviewModal.value = true }
const submitReview = () => reviewForm.patch(`/editor/reports/${reviewTarget.value.id}/review`, { onSuccess: () => { reviewModal.value = false } })

const statusBadge = (s) => ({ pending: 'badge-yellow', reviewed: 'badge-blue', resolved: 'badge-green', dismissed: 'badge-gray' }[s] ?? 'badge-gray')
const formatDate  = (d) => new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.page-header h2 { font-size: 20px; font-weight: 700; color: #111827; }
.page-header p  { font-size: 14px; color: #6b7280; margin-top: 2px; }
.total-badge { background: #fef9c3; color: #a16207; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }
.mb { margin-bottom: 16px; }

.stats-row { display: flex; gap: 16px; }
.mini-stat { background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 18px 24px; flex: 1; text-align: center; }
.ms-value { font-size: 26px; font-weight: 700; }
.ms-label { font-size: 13px; color: #6b7280; margin-top: 4px; }
.mini-stat.yellow .ms-value { color: #a16207; }
.mini-stat.blue .ms-value   { color: #1d4ed8; }
.mini-stat.green .ms-value  { color: #059669; }

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
.uname  { font-size: 14px; font-weight: 500; }
.uemail { font-size: 12px; color: #9ca3af; }
.empty-row { text-align: center; padding: 40px !important; color: #9ca3af; font-size: 14px; }

.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; }
.badge-gray   { background: #f3f4f6; color: #6b7280; }
.badge-red    { background: #fee2e2; color: #dc2626; }
.badge-blue   { background: #dbeafe; color: #1d4ed8; }
.badge-green  { background: #d1fae5; color: #059669; }
.badge-yellow { background: #fef9c3; color: #a16207; }

.btn-sm { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; border: none; transition: all 0.15s; }
.btn-purple { background: #ede9fe; color: #7c3aed; }
.btn-purple:hover { background: #ddd6fe; }

.pagination { display: flex; gap: 4px; padding: 12px 16px; border-top: 1px solid #e5e7eb; }
.page-btn { padding: 5px 10px; border-radius: 6px; border: 1px solid #e5e7eb; font-size: 13px; color: #374151; text-decoration: none; transition: all 0.15s; }
.page-btn:hover { background: #f3f4f6; }
.page-btn.active { background: #7c3aed; color: white; border-color: #7c3aed; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal { background: white; border-radius: 16px; width: 100%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); animation: slideUp 0.2s ease; }
@keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; }
.modal-header h3 { font-size: 16px; font-weight: 600; }
.modal-close { background: none; border: none; cursor: pointer; color: #6b7280; border-radius: 6px; padding: 4px; display: flex; }
.modal-close svg { width: 18px; height: 18px; }
.modal-body { padding: 20px 24px; display: flex; flex-direction: column; gap: 12px; }
.modal-text { color: #6b7280; font-size: 14px; }
.modal-select { padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; background: white; }
.modal-textarea { padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; resize: vertical; outline: none; font-family: inherit; }
.modal-textarea:focus, .modal-select:focus { border-color: #7c3aed; }
.modal-footer { display: flex; gap: 10px; justify-content: flex-end; padding: 16px 24px; border-top: 1px solid #e5e7eb; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.15s; }
.btn-outline { background: white; border: 1px solid #e5e7eb; color: #374151; }
.btn-outline:hover { background: #f9fafb; }
.btn-primary { background: #7c3aed; color: white; }
.btn-primary:hover { background: #6d28d9; }
</style>