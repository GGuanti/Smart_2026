<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import { Italian } from 'flatpickr/dist/l10n/it.js';
import { Trash2, Save, X } from 'lucide-vue-next';
import { computed } from 'vue';
import { useToast } from "vue-toastification";
const toast = useToast();
const focusNext = (event) => {
    event.preventDefault();

    const form = event.target.form;
    if (!form) return;

    const focusable = Array.from(
        form.querySelectorAll(
            'input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])'
        )
    ).filter(el => el.offsetParent !== null);

    const index = focusable.indexOf(event.target);
    if (index > -1 && focusable[index + 1]) {
        focusable[index + 1].focus();
    }
};

const magazzinoIcon = computed(() => {
    switch (form.StatoMagazzino) {
        case 'In arrivo':
            return '🚚';
        case 'Arrivato':
            return '✅';
        case 'In ritardo':
            return '⏳';
        default:
            return '⏳'; // Magazzino
    }
});
const props = defineProps({
    appointment: Object,
    clients: Array,
});

const toLocalInput = (value) => {
    if (!value) return '';
    const d = new Date(value);
    // yyyy-mm-ddThh:mm (HTML datetime-local style)
    return d.toISOString().slice(0, 16);
};

const formatDateForMySQL = (value) => {
    if (!value) return null;
    const date = new Date(value);
    return date.toISOString().slice(0, 19).replace('T', ' ');
};

const form = useForm({
    // Campi esistenti
    title: props.appointment.title ?? '',
    description: props.appointment.description ?? '',
    DataInizio: props.appointment.DataInizio ? String(props.appointment.DataInizio).substring(0, 10) : '',
    DataFine: props.appointment.DataFine ? String(props.appointment.DataFine).substring(0, 10) : '',
    DataConferma: props.appointment.DataConferma ? String(props.appointment.DataConferma).substring(0, 10) : '',
    DataConsegna: props.appointment.DataConsegna ? String(props.appointment.DataConsegna).substring(0, 10) : '',

    status: props.appointment.status ?? 'scheduled',
    StatoMagazzino: props.appointment.StatoMagazzino ?? 'Magazzino',

    // Nuovi campi
    Nordine: props.appointment.Nordine ?? '',

Colore: props.appointment.Colore ?? '',
    Riferimento: props.appointment.Riferimento ?? '',
    Pezzi: props.appointment.Pezzi ?? '',

    // Si/No (assicurati boolean)
    T: !!props.appointment.T,
    Tz: !!props.appointment.Tz,
    TL: !!props.appointment.TL,
    A: !!props.appointment.A,
    C: !!props.appointment.C,
    L: !!props.appointment.L,

    Annotazioni: props.appointment.Annotazioni ?? '',
});


const submit = () => {
    form.DataInizio = formatDateForMySQL(form.DataInizio);
    form.DataFine = formatDateForMySQL(form.DataFine);
    form.DataConferma = formatDateForMySQL(form.DataConferma);
    form.DataConsegna = formatDateForMySQL(form.DataConsegna);

    form.put(route('appointments.update', props.appointment.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.success("✅ Dati salvati con successo!",{position: "top-left"})
        },
        onError: () =>  toast.error('Errore nel salvataggio', {position: "top-left"}),
    });
};
const deleteAppointment = () => {
    if (confirm('Sei sicuro di voler eliminare questo appuntamento?')) {
        router.delete(route('appointments.destroy', props.appointment.id), {
            onSuccess: () => (window.location.href = route('appointments.calendar')),
            onError: () =>  toast.error("Errore durante l'eliminazione.",{position: "top-left"}),
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Appuntamento" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <!-- Titolo -->
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <!-- Titolo -->

<h2 class="text-2xl font-semibold flex items-center gap-2">
    <span class="text-2xl">{{ magazzinoIcon }}</span>
    Dettaglio Ordine: {{ appointment.title }}
</h2>
    <!-- Stato Magazzino -->
    <div class="w-full md:w-56">
        <label class="block text-xs font-semibold text-gray-600 mb-1">
            Stato Magazzino
        </label>

        <select
            v-model="form.StatoMagazzino"
            @keydown.enter="focusNext"
            class="block w-full rounded-lg border border-gray-300 shadow-sm
                   focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
        >
            <option value="Magazzino">Magazzino</option>
            <option value="In arrivo">In arrivo</option>
            <option value="Arrivato">Arrivato</option>
            <option value="In ritardo">In ritardo</option>
        </select>

        <p v-if="form.errors.StatoMagazzino"
           class="mt-1 text-xs text-red-600">
            {{ form.errors.StatoMagazzino }}
        </p>

        <!-- Badge colore (facoltativo ma consigliato) -->
        <span
            class="inline-block mt-2 px-2 py-1 rounded text-xs font-semibold"
            :class="{
                'bg-gray-200 text-gray-800': form.StatoMagazzino === 'Magazzino',
                'bg-yellow-200 text-yellow-800': form.StatoMagazzino === 'In arrivo',
                'bg-green-200 text-green-800': form.StatoMagazzino === 'Arrivato',
                'bg-red-200 text-red-800': form.StatoMagazzino === 'In ritardo',
            }"
        >
            {{ form.StatoMagazzino }}
        </span>
    </div>
</div>

                        <!-- Dati ordine / riferimento -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800">N° Ordine</label>
                                <input
                                    v-model="form.Nordine"
                                    @keydown.enter="focusNext"
                                     type="text"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                    placeholder="Es. 12345"
                                />
                                <p v-if="form.errors.Nordine" class="mt-2 text-sm text-red-600">{{ form.errors.Nordine }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-800">Riferimento</label>
                                <input
                                    v-model="form.Riferimento"
                                     @keydown.enter="focusNext"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                    placeholder="Es. Commessa / Referente"
                                />
                                <p v-if="form.errors.Riferimento" class="mt-2 text-sm text-red-600">{{ form.errors.Riferimento }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-800">Colore</label>
                                <input
                                    v-model="form.Colore"
                                     @keydown.enter="focusNext"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                    placeholder="Es. Rosso / RAL..."
                                />
                                <p v-if="form.errors.Colore" class="mt-2 text-sm text-red-600">{{ form.errors.Colore }}</p>
                            </div>
                        </div>

                        <!-- Date principali + stato -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div @keydown.enter="focusNext">
                                <label class="block text-sm font-semibold text-gray-800">Data Inizio</label>
                                <flat-pickr
                                    v-model="form.DataInizio"

                                    :config="{
                                        enableTime: false,
                                        altInput: true,
                                        altFormat: 'd/m/Y',
                                        dateFormat: 'Y-m-d',
                                        locale: Italian,
                                        allowInput: true
                                    }"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                />
                                <p v-if="form.errors.DataInizio" class="mt-2 text-sm text-red-600">{{ form.errors.DataInizio }}</p>
                            </div>

                            <div @keydown.enter="focusNext">
                                <label class="block text-sm font-semibold text-gray-800">Data Fine</label>
                                <flat-pickr
                                    v-model="form.DataFine"
                                    :config="{
                                        enableTime: false,
                                        altInput: true,
                                        altFormat: 'd/m/Y',
                                        dateFormat: 'Y-m-d',
                                        locale: Italian,
                                        allowInput: true,
                                        minDate: form.DataInizio
                                    }"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                />
                                <p v-if="form.errors.DataFine" class="mt-2 text-sm text-red-600">{{ form.errors.DataFine }}</p>
                            </div>

                            <div @keydown.enter="focusNext">
                                <label class="block text-sm font-semibold text-gray-800">Stato</label>
                                <select
                                    v-model="form.status"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                >
                                    <option value="scheduled">In programma</option>
                                    <option value="active">Attivi</option>
                                    <option value="completed">Completato</option>
                                    <option value="cancelled">Annullato</option>
                                </select>
                                <p v-if="form.errors.status" class="mt-2 text-sm text-red-600">{{ form.errors.status }}</p>
                            </div>
                        </div>

                        <!-- Date conferma / consegna + pezzi -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div @keydown.enter="focusNext">
                                <label class="block text-sm font-semibold text-gray-800">Data Conferma</label>
                                <flat-pickr
                                    v-model="form.DataConferma"
                                    :config="{
                                        enableTime: false,
                                        altInput: true,
                                        altFormat: 'd/m/Y',
                                        dateFormat: 'Y-m-d',
                                        locale: Italian,
                                        allowInput: true
                                    }"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                />
                                <p v-if="form.errors.DataConferma" class="mt-2 text-sm text-red-600">{{ form.errors.DataConferma }}</p>
                            </div>

                            <div @keydown.enter="focusNext">
                                <label class="block text-sm font-semibold text-gray-800">Data Consegna</label>
                                <flat-pickr
                                    v-model="form.DataConsegna"
                                    :config="{
                                        enableTime: false,
                                        altInput: true,
                                        altFormat: 'd/m/Y',
                                        dateFormat: 'Y-m-d',
                                        locale: Italian,
                                        allowInput: true,
                                        minDate: form.DataConferma
                                    }"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                />
                                <p v-if="form.errors.DataConsegna" class="mt-2 text-sm text-red-600">{{ form.errors.DataConsegna }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-800">Pezzi</label>
                                <input
                                    v-model="form.Pezzi"
                                    type="number"
                                    min="0"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                    placeholder="0"
                                />
                                <p v-if="form.errors.Pezzi" class="mt-2 text-sm text-red-600">{{ form.errors.Pezzi }}</p>
                            </div>
                        </div>

                        <!-- Opzioni -->
<div class="mb-6">
  <label class="block text-sm font-semibold text-gray-800 mb-2">Opzioni</label>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-2">

    <!-- COLONNA 1 -->
    <div class="space-y-2">
      <div class="flex items-center gap-3">
        <span class="text-sm font-semibold text-gray-800">Taglio</span>
        <label class="flex items-center gap-2 border border-gray-200 rounded-md px-2 py-1">

          <input type="checkbox" v-model="form.T" class="rounded scale-90" />
          <span class="text-sm">T</span>
        </label>
      </div>

      <div class="flex items-center gap-3">
        <span class="text-sm font-semibold text-gray-800">Assemblaggio</span>
        <label class="flex items-center gap-2 border border-gray-200 rounded-md px-2 py-1">
          <input type="checkbox" v-model="form.A" class="rounded scale-90" />
          <span class="text-sm">A</span>
        </label>
      </div>
    </div>

    <!-- COLONNA 2 -->
    <div class="space-y-2">
      <div class="flex items-center gap-3">
        <span class="text-sm font-semibold text-gray-800">Taglio Zoccolo</span>
        <label class="flex items-center gap-2 border border-gray-200 rounded-md px-2 py-1">
          <input type="checkbox" v-model="form.Tz" class="rounded scale-90" />
          <span class="text-sm">Tz</span>
        </label>
      </div>

      <div class="flex items-center gap-3">
        <span class="text-sm font-semibold text-gray-800">Comandi</span>
        <label class="flex items-center gap-2 border border-gray-200 rounded-md px-2 py-1">
          <input type="checkbox" v-model="form.C" class="rounded scale-90" />
          <span class="text-sm">C</span>
        </label>
      </div>
    </div>

    <!-- COLONNA 3 -->
    <div class="space-y-2">
      <div class="flex items-center gap-3">
        <span class="text-sm font-semibold text-gray-800">Taglio Lamelle</span>
        <label class="flex items-center gap-2 border border-gray-200 rounded-md px-2 py-1">
          <input type="checkbox" v-model="form.TL" class="rounded scale-90" />
          <span class="text-sm">TL</span>
        </label>
      </div>

      <div class="flex items-center gap-3">
        <span class="text-sm font-semibold text-gray-800">Montaggio Lamelle</span>
        <label class="flex items-center gap-2 border border-gray-200 rounded-md px-2 py-1">
          <input type="checkbox" v-model="form.L" class="rounded scale-90" />
          <span class="text-sm">L</span>
        </label>
      </div>
    </div>
  </div>
</div>
                        <!-- Descrizione -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-semibold text-gray-800">Descrizione</label>
                            <textarea
                                v-model="form.description"
                                id="description"
                                rows="4"
                                class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                placeholder="Dettagli dell'appuntamento"
                            ></textarea>
                            <p v-if="form.errors.description" class="mt-2 text-sm text-red-600">{{ form.errors.description }}</p>
                        </div>

                        <!-- Annotazioni -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-800">Annotazioni</label>
                            <textarea
                                v-model="form.Annotazioni"
                                rows="3"
                                class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                placeholder="Note interne..."
                            ></textarea>
                            <p v-if="form.errors.Annotazioni" class="mt-2 text-sm text-red-600">{{ form.errors.Annotazioni }}</p>
                        </div>

                        <!-- Pulsanti -->
                        <div class="flex flex-col md:flex-row justify-end items-stretch md:items-center gap-4 mt-6">
                            <Link
                                :href="route('appointments.calendar')"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300"
                            >
                                <X class="w-4 h-4" />
                                Chiudi
                            </Link>
                            <Link
                                :href="route('appointments.calendar')"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300"
                            >
                                <X class="w-4 h-4" />
                                Annulla
                            </Link>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                            >
                                <Save class="w-4 h-4" />
                                Salva Modifiche
                            </button>

                            <button
                                type="button"
                                @click="deleteAppointment"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
                            >
                                <Trash2 class="w-4 h-4" />
                                Elimina
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
