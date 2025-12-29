<script setup>
import Form from "./Form.vue";

const props = defineProps({
    ordine: { type: Object, required: true },
});
</script>

<template>
    <Form :ordine="props.ordine" mode="edit" />
</template>
