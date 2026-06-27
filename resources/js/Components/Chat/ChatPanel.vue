<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { MessageCircle, X, ArrowLeft, Send, Plus, Search, Trash2, Paperclip, FileText } from 'lucide-vue-next'

const axios = window.axios

/* ---------------- stato ---------------- */
const open          = ref(false)
const view          = ref('list')        // 'list' | 'thread' | 'new'
const conversations = ref([])
const users         = ref([])
const messages      = ref([])
const active        = ref(null)          // conversazione aperta
const draft         = ref('')
const search        = ref('')
const unreadTotal   = ref(0)
const loading       = ref(false)
const scroller      = ref(null)
const fileInput     = ref(null)
const selectedFiles = ref([])
const sending       = ref(false)

let badgePoll = null
let threadPoll = null

/* ---------------- derivati ---------------- */
const filteredUsers = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return users.value
  return users.value.filter(u =>
    (u.name || '').toLowerCase().includes(q) ||
    (u.email || '').toLowerCase().includes(q)
  )
})

const lastMessageId = computed(() =>
  messages.value.length ? messages.value[messages.value.length - 1].id : 0
)

const canSend = computed(() => !sending.value && (draft.value.trim() || selectedFiles.value.length))

/* ---------------- API ---------------- */
async function fetchUnread () {
  try {
    const { data } = await axios.get('/chat/unread-count')
    unreadTotal.value = data.count ?? 0
  } catch (_) { /* silenzioso */ }
}

async function fetchConversations () {
  try {
    const { data } = await axios.get('/chat/conversations')
    conversations.value = data
  } catch (_) { /* silenzioso */ }
}

async function fetchUsers () {
  try {
    const { data } = await axios.get('/chat/users')
    users.value = data
  } catch (_) { /* silenzioso */ }
}

async function openConversation (conv) {
  active.value = conv
  view.value = 'thread'
  messages.value = []
  loading.value = true
  try {
    const { data } = await axios.get(`/chat/conversations/${conv.id}/messages`)
    messages.value = data
    await markRead(conv.id)
    scrollToBottom()
    startThreadPoll()
  } finally {
    loading.value = false
  }
}

async function pollThread () {
  if (!active.value) return
  try {
    const { data } = await axios.get(
      `/chat/conversations/${active.value.id}/messages`,
      { params: { after: lastMessageId.value } }
    )
  if (data.length && mergeMessages(data)) {
      await markRead(active.value.id)
      scrollToBottom()
    }
  } catch (_) { /* silenzioso */ }
}
/* aggiunge solo i messaggi non già presenti (evita i doppioni del polling) */
function mergeMessages (incoming) {
  const seen = new Set(messages.value.map(m => m.id))
  const fresh = incoming.filter(m => !seen.has(m.id))
  if (fresh.length) messages.value.push(...fresh)
  return fresh.length > 0
}
async function send () {
  if (!canSend.value || !active.value) return
  const body  = draft.value.trim()
  const files = selectedFiles.value.slice()
  draft.value = ''
  selectedFiles.value = []
  sending.value = true
  try {
    let payload
    if (files.length) {
      payload = new FormData()
      if (body) payload.append('body', body)
      files.forEach(f => payload.append('attachments[]', f))
    } else {
      payload = { body }
    }
    const { data } = await axios.post(
      `/chat/conversations/${active.value.id}/messages`,
      payload
    )
 mergeMessages([data])
    scrollToBottom()
  } catch (_) {
    draft.value = body              // ripristina in caso di errore
    selectedFiles.value = files
  } finally {
    sending.value = false
  }
}
async function deleteConversation () {
  if (!active.value) return
  if (!confirm('Eliminare l\'intera conversazione? L\'azione è irreversibile.')) return
  try {
    await axios.delete(`/chat/conversations/${active.value.id}`)
    backToList()
  } catch (_) { /* silenzioso */ }
}
async function deleteMessage (m) {
  if (!confirm('Eliminare questo messaggio?')) return
  try {
    await axios.delete(`/chat/messages/${m.id}`)
    messages.value = messages.value.filter(x => x.id !== m.id)
    fetchConversations()
  } catch (_) { /* silenzioso */ }
}

async function startChatWith (user) {
  try {
    const { data } = await axios.post('/chat/direct', { user_id: user.id })
    await fetchConversations()
    const conv = conversations.value.find(c => c.id === data.id)
      || { id: data.id, title: user.name, type: 'direct' }
    await openConversation(conv)
  } catch (_) { /* silenzioso */ }
}

async function markRead (conversationId) {
  try {
    await axios.post(`/chat/conversations/${conversationId}/read`)
    const c = conversations.value.find(c => c.id === conversationId)
    if (c) c.unread = 0
    fetchUnread()
  } catch (_) { /* silenzioso */ }
}

/* ---------------- allegati ---------------- */
function pickFiles () {
  fileInput.value?.click()
}
function onFilesChosen (e) {
  const files = Array.from(e.target.files || [])
  selectedFiles.value.push(...files)
  e.target.value = ''   // permette di riselezionare lo stesso file
}
function removeFile (i) {
  selectedFiles.value.splice(i, 1)
}
function humanSize (bytes) {
  if (!bytes) return ''
  const u = ['B', 'KB', 'MB', 'GB']
  let n = bytes, i = 0
  while (n >= 1024 && i < u.length - 1) { n /= 1024; i++ }
  return `${n.toFixed(n < 10 && i > 0 ? 1 : 0)} ${u[i]}`
}

/* ---------------- UI ---------------- */
function toggle () {
  open.value = !open.value
  if (open.value) {
    view.value = 'list'
    fetchConversations()
  } else {
    stopThreadPoll()
    active.value = null
  }
}

function backToList () {
  view.value = 'list'
  active.value = null
  selectedFiles.value = []
  stopThreadPoll()
  fetchConversations()
}

function openNew () {
  view.value = 'new'
  search.value = ''
  if (!users.value.length) fetchUsers()
}

function scrollToBottom () {
  nextTick(() => {
    if (scroller.value) scroller.value.scrollTop = scroller.value.scrollHeight
  })
}

function fmtTime (iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

/* ---------------- polling lifecycle ---------------- */
function startThreadPoll () {
  stopThreadPoll()
  threadPoll = setInterval(pollThread, 3000)
}
function stopThreadPoll () {
  if (threadPoll) { clearInterval(threadPoll); threadPoll = null }
}

onMounted(() => {
  fetchUnread()
  badgePoll = setInterval(fetchUnread, 8000)
})

onUnmounted(() => {
  if (badgePoll) clearInterval(badgePoll)
  stopThreadPoll()
})

watch(open, v => { if (!v) stopThreadPoll() })
</script>

<template>
  <div class="fixed top-1 right-5 z-50">

    <!-- Bottone flottante -->
    <button
      v-if="!open"
      @click="toggle"
      class="relative flex h-14 w-14 items-center justify-center rounded-full bg-indigo-600 text-white shadow-lg transition hover:bg-indigo-700"
      aria-label="Apri chat"
    >
      <MessageCircle class="h-6 w-6" />
      <span
        v-if="unreadTotal > 0"
        class="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-xs font-semibold"
      >{{ unreadTotal > 99 ? '99+' : unreadTotal }}</span>
    </button>

    <!-- Pannello -->
    <div
      v-else
      class="flex h-[32rem] max-h-[80vh] w-[22rem] max-w-[92vw] flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl"
    >
      <!-- Header -->
      <div class="flex items-center gap-2 border-b border-gray-100 bg-indigo-600 px-3 py-2.5 text-white">
        <button v-if="view !== 'list'" @click="backToList" class="rounded p-1 hover:bg-white/10">
          <ArrowLeft class="h-5 w-5" />
        </button>
        <span class="flex-1 truncate font-semibold">
          {{ view === 'thread' ? active?.title : view === 'new' ? 'Nuova chat' : 'Messaggi' }}
        </span>
        <button v-if="view === 'list'" @click="openNew" class="rounded p-1 hover:bg-white/10" title="Nuova chat">
          <Plus class="h-5 w-5" />
        </button>
         <button v-if="view === 'thread'" @click="deleteConversation" class="rounded p-1 hover:bg-white/10" title="Elimina conversazione">
          <Trash2 class="h-5 w-5" />
        </button>
        <button @click="toggle" class="rounded p-1 hover:bg-white/10" aria-label="Chiudi">
          <X class="h-5 w-5" />
        </button>
      </div>

      <!-- LISTA CONVERSAZIONI -->
      <div v-if="view === 'list'" class="flex-1 overflow-y-auto">
        <p v-if="!conversations.length" class="p-6 text-center text-sm text-gray-400">
          Nessuna conversazione.<br />Tocca + per iniziarne una.
        </p>
        <button
          v-for="c in conversations"
          :key="c.id"
          @click="openConversation(c)"
          class="flex w-full items-center gap-3 border-b border-gray-50 px-3 py-2.5 text-left hover:bg-gray-50"
        >
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
            {{ (c.title || '?').charAt(0).toUpperCase() }}
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
              <span class="truncate font-medium text-gray-800">{{ c.title }}</span>
              <span class="shrink-0 text-[11px] text-gray-400">{{ fmtTime(c.last_message_at) }}</span>
            </div>
            <span class="block truncate text-xs text-gray-500">
              {{ c.last_message?.body || 'Nessun messaggio' }}
            </span>
          </div>
          <span
            v-if="c.unread > 0"
            class="flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-indigo-600 px-1 text-xs font-semibold text-white"
          >{{ c.unread }}</span>
        </button>
      </div>

      <!-- NUOVA CHAT -->
      <div v-else-if="view === 'new'" class="flex flex-1 flex-col overflow-hidden">
        <div class="flex items-center gap-2 border-b border-gray-100 px-3 py-2">
          <Search class="h-4 w-4 text-gray-400" />
          <input
            v-model="search"
            type="text"
            placeholder="Cerca utente…"
            class="w-full border-0 p-0 text-sm focus:ring-0"
          />
        </div>
        <div class="flex-1 overflow-y-auto">
          <button
            v-for="u in filteredUsers"
            :key="u.id"
            @click="startChatWith(u)"
            class="flex w-full items-center gap-3 border-b border-gray-50 px-3 py-2.5 text-left hover:bg-gray-50"
          >
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600">
              {{ (u.name || '?').charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0">
              <span class="block truncate font-medium text-gray-800">{{ u.name }}</span>
              <span class="block truncate text-xs text-gray-400">{{ u.email }}</span>
            </div>
          </button>
        </div>
      </div>

      <!-- THREAD MESSAGGI -->
      <template v-else>
        <div ref="scroller" class="flex-1 space-y-2 overflow-y-auto bg-gray-50 px-3 py-3">
          <p v-if="loading" class="text-center text-sm text-gray-400">Caricamento…</p>
          <div
            v-for="m in messages"
            :key="m.id"
            class="group flex items-center gap-1.5"
            :class="m.mine ? 'justify-end' : 'justify-start'"
          >
            <!-- cestino (solo sui propri messaggi) -->
            <button
              v-if="m.mine"
              @click="deleteMessage(m)"
              class="shrink-0 text-gray-400 opacity-0 transition hover:text-red-500 group-hover:opacity-60 hover:!opacity-100"
              title="Elimina messaggio"
            >
              <Trash2 class="h-4 w-4" />
            </button>

            <div
              class="max-w-[78%] rounded-2xl px-3 py-1.5 text-sm shadow-sm"
              :class="m.mine ? 'bg-indigo-600 text-white' : 'bg-white text-gray-800'"
            >
              <span v-if="!m.mine && active?.type === 'group'" class="mb-0.5 block text-[11px] font-semibold text-indigo-500">
                {{ m.user_name }}
              </span>

              <span v-if="m.body" class="whitespace-pre-wrap break-words">{{ m.body }}</span>

              <!-- allegati -->
              <div v-if="m.attachments?.length" class="mt-1 space-y-1">
                <template v-for="a in m.attachments" :key="a.id">
                  <!-- immagine: anteprima cliccabile -->
                  <a v-if="a.is_image" :href="a.url" target="_blank" rel="noopener" class="block">
                    <img :src="a.url" :alt="a.name" class="max-h-48 max-w-full rounded-lg" />
                  </a>
                  <!-- documento: link scaricabile -->
                  <a
                    v-else
                    :href="a.url"
                    target="_blank"
                    rel="noopener"
                    class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs"
                    :class="m.mine ? 'bg-white/15 text-white hover:bg-white/25' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                  >
                    <FileText class="h-4 w-4 shrink-0" />
                    <span class="min-w-0 flex-1 truncate">{{ a.name }}</span>
                    <span class="shrink-0 opacity-70">{{ humanSize(a.size) }}</span>
                  </a>
                </template>
              </div>

              <span class="mt-0.5 block text-right text-[10px] opacity-70">{{ fmtTime(m.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Input -->
        <div class="border-t border-gray-100">
          <!-- file selezionati (prima dell'invio) -->
          <div v-if="selectedFiles.length" class="flex flex-wrap gap-1.5 px-2 pt-2">
            <span
              v-for="(f, i) in selectedFiles"
              :key="i"
              class="flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-1 text-xs text-indigo-700"
            >
              <Paperclip class="h-3 w-3" />
              <span class="max-w-[120px] truncate">{{ f.name }}</span>
              <button @click="removeFile(i)" class="text-indigo-400 hover:text-red-500">
                <X class="h-3 w-3" />
              </button>
            </span>
          </div>

          <div class="flex items-end gap-2 p-2">
            <input ref="fileInput" type="file" multiple class="hidden" @change="onFilesChosen" />
            <button
              @click="pickFiles"
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-indigo-600"
              title="Allega file"
            >
              <Paperclip class="h-5 w-5" />
            </button>
            <textarea
              v-model="draft"
              rows="1"
              placeholder="Scrivi un messaggio…"
              class="max-h-28 flex-1 resize-none rounded-lg border-gray-200 text-sm focus:border-indigo-400 focus:ring-indigo-400"
              @keydown.enter.exact.prevent="send"
            />
            <button
              @click="send"
              :disabled="!canSend"
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white transition hover:bg-indigo-700 disabled:opacity-40"
            >
              <Send class="h-4 w-4" />
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
