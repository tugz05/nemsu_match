<template>
  <EditorLayout title="Social Feed">

    <div class="page-header">
      <div>
        <h2>Social Feed</h2>
        <p>Moderate and manage user posts</p>
      </div>
      <div class="total-badge">{{ posts.total }} Total Posts</div>
    </div>

    <div class="card">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Content</th>
              <th>Likes</th>
              <th>Comments</th>
              <th>Posted</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(post, i) in posts.data" :key="post.id">
              <td class="muted">{{ i + 1 }}</td>
              <td>
                <div class="user-cell">
                  <div class="user-ava">
                    <img v-if="post.user?.profile_picture" :src="post.user.profile_picture" />
                    <span v-else>{{ initials(post.user?.display_name) }}</span>
                  </div>
                  <div>
                    <div class="uname">{{ post.user?.display_name ?? 'Unknown' }}</div>
                    <div class="uemail">{{ post.user?.email ?? '' }}</div>
                  </div>
                </div>
              </td>
              <td><div class="post-content">{{ truncate(post.content, 80) }}</div></td>
              <td><span class="count pink">❤ {{ post.likes_count ?? 0 }}</span></td>
              <td><span class="count blue">💬 {{ post.comments_count ?? 0 }}</span></td>
              <td class="muted">{{ formatDate(post.created_at) }}</td>
              <td>
                <button @click="confirmDelete(post)" class="btn-del">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                  Remove
                </button>
              </td>
            </tr>
            <tr v-if="!posts.data.length">
              <td colspan="7" class="empty-row">
                <div class="empty-state">No posts in the feed yet.</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DELETE MODAL -->
    <Teleport to="body">
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
        <div class="modal">
          <div class="modal-header">
            <h3>Remove Post</h3>
            <button @click="showDeleteModal = false" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
          </div>
          <div class="modal-body">
            <p class="confirm-text">Remove this post by <strong>{{ deleteTarget?.user?.display_name }}</strong>? This cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button @click="showDeleteModal = false" class="btn btn-outline">Cancel</button>
            <button @click="doDelete" class="btn btn-danger">Remove Post</button>
          </div>
        </div>
      </div>
    </Teleport>

  </EditorLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import EditorLayout from './Layout/EditorLayout.vue'

const props = defineProps({ posts: Object })
const showDeleteModal = ref(false)
const deleteTarget = ref(null)

const confirmDelete = (post) => { deleteTarget.value = post; showDeleteModal.value = true }
const doDelete = () => {
router.delete(`/editor/social-feed/${deleteTarget.value.id}`, {
    onSuccess: () => { showDeleteModal.value = false }
  })
}
const initials = (name) => name ? name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) : 'U'
const truncate = (str, len) => !str ? '-' : str.length > len ? str.slice(0, len) + '...' : str
const formatDate = (d) => new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.page-header h2 { font-size: 20px; font-weight: 700; color: #111827; }
.page-header p  { font-size: 14px; color: #6b7280; margin-top: 2px; }
.total-badge { background: #ede9fe; color: #5b21b6; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }

.card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
.table-wrapper { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead th { text-align: left; padding: 11px 16px; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
tbody td { padding: 13px 16px; font-size: 14px; border-bottom: 1px solid #f3f4f6; color: #111827; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: #fafafa; }
.muted { color: #9ca3af !important; font-size: 13px !important; }

.user-cell { display: flex; align-items: center; gap: 10px; }
.user-ava { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #a855f7); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 12px; overflow: hidden; flex-shrink: 0; }
.user-ava img { width: 100%; height: 100%; object-fit: cover; }
.uname  { font-size: 14px; font-weight: 500; }
.uemail { font-size: 12px; color: #9ca3af; }
.post-content { font-size: 13px; color: #374151; max-width: 280px; }
.count { font-size: 12px; font-weight: 500; padding: 3px 8px; border-radius: 6px; }
.count.pink { background: #fce7f3; color: #9d174d; }
.count.blue { background: #dbeafe; color: #1d4ed8; }

.btn-del { display: inline-flex; align-items: center; gap: 5px; background: #fee2e2; color: #dc2626; border: none; border-radius: 6px; padding: 6px 12px; font-size: 12px; font-weight: 500; cursor: pointer; transition: background 0.15s; }
.btn-del svg { width: 14px; height: 14px; }
.btn-del:hover { background: #fecaca; }

.empty-row { text-align: center; padding: 40px !important; }
.empty-state { color: #9ca3af; font-size: 14px; }

/* MODAL */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal { background: white; border-radius: 16px; width: 100%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); animation: slideUp 0.2s ease; }
@keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; }
.modal-header h3 { font-size: 16px; font-weight: 600; }
.modal-close { background: none; border: none; cursor: pointer; color: #6b7280; border-radius: 6px; padding: 4px; display: flex; }
.modal-close svg { width: 18px; height: 18px; }
.modal-body { padding: 24px; }
.modal-footer { display: flex; gap: 10px; justify-content: flex-end; padding: 16px 24px; border-top: 1px solid #e5e7eb; }
.confirm-text { color: #6b7280; font-size: 14px; }
.confirm-text strong { color: #111827; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.15s; }
.btn-outline { background: white; border: 1px solid #e5e7eb; color: #374151; }
.btn-outline:hover { background: #f9fafb; }
.btn-danger  { background: #dc2626; color: white; }
.btn-danger:hover { background: #b91c1c; }
</style>