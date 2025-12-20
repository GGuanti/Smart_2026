<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import FlatPickr from 'vue-flatpickr-component'
import 'flatpickr/dist/flatpickr.css'
import { Italian } from 'flatpickr/dist/l10n/it.js'

/* ─────────────────────────────────────────────────────────
   Props & contesto
───────────────────────────────────────────────────────── */
const toast = useToast()
const page = usePage()
const currentUser = computed(() => page.props.auth.user)

const props = defineProps({
  codCliente: { type: String, default: '' },
  nomeUtente: { type: String, default: '' }, // per compilare UtenteMod
})

/* ─────────────────────────────────────────────────────────
   Helpers date & formattazioni
───────────────────────────────────────────────────────── */
const toDate = (v) => {
  if (!v) return null
  if (v instanceof Date) return v
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
const giorniResidui = (dataScadenza) => {
  const fine = toDate(dataScadenza)
  if (!fine) return null
  const diff = Math.floor((fine - todayAt00()) / (1000*60*60*24))
  return diff
}
const getStatoVisita = (dataScadenza) => {
  const fine = toDate(dataScadenza)
  if (!fine) return ''
  return fine < todayAt00() ? 'Scaduta' : 'Valida'
}
const normalizeEmpty = v => (v === '' ? null : v)

/* ─────────────────────────────────────────────────────────
   Stato locale
───────────────────────────────────────────────────────── */
const visite = ref([])
const isLoading = ref(false)
const editingId = ref(null)

const inEditing = ref({
  IdVisita: '',
  UtenteMod: '',
  DataModifica: null,   // valorizzata in update
  DataVisita: null,
  DataScadenza: null,
  CodCliente: '',
})

const nuovaVisita = ref({
  IdVisita: '',
  UtenteMod: props.nomeUtente || currentUser.value?.name || '',
  DataModifica: null,   // sarà valorizzata dal backend
  DataVisita: null,
  DataScadenza: null,
  CodCliente: props.codCliente || '',
})

const errors = ref({})
const errorsUpdate = ref({})

const isEditing = row => editingId.value === row._pk
const rowId = (row) => row?.IdVisita ?? row?.id ?? row?._pk

/* ─────────────────────────────────────────────────────────
   Validazioni
───────────────────────────────────────────────────────── */
const validateCreate = () => {
  const err = {}
  if (!nuovaVisita.value.DataVisita) err.DataVisita = 'Campo obbligatorio'
  if (!nuovaVisita.value.DataScadenza) err.DataScadenza = 'Campo obbligatorio'
  if (!nuovaVisita.value.CodCliente) err.CodCliente = 'Campo obbligatorio'
  errors.value = err
  return Object.keys(err).length === 0
}

const validateUpdate = () => {
  const err = {}
  if (!inEditing.value.DataVisita) err.DataVisita = 'Campo obbligatorio'
  if (!inEditing.value.DataScadenza) err.DataScadenza = 'Campo obbligatorio'
  if (!inEditing.value.CodCliente) err.CodCliente = 'Campo obbligatorio'
  errorsUpdate.value = err
  return Object.keys(err).length === 0
}

/* ─────────────────────────────────────────────────────────
   Fetch
───────────────────────────────────────────────────────── */
const fetchVisite = async () => {
  try {
    isLoading.value = true
    visite.value = []
    await nextTick()
    const { data } = await axios.get('/visite-mediche', { params: { codCliente: props.codCliente } })
    const arr = Array.isArray(data) ? data : []
    visite.value = (arr ?? [])
      .filter(r => r && typeof r === 'object')
      .map((r, i) => {
        const pk = r.IdVisita ?? r.id ?? `idx-${i}`
        return { ...r, _pk: String(pk) }
      })
  } catch (e) {
    toast.error('Errore nel caricamento visite mediche')
    console.error('GET /visite-mediche error:', e?.response?.data || e)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchVisite)

/* ─────────────────────────────────────────────────────────
   Flussi di edit
───────────────────────────────────────────────────────── */
const edit = (row) => {
  editingId.value = row._pk
  inEditing.value = {
    IdVisita: row.IdVisita || '',
    UtenteMod: row.UtenteMod || (props.nomeUtente || currentUser.value?.name || ''),
    DataModifica: toDate(row.DataModifica),
    DataVisita: toDate(row.DataVisita),
    DataScadenza: toDate(row.DataScadenza),
    CodCliente: row.CodCliente ?? props.codCliente ?? '',
  }
  errorsUpdate.value = {}
}

const cancelEdit = () => {
  editingId.value = null
  inEditing.value = {
    IdVisita: '',
    UtenteMod: '',
    DataModifica: null,
    DataVisita: null,
    DataScadenza: null,
    CodCliente: '',
  }
  errorsUpdate.value = {}
}

/* ─────────────────────────────────────────────────────────
   CRUD
───────────────────────────────────────────────────────── */
const addVisita = async () => {
  if (!validateCreate()) {
    toast.error('Compila i campi obbligatori')
    return
  }
  const payload = {
    UtenteMod: normalizeEmpty(nuovaVisita.value.UtenteMod),
    DataVisita: formatDateForMySQL(nuovaVisita.value.DataVisita),
    DataScadenza: formatDateForMySQL(nuovaVisita.value.DataScadenza),
    CodCliente: nuovaVisita.value.CodCliente,
  }
  try {
    await axios.post('/visite-mediche', payload)
    toast.success('Visita inserita')
    nuovaVisita.value = {
      IdVisita: '',
      UtenteMod: props.nomeUtente || currentUser.value?.name || '',
      DataModifica: null,
      DataVisita: null,
      DataScadenza: null,
      CodCliente: props.codCliente || '',
    }
    errors.value = {}
    fetchVisite()
  } catch (e) {
    console.error('POST /visite-mediche errors:', e?.response?.data || e)
    const errs = e?.response?.data?.errors || {}
    const msgs = Object.entries(errs).map(([k,v]) => `${k}: ${Array.isArray(v)?v[0]:v}`).join('\n')
    toast.error(`Errore validazione:\n${msgs || 'Verifica i campi'}`)
  }
}

const update = async (id) => {
  if (!validateUpdate()) {
    toast.error('Compila i campi obbligatori')
    return
  }
  const payload = {
    UtenteMod: normalizeEmpty(inEditing.value.UtenteMod) || (props.nomeUtente || currentUser.value?.name || ''),
    // DataModifica la valorizza il backend (NOW()) se vuoi
    DataVisita: formatDateForMySQL(inEditing.value.DataVisita),
    DataScadenza: formatDateForMySQL(inEditing.value.DataScadenza),
    CodCliente: inEditing.value.CodCliente,
  }
  try {
    await axios.put(`/visite-mediche/${encodeURIComponent(id)}`, payload)
    toast.success('Visita aggiornata')
    cancelEdit()
    fetchVisite()
  } catch (e) {
    console.error('PUT /visite-mediche error:', e?.response?.data || e)
    const errs = e?.response?.data?.errors || {}
    const msgs = Object.entries(errs).map(([k,v]) => `${k}: ${Array.isArray(v)?v[0]:v}`).join('\n')
    toast.error(`Errore salvataggio:\n${msgs || 'Verifica i campi'}`)
  }
}

const destroy = async (id) => {
  if (!confirm('Eliminare questa visita?')) return
  try {
    await axios.delete(`/visite-mediche/${encodeURIComponent(id)}`)
    toast.success('Visita eliminata')
    fetchVisite()
  } catch (e) {
    toast.error('Errore durante l’eliminazione')
    console.error('DELETE /visite-mediche error:', e?.response?.data || e)
  }
}
</script>

<template>
  <div class="p-4 bg-white rounded shadow" @keydown.enter.prevent>
    <table class="w-full text-sm border">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 border">Id</th>
          <th class="p-2 border">Utente Modifica</th>
          <th class="p-2 border">Data Modifica</th>
          <th class="p-2 border">Data Visita</th>
          <th class="p-2 border">Data Scadenza</th>
          <th class="p-2 border">Stato</th>
          <th class="p-2 border">Giorni residui</th>
          <th class="p-2 border">CodCliente</th>
          <th class="p-2 border">Azioni</th>
        </tr>
      </thead>

      <tbody>
        <tr v-if="isLoading">
          <td colspan="9" class="text-center py-4">Caricamento...</td>
        </tr>

        <tr v-for="row in visite" :key="row._pk">
          <!-- Id -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ row.IdVisita }}</div>
            <div v-else>
              <input v-model="inEditing.IdVisita" class="w-full border rounded bg-gray-100" readonly />
            </div>
          </td>

          <!-- UtenteMod -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ row.UtenteMod }}</div>
            <div v-else>
              <input v-model="inEditing.UtenteMod" class="w-full border rounded bg-gray-100" readonly />
            </div>
          </td>

          <!-- DataModifica (sola lettura) -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ formatDate(row.DataModifica) }}</div>
            <div v-else>
              <input :value="formatDate(inEditing.DataModifica)" class="w-full border rounded bg-gray-100" readonly />
            </div>
          </td>

          <!-- DataVisita -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ formatDate(row.DataVisita) }}</div>
            <div v-else>
              <FlatPickr
                :model-value="inEditing.DataVisita ?? null"
                @update:modelValue="val => inEditing.DataVisita = val"
                :config="{ locale: Italian, altInput: true, altFormat: 'd/m/Y', dateFormat: 'Y-m-d' }"
                class="w-full"
              />
              <p v-if="errorsUpdate.DataVisita" class="text-red-600 text-xs mt-1">{{ errorsUpdate.DataVisita }}</p>
            </div>
          </td>

          <!-- DataScadenza -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ formatDate(row.DataScadenza) }}</div>
            <div v-else>
              <FlatPickr
                :model-value="inEditing.DataScadenza ?? null"
                @update:modelValue="val => inEditing.DataScadenza = val"
                :config="{ locale: Italian, altInput: true, altFormat: 'd/m/Y', dateFormat: 'Y-m-d' }"
                class="w-full"
              />
              <p v-if="errorsUpdate.DataScadenza" class="text-red-600 text-xs mt-1">{{ errorsUpdate.DataScadenza }}</p>
            </div>
          </td>

          <!-- Stato calcolato -->
          <td class="border p-1">
            <template v-if="isEditing(row)">
              <input :value="getStatoVisita(inEditing.DataScadenza)" class="w-full border rounded bg-gray-100" readonly />
            </template>
            <template v-else>
              {{ getStatoVisita(row.DataScadenza) }}
            </template>
          </td>

          <!-- Giorni residui -->
          <td class="border p-1 text-center">
            <template v-if="isEditing(row)">
              <input :value="giorniResidui(inEditing.DataScadenza) ?? ''" class="w-full border rounded bg-gray-100 text-center" readonly />
            </template>
            <template v-else>
              {{ giorniResidui(row.DataScadenza) ?? '' }}
            </template>
          </td>

          <!-- CodCliente -->
          <td class="border p-1">
            <div v-if="!isEditing(row)">{{ row.CodCliente }}</div>
            <div v-else>
              <input v-model="inEditing.CodCliente" class="w-full border rounded bg-gray-100" readonly />
              <p v-if="errorsUpdate.CodCliente" class="text-red-600 text-xs mt-1">{{ errorsUpdate.CodCliente }}</p>
            </div>
          </td>

          <!-- Azioni -->
          <td class="p-1 border text-left">
            <div class="flex items-center gap-2">
              <button type="button" @click="edit(row)" class="text-blue-600 font-bold">✏️ Modifica</button>

              <button
                v-if="page.props.auth.user.profilo === 'admin'"
                type="button"
                @click="destroy(rowId(row))"
                class="text-red-600 font-bold"
              >
                🗑️ Cancella
              </button>

              <button
                v-if="isEditing(row)"
                type="button"
                @click="update(rowId(row))"
                class="text-green-600 font-bold"
              >
                💾 Salva
              </button>

              <button
                v-if="isEditing(row)"
                type="button"
                @click="cancelEdit"
                class="text-gray-600 font-bold"
              >
                ↩️ Annulla
              </button>
            </div>
          </td>
        </tr>

        <!-- Riga inserimento -->
        <tr class="bg-gray-50">
          <td class="p-1 border">
            <input v-model="nuovaVisita.IdVisita" class="w-full border rounded bg-gray-100" readonly />
          </td>

          <td class="p-1 border">
            <input v-model="nuovaVisita.UtenteMod" class="w-full border rounded bg-gray-100" readonly />
          </td>

          <td class="p-1 border">
            <input class="w-full border rounded bg-gray-100" readonly placeholder="(auto dal sistema)" />
          </td>

          <td class="p-1 border">
            <FlatPickr
              :model-value="nuovaVisita.DataVisita ?? null"
              @update:modelValue="val => nuovaVisita.DataVisita = val"
              :config="{ locale: Italian, altInput: true, altFormat: 'd/m/Y', dateFormat: 'Y-m-d' }"
              class="w-full"
            />
            <p v-if="errors.DataVisita" class="text-red-600 text-xs mt-1">{{ errors.DataVisita }}</p>
          </td>

          <td class="p-1 border">
            <FlatPickr
              :model-value="nuovaVisita.DataScadenza ?? null"
              @update:modelValue="val => nuovaVisita.DataScadenza = val"
              :config="{ locale: Italian, altInput: true, altFormat: 'd/m/Y', dateFormat: 'Y-m-d' }"
              class="w-full"
            />
            <p v-if="errors.DataScadenza" class="text-red-600 text-xs mt-1">{{ errors.DataScadenza }}</p>
          </td>

          <td class="p-1 border">
            <input :value="getStatoVisita(nuovaVisita.DataScadenza)" class="w-full border rounded bg-gray-100" readonly />
          </td>

          <td class="p-1 border text-center">
            <input :value="giorniResidui(nuovaVisita.DataScadenza) ?? ''" class="w-full border rounded bg-gray-100 text-center" readonly />
          </td>

          <td class="p-1 border">
            <input v-model="nuovaVisita.CodCliente" class="w-full border rounded bg-gray-100" readonly />
            <p v-if="errors.CodCliente" class="text-red-600 text-xs mt-1">{{ errors.CodCliente }}</p>
          </td>

          <td class="p-1 border text-center">
            <button type="button" @click="addVisita" class="text-green-700 font-bold">➕</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
