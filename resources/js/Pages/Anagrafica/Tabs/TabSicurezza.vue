<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import FlatPickr from 'vue-flatpickr-component'
import 'flatpickr/dist/flatpickr.css'
import { Italian } from 'flatpickr/dist/l10n/it.js'

const toast = useToast()

const props = defineProps({
  codCliente: String,
  corsi: Array,
  corsiDisponibili: Array
})

const editingId = ref(null)
const localCorsi = ref([])
const isLoading = ref(false)

const newCorso = ref({
  IdCorso: '',
  DurataAttestato: '',
  DataAttestato: '',
  Note: '',
  Stato: '',
})

const inEditing = ref({})

const formatDate = (val) => {
  if (!val) return ''
  const date = new Date(val)
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const year = date.getFullYear()
  return `${day}/${month}/${year}`
}

const formatDateForMySQL = (value) => {
  if (!value) return null
  const date = new Date(value)
  return date.toISOString().split('T')[0]
}

const fetchCorsi = async () => {
  try {
    isLoading.value = true
    localCorsi.value = []
    await nextTick()

    const { data } = await axios.get('/corsi', {
      params: { cod: props.codCliente }
    })

    localCorsi.value = [...data]
  } catch (err) {
    console.error('Errore fetch corsi:', err)
    toast.error('Errore aggiornamento elenco corsi')
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchCorsi)

const edit = (corso) => {
  if (!corso || !corso.IdTabCorso) return
  editingId.value = corso.IdTabCorso
  const corsoDisponibile = props.corsiDisponibili.find(c => c.TipoCorso === corso.TipoCorso)
  inEditing.value = {
    IdCorso: corsoDisponibile?.IdCorso ?? '',
    DurataAttestato: corso.DurataAttestato,
    DataAttestato: corso.DataAttestato,
    Note: corso.Note,
    Stato: corso.Stato,
    CodCliente: corso.CodCliente,
    UtenteMod: '',
    DataModifica: new Date().toISOString().split('T')[0]
  }
}

const cancelEdit = () => {
  editingId.value = null
  inEditing.value = {}
}

const update = async (corsoId) => {
  const payload = {
    ...inEditing.value,
    DataAttestato: formatDateForMySQL(inEditing.value.DataAttestato)
  }

  try {
    await axios.put(`/corsi/${corsoId}`, payload)
    toast.success('Corso aggiornato')
    cancelEdit()
    await fetchCorsi()
  } catch (err) {
    console.error('Errore update:', err)
    toast.error('Errore durante il salvataggio')
  }
}

const destroy = (corsoId) => {
  if (confirm('Sei sicuro di voler eliminare questo corso?')) {
    axios.delete(`/corsi/${corsoId}`).then(() => {
      toast.success('Corso eliminato')
      fetchCorsi()
    }).catch(err => {
      console.error('Errore delete:', err)
      toast.error("Errore durante l'eliminazione")
    })
  }
}

const addCorso = () => {
  if (!newCorso.value.IdCorso || !newCorso.value.DataAttestato) {
    toast.error('Compila almeno tipo corso e data')
    return
  }

  const payload = {
    ...newCorso.value,
    DataAttestato: formatDateForMySQL(newCorso.value.DataAttestato),
    CodCliente: props.codCliente,
    UtenteMod: '',
    DataModifica: new Date().toISOString().split('T')[0]
  }

  axios.post('/corsi', payload).then(() => {
    toast.success('Corso creato')
    newCorso.value = {
      IdCorso: '',
      DurataAttestato: '',
      DataAttestato: '',
      Note: '',
      Stato: '',
    }
    fetchCorsi()
  }).catch(err => {
    console.error('Errore insert:', err)
    toast.error('Errore durante la creazione del corso')
  })
}

const corsiValidi = computed(() => localCorsi.value.filter(c => c && c.IdTabCorso))
</script>

<template>
  <div class="p-4 bg-white rounded shadow">
    <h2 class="text-lg font-semibold mb-4">Corsi Formazione</h2>
    <table class="w-full text-sm border">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 border">Tipo Corso</th>
          <th class="p-2 border">Durata</th>
          <th class="p-2 border">Data Attestato</th>
          <th class="p-2 border">Note</th>
          <th class="p-2 border">Stato</th>
          <th class="p-2 border">Azioni</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="isLoading">
          <td colspan="6" class="text-center text-gray-500 py-4">Caricamento corsi...</td>
        </tr>

        <tr v-for="(corso, index) in corsiValidi" :key="corso.IdTabCorso || index">
          <td class="border p-1">
            <template v-if="editingId === corso.IdTabCorso">
              <select v-model="inEditing.IdCorso" class="w-full border rounded px-1 py-0.5">
                <option v-for="op in props.corsiDisponibili" :key="op.IdCorso" :value="op.IdCorso">{{ op.TipoCorso }}</option>
              </select>
            </template>
            <template v-else>
              {{ props.corsiDisponibili.find(c => c.IdCorso === corso.IdCorso)?.TipoCorso ?? corso.TipoCorso }}
            </template>
          </td>

          <td class="border p-1">
            <template v-if="editingId === corso.IdTabCorso">
              <input v-model="inEditing.DurataAttestato" type="number" class="w-full border rounded px-1 py-0.5" />
            </template>
            <template v-else>
              {{ corso.DurataAttestato }}
            </template>
          </td>

          <td class="border p-1">
            <template v-if="editingId === corso.IdTabCorso">
              <FlatPickr
                v-model="inEditing.DataAttestato"
                :config="{
                  enableTime: false,
                  altInput: true,
                  altFormat: 'd/m/Y',
                  dateFormat: 'Y-m-d',
                  locale: Italian,
                  allowInput: true
                }"
                class="w-full border rounded px-1 py-0.5"
              />
            </template>
            <template v-else>
              {{ formatDate(corso.DataAttestato) }}
            </template>
          </td>

          <td class="border p-1">
            <template v-if="editingId === corso.IdTabCorso">
              <input v-model="inEditing.Note" class="w-full border rounded px-1 py-0.5" />
            </template>
            <template v-else>
              {{ corso.Note }}
            </template>
          </td>

          <td class="border p-1">
            <template v-if="editingId === corso.IdTabCorso">
              <input v-model="inEditing.Stato" class="w-full border rounded px-1 py-0.5" />
            </template>
            <template v-else>
              {{ corso.Stato }}
            </template>
          </td>

          <td class="border p-1 text-center whitespace-nowrap">
            <template v-if="editingId === corso.IdTabCorso">
              <button type="button" class="text-green-600 font-bold mr-1" @click="update(corso.IdTabCorso)">💾</button>
              <button type="button" class="text-gray-600 font-bold" @click="cancelEdit">✖</button>
            </template>
            <template v-else>
              <button type="button" class="text-blue-600 font-bold mr-1" @click="edit(corso)">✏️</button>
              <button type="button" class="text-red-600 font-bold" @click="destroy(corso.IdTabCorso)">🗑️</button>
            </template>
          </td>
        </tr>

        <!-- Riga Nuovo Corso -->
        <tr class="bg-gray-50">
          <td class="p-1 border">
            <select v-model="newCorso.IdCorso" class="w-full border rounded px-1 py-0.5">
              <option value="">-- seleziona --</option>
              <option v-for="op in props.corsiDisponibili" :key="op.IdCorso" :value="op.IdCorso">{{ op.TipoCorso }}</option>
            </select>
          </td>
          <td class="p-1 border">
            <input type="number" v-model="newCorso.DurataAttestato" class="w-full border rounded px-1 py-0.5" />
          </td>
          <td class="p-1 border">
            <FlatPickr
              v-model="newCorso.DataAttestato"
              :config="{
                enableTime: false,
                altInput: true,
                altFormat: 'd/m/Y',
                dateFormat: 'Y-m-d',
                locale: Italian,
                allowInput: true
              }"
              class="w-full border rounded px-1 py-0.5"
            />
          </td>
          <td class="p-1 border">
            <input v-model="newCorso.Note" class="w-full border rounded px-1 py-0.5" />
          </td>
          <td class="p-1 border">
            <input v-model="newCorso.Stato" class="w-full border rounded px-1 py-0.5" />
          </td>
          <td class="p-1 border text-center">
            <button type="button" class="text-green-700 font-bold" @click="addCorso">➕ Aggiungi</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
