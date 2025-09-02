<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { onMounted, onUnmounted, ref, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import axios from 'axios';

const props = defineProps({
  records: Array,
  columns: Array,
  nomeTabella: String,
  tipoU: String
})

const tableRef = ref(null)
let tableInstance = null
const gridKey = `anagrafica_${props.tipoU}`

// ✅ Carica preferenze colonne salvate
const loadUserColumns = async () => {
  try {
    const { data } = await axios.get('/preferences', {
      params: { key: gridKey }
    })

    return data.length ? data : props.columns.map(col => ({
      title: col.replace(/_/g, ' '),
      field: col,
      headerFilter: true
    }))
  } catch (err) {
    console.error('❌ Errore caricamento colonne:', err)
    return props.columns.map(col => ({
      title: col.replace(/_/g, ' '),
      field: col,
      headerFilter: true
    }))
  }
}

// ✅ Salva configurazione colonne (esclude "Azioni")
const saveUserColumns = () => {
  if (!tableInstance) return
  const layout = tableInstance.getColumnLayout().filter(col => col.field !== 'azioni')
  fetch('/user/columns', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    },
    body: JSON.stringify({ key: gridKey, columns: layout })
  }).catch(error => console.error('❌ Errore salvataggio colonne:', error))
}

// ✅ Reset colonne
const resetUserColumns = async () => {
  try {
    await axios.post('/user/columns/reset', {
      key: gridKey
    })
    location.reload()
  } catch (error) {
    console.error('❌ Errore nel reset colonne:', error)
  }
}

// ✅ Setup Tabulator
onMounted(async () => {
  await nextTick()

  let tabulatorColumns = await loadUserColumns()

  // 🔁 Rimuove "Azioni" se già presente
  tabulatorColumns = tabulatorColumns.filter(c => c.field !== 'azioni')

  // ✅ Inserisce colonna "Azioni" all'inizio
  tabulatorColumns.unshift({
    title: 'Azioni',
    field: 'azioni',
    headerSort: false,
    hozAlign: 'center',
    width: 130,
    visible: true,
    formatter: () => `
      <button class='edit-btn bg-yellow-500 text-white px-2 py-1 mr-2' data-action="edit" title="Modifica">✏️</button>
      <button class='bg-red-500 text-white px-2 py-1' data-action="delete" title="Elimina">🗑️</button>`,
    cellClick: (e, cell) => {
      const id = cell.getRow().getData().IDAnagrafica
      const action = e.target.getAttribute('data-action')
      if (!id || !action) return

      if (action === 'edit') {
        router.visit(`/${props.nomeTabella}/${id}/edit`)
      } else if (action === 'delete') {
        if (confirm('Sei sicuro di voler eliminare questo record?')) {
          router.delete(`/${props.nomeTabella}/${id}`)
        }
      }
    }
  })

  tableInstance = new Tabulator(tableRef.value, {
    data: props.records,
    columns: tabulatorColumns,
    layout: 'fitColumns',
    pagination: 'local',
    paginationSize: 20,
    height: '90%',
    reactiveData: true,
    movableColumns: true,
    resizableColumns: true,
    columnMoved: saveUserColumns,
    columnResized: saveUserColumns,
    columnVisibilityChanged: saveUserColumns
  })
})

onUnmounted(() => {
  if (tableInstance) {
    tableInstance.destroy()
  }
})
</script>

<template>
  <AuthenticatedLayout>
    <div class="p-6 bg-white shadow rounded-lg h-screen flex flex-col">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">
          Anagrafica – Filtro: <span class="text-blue-600">{{ tipoU }}</span>
        </h2>

        <div class="flex gap-2">
          <button
            @click="saveUserColumns"
            class="bg-orange-500 text-white px-3 py-1 rounded"
          >
            💾 Salva colonne
          </button>
          <button
            @click="resetUserColumns"
            class="bg-gray-500 text-white px-3 py-1 rounded"
          >
            🔄 Reset colonne
          </button>
          <button
            @click="router.visit(`/${nomeTabella}/create`)"
            class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700"
          >
            ➕ Nuovo
          </button>
        </div>
      </div>

      <div class="flex-grow overflow-hidden">
        <div ref="tableRef" class="w-full h-full" />
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style>
@import "tabulator-tables/dist/css/tabulator.min.css";
</style>
