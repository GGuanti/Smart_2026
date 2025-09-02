<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ReportGenerator from '@/Components/ReportGenerator.vue'
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Genera Report" />
  <AuthenticatedLayout>
    <div class="w-screen h-screen p-0 m-0">
      <ReportGenerator />
    </div>
  </AuthenticatedLayout>
</template>

