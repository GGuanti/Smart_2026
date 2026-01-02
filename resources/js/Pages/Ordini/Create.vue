<!-- resources/js/Pages/Ordini/Create.vue -->
<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import Form from "@/Pages/Ordini/Form.vue";

const props = defineProps({
  nextNordine: { type: Number, default: null },
});
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Nuovo Ordine" />
    <Form :nextNordine="props.nextNordine" mode="create" />
  </AuthenticatedLayout>
</template>
