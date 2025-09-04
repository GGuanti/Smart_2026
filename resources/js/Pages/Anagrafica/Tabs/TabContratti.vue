<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import FlatPickr from 'vue-flatpickr-component'
import 'flatpickr/dist/flatpickr.css'
import { Italian } from 'flatpickr/dist/l10n/it.js'

const toast = useToast()
const page = usePage()
const currentUser = computed(() => page.props.auth.user)
const rowId = (row) => row?.IdContratti ?? row?.IdContratto ?? row?._pk

const props = defineProps({
  codCliente: String,
  contratti: Array,
  tipiContratto: Array,
  Professione: Array,
  nomeUtente: String,
  CodFiscale: String
})

// --- helpers date ---
const toDate = (v) => {
  if (!v) return null
  if (v instanceof Date) return v
  // gestisce "YYYY-MM-DD" o ISO
  const d = new Date(v)
  return isNaN(d.getTime()) ? null : d
}
const formatDate = (val) => {
  const d = toDate(val)
  return d ? d.toLocaleDateString('it-IT') : ''
}
const formatDateForMySQL = (value) => {
  const d = toDate(value)
  return d ? d.toISOString().split('T')[0] : null
}
const todayAt00 = () => {
  const d = new Date()
  d.setHours(0,0,0,0)
  return d
}
const getStatoContratto = (dataFine) => {
  const fine = toDate(dataFine)
  if (!fine) return '' // nessuna data => nessuno stato
  // se la data fine è prima di oggi -> Scaduto
  return fine < todayAt00() ? 'Scaduto' : 'In vigore'
}
const normalizeEmpty = v => (v === '' ? null : v)

// Normalizza professioni in una lista di stringhe
const professioni = computed(() =>
  (props.Professione || [])
    .map(p => (typeof p === 'string' ? p : (p?.Professione ?? p?.nome ?? p?.label ?? '')))
    .filter(Boolean)
)

// --- state ---
const contratti = ref([])
const isLoading = ref(false)
const editingId = ref(null)
const inEditing = ref({
  IdContratto: '',
  NomeCognUser: '',
  TipoContr: '',
  Professione: '',
  CCNL: '',
  DataContratto: null,
  DataInizio: null,
  DataFineContratto: null,
  Stato: '',
  StatoContratto: '', // calcolato via watch
  CodFiscale: '',
  CodCliente: ''
})
const errors = ref({})
const errorsUpdate = ref({})

const newContratto = ref({
  IdContratto: '',
  NomeCognUser: props.nomeUtente ?? '',
  TipoContr: '',
  DataContratto: new Date(),
  DataInizio: null,
  DataFineContratto: null,
  Stato: '',
  Professione: '',
  CCNL: '',
  StatoContratto: '', // calcolato via watch
  CodFiscale: props.CodFiscale ?? '',
  CodCliente: props.codCliente
})

const isEditing = row => editingId.value === row._pk

// --- validation ---
const validateContratto = () => {
  const err = {}
  if (!newContratto.value.NomeCognUser) err.NomeCognUser = 'Campo obbligatorio'
  if (!newContratto.value.Professione) err.Professione = 'Campo obbligatorio'
  if (!newContratto.value.TipoContr) err.TipoContr = 'Campo obbligatorio'
  if (!newContratto.value.Stato) err.Stato = 'Campo obbligatorio'
  if (!newContratto.value.DataContratto) err.DataContratto = 'Campo obbligatorio'
  if (!newContratto.value.DataInizio) err.DataInizio = 'Campo obbligatorio'
  if (!newContratto.value.DataFineContratto) err.DataFineContratto = 'Campo obbligatorio'
  // StatoContratto è calcolato
  errors.value = err
  return Object.keys(err).length === 0
}

const validateUpdateContratto = () => {
  const err = {}
  if (!inEditing.value.NomeCognUser) err.NomeCognUser = 'Campo obbligatorio'
  if (!inEditing.value.Professione) err.Professione = 'Campo obbligatorio'
  if (!inEditing.value.Stato) err.Stato = 'Campo obbligatorio'
  if (!inEditing.value.CCNL && inEditing.value.CCNL !== 0) err.CCNL = 'Campo obbligatorio'
  if (!inEditing.value.TipoContr) err.TipoContr = 'Campo obbligatorio'
  if (!inEditing.value.DataContratto) err.DataContratto = 'Campo obbligatorio'
  if (!inEditing.value.DataInizio) err.DataInizio = 'Campo obbligatorio'
  if (!inEditing.value.DataFineContratto) err.DataFineContratto = 'Campo obbligatorio'
  // StatoContratto è calcolato
  errorsUpdate.value = err
  return Object.keys(err).length === 0
}

// --- fetch ---
const fetchContratti = async () => {
  try {
    isLoading.value = true
    contratti.value = []
    await nextTick()
    const { data } = await axios.get('/contratti', { params: { codCliente: props.codCliente } })
    const arr = Array.isArray(data) ? data : []
    contratti.value = (arr ?? [])
      .filter(r => r && typeof r === 'object')
      .map((r, i) => {
        const pk = r.IdContratti ?? r.IdContratto ?? r.id ?? `idx-${i}`
        return { ...r, _pk: String(pk) }
      })
  } catch (e) {
    toast.error('Errore nel caricamento contratti')
    console.error('GET /contratti error:', e?.response?.data || e)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchContratti)

// --- edit flows ---
const edit = (row) => {
  editingId.value = row._pk
  inEditing.value = {
    IdContratto: row.IdContratto || '',
    NomeCognUser: row.NomeCognUser || '',
    TipoContr: row.TipoContr || '',
    Professione: row.Professione ?? row.professione ?? '',
    CCNL: row.CCNL ?? '',
    DataContratto: toDate(row.DataContratto),
    DataInizio: toDate(row.DataInizio),
    DataFineContratto: toDate(row.DataFineContratto),
    Stato: row.Stato ?? '',
    StatoContratto: getStatoContratto(row.DataFineContratto),
    CodFiscale: row.CodFiscale ?? '',
    CodCliente: row.CodCliente ?? ''
  }
  errorsUpdate.value = {}
}

const cancelEdit = () => {
  editingId.value = null
  inEditing.value = {
    IdContratto: '',
    NomeCognUser: '',
    TipoContr: '',
    Professione: '',
    CCNL: '',
    DataContratto: null,
    DataInizio: null,
    DataFineContratto: null,
    Stato: '',
    StatoContratto: '',
    CodFiscale: '',
    CodCliente: ''
  }
  errorsUpdate.value = {}
}

// --- CCNL by Professione ---
const fetchCCNL = async (mode /* 'new' | 'edit' */) => {
  const professione = mode === 'new' ? newContratto.value.Professione : inEditing.value.Professione
  if (!professione) return
  try {
    const { data } = await axios.get('/professioni/ccnl', { params: { professione } })
    const value = data ?? null
    if (mode === 'new') newContratto.value.CCNL = value
    else inEditing.value.CCNL = value
  } catch (e) {
    toast.error('Errore nel caricamento CCNL')
    console.error('GET /professioni/ccnl error:', e?.response?.data || e)
  }
}
watch(() => newContratto.value.Professione, () => fetchCCNL('new'))
watch(() => inEditing.value.Professione, () => fetchCCNL('edit'))

// --- StatoContratto calcolato ---
watch(() => newContratto.value.DataFineContratto, (v) => {
  newContratto.value.StatoContratto = getStatoContratto(v)
})
watch(() => inEditing.value.DataFineContratto, (v) => {
  inEditing.value.StatoContratto = getStatoContratto(v)
})

// --- CRUD ---
const update = async (id) => {
  if (!validateUpdateContratto()) {
    toast.error('Compila i campi obbligatori')
    return
  }
  const payload = {
    IdContratto: normalizeEmpty(inEditing.value.IdContratto),
    NomeCognUser: inEditing.value.NomeCognUser?.trim(),
    TipoContr: inEditing.value.TipoContr,
    Professione: inEditing.value.Professione,
    CCNL: inEditing.value.CCNL !== '' ? Number(inEditing.value.CCNL) : null,
    DataContratto: formatDateForMySQL(inEditing.value.DataContratto),
    DataInizio: formatDateForMySQL(inEditing.value.DataInizio),
    DataFineContratto: formatDateForMySQL(inEditing.value.DataFineContratto),
    Stato: normalizeEmpty(inEditing.value.Stato),
    StatoContratto: normalizeEmpty(inEditing.value.StatoContratto),
    CodFiscale: normalizeEmpty(inEditing.value.CodFiscale),
    CodCliente: inEditing.value.CodCliente,
  }

  try {
    await axios.put(`/contratti/${id}`, payload)
    toast.success('Contratto aggiornato')
    cancelEdit()
    fetchContratti()
  } catch (e) {
    console.error('PUT /contratti error:', e?.response?.data || e)
    const errs = e?.response?.data?.errors || {}
    const msgs = Object.entries(errs).map(([k,v]) => `${k}: ${Array.isArray(v)?v[0]:v}`).join('\n')
    toast.error(`Errore salvataggio:\n${msgs || 'Verifica i campi'}`)
  }
}

const destroy = async (id) => {
  if (!confirm('Eliminare questo contratto?')) return
  try {
    await axios.delete(`/contratti/${id}`)
    toast.success('Contratto eliminato')
    fetchContratti()
  } catch (e) {
    toast.error("Errore durante l'eliminazione")
    console.error('DELETE /contratti error:', e?.response?.data || e)
  }
}

const addContratto = async () => {
  if (!validateContratto()) {
    toast.error('Compila i campi obbligatori')
    return
  }
  const payload = {
    IdContratto: normalizeEmpty(newContratto.value.IdContratto),
    NomeCognUser: newContratto.value.NomeCognUser?.trim(),
    TipoContr: newContratto.value.TipoContr,
    Professione: newContratto.value.Professione,
    CCNL: newContratto.value.CCNL !== '' ? Number(newContratto.value.CCNL) : null,
    DataContratto: formatDateForMySQL(newContratto.value.DataContratto),
    DataInizio: formatDateForMySQL(newContratto.value.DataInizio),
    DataFineContratto: formatDateForMySQL(newContratto.value.DataFineContratto),
    Stato: newContratto.value.Stato,
    StatoContratto: normalizeEmpty(newContratto.value.StatoContratto) ?? 'In vigore',
    CodFiscale: normalizeEmpty(newContratto.value.CodFiscale),
    CodCliente: newContratto.value.CodCliente,
  }

  try {
    await axios.post('/contratti', payload)
    toast.success('Contratto creato')
    newContratto.value = {
      IdContratto: '',
      NomeCognUser: props.nomeUtente ?? '',
      TipoContr: '',
      DataContratto: new Date(),
      DataInizio: null,
      DataFineContratto: null,
      Stato: '',
      Professione: '',
      CCNL: '',
      StatoContratto: '',
      CodFiscale: props.CodFiscale ?? '',
      CodCliente: props.codCliente
    }
    errors.value = {}
    fetchContratti()
  } catch (e) {
    console.error('POST /contratti errors:', e?.response?.data || e)
    const errs = e?.response?.data?.errors || {}
    const msgs = Object.entries(errs).map(([k,v]) => `${k}: ${Array.isArray(v)?v[0]:v}`).join('\n')
    toast.error(`Errore validazione:\n${msgs || 'Verifica i campi'}`)
  }
}
const GeneraContratti = (row) => {
  const id = rowId(row)
  if (!id) return toast.error('ID contratto non valido')
  // con Ziggy:
  // window.open(route('contratti.report', id), '_blank')

  // senza Ziggy:
  window.open(`/contratti/${encodeURIComponent(id)}/report`, '_blank')
}
</script>

<template>
  <div class="p-4 bg-white rounded shadow" @keydown.enter.prevent>
    <h2 class="text-lg font-semibold mb-4">Contratti - {{ props.nomeUtente }}</h2>

    <table class="w-full text-sm border">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 border">Id</th>
          <th class="p-2 border">Utente</th>
          <th class="p-2 border">Tipo </th>
          <th class="p-2 border">Professione</th>
          <th class="p-2 border">Livelli CCNL</th>
          <th class="p-2 border">Data Contratto</th>
          <th class="p-2 border">Inizio</th>
          <th class="p-2 border">Fine</th>
          <th class="p-2 border">Stato</th>
          <th class="p-2 border">Stato Contratto</th>
          <th class="p-2 border">Cod.Fiscale</th>
          <th class="p-2 border">Azioni</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="isLoading">
          <td colspan="12" class="text-center py-4">Caricamento...</td>
        </tr>

        <tr v-for="row in contratti" :key="row._pk">
          <!-- Id -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ row.IdContratto }}</div>
            <div v-else>
              <input v-model="inEditing.IdContratto" class="w-full border rounded" readonly />
            </div>
          </td>

          <!-- Utente -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ row.NomeCognUser }}</div>
            <div v-else>
              <input v-model="inEditing.NomeCognUser" class="w-full border rounded bg-gray-100" readonly />
            </div>
          </td>

          <!-- Tipo -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ row.TipoContr }}</div>
            <div v-else>
              <select v-model="inEditing.TipoContr" class="w-full border rounded" disabled>
                <option value="">-- Seleziona --</option>
                <option v-for="tipo in props.tipiContratto" :key="tipo" :value="tipo">{{ tipo }}</option>
              </select>
            </div>
          </td>

          <!-- Professione -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ row.Professione }}</div>
            <div v-else>
              <select v-model="inEditing.Professione" class="w-full border rounded">
                <option value="">-- Seleziona --</option>
                <option v-for="p in professioni" :key="p" :value="p">{{ p }}</option>
              </select>
              <p v-if="errorsUpdate.Professione" class="text-red-600 text-xs mt-1">{{ errorsUpdate.Professione }}</p>
            </div>
          </td>

          <!-- CCNL -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ row.CCNL }}</div>
            <div v-else>
              <input v-model="inEditing.CCNL" class="w-full border rounded" readonly />
              <p v-if="errorsUpdate.CCNL" class="text-red-600 text-xs mt-1">{{ errorsUpdate.CCNL }}</p>
            </div>
          </td>

          <!-- Data Contratto -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ formatDate(row.DataContratto) }}</div>
            <div v-else>
              <FlatPickr
                :model-value="inEditing.DataContratto ?? null"
                @update:modelValue="val => inEditing.DataContratto = val"
                :config="{ locale: Italian, altInput: true, altFormat: 'd/m/Y', dateFormat: 'Y-m-d' }"
                class="w-full"
              />
              <p v-if="errorsUpdate.DataContratto" class="text-red-600 text-xs mt-1">{{ errorsUpdate.DataContratto }}</p>
            </div>
          </td>

          <!-- Inizio -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ formatDate(row.DataInizio) }}</div>
            <div v-else>
              <FlatPickr
                :model-value="inEditing.DataInizio ?? null"
                @update:modelValue="val => inEditing.DataInizio = val"
                :config="{ locale: Italian, altInput: true, altFormat: 'd/m/Y', dateFormat: 'Y-m-d' }"
                class="w-full"
              />
              <p v-if="errorsUpdate.DataInizio" class="text-red-600 text-xs mt-1">{{ errorsUpdate.DataInizio }}</p>
            </div>
          </td>

          <!-- Fine -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ formatDate(row.DataFineContratto) }}</div>
            <div v-else>
              <FlatPickr
                :model-value="inEditing.DataFineContratto ?? null"
                @update:modelValue="val => inEditing.DataFineContratto = val"
                :config="{ locale: Italian, altInput: true, altFormat: 'd/m/Y', dateFormat: 'Y-m-d' }"
                class="w-full"
              />
              <p v-if="errorsUpdate.DataFineContratto" class="text-red-600 text-xs mt-1">{{ errorsUpdate.DataFineContratto }}</p>
            </div>
          </td>

            <!-- Stato (libero) -->
            <td class="border p-1">
            <template v-if="isEditing(row)">
            <select v-model="inEditing.Stato" class="w-full border rounded">
                <option value="">-- Seleziona --</option>
                <option value="Da Approvare">Da Approvare</option>
                <option value="Approvato">Approvato</option>
                <option value="Da Prorogare">Da Prorogare</option>
            </select>
            <p v-if="errorsUpdate.Stato" class="text-red-600 text-xs mt-1">{{ errorsUpdate.Stato }}</p>
            </template>
            <template v-else>
            {{ row.Stato }}
            </template>
            </td>

          <!-- Stato Contratto (calcolato) -->
          <td class="border p-1">
            <template v-if="isEditing(row)">
              <input v-model="inEditing.StatoContratto" class="w-full border rounded bg-gray-100" readonly />
            </template>
            <template v-else>
              {{ getStatoContratto(row.DataFineContratto) }}
            </template>
          </td>

          <!-- Cod.Fiscale -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ row.CodFiscale }}</div>
            <div v-else>
              <input v-model="inEditing.CodFiscale" class="w-full border rounded" />
            </div>
          </td>

          <!-- Azioni -->
          <td class="p-1 border text-left">
  <div class="flex items-center gap-2">
    <button
      type="button"
      @click="edit(row)"
      class="text-blue-600 font-bold"
    >
      ✏️ Modifica
    </button>

    <button
      v-if="page.props.auth.user.profilo === 'admin'"
      type="button"
      @click="destroy(row.IdContratti ?? row.IdContratto ?? row._pk)"
      class="text-red-600 font-bold"
    >
      🗑️ Cancella
    </button>

    <button
      v-if="page.props.auth.user.profilo === 'admin'"
      type="button"
  @click="GeneraContratti(row)"
      class="text-gray-600 font-bold"
    >
      🖨️ Genera Contratto
    </button>

    <button
      v-if="page.props.auth.user.profilo === 'admin'"
      type="button"
      @click="SalvaContratto"
      class="text-green-600 font-bold"
    >
      💾 Salva Contratto
    </button>
  </div>
</td>
        </tr>

        <!-- Nuovo contratto -->
        <tr class="bg-gray-50">
          <td class="p-1 border">
            <input v-model="newContratto.IdContratto" class="w-full border rounded bg-gray-100" readonly />
          </td>
          <td class="p-1 border">
            <input v-model="newContratto.NomeCognUser" class="w-full border rounded bg-gray-100" readonly />
          </td>
          <td class="p-1 border">
            <select v-model="newContratto.TipoContr" class="w-full border rounded">
              <option value="">-- Seleziona --</option>
              <option v-for="tipo in props.tipiContratto" :key="tipo" :value="tipo">{{ tipo }}</option>
            </select>
            <p v-if="errors.TipoContr" class="text-red-600 text-xs mt-1">{{ errors.TipoContr }}</p>
          </td>
          <td class="p-1 border">
            <select v-model="newContratto.Professione" class="w-full border rounded">
              <option value="">-- Seleziona --</option>
              <option v-for="p in professioni" :key="p" :value="p">{{ p }}</option>
            </select>
            <p v-if="errors.Professione" class="text-red-600 text-xs mt-1">{{ errors.Professione }}</p>
          </td>
          <td class="p-1 border">
            <input v-model="newContratto.CCNL" class="w-full border rounded" readonly />
            <p v-if="errors.CCNL" class="text-red-600 text-xs mt-1">{{ errors.CCNL }}</p>
          </td>
          <td class="p-1 border">
            <FlatPickr
              :model-value="newContratto.DataContratto ?? null"
              @update:modelValue="val => newContratto.DataContratto = val"
              :config="{ locale: Italian, altInput: true, altFormat: 'd/m/Y', dateFormat: 'Y-m-d' }"
              class="w-full"
            />
            <p v-if="errors.DataContratto" class="text-red-600 text-xs mt-1">{{ errors.DataContratto }}</p>
          </td>
          <td class="p-1 border">
            <FlatPickr
              :model-value="newContratto.DataInizio ?? null"
              @update:modelValue="val => newContratto.DataInizio = val"
              :config="{ locale: Italian, altInput: true, altFormat: 'd/m/Y', dateFormat: 'Y-m-d' }"
              class="w-full"
            />
            <p v-if="errors.DataInizio" class="text-red-600 text-xs mt-1">{{ errors.DataInizio }}</p>
          </td>
          <td class="p-1 border">
            <FlatPickr
              :model-value="newContratto.DataFineContratto ?? null"
              @update:modelValue="val => newContratto.DataFineContratto = val"
              :config="{ locale: Italian, altInput: true, altFormat: 'd/m/Y', dateFormat: 'Y-m-d' }"
              class="w-full"
            />
            <p v-if="errors.DataFineContratto" class="text-red-600 text-xs mt-1">{{ errors.DataFineContratto }}</p>
          </td>
            <td class="p-1 border">
            <select v-model="newContratto.Stato" class="w-full border rounded">
                <option value="">-- Seleziona --</option>
                <option value="Da Approvare">Da Approvare</option>
                <option value="Approvato">Approvato</option>
                <option value="Da Prorogare">Da Prorogare</option>
            </select>
            <p v-if="errors.Stato" class="text-red-600 text-xs mt-1">{{ errors.Stato }}</p>
            </td>
          <td class="p-1 border">
            <input v-model="newContratto.StatoContratto" class="w-full border rounded bg-gray-100" readonly />
          </td>
          <td class="p-1 border">
            <input v-model="newContratto.CodFiscale" class="w-full border rounded bg-gray-100" readonly />
          </td>
          <td class="p-1 border text-center">
            <button type="button" @click="addContratto" class="text-green-700 font-bold">➕</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
