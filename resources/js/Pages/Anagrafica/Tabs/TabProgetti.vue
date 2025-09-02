<script setup>
import { ref, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { TabulatorFull as Tabulator } from 'tabulator-tables'
import 'tabulator-tables/dist/css/tabulator.min.css'

const toast = useToast()

const props = defineProps({
  codCliente: String,
  progetti: Array,
  progettiDisponibili: Array
})

const tableRef = ref(null)
let tableInstance = null

// Inizializza quando i dati sono disponibili
onMounted(() => {
  if (props.progetti.length === 0) {
    watch(() => props.progetti, (val) => {
      if (val.length > 0) initTabulator(val)
    }, { immediate: true })
  } else {
    initTabulator(props.progetti)
  }
})

function initTabulator(data) {
  tableInstance = new Tabulator(tableRef.value, {
    height: "500px",
    data: data,
    layout: "fitColumns",
    pagination: "local",
    paginationSize: 10,
    movableColumns: true,
    columns: [
      { title: "ID", field: "IdProgetto", visible: false },
      { title: "Descrizione", field: "DescrizioneProgetto", editor: "input" },
      { title: "Data Contratto", field: "DataContratto", editor: "input" },
      { title: "Accordi", field: "Accordi", editor: "input" },
      { title: "Importo (€)", field: "ImportoNettoConcordato", hozAlign: "right", editor: "input" },
      {
        title: "Azioni",
        formatter: () => "<button class='text-red-600 font-bold'>🗑️</button>",
        width: 80,
        hozAlign: "center",
        cellClick: (e, cell) => {
          const row = cell.getRow().getData()
          if (confirm(`Eliminare il progetto "${row.DescrizioneProgetto}"?`)) {
            deleteProgetto(row.IdProgetto)
          }
        }
      }
    ],
    cellEdited: async function(cell) {
      const updated = cell.getRow().getData()
      try {
        await axios.put(`/progetti/${updated.IdProgetto}`, updated)
        toast.success("✅ Progetto aggiornato")
      } catch (err) {
        console.error(err)
        toast.error("❌ Errore aggiornamento")
      }
    }
  })
}

const deleteProgetto = async (id) => {
  try {
    await axios.delete(`/progetti/${id}`)
    toast.success("🗑️ Progetto eliminato")
    tableInstance.deleteRow(id)
  } catch (err) {
    console.error(err)
    toast.error("❌ Errore durante l'eliminazione")
  }
}
</script>

<template>
  <div class="p-4 bg-white rounded shadow">
    <h2 class="text-lg font-semibold mb-4">📋 Progetti (Capo Progetto)</h2>
    <div class="text-blue-600 text-sm mb-2">Progetti ricevuti: {{ props.progetti.length }}</div>
    <div ref="tableRef" class="tabulator"></div>
  </div>
</template>

<style scoped>
.tabulator {
  font-size: 0.85rem;
}
</style>
