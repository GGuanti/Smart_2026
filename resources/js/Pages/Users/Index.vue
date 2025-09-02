<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { TabulatorFull as Tabulator } from 'tabulator-tables';

const props = defineProps({
  users: Array
});

const form = ref({ id: null, name: '', email: '', profilo: '' , password: ''});
const tableRef = ref(null);
let tableInstance = null;

// 🔍 Filtri dinamici
const dynamicFilters = ref([
  { field: 'name', type: 'like', value: '' }
]);

const availableFields = [
  { label: 'name', value: 'name' },
  { label: 'Email', value: 'email' },
  { label: 'profilo', value: 'profilo' },
  { label: 'password', value: 'password' }
];

const operatorsByType = {
  string: ['like', '='],
  number: ['=', '>=', '<=']
};

const getFieldType = (field) => {
  if (['profilo'].includes(field)) return 'number';
  return 'string';
};

const addFilterRow = () => {
  dynamicFilters.value.push({ field: 'name', type: 'like', value: '' });
};

const removeFilterRow = (index) => {
  dynamicFilters.value.splice(index, 1);
};

const applyDynamicFilters = () => {
  if (!tableInstance) return;

  const filters = dynamicFilters.value
    .filter(f => f.value !== '')
    .map(f => ({
      field: f.field,
      type: f.type,
      value: getFieldType(f.field) === 'number' ? parseFloat(f.value) : f.value
    }));

  tableInstance.setFilter(filters);
};

const resetColumnLayout = () => {
  localStorage.removeItem('TBl-users');
  window.location.reload();
};

const resetForm = () => {
  form.value = { id: null, name: '', email: '', profilo: '', password: '' };
};

const submit = () => {
  if (!form.value.name || form.value.profilo === '' || form.value.password === '') {
    alert('name e profilo sono obbligatori');
    return;
  }


  const method = form.value.id ? 'put' : 'post';
  const url = form.value.id ? `/users/${form.value.id}` : '/users';

  router[method](url, form.value, {
    onSuccess: () => resetForm()
  });
};

const edit = (data) => {
  form.value = { ...data };
};

const del = (id) => {
  if (confirm('Sicuro di voler eliminare?')) {
    router.delete(`/users/${id}`);
  }
};

onMounted(() => {
  tableInstance = new Tabulator(tableRef.value, {
    height: "600px",
    data: props.users,
    layout: "fitColumns",
    reactiveData: true,
    movableColumns: true,
    resizableColumns: true,
    persistence: {
      columns: true
    },
    persistenceID: 'TBl-users',
    persistenceMode: 'local',
    columns: [
      { title: "ID", field: "id", width: 60 },
      { title: "name", field: "name", sorter: "string" },
      { title: "email", field: "email" },
      { title: "profilo", field: "profilo", sorter: "string" },
      {
        title: "Azioni",
        field: "azioni",
        formatter: () => `
          <button class='edit-btn bg-yellow-500 text-white px-2 py-1 mr-2'>✏️</button>
          <button class='bg-red-500 text-white px-2 py-1'>🗑️</button>`,
        width: 130,
        cellClick: (e, cell) => {
          const row = cell.getRow().getData();
          if (e.target.textContent.includes('✏️')) edit(row);
          if (e.target.textContent.includes('🗑️')) del(row.id);
        }
      }
    ]
  });
});

watch(() => props.users, (newData) => {
  if (tableInstance) {
    tableInstance.replaceData(newData);
    applyDynamicFilters();
  }
});
</script>

<template>
  <AuthenticatedLayout>
    <div>
      <h1 class="text-2xl font-bold mb-4">Elenco users</h1>

      <!-- 🎯 Filtri dinamici -->
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

          <button @click="removeFilterRow(index)" class="bg-red-500 text-white px-2 py-1 rounded">🗑️ </button>
        </div>
        <button @click="addFilterRow" class="bg-blue-500 text-white px-3 py-1 rounded">➕ Aggiungi filtro</button>
        <button @click="applyDynamicFilters" class="bg-green-600 text-white px-3 py-1 rounded ml-2">🔍 Applica filtri</button>
        <button @click="resetColumnLayout" class="bg-gray-500 text-white px-3 py-1 rounded ml-2">Reset colonne</button>
      </div>




      <!-- 📝 Form inserimento/modifica -->
      <form @submit.prevent="submit" class="mb-4 flex gap-2 flex-wrap">
        <input v-model="form.name" placeholder="Nome" class="border p-2" />
        <input v-model="form.email" placeholder="email" class="border p-2" />
        <input v-model="form.password" placeholder="password" class="border p-2" />

<select v-model="form.profilo" class="border p-2">
  <option disabled value="">-- seleziona profilo --</option>
  <option value="admin">Amministratore</option>
  <option value="user">User</option>
</select>


        <button class="bg-blue-500 text-white px-4 py-2 rounded">
          {{ form.id ? 'Modifica' : 'Aggiungi' }}
        </button>
      </form>

      <!-- 📊 Tabella users -->
      <div ref="tableRef" class="mt-6" style="height: 400px;"></div>
    </div>
  </AuthenticatedLayout>
</template>

<style>
@import "tabulator-tables/dist/css/tabulator.min.css";
</style>
