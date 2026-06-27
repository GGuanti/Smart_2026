<template>
  <div class="sg-wrap">

    <!-- HEADER (opzionale — nascondi con :show-header="false") -->
    <div v-if="showHeader" class="sg-header">
      <div class="sg-left">
        <span class="sg-logo">Smart<span>Grid</span></span>
        <span class="sg-query-name">
          <span class="sq-dot" />
          {{ queryName }}
        </span>
        <span class="sg-count">{{ filtered.length }} righe</span>
      </div>
      <div class="sg-right">
        <!-- Salva layout -->
        <button class="btn-save" :class="{ dirty: layoutDirty, saved: saveStatus==='ok' }"
          :disabled="!layoutDirty" @click="handleSave">
          {{ saveStatus==='saving' ? '⏳ Salvo...' : saveStatus==='ok' ? '✓ Salvato!' : '💾 Salva layout' }}
        </button>

        <!-- Reset -->
        <button class="btn-ghost" @click="handleReset">↺ Reset</button>

        <!-- Colonne -->
        <div class="dropdown-wrap" ref="colMenuRef">
          <button class="btn-ghost" @click="showColMenu = !showColMenu">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Colonne
          </button>
          <div v-if="showColMenu" class="dropdown">
            <div class="dropdown-head">Layout: <strong>{{ queryName }}</strong></div>
            <label v-for="col in cols" :key="col.key" class="col-toggle">
              <input type="checkbox" :checked="col.visible" @change="toggleCol(col.key)" />
              {{ col.label }}
            </label>
          </div>
        </div>

        <!-- Excel -->
        <button class="btn-excel" @click="exportExcel">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
          Excel
        </button>
      </div>
    </div>

    <!-- DIRTY BAR -->
    <div v-if="layoutDirty && showHeader" class="dirty-bar">
      ⚠ Layout modificato — clicca <strong>Salva layout</strong> per salvare la posizione delle colonne per <strong>{{ queryName }}</strong>
    </div>

    <!-- TABLE -->
    <div class="sg-table-wrap">
      <table class="sg-table" :style="{ minWidth: totalWidth + 'px' }">
        <thead>
          <!-- Headers -->
          <tr class="head-row">
            <th v-for="(col, ci) in visibleCols" :key="col.key"
              class="th"
              :class="{ 'drag-over': dragOverIdx===ci, dragging: draggingIdx===ci }"
              :style="{ width: col.width + 'px', minWidth: col.width + 'px' }"
              draggable="true"
              @dragstart="onDragStart(ci)"
              @dragover.prevent="onDragOver(ci)"
              @drop="onDrop(ci)"
              @dragend="onDragEnd"
            >
              <div class="th-inner">
                <span class="drag-handle">⠿</span>
                <span class="th-label" :class="{ sorted: sortKey===col.key }" @click="setSort(col.key)">
                  {{ col.label }}
                  <span v-if="sortKey===col.key" class="sort-arr">{{ sortDir==='asc'?'↑':'↓' }}</span>
                </span>
              </div>
              <div class="resize-handle" @mousedown="startResize(col.key, $event)" />
            </th>
          </tr>
          <!-- Filters -->
          <tr class="filter-row">
            <th v-for="col in visibleCols" :key="'f'+col.key"
              class="filter-th"
              :style="{ width: col.width + 'px', minWidth: col.width + 'px' }"
            >
              <div class="filter-cell">
                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#252d3d" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <input v-model="filters[col.key]" :placeholder="col.label" class="filter-input" @input="page=1" />
                <button v-if="filters[col.key]" class="filter-clear" @click="filters[col.key]=''">×</button>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, ri) in paginated" :key="ri" class="data-row" :class="{ even: ri%2===0 }">
            <td v-for="col in visibleCols" :key="col.key"
              class="td"
              :style="{ width: col.width + 'px', minWidth: col.width + 'px' }"
            >
              <slot :name="'cell-' + col.key" :value="row[col.key]" :row="row">
                {{ row[col.key] }}
              </slot>
            </td>
          </tr>
          <tr v-if="paginated.length===0">
            <td :colspan="visibleCols.length" class="empty-td">Nessun risultato trovato</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- FOOTER -->
    <div class="sg-footer">
      <div class="footer-left">
        <span class="page-info">
          {{ filtered.length ? `${(page-1)*perPage+1}–${Math.min(page*perPage, filtered.length)} di ${filtered.length}` : '0 risultati' }}
        </span>
        <span v-if="layoutSaved" class="saved-badge">● Layout salvato</span>
      </div>
      <div class="footer-right">
        <select v-model="perPage" class="per-page" @change="page=1">
          <option :value="10">10 / pag.</option>
          <option :value="25">25 / pag.</option>
          <option :value="50">50 / pag.</option>
        </select>
        <button class="page-btn" :disabled="page===1" @click="page=1">«</button>
        <button class="page-btn" :disabled="page===1" @click="page--">‹</button>
        <span v-for="p in pageNums" :key="p"
          class="page-num" :class="{ active: p===page, ellipsis: p==='...' }"
          @click="typeof p==='number' && (page=p)">{{ p }}</span>
        <button class="page-btn" :disabled="page===totalPages" @click="page++">›</button>
        <button class="page-btn" :disabled="page===totalPages" @click="page=totalPages">»</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import * as XLSX from 'xlsx'

// ── Props ──────────────────────────────────────────────────────
const props = defineProps({
  queryName:     { type: String,  required: true          },
  rows:          { type: Array,   default: () => []       },
  savedLayout:   { type: Array,   default: null           },
  columnLabels:  { type: Object,  default: () => ({})     }, // label custom per field key
  showHeader:    { type: Boolean, default: true           }, // nascondi header interno se hai un tuo
})
const emit = defineEmits(['layout-saved'])

// ── State ──────────────────────────────────────────────────────
const cols        = ref([])
const filters     = reactive({})
const sortKey     = ref(null)
const sortDir     = ref('asc')
const page        = ref(1)
const perPage     = ref(10)
const showColMenu = ref(false)
const draggingIdx = ref(null)
const dragOverIdx = ref(null)
const saveStatus  = ref(null)   // null | 'saving' | 'ok' | 'err'
const layoutDirty = ref(false)
const layoutSaved = ref(!!props.savedLayout)
const colMenuRef  = ref(null)

// ── Build columns ──────────────────────────────────────────────
function buildColumns(rows, saved) {
  if (!rows?.length) return []
  const keys = Object.keys(rows[0])
  const savedMap = saved ? Object.fromEntries(saved.map(c => [c.key, c])) : {}

  const autoLabel = (key) =>
    props.columnLabels?.[key]           // 1. label custom passata dall'esterno
    ?? key.replace(/^_/, '')           // 2. rimuove underscore iniziale
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase())

  const result = keys.map((key, idx) => {
    const s = savedMap[key]
    return {
      key,
      label:   s?.label   ?? autoLabel(key),
      width:   s?.width   ?? Math.max(90, Math.min(220, key.length * 13 + 50)),
      visible: s?.visible ?? true,
      order:   s?.order   ?? idx,
    }
  })
  return result.sort((a, b) => a.order - b.order)
}

onMounted(() => {
  cols.value = buildColumns(props.rows, props.savedLayout)
  props.rows.forEach((_, i) => {}) // init filters
  Object.keys(props.rows[0] ?? {}).forEach(k => filters[k] = '')
})

// ── Computed ──────────────────────────────────────────────────
const visibleCols = computed(() => cols.value.filter(c => c.visible))
const totalWidth  = computed(() => visibleCols.value.reduce((s, c) => s + c.width, 0))

const filtered = computed(() => {
  return props.rows.filter(row =>
    cols.value.every(col => {
      const f = filters[col.key]
      return !f || String(row[col.key] ?? '').toLowerCase().includes(f.toLowerCase())
    })
  ).sort((a, b) => {
    if (!sortKey.value) return 0
    const va = String(a[sortKey.value] ?? ''), vb = String(b[sortKey.value] ?? '')
    return sortDir.value === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va)
  })
})

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage.value)))
const paginated  = computed(() => {
  const p = Math.min(page.value, totalPages.value)
  return filtered.value.slice((p - 1) * perPage.value, p * perPage.value)
})

const pageNums = computed(() => {
  const t = totalPages.value, c = page.value, p = []
  if (t <= 7) { for (let i = 1; i <= t; i++) p.push(i); return p }
  p.push(1); if (c > 3) p.push('...')
  for (let i = Math.max(2, c-1); i <= Math.min(t-1, c+1); i++) p.push(i)
  if (c < t - 2) p.push('...'); p.push(t)
  return p
})

const layoutPayload = computed(() =>
  cols.value.map((c, idx) => ({ key: c.key, label: c.label, width: c.width, visible: c.visible, order: idx }))
)

// ── Methods ──────────────────────────────────────────────────
const markDirty = () => { layoutDirty.value = true; saveStatus.value = null }

const toggleCol = key => { const c = cols.value.find(c => c.key === key); if (c) { c.visible = !c.visible; markDirty() } }

const setSort = key => {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'asc' }
}

// Resize
let resizing = null
const startResize = (key, e) => {
  const col = cols.value.find(c => c.key === key)
  resizing = { key, startX: e.clientX, startW: col?.width ?? 100 }
  document.addEventListener('mousemove', onResize)
  document.addEventListener('mouseup', stopResize)
  e.preventDefault()
}
const onResize = e => {
  if (!resizing) return
  const col = cols.value.find(c => c.key === resizing.key)
  if (col) { col.width = Math.max(60, resizing.startW + (e.clientX - resizing.startX)); markDirty() }
}
const stopResize = () => { resizing = null; document.removeEventListener('mousemove', onResize); document.removeEventListener('mouseup', stopResize) }

// Drag & drop
const onDragStart = i => { draggingIdx.value = i }
const onDragOver  = i => { dragOverIdx.value = i }
const onDrop      = targetIdx => {
  if (draggingIdx.value === null || draggingIdx.value === targetIdx) return
  const vis  = visibleCols.value
  const srcK = vis[draggingIdx.value].key, tgtK = vis[targetIdx].key
  const next = [...cols.value]
  const si   = next.findIndex(c => c.key === srcK)
  const ti   = next.findIndex(c => c.key === tgtK)
  const [m]  = next.splice(si, 1); next.splice(ti, 0, m)
  cols.value = next; draggingIdx.value = null; dragOverIdx.value = null; markDirty()
}
const onDragEnd = () => { draggingIdx.value = null; dragOverIdx.value = null }

// Save layout — POST a Laravel
const handleSave = () => {
  saveStatus.value = 'saving'
  router.post(route('grid-layouts.store'), {
    query_name: props.queryName,
    layout:     layoutPayload.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      saveStatus.value = 'ok'
      layoutDirty.value = false
      layoutSaved.value = true
      emit('layout-saved', { queryName: props.queryName, layout: layoutPayload.value })
      setTimeout(() => saveStatus.value = null, 2500)
    },
    onError: () => { saveStatus.value = 'err' },
  })
}

const handleReset = () => {
  cols.value = buildColumns(props.rows, null)
  Object.keys(filters).forEach(k => filters[k] = '')
  sortKey.value = null; page.value = 1; markDirty()
}

// Excel export
const exportExcel = () => {
  const header = visibleCols.value.map(c => c.label)
  const data   = filtered.value.map(r => visibleCols.value.map(c => r[c.key] ?? ''))
  const ws = XLSX.utils.aoa_to_sheet([header, ...data])
  ws['!cols'] = visibleCols.value.map(c => ({ wch: Math.max(10, Math.floor(c.width / 7)) }))
  const wb = XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb, ws, props.queryName)
  XLSX.writeFile(wb, `${props.queryName}_${new Date().toISOString().slice(0,10)}.xlsx`)
}

// Click outside
const onClickOut = e => { if (colMenuRef.value && !colMenuRef.value.contains(e.target)) showColMenu.value = false }
onMounted(()    => document.addEventListener('click', onClickOut))
onBeforeUnmount(() => document.removeEventListener('click', onClickOut))
</script>

<style scoped>
.sg-wrap { display:flex; flex-direction:column; height:100%; background:#080b14; font-family:'DM Sans','Helvetica Neue',sans-serif; color:#c9d1d9; border-radius:12px; overflow:hidden; border:1px solid #1c2333; }

/* Header */
.sg-header { background:#0d1117; border-bottom:1px solid #1c2333; padding:0 16px; display:flex; align-items:stretch; justify-content:space-between; flex-shrink:0; min-height:48px; }
.sg-left, .sg-right { display:flex; align-items:center; gap:10px; }
.sg-logo { font-weight:800; font-size:15px; color:#c9d1d9; letter-spacing:-0.3px; }
.sg-logo span { color:#58a6ff; }
.sg-query-name { display:flex; align-items:center; gap:6px; background:#161b22; border:1px solid #30363d; border-radius:20px; padding:3px 10px; font-size:12px; color:#8b949e; }
.sq-dot { width:6px; height:6px; border-radius:50%; background:#3fb950; }
.sg-count { font-size:11px; color:#6e7681; background:#1c2333; border-radius:20px; padding:2px 9px; }

/* Buttons */
.btn-save { display:flex; align-items:center; gap:6px; padding:6px 14px; border-radius:7px; border:1px solid #1f6feb44; background:#0d1f3c; color:#8b949e; font-size:12px; font-weight:600; cursor:pointer; transition:all .2s; font-family:inherit; }
.btn-save.dirty  { background:#1f6feb; border-color:#1f6feb; color:#fff; }
.btn-save.saved  { background:#0d2d1a; border-color:#3fb95044; color:#3fb950; }
.btn-save:disabled:not(.saved) { opacity:0.4; cursor:default; }
.btn-ghost { display:flex; align-items:center; gap:6px; padding:6px 12px; border-radius:7px; border:1px solid #30363d; background:#161b22; color:#8b949e; font-size:12px; cursor:pointer; transition:all .15s; font-family:inherit; }
.btn-ghost:hover { background:#1c2333; color:#c9d1d9; }
.btn-excel { display:flex; align-items:center; gap:6px; padding:6px 12px; border-radius:7px; border:1px solid #3fb95044; background:#0d2d1a; color:#3fb950; font-size:12px; font-weight:600; cursor:pointer; font-family:inherit; }

/* Dirty bar */
.dirty-bar { background:#161009; border-bottom:1px solid #d2992244; padding:5px 16px; font-size:12px; color:#d29922; flex-shrink:0; }

/* Dropdown */
.dropdown-wrap { position:relative; }
.dropdown { position:absolute; top:calc(100% + 6px); right:0; z-index:200; background:#161b22; border:1px solid #30363d; border-radius:10px; padding:8px; min-width:200px; box-shadow:0 12px 40px #00000088; }
.dropdown-head { font-size:10px; font-weight:700; color:#6e7681; text-transform:uppercase; letter-spacing:.8px; padding:4px 8px 10px; }
.col-toggle { display:flex; align-items:center; gap:9px; padding:7px 10px; border-radius:7px; cursor:pointer; font-size:13px; color:#8b949e; }
.col-toggle:hover { background:#1c2333; color:#c9d1d9; }
.col-toggle input { accent-color:#58a6ff; cursor:pointer; width:14px; height:14px; }

/* Table */
.sg-table-wrap { flex:1; overflow:auto; }
.sg-table { border-collapse:collapse; width:100%; table-layout:fixed; }

.head-row { position:sticky; top:0; z-index:10; background:#0d1117; }
.th { padding:0; border-right:1px solid #1c2333; border-bottom:1px solid #1c2333; position:relative; user-select:none; transition:background .1s; }
.th:last-child { border-right:none; }
.th.drag-over  { background:#58a6ff11; }
.th.dragging   { opacity:.4; }
.th-inner { display:flex; align-items:center; gap:6px; padding:10px 12px; }
.drag-handle { color:#252d3d; font-size:13px; cursor:grab; }
.th-label { font-size:11px; font-weight:600; color:#6e7681; text-transform:uppercase; letter-spacing:.6px; cursor:pointer; display:flex; align-items:center; gap:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.th-label.sorted { color:#58a6ff; }
.sort-arr { color:#58a6ff; font-size:10px; }
.resize-handle { position:absolute; top:0; right:-3px; width:6px; height:100%; cursor:col-resize; z-index:5; }
.resize-handle:hover { background:#58a6ff; border-radius:3px; }

.filter-row { position:sticky; top:41px; z-index:9; background:#0a0f16; }
.filter-th { padding:0; border-right:1px solid #1c2333; border-bottom:2px solid #1c2333; }
.filter-th:last-child { border-right:none; }
.filter-cell { display:flex; align-items:center; gap:4px; padding:5px 8px; }
.filter-input { flex:1; background:transparent; border:none; color:#c9d1d9; font-size:12px; outline:none; min-width:0; font-family:inherit; }
.filter-input::placeholder { color:#252d3d; }
.filter-clear { background:none; border:none; color:#6e7681; cursor:pointer; font-size:14px; line-height:1; padding:0; }

.data-row { border-bottom:1px solid #0d1117; transition:background .08s; }
.data-row.even { background:#080b14; }
.data-row:hover { background:#0d1a2a !important; }
.td { padding:9px 12px; border-right:1px solid #0d1117; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; font-size:13px; color:#8b949e; }
.td:last-child { border-right:none; }
.empty-td { text-align:center; padding:48px; color:#6e7681; font-size:14px; }

/* Footer */
.sg-footer { background:#0d1117; border-top:1px solid #1c2333; padding:10px 16px; display:flex; justify-content:space-between; align-items:center; flex-shrink:0; }
.footer-left, .footer-right { display:flex; align-items:center; gap:8px; }
.page-info { font-size:12px; color:#6e7681; }
.saved-badge { font-size:11px; color:#3fb950; display:flex; align-items:center; gap:4px; }
.per-page { background:#1c2333; border:1px solid #30363d; border-radius:6px; color:#8b949e; font-size:12px; padding:4px 8px; cursor:pointer; font-family:inherit; }
.page-btn { width:30px; height:30px; background:transparent; border:1px solid #30363d; border-radius:6px; color:#8b949e; font-size:13px; cursor:pointer; display:flex; align-items:center; justify-content:center; }
.page-btn:disabled { opacity:.3; cursor:default; }
.page-num { width:30px; height:30px; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:13px; cursor:pointer; color:#6e7681; }
.page-num.active { background:#1f6feb; color:#fff; font-weight:700; }
.page-num.ellipsis { cursor:default; }
</style>
