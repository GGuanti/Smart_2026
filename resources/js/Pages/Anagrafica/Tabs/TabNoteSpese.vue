<script setup>
import { ref, onMounted } from 'vue'
import { TabulatorFull as Tabulator } from 'tabulator-tables'
import 'tabulator-tables/dist/css/tabulator.min.css'

const props = defineProps({
  codCliente: String,
   nomeUtente: String,
  noteSpese: Array
})

const tableRef = ref(null)
const tabulatorInstance = ref(null)

const formatDDMMYYYY = (value) => {
  if (!value) return ''
  const d = new Date(value)
  return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`
}

const initTable = () => {
  tabulatorInstance.value = new Tabulator(tableRef.value, {
    height: "400px",
    layout: "fitData",
    data: props.noteSpese,
    placeholder: "Nessun dato disponibile",
    columns: [
      {
        title: "Data Doc",
        field: "DataDoc",
        sorter: (a, b) => {
          const d1 = a ? new Date(a) : new Date(0)
          const d2 = b ? new Date(b) : new Date(0)
          return d1 - d2
        },
        hozAlign: "left",
        headerFilter: "input",
        formatter: (cell) => formatDDMMYYYY(cell.getValue())
      },
      {
        title: "Data Pag",
        field: "DataPag",
        sorter: (a, b) => {
          const d1 = a ? new Date(a) : new Date(0)
          const d2 = b ? new Date(b) : new Date(0)
          return d1 - d2
        },
        hozAlign: "left",
        headerFilter: "input",
        formatter: (cell) => formatDDMMYYYY(cell.getValue())
      },
      {
        title: "Coddoc",
        field: "Coddoc",
        headerFilter: "input"
      },
      {
        title: "CodAtt",
        field: "CodAtt",
        headerFilter: "input"
      },
      {
        title: "Note",
        field: "note",
        headerFilter: "input"
      },
      {
        title: "Importo -",
        field: "Neg",
        sorter: "number",
        hozAlign: "right",
        headerFilter: "input",
        formatter: "money",
        formatterParams: {
          decimal: ",",
          thousand: ".",
          precision: 2,
          symbol: "€ ",
          symbolAfter: false
        }
      }
    ]
  })
}

onMounted(() => {
  initTable()
})
</script>

<template>
  <div class="p-4 bg-white rounded shadow">
   <h2 class="text-lg font-semibold mb-4">Note Spese - {{ props.nomeUtente }}</h2>
        <div ref="tableRef"></div>
  </div>
</template>
