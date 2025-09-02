<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Head, useForm, usePage, Link } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import FlatPickr from 'vue-flatpickr-component'
import 'flatpickr/dist/flatpickr.css'
import { Italian } from 'flatpickr/dist/l10n/it.js'

const toast = useToast()
const page = usePage()

const props = defineProps({
  project: { type: Object, required: true },
  auth: { type: Object, default: () => ({ user: null }) }
})

const isAdmin = computed(() => props?.auth?.user?.profilo === 'admin')

const form = useForm({
  IdProgetto: props.project?.IdProgetto ?? '',
  RagioneSocialeCommittenti: props.project?.RagioneSocialeCommittenti ?? '',
  DesProgetto: props.project?.DesProgetto ?? '',
  DataInzProgetto: props.project?.DataInzProgetto ?? '',
  DataFineProgetto: props.project?.DataFineProgetto ?? '',
  DataPagamento: props.project?.DataPagamento ?? '',
  Note: props.project?.Note ?? ''
})

const fpOpts = {
  locale: Italian,
  dateFormat: 'Y-m-d',
  allowInput: true
}

const saving = ref(false)

const submit = () => {
  saving.value = true
  form.put(route('progetti.update', form.IdProgetto), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Progetto salvato ✅')
    },
    onError: () => {
      toast.error('Controlla i campi evidenziati')
    },
    onFinish: () => {
      saving.value = false
    }
  })
}

const destroyProject = () => {
  if (!isAdmin.value) return
  if (confirm('Eliminare definitivamente questo progetto?')) {
    form.delete(route('progetti.destroy', form.IdProgetto), {
      onSuccess: () => toast.success('Progetto eliminato 🗑️'),
      onError: () => toast.error('Impossibile eliminare il progetto')
    })
  }
}
</script>

<template>
  <Head :title="`Modifica Progetto #${form.IdProgetto}`" />

  <div class="max-w-5xl mx-auto p-4">
    <!-- Breadcrumb -->
    <nav class="text-sm mb-4 text-gray-600">
      <Link href="/dashboard" class="hover:underline">Dashboard</Link>
      <span class="mx-2">/</span>
      <Link href="/progetti" class="hover:underline">Progetti</Link>
      <span class="mx-2">/</span>
      <span class="font-semibold">Edit #{{ form.IdProgetto }}</span>
    </nav>

    <div class="bg-white rounded-2xl shadow p-6">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Modifica Progetto #{{ form.IdProgetto }}</h1>
        <div class="space-x-2">
          <Link
            href="/progetti"
            class="px-3 py-2 border rounded-lg hover:bg-gray-50"
          >Annulla</Link>

          <button
            v-if="isAdmin"
            type="button"
            @click="destroyProject"
            class="px-3 py-2 bg-red-600 text-white rounded-lg"
            title="Elimina progetto"
          >Elimina</button>

          <button
            type="button"
            @click="submit"
            :disabled="saving"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg disabled:opacity-60"
          >
            {{ saving ? 'Salvataggio…' : 'Salva' }}
          </button>
        </div>
      </div>

      <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Ragione Sociale -->
        <div class="col-span-2">
          <label class="block text-sm font-medium mb-1">Ragione Sociale Committenti</label>
          <input
            v-model.trim="form.RagioneSocialeCommittenti"
            type="text"
            class="w-full border rounded-lg p-2"
            :class="{'border-red-500': form.errors.RagioneSocialeCommittenti}"
            placeholder="Es. ACME S.p.A."
          />
          <p v-if="form.errors.RagioneSocialeCommittenti" class="text-red-600 text-xs mt-1">
            {{ form.errors.RagioneSocialeCommittenti }}
          </p>
        </div>

        <!-- Descrizione -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Descrizione Progetto</label>
          <textarea
            v-model.trim="form.DesProgetto"
            rows="4"
            class="w-full border rounded-lg p-2"
            :class="{'border-red-500': form.errors.DesProgetto}"
            placeholder="Breve descrizione/accordi…"
          />
          <p v-if="form.errors.DesProgetto" class="text-red-600 text-xs mt-1">
            {{ form.errors.DesProgetto }}
          </p>
        </div>

        <!-- Date -->
        <div>
          <label class="block text-sm font-medium mb-1">Data Inizio</label>
          <FlatPickr
            v-model="form.DataInzProgetto"
            :config="fpOpts"
            class="w-full border rounded-lg p-2"
            :class="{'border-red-500': form.errors.DataInzProgetto}"
          />
          <p v-if="form.errors.DataInzProgetto" class="text-red-600 text-xs mt-1">
            {{ form.errors.DataInzProgetto }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Data Fine</label>
          <FlatPickr
            v-model="form.DataFineProgetto"
            :config="fpOpts"
            class="w-full border rounded-lg p-2"
            :class="{'border-red-500': form.errors.DataFineProgetto}"
          />
          <p v-if="form.errors.DataFineProgetto" class="text-red-600 text-xs mt-1">
            {{ form.errors.DataFineProgetto }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Data Pagamento</label>
          <FlatPickr
            v-model="form.DataPagamento"
            :config="fpOpts"
            class="w-full border rounded-lg p-2"
            :class="{'border-red-500': form.errors.DataPagamento}"
          />
          <p v-if="form.errors.DataPagamento" class="text-red-600 text-xs mt-1">
            {{ form.errors.DataPagamento }}
          </p>
        </div>

        <!-- Note -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Note</label>
          <textarea
            v-model.trim="form.Note"
            rows="3"
            class="w-full border rounded-lg p-2"
            :class="{'border-red-500': form.errors.Note}"
            placeholder="Note interne…"
          />
          <p v-if="form.errors.Note" class="text-red-600 text-xs mt-1">
            {{ form.errors.Note }}
          </p>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
/* niente colori personalizzati per mantenerlo coerente col tema Tailwind del progetto */
</style>
