<script setup>
import { computed } from "vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";

const props = defineProps({
  mode: String,           // "create" | "edit"
  accessorio: Object,     // null | record
});

const page = usePage();

const isEdit = computed(() => props.mode === "edit" && props.accessorio?.id);

const form = useForm({
  cod_art: props.accessorio?.cod_art ?? "",
  des_accessori: props.accessorio?.des_accessori ?? "",
  codice_metodo: props.accessorio?.codice_metodo ?? "",
  importo: props.accessorio?.importo ?? 0,
  vis_dim: !!props.accessorio?.vis_dim,
  nascondi: !!props.accessorio?.nascondi,
});

function submit() {
  if (isEdit.value) {
    form.put(route("accessori.update", props.accessorio.id));
  } else {
    form.post(route("accessori.store"));
  }
}
</script>

<template>
  <Head :title="isEdit ? 'Modifica Accessorio' : 'Nuovo Accessorio'" />

  <div class="p-6 space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">
        {{ isEdit ? "Modifica Accessorio" : "Nuovo Accessorio" }}
      </h1>

      <Link class="px-3 py-2 rounded-lg border" :href="route('accessori.index')">
        Torna elenco
      </Link>
    </div>

    <div class="rounded-xl border bg-white p-4">
      <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <div class="md:col-span-3">
          <label class="block text-sm font-medium">CodArt</label>
          <input v-model="form.cod_art" class="mt-1 w-full rounded-lg border-slate-300" />
          <div v-if="form.errors.cod_art" class="text-sm text-red-600 mt-1">{{ form.errors.cod_art }}</div>
        </div>

        <div class="md:col-span-7">
          <label class="block text-sm font-medium">Descrizione</label>
          <input v-model="form.des_accessori" class="mt-1 w-full rounded-lg border-slate-300" />
          <div v-if="form.errors.des_accessori" class="text-sm text-red-600 mt-1">{{ form.errors.des_accessori }}</div>
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium">Importo</label>
          <input v-model="form.importo" type="number" step="0.01" class="mt-1 w-full rounded-lg border-slate-300" />
          <div v-if="form.errors.importo" class="text-sm text-red-600 mt-1">{{ form.errors.importo }}</div>
        </div>

        <div class="md:col-span-4">
          <label class="block text-sm font-medium">Codice Metodo</label>
          <input v-model="form.codice_metodo" class="mt-1 w-full rounded-lg border-slate-300" />
          <div v-if="form.errors.codice_metodo" class="text-sm text-red-600 mt-1">{{ form.errors.codice_metodo }}</div>
        </div>

        <div class="md:col-span-8 flex items-center gap-6 pt-6">
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" v-model="form.vis_dim" />
            <span class="text-sm">VisDim</span>
          </label>

          <label class="inline-flex items-center gap-2">
            <input type="checkbox" v-model="form.nascondi" />
            <span class="text-sm">Nascondi</span>
          </label>
        </div>

        <div class="md:col-span-12 flex gap-2 justify-end pt-2">
          <button type="submit" class="px-4 py-2 rounded-lg border" :disabled="form.processing">
            {{ isEdit ? "Salva modifiche" : "Crea accessorio" }}
          </button>
        </div>
      </form>
    </div>

    <div v-if="page.props.flash?.success" class="text-sm text-slate-700">
      {{ page.props.flash.success }}
    </div>
  </div>
</template>
