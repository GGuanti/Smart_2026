<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import { Italian } from 'flatpickr/dist/l10n/it.js';
import { Trash2, Save, X } from 'lucide-vue-next'
import { FileText } from 'lucide-vue-next'

const props = defineProps({
    appointment: Object,
    clients: Array,
});

const form = useForm({
    title: props.appointment.title,
    description: props.appointment.description,
    DataInizio: new Date(props.appointment.DataInizio).toISOString().slice(0, 16),
    DataFine: props.appointment.DataFine
        ? new Date(props.appointment.DataFine).toISOString().slice(0, 16)
        : '',
    status: props.appointment.status,
});

const formatDateForMySQL = (value) => {
    if (!value) return null;
    const date = new Date(value);
    return date.toISOString().slice(0, 19).replace('T', ' ');
};

const submit = () => {
    form.DataInizio = formatDateForMySQL(form.DataInizio);
    form.DataFine = formatDateForMySQL(form.DataFine);

    form.put(route('appointments.update', props.appointment.id), {
        onSuccess: () => {
            router.visit(route('appointments.calendar'));
        },
        onError: () => {
            alert('There was an error updating the appointment.');
        },
    });
};
const deleteAppointment = () => {
    if (confirm('Sei sicuro di voler eliminare questo appuntamento?')) {
        router.delete(route('appointments.destroy', props.appointment.id), {
            onSuccess: () => {
                window.location.href = route('appointments.calendar');
            },
            onError: () => {
                alert('Errore durante l\'eliminazione.');
            },
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
                    <h2 class="text-2xl font-semibold mb-6">Modifica Appuntamento: {{ appointment.title }}</h2>

                    <form @submit.prevent="submit">
 <!-- Titolo -->
<div class="mb-6">
    <label for="title" class="block text-sm font-semibold text-gray-800">Titolo</label>
    <input
        v-model="form.title"
        id="title"
        type="text"
        class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
        placeholder="Inserisci il titolo dell'appuntamento"
    />
    <p v-if="form.errors.title" class="mt-2 text-sm text-red-600">{{ form.errors.title }}</p>
</div>

<!-- Date e Stato -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

    <!-- Data Inizio -->
    <div>
        <label for="DataInizio" class="block text-sm font-semibold text-gray-800">Data Inizio</label>
        <flat-pickr
            v-model="form.DataInizio"
            :config="{
                enableTime: true,
                altInput: true,
                altFormat: 'd/m/Y H:i',
                dateFormat: 'Y-m-d H:i:s',
                locale: Italian,
                allowInput: true
            }"
            class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
        />
        <p v-if="form.errors.DataInizio" class="mt-2 text-sm text-red-600">{{ form.errors.DataInizio }}</p>
    </div>

    <!-- Data Fine -->
    <div>
        <label for="DataFine" class="block text-sm font-semibold text-gray-800">Data Fine</label>
        <flat-pickr
            v-model="form.DataFine"
            :config="{
                enableTime: true,
                altInput: true,
                altFormat: 'd/m/Y H:i',
                dateFormat: 'Y-m-d H:i:s',
                locale: Italian,
                allowInput: true,
                minDate: form.DataInizio
            }"
            class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
        />
        <p v-if="form.errors.DataFine" class="mt-2 text-sm text-red-600">{{ form.errors.DataFine }}</p>
    </div>

    <!-- Stato -->
    <div>
        <label for="status" class="block text-sm font-semibold text-gray-800">Stato</label>
        <select
            v-model="form.status"
            id="status"
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

<!-- Pulsanti -->
<div class="flex flex-col md:flex-row justify-end items-stretch md:items-center gap-4 mt-6">
    <!-- Annulla -->
    <Link
        :href="route('appointments.calendar')"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300"
    >
        <X class="w-4 h-4" />
        Annulla
    </Link>

    <!-- Salva -->
    <button
        type="submit"
        :disabled="form.processing"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
    >
        <Save class="w-4 h-4" />
        Salva Modifiche
    </button>

    <!-- Elimina -->
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
