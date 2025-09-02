<script setup>
import { ref, onMounted } from 'vue'
import { TabulatorFull as Tabulator } from 'tabulator-tables'
import { router } from '@inertiajs/vue3'


const props = defineProps({
  records: Array,
  columns: Array,
  nomeTabella: String
})

const tableRef = ref(null)

onMounted(() => {
  if (!tableRef.value || !props.records.length || !props.columns.length) return

  const tabulatorColumns = props.columns.map(col => ({
    title: col.toUpperCase(),
    field: col,
 headerFilter: true
  }))
// 👉 Aggiungi colonna azioni in coda
tabulatorColumns.push({
    title: 'Azioni',
    headerSort: false,
    formatter: () => `<button class='edit-btn bg-yellow-500 text-white px-2 py-1 rounded'>✏️</button>`,
    width: 100,
    hozAlign: 'center',
    cellClick: (e, cell) => {
      const rowData = cell.getRow().getData()
      // alert(`Modifica record ID: ${rowData.id}`)
        router.visit(`/${props.nomeTabella}/${rowData.id}/edit`)
      // oppure: router.visit(`/profile/${rowData.id}/edit`)
    }
  })



  new Tabulator(tableRef.value, {
    data: props.records,
    columns: tabulatorColumns,
    layout: 'fitColumns',
    pagination: 'local',
    paginationSize: 10,
    height: '100%',
    movableColumns: true,
    resizableRows: true
  })
})
</script>

<template>
  <div class="h-screen flex flex-col">
    <h1 class="text-xl font-bold p-4 border-b">Tabella: {{ nomeTabella }}</h1>

    <!-- Contenitore che si espande e ospita la tabella -->
    <div class="flex-grow overflow-hidden">
      <div ref="tableRef" class="w-full h-full"></div>
    </div>
  </div>
</template>

<style scoped>
html, body {
  height: 100%;
  margin: 0;
}
</style>
