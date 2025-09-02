<!-- resources/js/Pages/Drawing/DxfDbTest.vue -->
<script setup>
import { ref } from 'vue'
import axios from 'axios'
import DxfTest from './DxfTest.vue' // il tuo viewer

const form = ref({ Codice:'', Descrizione:'', LRG:'', ALT:'', file:null })
const newId = ref(null)
const viewId = ref('')
const dxfText = ref('')

async function onFile(e){ form.value.file = e.target.files?.[0] || null }

async function upload(){
  const fd = new FormData()
  for (const k of ['Codice','Descrizione','LRG','ALT']) fd.append(k, form.value[k] ?? '')
  fd.append('file', form.value.file)
  const { data } = await axios.post('/disegni-dxf', fd)
  newId.value = data.IdRigaDXF
  alert('Salvato con IdRigaDXF = ' + newId.value)
}

async function loadFromDb(){
  if(!viewId.value) return
  const { data } = await axios.get(`/disegni-dxf/${viewId.value}/raw`, { responseType: 'text' })
  dxfText.value = data
}
</script>

<template>
  <div class="p-6 space-y-6">
    <h1 class="text-xl font-semibold">Archivio Disegni DXF (DB)</h1>

    <!-- Upload nel DB -->
    <div class="border rounded p-4 space-y-2">
      <div class="font-medium">Carica DXF nel DB</div>
      <div class="flex gap-2">
        <input class="border p-2 rounded" placeholder="Codice" v-model="form.Codice">
        <input class="border p-2 rounded" placeholder="Descrizione" v-model="form.Descrizione" style="width:260px">
        <input class="border p-2 rounded" placeholder="LRG" v-model="form.LRG" style="width:90px">
        <input class="border p-2 rounded" placeholder="ALT" v-model="form.ALT" style="width:90px">
        <input type="file" accept=".dxf" @change="onFile">
        <button class="px-3 py-2 bg-blue-600 text-white rounded" @click="upload">Salva</button>
      </div>
      <div v-if="newId" class="text-sm text-green-700">Inserito IdRigaDXF: {{ newId }}</div>
    </div>

    <!-- Carica nel viewer partendo dall'Id -->
    <div class="border rounded p-4 space-y-2">
      <div class="font-medium">Apri viewer da DB</div>
      <div class="flex items-center gap-2">
        <input class="border p-2 rounded w-40" placeholder="IdRigaDXF" v-model="viewId">
        <button class="px-3 py-2 bg-emerald-600 text-white rounded" @click="loadFromDb">Carica</button>
      </div>

      <div v-if="dxfText">
        <!-- Passo il testo DXF direttamente al tuo viewer -->
        <DxfTest :dxf-text="dxfText" />
      </div>
    </div>
  </div>
</template>
