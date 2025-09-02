<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { TabulatorFull as Tabulator } from 'tabulator-tables';

const gridKey = 'articoli';
const props = defineProps({ articoli: Array });

const form = ref({ id: null, nome: '', descrizione: '', prezzo: '' });
const tableRef = ref(null);
let tableInstance = null;

const dynamicFilters = ref([{ field: 'nome', type: 'like', value: '' }]);

const availableFields = [
  { label: 'Nome', value: 'nome' },
  { label: 'Descrizione', value: 'descrizione' },
  { label: 'Prezzo', value: 'prezzo' }
];

const operatorsByType = {
  string: ['like', '='],
  number: ['=', '>=', '<=']
};

const getFieldType = (field) => ['prezzo'].includes(field) ? 'number' : 'string';
const addFilterRow = () => dynamicFilters.value.push({ field: 'nome', type: 'like', value: '' });
const removeFilterRow = index => dynamicFilters.value.splice(index, 1);

const applyDynamicFilters = () => {
  if (!tableInstance) return;
  const filters = dynamicFilters.value.filter(f => f.value !== '').map(f => ({
    field: f.field,
    type: f.type,
    value: getFieldType(f.field) === 'number' ? parseFloat(f.value) : f.value
  }));
  tableInstance.setFilter(filters);
};

const resetColumnLayout = async () => {
  await fetch('/user/columns', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    },
    body: JSON.stringify({ key: gridKey, columns: [] })
  });
  location.reload();
};

const saveUserColumns = () => {
  if (!tableInstance) return;
  const layout = tableInstance.getColumnLayout();
  fetch('/user/columns', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    },
    body: JSON.stringify({ key: gridKey, columns: layout })
  });
};

const resetForm = () => form.value = { id: null, nome: '', descrizione: '', prezzo: '' };

const submit = () => {
  if (!form.value.nome || form.value.prezzo === '') return alert('Nome e Prezzo sono obbligatori');
  const method = form.value.id ? 'put' : 'post';
  const url = form.value.id ? `/articoli/${form.value.id}` : '/articoli';
  router[method](url, form.value, { onSuccess: () => resetForm() });
};

const edit = data => form.value = { ...data };
const del = id => confirm('Sicuro di voler eliminare?') && router.delete(`/articoli/${id}`);

onMounted(async () => {
  await nextTick();

  const res = await fetch(`/user/columns?key=${gridKey}`);
  const savedLayout = await res.json();

  const actionsColumn = {
    title: "Azioni",
    field: "azioni",
    width: 130,
    formatter: () => `
      <button class='edit-btn bg-yellow-500 text-white px-2 py-1 mr-2'>✏️</button>
      <button class='bg-red-500 text-white px-2 py-1'>🗑️</button>`,
    cellClick: (e, cell) => {
      const row = cell.getRow().getData();
      if (e.target.textContent.includes('✏️')) edit(row);
      if (e.target.textContent.includes('🗑️')) del(row.id);
    }
  };

  const defaultColumns = [
    { title: "ID", field: "id", width: 60 },
    { title: "Nome", field: "nome", sorter: "string" },
    { title: "Descrizione", field: "descrizione" },
    { title: "Prezzo", field: "prezzo", formatter: "money" },
    actionsColumn,
  ];

  const finalColumns = Array.isArray(savedLayout) && savedLayout.length > 0
    ? [...savedLayout.filter(col => col.field !== 'azioni'), actionsColumn]
    : defaultColumns;

  tableInstance = new Tabulator(tableRef.value, {
    height: "600px",
    data: props.articoli,
    layout: "fitColumns",
    reactiveData: true,
    movableColumns: true,
    resizableColumns: true,
    columns: finalColumns,
    columnMoved: saveUserColumns,
    columnResized: saveUserColumns,
    columnVisibilityChanged: saveUserColumns,
  });
});

watch(() => props.articoli, (newData) => {
  if (tableInstance) {
    tableInstance.replaceData(newData);
    applyDynamicFilters();
  }
});
</script>

<template>
  <AuthenticatedLayout>
    <div>
      <h1 class="text-2xl font-bold mb-4">📦 Elenco Articoli</h1>

      <div class="mb-6 border p-4 rounded bg-gray-100">
        <h2 class="font-semibold mb-2">Filtri avanzati</h2>
        <div v-for="(filter, index) in dynamicFilters" :key="index" class="flex gap-2 mb-2 items-center">
          <select v-model="filter.field" class="border p-2" @change="filter.type = operatorsByType[getFieldType(filter.field)][0]">
            <option v-for="field in availableFields" :value="field.value">{{ field.label }}</option>
          </select>
          <select v-model="filter.type" class="border p-2">
            <option v-for="op in operatorsByType[getFieldType(filter.field)]" :value="op">{{ op }}</option>
          </select>
          <input v-model="filter.value" class="border p-2" :type="getFieldType(filter.field) === 'number' ? 'number' : 'text'" />
          <button @click="removeFilterRow(index)" class="bg-red-500 text-white px-2 py-1 rounded">🗑️</button>
        </div>

        <button @click="addFilterRow" class="bg-blue-500 text-white px-3 py-1 rounded">➕ Aggiungi filtro</button>
        <button @click="applyDynamicFilters" class="bg-green-600 text-white px-3 py-1 rounded ml-2">🔍 Applica filtri</button>
        <button @click="saveUserColumns" class="bg-orange-500 text-white px-3 py-1 rounded ml-2">💾 Posizione colonne</button>
        <button @click="resetColumnLayout" class="bg-gray-500 text-white px-3 py-1 rounded ml-2">Reset colonne</button>
      </div>

      <form @submit.prevent="submit" class="mb-4 flex gap-2 flex-wrap">
        <input v-model="form.nome" placeholder="Nome" class="border p-2" />
        <input v-model="form.descrizione" placeholder="Descrizione" class="border p-2" />
        <input v-model.number="form.prezzo" placeholder="Prezzo" class="border p-2" type="number" step="0.01" />
        <button class="bg-blue-500 text-white px-4 py-2 rounded">
          {{ form.id ? 'Modifica' : 'Aggiungi' }}
        </button>
      </form>

      <div ref="tableRef" class="mt-6" style="height: 400px;"></div>
    </div>
  </AuthenticatedLayout>
</template>

<style>
@import "tabulator-tables/dist/css/tabulator.min.css";
</style>
