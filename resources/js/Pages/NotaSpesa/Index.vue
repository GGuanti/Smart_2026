<script setup>
import { onMounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { TabulatorFull as Tabulator } from 'tabulator-tables'
import 'tabulator-tables/dist/css/tabulator.min.css'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const table = ref(null)
const noteSpese = usePage().props.noteSpese
const codCliente = ref('')
const filteredNoteSpese = ref([])

const applyFilter = () => {
  filteredNoteSpese.value = noteSpese.filter(nota => {
    return codCliente.value === '' || nota.CodCliente?.toLowerCase().includes(codCliente.value.toLowerCase())
  })

  if (table.value && table.value._tabulator) {
    table.value._tabulator.replaceData(filteredNoteSpese.value)
  }
}

onMounted(() => {
  filteredNoteSpese.value = noteSpese

  new Tabulator(table.value, {
    data: filteredNoteSpese.value,
    layout: 'fitColumns',
    height: '600px',
    pagination: 'local',
    paginationSize: 20,
    columns: [
      { title: 'Data Documento', field: 'DataDoc' },
      { title: 'Data Pagamento', field: 'DataPag' },
      { title: 'Cod. Documento', field: 'Coddoc' },
      { title: 'Cod. Attività', field: 'CodAtt' },
      { title: 'Cliente/Fornitore', field: 'Cliente_Fornitore' },
      { title: 'Causale', field: 'Causale_Banca_Note_Spese' },
      { title: 'Note', field: 'note' },
      { title: 'Importo -', field: 'ImportoNegativo' },
      { title: 'Cod. Cliente', field: 'CodCliente' },
    ],
  })
})

watch(codCliente, applyFilter)
</script>

<template>
  <AuthenticatedLayout>
    <div class="p-4 bg-white rounded shadow">
      <h2 class="text-xl font-bold mb-4">💰 Note Spese</h2>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Filtra per CodCliente</label>
        <input v-model="codCliente" type="text" placeholder="Inserisci CodCliente..."
               class="border rounded px-3 py-2 w-full max-w-xs shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500" />
      </div>

      <div ref="table" />
    </div>
  </AuthenticatedLayout>
</template>
