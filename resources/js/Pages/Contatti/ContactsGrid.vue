<template>
  <div class="grid-wrapper">

    <!-- ── TOOLBAR ─────────────────────────────────────────── -->
    <div class="toolbar">
      <div class="toolbar-left">
        <span class="grid-title">Contatti</span>
        <span class="row-count">{{ filteredRows.length }} risultati</span>
      </div>
      <div class="toolbar-right">
        <!-- Colonne visibili -->
        <div class="dropdown-wrap" ref="colMenuRef">
          <button class="btn-tool" @click="showColMenu = !showColMenu">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Colonne
          </button>
          <div v-if="showColMenu" class="dropdown">
            <div class="dropdown-title">Mostra / Nascondi</div>
            <label v-for="col in allColumns" :key="col.key" class="col-toggle">
              <input type="checkbox" :checked="col.visible" @change="toggleCol(col.key)" />
              {{ col.label }}
            </label>
          </div>
        </div>

        <!-- Reset layout -->
        <button class="btn-tool" @click="resetLayout">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.21"/></svg>
          Reset
        </button>

        <!-- Export Excel -->
        <button class="btn-export" @click="exportExcel">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          Esporta Excel
        </button>
      </div>
    </div>

    <!-- ── GRIGLIA ─────────────────────────────────────────── -->
    <div class="table-container" ref="tableContainer">
      <table class="data-table" :style="{ minWidth: totalWidth + 'px' }">
        <thead>
          <!-- Intestazioni -->
          <tr class="header-row">
            <th
              v-for="(col, colIdx) in visibleColumns"
              :key="col.key"
              class="th"
              :style="{ width: col.width + 'px', minWidth: col.width + 'px' }"
              :class="{ 'drag-over': dragOverCol === colIdx, 'dragging': draggingCol === colIdx }"
              draggable="true"
              @dragstart="onDragStart(colIdx, $event)"
              @dragover.prevent="onDragOver(colIdx)"
              @drop="onDrop(colIdx)"
              @dragend="onDragEnd"
            >
              <div class="th-inner">
                <span class="th-drag-icon">⠿</span>
                <span class="th-label" @click="setSort(col.key)">
                  {{ col.label }}
                  <span v-if="sortKey === col.key" class="sort-icon">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                </span>
              </div>
              <!-- Resize handle -->
              <div
                class="resize-handle"
                @mousedown="startResize(col.key, $event)"
              />
            </th>
          </tr>

          <!-- Filtri -->
          <tr class="filter-row">
            <th
              v-for="col in visibleColumns"
              :key="'f-' + col.key"
              class="filter-th"
              :style="{ width: col.width + 'px', minWidth: col.width + 'px' }"
            >
              <div class="filter-cell">
                <svg class="filter-icon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <input
                  v-model="filters[col.key]"
                  class="filter-input"
                  :placeholder="col.label"
                  @input="currentPage = 1"
                />
                <button v-if="filters[col.key]" class="filter-clear" @click="filters[col.key] = ''">×</button>
              </div>
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="(row, idx) in paginatedRows"
            :key="row.id"
            class="data-row"
            :class="{ 'row-even': idx % 2 === 0 }"
          >
            <td
              v-for="col in visibleColumns"
              :key="col.key"
              class="td"
              :style="{ width: col.width + 'px', minWidth: col.width + 'px' }"
            >
              <!-- Celle speciali -->
              <template v-if="col.key === 'status'">
                <span class="status-pill" :class="'status-' + row.status">{{ row.status }}</span>
              </template>
              <template v-else-if="col.key === 'value'">
                <span class="value-cell">{{ row.value }}</span>
              </template>
              <template v-else-if="col.key === 'name'">
                <div class="name-cell">
                  <div class="avatar">{{ row.name.split(' ').map(n => n[0]).join('').slice(0,2) }}</div>
                  {{ row.name }}
                </div>
              </template>
              <template v-else>
                <span class="cell-text">{{ row[col.key] }}</span>
              </template>
            </td>
          </tr>

          <tr v-if="paginatedRows.length === 0">
            <td :colspan="visibleColumns.length" class="empty-row">
              Nessun risultato trovato
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ── PAGINAZIONE ────────────────────────────────────── -->
    <div class="pagination">
      <div class="pagination-info">
        Mostra {{ paginationInfo.from }}–{{ paginationInfo.to }} di {{ filteredRows.length }}
      </div>
      <div class="pagination-controls">
        <select v-model="perPage" class="per-page-select" @change="currentPage = 1">
          <option :value="10">10 / pag.</option>
          <option :value="25">25 / pag.</option>
          <option :value="50">50 / pag.</option>
        </select>
        <button class="page-btn" :disabled="currentPage === 1" @click="currentPage = 1">«</button>
        <button class="page-btn" :disabled="currentPage === 1" @click="currentPage--">‹</button>
        <span
          v-for="p in visiblePages"
          :key="p"
          class="page-num"
          :class="{ active: p === currentPage, ellipsis: p === '...' }"
          @click="typeof p === 'number' && (currentPage = p)"
        >{{ p }}</span>
        <button class="page-btn" :disabled="currentPage === totalPages" @click="currentPage++">›</button>
        <button class="page-btn" :disabled="currentPage === totalPages" @click="currentPage = totalPages">»</button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, onBeforeUnmount } from 'vue'
import * as XLSX from 'xlsx'   // npm install xlsx

// ─── PROPS ───────────────────────────────────────────────────
const props = defineProps({
  contacts: { type: Array, default: () => [] }
})

// ─── COLONNE ─────────────────────────────────────────────────
const allColumns = reactive([
  { key: 'id',      label: 'ID',       width: 70,  visible: true  },
  { key: 'name',    label: 'Nome',     width: 200, visible: true  },
  { key: 'company', label: 'Azienda',  width: 180, visible: true  },
  { key: 'email',   label: 'Email',    width: 220, visible: true  },
  { key: 'phone',   label: 'Telefono', width: 140, visible: true  },
  { key: 'status',  label: 'Status',   width: 120, visible: true  },
  { key: 'city',    label: 'Città',    width: 130, visible: true  },
  { key: 'value',   label: 'Valore',   width: 120, visible: true  },
  { key: 'source',  label: 'Fonte',    width: 120, visible: false },
  { key: 'created', label: 'Creato il',width: 130, visible: false },
])

const visibleColumns = computed(() => allColumns.filter(c => c.visible))
const totalWidth     = computed(() => visibleColumns.value.reduce((s, c) => s + c.width, 0))

const toggleCol  = (key) => { const c = allColumns.find(c => c.key === key); if(c) c.visible = !c.visible }
const resetLayout = () => {
  allColumns.forEach(c => {
    const def = defaultColumns.find(d => d.key === c.key)
    if (def) { c.width = def.width; c.visible = def.visible }
  })
  allColumns.sort((a, b) => defaultColumns.findIndex(d => d.key === a.key) - defaultColumns.findIndex(d => d.key === b.key))
  Object.keys(filters).forEach(k => filters[k] = '')
  sortKey.value = null; sortDir.value = 'asc'
}
const defaultColumns = JSON.parse(JSON.stringify(allColumns))

// ─── DATI DEMO (sostituisci con props.contacts in produzione) ─
const demoData = Array.from({ length: 87 }, (_, i) => ({
  id:      i + 1,
  name:    ['Marco Rossi','Sofia Bianchi','Luca Ferrari','Anna Marino','Giorgio Conti',
             'Elena Ricci','Paolo Esposito','Chiara Romano','Andrea Colombo','Sara Greco'][i % 10],
  company: ['TechCorp Srl','Innovate SpA','Digital Hub','StartUp Lab','Nexus Group',
             'Alpha SRL','Beta SpA','Gamma Ltd','Delta Inc','Epsilon GmbH'][i % 10],
  email:   `contatto${i+1}@example.it`,
  phone:   `+39 3${String(Math.floor(Math.random()*9000000+1000000))}`,
  status:  ['cliente','lead','prospect','inattivo'][i % 4],
  city:    ['Milano','Roma','Torino','Napoli','Bologna','Firenze','Palermo','Bari'][i % 8],
  value:   `€${((i + 1) * 1234 % 50000 + 1000).toLocaleString('it')}`,
  source:  ['Web','Referral','LinkedIn','Cold Call','Email'][i % 5],
  created: `${String((i % 28) + 1).padStart(2,'0')}/${String((i % 12) + 1).padStart(2,'0')}/2024`,
}))

const rows = computed(() => props.contacts.length ? props.contacts : demoData)

// ─── FILTRI ───────────────────────────────────────────────────
const filters = reactive(Object.fromEntries(allColumns.map(c => [c.key, ''])))

const filteredRows = computed(() => {
  return rows.value.filter(row =>
    allColumns.every(col => {
      if (!filters[col.key]) return true
      return String(row[col.key] ?? '').toLowerCase().includes(filters[col.key].toLowerCase())
    })
  ).sort((a, b) => {
    if (!sortKey.value) return 0
    const va = String(a[sortKey.value] ?? '')
    const vb = String(b[sortKey.value] ?? '')
    return sortDir.value === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va)
  })
})

// ─── ORDINAMENTO ─────────────────────────────────────────────
const sortKey = ref(null)
const sortDir = ref('asc')
const setSort = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'asc' }
}

// ─── PAGINAZIONE ─────────────────────────────────────────────
const currentPage = ref(1)
const perPage     = ref(10)
const totalPages  = computed(() => Math.max(1, Math.ceil(filteredRows.value.length / perPage.value)))
const paginatedRows = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredRows.value.slice(start, start + perPage.value)
})
const paginationInfo = computed(() => ({
  from: filteredRows.value.length ? (currentPage.value - 1) * perPage.value + 1 : 0,
  to:   Math.min(currentPage.value * perPage.value, filteredRows.value.length),
}))
const visiblePages = computed(() => {
  const total = totalPages.value, cur = currentPage.value, pages = []
  if (total <= 7) { for(let i=1;i<=total;i++) pages.push(i); return pages }
  pages.push(1)
  if (cur > 3) pages.push('...')
  for (let i = Math.max(2, cur-1); i <= Math.min(total-1, cur+1); i++) pages.push(i)
  if (cur < total - 2) pages.push('...')
  pages.push(total)
  return pages
})

// ─── RESIZE COLONNE ──────────────────────────────────────────
let resizingKey = null, resizeStartX = 0, resizeStartW = 0

const startResize = (key, e) => {
  resizingKey = key; resizeStartX = e.clientX
  resizeStartW = allColumns.find(c => c.key === key)?.width ?? 100
  document.addEventListener('mousemove', onResize)
  document.addEventListener('mouseup', stopResize)
  e.preventDefault()
}
const onResize = (e) => {
  if (!resizingKey) return
  const col = allColumns.find(c => c.key === resizingKey)
  if (col) col.width = Math.max(60, resizeStartW + (e.clientX - resizeStartX))
}
const stopResize = () => {
  resizingKey = null
  document.removeEventListener('mousemove', onResize)
  document.removeEventListener('mouseup', stopResize)
}

// ─── DRAG & DROP COLONNE ─────────────────────────────────────
const draggingCol = ref(null)
const dragOverCol = ref(null)

const onDragStart = (idx, e) => {
  draggingCol.value = idx
  e.dataTransfer.effectAllowed = 'move'
}
const onDragOver = (idx) => { dragOverCol.value = idx }
const onDrop = (targetIdx) => {
  if (draggingCol.value === null || draggingCol.value === targetIdx) return
  const visible = visibleColumns.value
  const srcKey  = visible[draggingCol.value].key
  const tgtKey  = visible[targetIdx].key
  const srcAll  = allColumns.findIndex(c => c.key === srcKey)
  const tgtAll  = allColumns.findIndex(c => c.key === tgtKey)
  const [moved] = allColumns.splice(srcAll, 1)
  allColumns.splice(tgtAll, 0, moved)
  draggingCol.value = null; dragOverCol.value = null
}
const onDragEnd = () => { draggingCol.value = null; dragOverCol.value = null }

// ─── EXPORT EXCEL ────────────────────────────────────────────
const exportExcel = () => {
  const cols = visibleColumns.value
  const header = cols.map(c => c.label)
  const data   = filteredRows.value.map(row => cols.map(c => row[c.key] ?? ''))
  const ws = XLSX.utils.aoa_to_sheet([header, ...data])

  // Stile intestazione
  const range = XLSX.utils.decode_range(ws['!ref'])
  for (let C = range.s.c; C <= range.e.c; C++) {
    const cell = ws[XLSX.utils.encode_cell({ r: 0, c: C })]
    if (cell) cell.s = { font: { bold: true }, fill: { fgColor: { rgb: '6366F1' } } }
  }

  // Larghezze colonne
  ws['!cols'] = cols.map(c => ({ wch: Math.max(10, Math.floor(c.width / 7)) }))

  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Contatti')
  XLSX.writeFile(wb, `contatti_${new Date().toISOString().slice(0,10)}.xlsx`)
}

// ─── DROPDOWN CLICK OUTSIDE ──────────────────────────────────
const showColMenu = ref(false)
const colMenuRef  = ref(null)
const onClickOutside = (e) => {
  if (colMenuRef.value && !colMenuRef.value.contains(e.target)) showColMenu.value = false
}
onMounted(()    => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>

<style scoped>
/* ─── BASE ─────────────────────────────────────────────────── */
.grid-wrapper {
  background: #0f0f18;
  border: 1px solid #1e1e2e;
  border-radius: 16px;
  overflow: hidden;
  font-family: 'DM Sans', sans-serif;
  color: #e2e8f0;
}

/* ─── TOOLBAR ──────────────────────────────────────────────── */
.toolbar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px 20px; border-bottom: 1px solid #1e1e2e;
  background: #0d0d1a;
}
.toolbar-left  { display: flex; align-items: center; gap: 12px; }
.toolbar-right { display: flex; align-items: center; gap: 8px; }
.grid-title    { font-weight: 700; font-size: 15px; color: #fff; }
.row-count     { font-size: 12px; color: #64748b; background: #1e1e2e; padding: 2px 10px; border-radius: 20px; }

.btn-tool {
  display: flex; align-items: center; gap: 6px;
  background: #1e1e2e; border: 1px solid #2d2d44;
  border-radius: 8px; color: #94a3b8; font-size: 12px;
  padding: 6px 12px; cursor: pointer; transition: all 0.15s;
}
.btn-tool:hover { background: #2d2d44; color: #e2e8f0; }

.btn-export {
  display: flex; align-items: center; gap: 6px;
  background: linear-gradient(135deg, #10b981, #059669);
  border: none; border-radius: 8px; color: #fff;
  font-size: 12px; font-weight: 600; padding: 6px 14px; cursor: pointer;
  transition: opacity 0.15s;
}
.btn-export:hover { opacity: 0.85; }

/* ─── DROPDOWN ─────────────────────────────────────────────── */
.dropdown-wrap { position: relative; }
.dropdown {
  position: absolute; top: calc(100% + 6px); right: 0; z-index: 100;
  background: #16162a; border: 1px solid #2d2d44; border-radius: 12px;
  padding: 8px; min-width: 180px; box-shadow: 0 8px 32px #000a;
}
.dropdown-title { font-size: 10px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 8px 8px; }
.col-toggle {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 8px; border-radius: 8px; cursor: pointer;
  font-size: 13px; color: #94a3b8; transition: background 0.1s;
}
.col-toggle:hover { background: #1e1e2e; color: #e2e8f0; }
.col-toggle input { accent-color: #6366f1; cursor: pointer; }

/* ─── TABELLA ──────────────────────────────────────────────── */
.table-container { overflow-x: auto; overflow-y: auto; max-height: 520px; }
.data-table { border-collapse: collapse; width: 100%; table-layout: fixed; }

/* HEADER */
.header-row { position: sticky; top: 0; z-index: 10; background: #0d0d1a; }
.th {
  padding: 0; border-right: 1px solid #1e1e2e;
  border-bottom: 1px solid #1e1e2e; position: relative;
  user-select: none; transition: background 0.1s;
}
.th:last-child { border-right: none; }
.th.drag-over  { background: #6366f122; }
.th.dragging   { opacity: 0.4; }

.th-inner {
  display: flex; align-items: center; gap: 6px;
  padding: 10px 12px;
}
.th-drag-icon { color: #334155; font-size: 14px; cursor: grab; flex-shrink: 0; }
.th-label {
  font-size: 11px; font-weight: 600; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.5px;
  cursor: pointer; display: flex; align-items: center; gap: 4px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.th-label:hover { color: #94a3b8; }
.sort-icon { color: #6366f1; }

/* RESIZE HANDLE */
.resize-handle {
  position: absolute; top: 0; right: -3px;
  width: 6px; height: 100%; cursor: col-resize; z-index: 5;
  background: transparent; transition: background 0.15s;
}
.resize-handle:hover,
.resize-handle:active { background: #6366f1; border-radius: 3px; }

/* FILTRI */
.filter-row { position: sticky; top: 41px; z-index: 9; background: #0d0d1a; }
.filter-th { padding: 0; border-right: 1px solid #1e1e2e; border-bottom: 2px solid #1e1e2e; }
.filter-th:last-child { border-right: none; }
.filter-cell { display: flex; align-items: center; gap: 4px; padding: 5px 8px; }
.filter-icon { flex-shrink: 0; color: #334155; }
.filter-input {
  flex: 1; background: transparent; border: none;
  color: #94a3b8; font-size: 12px; outline: none; min-width: 0;
}
.filter-input::placeholder { color: #334155; }
.filter-input:focus { color: #e2e8f0; }
.filter-clear {
  background: none; border: none; color: #475569; cursor: pointer;
  font-size: 14px; line-height: 1; flex-shrink: 0;
}

/* BODY */
.data-row { border-bottom: 1px solid #12121e; transition: background 0.1s; }
.data-row:hover { background: #12121e; }
.row-even { background: #0b0b15; }
.row-even:hover { background: #12121e; }

.td {
  padding: 10px 12px; border-right: 1px solid #12121e;
  overflow: hidden; white-space: nowrap; text-overflow: ellipsis;
  font-size: 13px; color: #94a3b8;
}
.td:last-child { border-right: none; }

/* CELLE SPECIALI */
.name-cell { display: flex; align-items: center; gap: 8px; color: #e2e8f0; font-weight: 500; }
.avatar {
  width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700; color: #fff;
}
.value-cell { font-weight: 700; color: #fff; }
.cell-text  { display: block; overflow: hidden; text-overflow: ellipsis; }

.status-pill {
  display: inline-block; font-size: 11px; font-weight: 600;
  border-radius: 20px; padding: 2px 10px;
}
.status-cliente  { background: #052e1680; color: #4ade80; }
.status-lead     { background: #42270280; color: #fbbf24; }
.status-prospect { background: #0c2a4280; color: #60a5fa; }
.status-inattivo { background: #1c1c2880; color: #64748b; }

.empty-row { text-align: center; padding: 40px; color: #475569; font-size: 14px; }

/* ─── PAGINAZIONE ──────────────────────────────────────────── */
.pagination {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 20px; border-top: 1px solid #1e1e2e; background: #0d0d1a;
}
.pagination-info    { font-size: 12px; color: #64748b; }
.pagination-controls { display: flex; align-items: center; gap: 4px; }

.per-page-select {
  background: #1e1e2e; border: 1px solid #2d2d44; border-radius: 6px;
  color: #94a3b8; font-size: 12px; padding: 4px 8px; cursor: pointer;
  margin-right: 8px;
}
.page-btn {
  background: #1e1e2e; border: 1px solid #2d2d44; border-radius: 6px;
  color: #94a3b8; font-size: 13px; width: 30px; height: 30px; cursor: pointer;
  transition: all 0.1s; display: flex; align-items: center; justify-content: center;
}
.page-btn:hover:not(:disabled) { background: #2d2d44; color: #fff; }
.page-btn:disabled { opacity: 0.3; cursor: default; }

.page-num {
  width: 30px; height: 30px; border-radius: 6px; display: flex;
  align-items: center; justify-content: center; font-size: 13px; cursor: pointer;
  color: #64748b; transition: all 0.1s; user-select: none;
}
.page-num:hover:not(.ellipsis) { background: #1e1e2e; color: #e2e8f0; }
.page-num.active { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; font-weight: 700; }
.page-num.ellipsis { cursor: default; }
</style>
