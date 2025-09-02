<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import flatPickr from 'vue-flatpickr-component'
import 'flatpickr/dist/flatpickr.css'
import { Italian } from 'flatpickr/dist/l10n/it.js';

const props = defineProps({
    DataInizio: String,
    DataFine: String,
});

const form = useForm({
  title: '',
  description: '',
  DataInizio: props.DataInizio || '',
  DataFine: props.DataFine || '',
  status: 'scheduled',
});


const submit = () => {
    form.post(route('appointments.store'));
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Create Appointment" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h2 class="text-2xl font-semibold mb-6">Create New Appointment</h2>

                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                                <input v-model="form.title" id="title" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p v-if="form.errors.title" class="mt-2 text-sm text-red-600">{{ form.errors.title }}</p>
                            </div>

                            <div>
                                <label for="DataInizio" class="block text-sm font-medium text-gray-700">Data Inizio</label>
                                <flat-pickr
                                v-model="form.DataInizio"
                                :config="{
                                    enableTime: true,
                                    altInput: true,
                                    altFormat: 'd/m/Y H:i',
                                    dateFormat: 'Y-m-d H:i:S', // <-- inviato a Laravel
                                    locale: Italian
                                }"
                                />

                                <p v-if="form.errors.DataInizio" class="mt-2 text-sm text-red-600">{{ form.errors.DataInizio }}</p>
                            </div>
                                      <div>
                                <label for="DataFine" class="block text-sm font-medium text-gray-700">Data Fine</label>
                                <flat-pickr
                                v-model="form.DataFine"
                                :config="{
                                    enableTime: true,
                                    altInput: true,
                                    altFormat: 'd/m/Y H:i',
                                    dateFormat: 'Y-m-d H:i:S', // <-- inviato a Laravel
                                    locale: Italian
                                }"
                                />

                                <p v-if="form.errors.DataFine" class="mt-2 text-sm text-red-600">{{ form.errors.DataFine }}</p>
                            </div>



                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700"> Stato Attività </label>
                                <select v-model="form.status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="scheduled">Scheduled</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <p v-if="form.errors.status" class="mt-2 text-sm text-red-600">{{ form.errors.status }}</p>
                            </div>

                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea v-model="form.description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                <p v-if="form.errors.description" class="mt-2 text-sm text-red-600">{{ form.errors.description }}</p>
                            </div>
                        </div>

                        <div class="flex justify-end mt-6 space-x-4">
                            <Link :href="route('appointments.calendar')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</Link>
                            <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50">
                                Save Appointment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
