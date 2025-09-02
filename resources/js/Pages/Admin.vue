<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Amministrazione" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">Area Amministrazione</h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
          <div class="border-b border-gray-200 bg-white p-6">
            <h1 class="text-2xl font-bold text-red-600 mb-4">Benvenuto, Amministratore!</h1>
            <p class="text-gray-700">
              Questa è un'area riservata agli utenti con ruolo <strong>admin</strong>.
              Da qui potrai gestire utenti, ruoli, permessi, o qualsiasi funzionalità amministrativa.
            </p>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
