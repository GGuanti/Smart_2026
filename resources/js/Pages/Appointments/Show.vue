<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    appointment: Object,
});
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="appointment.title" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-2xl font-semibold">{{ appointment.title }}</h2>
                            <p class="text-gray-500">With {{ appointment.client.name }}</p>
                        </div>
                        <div class="flex space-x-2">
                            <Link :href="route('appointments.edit', appointment.id)" class="px-4 py-2 bg-indigo-500 text-white rounded hover:bg-indigo-600">Edit</Link>
                            <Link :href="route('appointments.calendar')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Back</Link>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Appointment Details</h3>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Client</p>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <Link :href="route('clients.show', appointment.client.id)" class="text-blue-600 hover:underline">
                                            {{ appointment.client.name }}
                                        </Link>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Status</p>
                                    <span :class="{
                                        'bg-green-100 text-green-800': appointment.status === 'completed',
                                        'bg-red-100 text-red-800': appointment.status === 'cancelled',
                                        'bg-blue-100 text-blue-800': appointment.status === 'scheduled',
                                    }" class="text-xs px-2 py-1 rounded-full">
                                        {{ appointment.status }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Start Time</p>
                                    <p class="mt-1 text-sm text-gray-900">{{ new Date(appointment.start_time).toLocaleString() }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">End Time</p>
                                    <p class="mt-1 text-sm text-gray-900">{{ new Date(appointment.end_time).toLocaleString() }}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Description</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-900 whitespace-pre-line">{{ appointment.description || 'No description available' }}</p>
                            </div>

                            <div class="mt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Client Information</h3>
                                <div class="space-y-2">
                                    <p class="text-sm">
                                        <span class="font-medium text-gray-500">Email:</span>
                                        {{ appointment.client.email }}
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-medium text-gray-500">Phone:</span>
                                        {{ appointment.client.phone || 'N/A' }}
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-medium text-gray-500">Company:</span>
                                        {{ appointment.client.company_name || 'N/A' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
