<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import AdminSidebar from './adminSidebar/AdminSidebar.vue';
import { Megaphone, Plus, Trash2, Edit2, Pin } from 'lucide-vue-next';

const page = usePage();
const currentRoute = 'admin.announcements';

type Announcement = {
  id: number;
  title: string;
  body: string;
  is_pinned: boolean;
  published_at: string | null;
  created_at: string | null;
  creator?: null | { id: number; display_name: string };
};

const announcements = computed<Announcement[]>(() => (page.props as any).announcements?.data ?? []);
const pagination = computed(() => (page.props as any).announcements ?? {});

const showCreate = ref(false);
const showEdit = ref<Announcement | null>(null);

const createForm = useForm({
  title: '',
  body: '',
  is_pinned: false,
  publish_now: true,
});

function submitCreate() {
  createForm.post('/admin/announcements', {
    onSuccess: () => {
      showCreate.value = false;
      createForm.reset();
      createForm.clearErrors();
    },
    onError: () => {
      console.error('Announcement create validation failed', createForm.errors);
    },
  });
}

const editForm = useForm({
  title: '',
  body: '',
  is_pinned: false,
  publish_now: true,
});

function openEdit(a: Announcement) {
  showEdit.value = a;
  editForm.title = a.title;
  editForm.body = a.body;
  editForm.is_pinned = a.is_pinned;
  editForm.publish_now = Boolean(a.published_at);
}

function submitEdit() {
  if (!showEdit.value) return;
  editForm.put(`/admin/announcements/${showEdit.value.id}`, {
    onSuccess: () => {
      showEdit.value = null;
      editForm.clearErrors();
    },
    onError: () => {
      console.error('Announcement update validation failed', editForm.errors);
    },
  });
}

function deleteAnnouncement(id: number) {
  if (!confirm('Delete this announcement?')) return;
  // Use a small useForm instance for delete
  const del = useForm({});
  del.delete(`/admin/announcements/${id}`);
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex">
    <AdminSidebar :current-route="currentRoute" />
    <div class="flex-1">
      <Head title="Admin • Announcements" />
      <header class="bg-white border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Megaphone class="w-6 h-6 text-blue-600" />
            <h1 class="text-lg font-bold text-gray-900">Announcements</h1>
          </div>
          <button
            type="button"
            @click="showCreate = true"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-semibold text-sm hover:from-blue-700 hover:to-cyan-600"
          >
            <Plus class="w-4 h-4" />
            New Announcement
          </button>
        </div>
      </header>

      <main class="max-w-5xl mx-auto p-6 space-y-4">
        <div v-if="announcements.length === 0" class="bg-white rounded-xl border border-gray-100 p-8 text-center">
          <p class="text-gray-600">No announcements yet.</p>
        </div>

        <div
          v-for="a in announcements"
          :key="a.id"
          class="bg-white rounded-xl border border-gray-100 overflow-hidden"
        >
          <div class="p-5">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <Pin v-if="a.is_pinned" class="w-4 h-4 text-amber-500" />
                  <h2 class="text-lg font-semibold text-gray-900 truncate">{{ a.title }}</h2>
                </div>
                <p class="text-sm text-gray-600 mt-1 whitespace-pre-line">{{ a.body }}</p>
                <p class="text-xs text-gray-400 mt-2">
                  {{ a.published_at ? 'Published' : 'Draft' }}
                </p>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-sm text-gray-700 hover:bg-gray-50"
                  @click="openEdit(a)"
                >
                  <Edit2 class="w-4 h-4" /> Edit
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-sm text-red-600 hover:bg-red-50"
                  @click="deleteAnnouncement(a.id)"
                >
                  <Trash2 class="w-4 h-4" /> Delete
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="pagination.next_page_url" class="flex justify-center pt-2">
          <Link
            :href="pagination.next_page_url"
            preserve-state
            preserve-scroll
            class="px-4 py-2 rounded-xl bg-white border border-gray-200 text-gray-700 font-semibold text-sm hover:bg-gray-50"
          >
            Next Page
          </Link>
        </div>
      </main>
    </div>
  </div>

  <!-- Create Modal -->
  <div
    v-if="showCreate"
    class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4"
    @click.self="showCreate = false"
  >
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
      <div class="p-4 border-b font-semibold">New Announcement</div>
      <div class="p-4 space-y-3">
        <div>
          <input v-model="createForm.title" type="text" placeholder="Title" class="w-full rounded-lg border px-3 py-2" />
          <p v-if="createForm.errors.title" class="mt-1 text-xs text-red-600">
            {{ createForm.errors.title }}
          </p>
        </div>
        <div>
          <textarea v-model="createForm.body" rows="5" placeholder="Body" class="w-full rounded-lg border px-3 py-2" />
          <p v-if="createForm.errors.body" class="mt-1 text-xs text-red-600">
            {{ createForm.errors.body }}
          </p>
        </div>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="createForm.is_pinned" type="checkbox" />
          Pin this announcement
        </label>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="createForm.publish_now" type="checkbox" />
          Publish now
        </label>
      </div>
      <div class="p-4 border-t flex justify-end gap-2">
        <button type="button" class="px-4 py-2 rounded-lg bg-gray-100" @click="showCreate = false">Cancel</button>
        <button
          type="button"
          class="px-4 py-2 rounded-lg bg-blue-600 text-white"
          :disabled="createForm.processing"
          @click="submitCreate"
        >
          {{ createForm.processing ? 'Saving…' : 'Create' }}
        </button>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <div
    v-if="showEdit"
    class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4"
    @click.self="showEdit = null"
  >
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
      <div class="p-4 border-b font-semibold">Edit Announcement</div>
      <div class="p-4 space-y-3">
        <div>
          <input v-model="editForm.title" type="text" placeholder="Title" class="w-full rounded-lg border px-3 py-2" />
          <p v-if="editForm.errors.title" class="mt-1 text-xs text-red-600">
            {{ editForm.errors.title }}
          </p>
        </div>
        <div>
          <textarea v-model="editForm.body" rows="5" placeholder="Body" class="w-full rounded-lg border px-3 py-2" />
          <p v-if="editForm.errors.body" class="mt-1 text-xs text-red-600">
            {{ editForm.errors.body }}
          </p>
        </div>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="editForm.is_pinned" type="checkbox" />
          Pin this announcement
        </label>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="editForm.publish_now" type="checkbox" />
          Publish now
        </label>
      </div>
      <div class="p-4 border-t flex justify-end gap-2">
        <button type="button" class="px-4 py-2 rounded-lg bg-gray-100" @click="showEdit = null">Cancel</button>
        <button
          type="button"
          class="px-4 py-2 rounded-lg bg-blue-600 text-white"
          :disabled="editForm.processing"
          @click="submitEdit"
        >
          {{ editForm.processing ? 'Saving…' : 'Save' }}
        </button>
      </div>
    </div>
  </div>
</template>
