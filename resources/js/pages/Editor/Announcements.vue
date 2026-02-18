<template>
  <EditorLayout title="Announcements">

    <div class="page-header">
      <div>
        <h2>Announcements</h2>
        <p>Create and manage announcements for users</p>
      </div>
    </div>

    <!-- Create / Edit Form -->
    <div class="card mb">
      <div class="card-header">
        <h3>{{ editing ? 'Edit Announcement' : 'New Announcement' }}</h3>
      </div>
      <div class="card-body">
        <form @submit.prevent="submit">
          <div class="form-group">
            <label>Title</label>
            <input v-model="form.title" type="text" placeholder="Announcement title..." required />
            <span v-if="form.errors.title" class="form-error">{{ form.errors.title }}</span>
          </div>

          <div class="form-group">
            <label>Content</label>
            <textarea v-model="form.content" rows="4" placeholder="Write your announcement..."></textarea>
            <span v-if="form.errors.content" class="form-error">{{ form.errors.content }}</span>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Type</label>
              <select v-model="form.type">
                <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Target Group</label>
              <select v-model="form.target_group">
                <option v-for="g in target_groups" :key="g.value" :value="g.value">{{ g.label }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Schedule At <span class="optional">(optional)</span></label>
              <input v-model="form.scheduled_at" type="datetime-local" />
              <span v-if="form.errors.scheduled_at" class="form-error">{{ form.errors.scheduled_at }}</span>
            </div>
            <div class="form-group">
              <label>Status</label>
              <label class="toggle-wrap">
                <input type="checkbox" v-model="form.is_active" class="toggle-input" />
                <div class="toggle-track" :class="{ on: form.is_active }">
                  <div class="toggle-thumb"></div>
                </div>
                <span class="toggle-label">{{ form.is_active ? 'Active' : 'Draft' }}</span>
              </label>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" :disabled="form.processing" class="btn btn-primary">
              {{ editing ? 'Update' : 'Publish' }} Announcement
            </button>
            <button v-if="editing" type="button" @click="cancelEdit" class="btn btn-outline">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- List -->
    <div class="card">
      <div class="card-header">
        <h3>All Announcements</h3>
        <span class="badge-count">{{ announcements.total }} total</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Title</th>
              <th>Type</th>
              <th>Target</th>
              <th>Status</th>
              <th>Scheduled</th>
              <th>By</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in announcements.data" :key="item.id">
              <td>
                <div class="ann-title">{{ item.title }}</div>
                <div class="ann-body">{{ truncate(item.content, 60) }}</div>
              </td>
              <td><span :class="['badge', typeBadge(item.type)]">{{ item.type }}</span></td>
              <td><span class="badge badge-blue">{{ item.target_group_label }}</span></td>
              <td><span :class="['badge', statusBadge(item.status)]">{{ item.status }}</span></td>
              <td class="muted">{{ item.scheduled_at ? formatDate(item.scheduled_at) : '—' }}</td>
              <td class="muted">{{ item.created_by_name }}</td>
              <td class="muted">{{ formatDate(item.created_at) }}</td>
              <td>
                <div class="action-btns">
                  <button @click="toggleActive(item)" :class="['btn-sm', item.is_active ? 'btn-warn' : 'btn-success']">
                    {{ item.is_active ? 'Deactivate' : 'Activate' }}
                  </button>
                  <button @click="startEdit(item)" class="btn-sm btn-gray">Edit</button>
                  <button @click="deleteItem(item)" class="btn-sm btn-danger">Delete</button>
                </div>
              </td>
            </tr>
            <tr v-if="!announcements.data.length">
              <td colspan="8" class="empty-row">No announcements yet.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="announcements.last_page > 1" class="pagination">
        <Link v-for="link in announcements.links" :key="link.label"
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
import { useForm, Link, router } from '@inertiajs/vue3'
import EditorLayout from './Layout/EditorLayout.vue'

const props = defineProps({
  announcements: Object,
  target_groups: Array,
  types: Array,
})

const editing = ref(null)

const form = useForm({
  title: '',
  content: '',
  type: 'general',
  is_active: true,
  target_group: 'all',
  scheduled_at: '',
})

const submit = () => {
  if (editing.value) {
    form.put(route('editor.announcements.update', editing.value), { onSuccess: cancelEdit })
  } else {
    form.post(route('editor.announcements.store'), { onSuccess: () => form.reset() })
  }
}

const startEdit = (item) => {
  editing.value    = item.id
  form.title       = item.title
  form.content     = item.content
  form.type        = item.type
  form.is_active   = item.is_active
  form.target_group = item.target_group
  form.scheduled_at = item.scheduled_at ? item.scheduled_at.slice(0, 16) : ''
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const cancelEdit = () => { editing.value = null; form.reset() }

const toggleActive = (item) => router.patch(route('editor.announcements.toggle', item.id))

const deleteItem = (item) => {
  if (confirm(`Delete "${item.title}"?`)) router.delete(route('editor.announcements.destroy', item.id))
}

const typeBadge   = (t) => ({ urgent: 'badge-red', event: 'badge-blue', general: 'badge-gray' }[t] ?? 'badge-gray')
const statusBadge = (s) => ({ draft: 'badge-gray', scheduled: 'badge-yellow', published: 'badge-green' }[s] ?? 'badge-gray')
const truncate    = (str, len) => !str ? '—' : str.length > len ? str.slice(0, len) + '...' : str
const formatDate  = (d) => new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.page-header h2 { font-size: 20px; font-weight: 700; color: #111827; }
.page-header p  { font-size: 14px; color: #6b7280; margin-top: 2px; }
.mb { margin-bottom: 20px; }

.card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
.card-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
.card-header h3 { font-size: 15px; font-weight: 600; color: #111827; }
.badge-count { background: #ede9fe; color: #5b21b6; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.card-body { padding: 20px; }

.form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
.form-group label { font-size: 13px; font-weight: 600; color: #374151; }
.form-group input, .form-group select, .form-group textarea { padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: #111827; outline: none; transition: border 0.15s; font-family: inherit; resize: vertical; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.08); }
.form-error { font-size: 12px; color: #dc2626; }
.optional { color: #9ca3af; font-weight: 400; }

.form-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }

.toggle-wrap { display: flex; align-items: center; gap: 10px; cursor: pointer; margin-top: 6px; }
.toggle-input { display: none; }
.toggle-track { width: 40px; height: 22px; border-radius: 11px; background: #d1d5db; position: relative; transition: background 0.2s; flex-shrink: 0; }
.toggle-track.on { background: #7c3aed; }
.toggle-thumb { width: 16px; height: 16px; border-radius: 50%; background: white; position: absolute; top: 3px; left: 3px; transition: left 0.2s; }
.toggle-track.on .toggle-thumb { left: 21px; }
.toggle-label { font-size: 14px; color: #374151; }

.form-actions { display: flex; gap: 10px; padding-top: 4px; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.15s; }
.btn-primary { background: #7c3aed; color: white; }
.btn-primary:hover { background: #6d28d9; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-outline { background: white; border: 1px solid #e5e7eb; color: #374151; }
.btn-outline:hover { background: #f9fafb; }

.table-wrapper { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead th { text-align: left; padding: 11px 16px; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
tbody td { padding: 13px 16px; font-size: 14px; border-bottom: 1px solid #f3f4f6; color: #111827; vertical-align: middle; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: #fafafa; }
.muted { color: #9ca3af !important; font-size: 13px !important; }
.ann-title { font-size: 14px; font-weight: 500; color: #111827; }
.ann-body  { font-size: 12px; color: #9ca3af; margin-top: 2px; }
.empty-row { text-align: center; padding: 40px !important; color: #9ca3af; font-size: 14px; }

.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; text-transform: capitalize; }
.badge-gray   { background: #f3f4f6; color: #6b7280; }
.badge-red    { background: #fee2e2; color: #dc2626; }
.badge-blue   { background: #dbeafe; color: #1d4ed8; }
.badge-green  { background: #d1fae5; color: #059669; }
.badge-yellow { background: #fef9c3; color: #a16207; }

.action-btns { display: flex; gap: 6px; flex-wrap: wrap; }
.btn-sm { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; border: none; transition: all 0.15s; }
.btn-warn    { background: #fef9c3; color: #a16207; }
.btn-warn:hover { background: #fef08a; }
.btn-success { background: #d1fae5; color: #059669; }
.btn-success:hover { background: #a7f3d0; }
.btn-gray    { background: #f3f4f6; color: #374151; }
.btn-gray:hover { background: #e5e7eb; }
.btn-danger  { background: #fee2e2; color: #dc2626; }
.btn-danger:hover { background: #fecaca; }

.pagination { display: flex; gap: 4px; padding: 12px 16px; border-top: 1px solid #e5e7eb; }
.page-btn { padding: 5px 10px; border-radius: 6px; border: 1px solid #e5e7eb; font-size: 13px; color: #374151; text-decoration: none; transition: all 0.15s; }
.page-btn:hover { background: #f3f4f6; }
.page-btn.active { background: #7c3aed; color: white; border-color: #7c3aed; }
</style>