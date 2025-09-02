<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
const props = defineProps({
  totalUsers: Number
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
       <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 text-lg">
            <h2 class="text-2xl font-semibold mb-4">👥 Riepilogo Utenti</h2>
            <p>Numero totale di utenti registrati: <strong>{{ totalUsers }}</strong></p>
          </div>
        </div>
      </div>
    </div>
    </AuthenticatedLayout>
</template>
