<script setup>
import { ref, onMounted } from 'vue'
import { TabulatorFull as Tabulator } from 'tabulator-tables'
import 'tabulator-tables/dist/css/tabulator.min.css'

const props = defineProps({
  codCliente: String,
  giornate: Array,
})

const filtroDataInizio = ref(null)
const filtroDataFine = ref(null)
const pdfUrl = ref(null)

const tableRef = ref(null)
const tabulatorInstance = ref(null)

const formatDate = (value) => {
  if (!value) return ''
  const d = new Date(value)
  return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`
}
const formatDDMMYYYY = (value) => {
  if (!value) return ''
  const d = new Date(value)
  return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`
}
const initTable = () => {
  tabulatorInstance.value = new Tabulator(tableRef.value, {
    height: '400px',
    data: props.giornate,
    layout: 'fitData',
    columns: [
      {
        title: "Data",
        field: "Data",
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
        title: 'Descrizione',
        field: 'Descrizioneprogetto',
        headerFilter: 'input',
      },
      {
        title: 'IDContratto',
        field: 'IDContratto',
        headerFilter: 'input',
      },
      {
  title: 'Diaria',
  field: 'Diaria',
  hozAlign: 'center',
  sorter: "number", // Usa il valore reale
  formatter: function (cell) {
    const val = parseInt(cell.getValue());
    return val === -1 ? '✅' : '❌';
  },
  headerFilter: 'input',
  headerFilterParams: {
    values: {
      '': 'Tutti',
      '-1': '✅',
      '0': '❌'
    }
  },
  // 🔥 Essenziale per far funzionare il filtro con valori numerici
  headerFilterFunc: (headerValue, rowValue) => {
    return headerValue === '' || String(rowValue) === headerValue;
  }
},



      {
        title: 'Importo',
        field: 'Retribuzione',
        sorter: 'number',
        hozAlign: 'right',
        headerFilter: 'input',
        formatter: 'money',
        formatterParams: {
          decimal: ',',
          thousand: '.',
          symbol: '€',
          precision: 2,
        },
      },
    ],
    placeholder: 'Nessun dato disponibile',
  })
}

onMounted(() => {
  initTable()
})

const generaReport = async () => {
  if (!filtroDataInizio.value || !filtroDataFine.value) {
    alert('Inserisci data inizio e data fine')
    return
  }

  try {
    const url = `/giornate/stampa-pdf?codCliente=${encodeURIComponent(props.codCliente)}&data_inizio=${filtroDataInizio.value}&data_fine=${filtroDataFine.value}`
    pdfUrl.value = url
    window.open(url, '_blank') // facoltativo: apre in nuova scheda
  } catch (error) {
    console.error('Errore generazione PDF:', error)
  }
}
</script>

<template>

  <div class="p-2 bg-white rounded shadow overflow-x-auto">
       <div ref="tableRef"></div>
         <div v-if="pdfUrl" class="border rounded overflow-hidden mt-4">
    <iframe :src="pdfUrl" width="100%" height="800px" class="w-full" />
  </div>
  </div>
</template>
