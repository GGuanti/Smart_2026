<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  tipoU: String
})

const selectedTab = ref('generali')

const tabs = computed(() => {
  if (props.tipoU === 'U') {
    return [
      { name: 'Generali', key: 'generali' },
      { name: 'Residenza/Domicilio', key: 'residenza' },
      { name: 'Dati Fiscali', key: 'fiscali' },
      { name: 'Professione', key: 'professione' },
      { name: 'Libro Soci', key: 'librosoci' },
      { name: 'Sicurezza', key: 'sicurezza' },
      { name: 'Progetti', key: 'progetti' },
      { name: 'Attivita', key: 'attivita' },
      { name: 'Note Spese', key: 'notespese' },
      { name: 'Giornate', key: 'giornate' },
      { name: 'Contratti Lavoratore', key: 'contratti' },
      { name: 'Sorveglianza Sanitaria', key: 'sorveglianza' },

    ]
  } else if (props.tipoU === 'C') {
    return [
      { name: 'Generali', key: 'generali' },
      { name: 'Dati contatto', key: 'contatto' },
      { name: 'Dati Fiscali', key: 'fiscali' },
      { name: 'Elenco Movimenti', key: 'movimenti' },
      { name: 'Solleciti', key: 'solleciti' },
      { name: 'Piano Rientro', key: 'rientro' },
      { name: 'Accordi Commerciali', key: 'accordi' },
      { name: 'Studio Legale', key: 'legale' },
    ]
  } else if (props.tipoU === 'F') {
    return [
      { name: 'Generali', key: 'generali' },
      { name: 'Dati contatto', key: 'contatto' },
      { name: 'Dati Fiscali', key: 'fiscali' },
      { name: 'Elenco Movimenti', key: 'movimenti' },
    ]
  }
  return []
})
</script>

<template>
  <div>
    <div class="flex flex-wrap border-b border-gray-300 mb-4 space-x-2">
<!-- CORRETTO -->
<button
  v-for="tab in tabs"
  :key="tab.key"
  type="button"
  @click="selectedTab = tab.key"
  class="px-4 py-2 text-sm font-medium rounded-t-md border border-b-0"
  :class="{
    'bg-white text-blue-600 border-gray-300': selectedTab === tab.key,
    'bg-gray-100 text-gray-600 border-transparent hover:bg-gray-200': selectedTab !== tab.key
  }"
>
  {{ tab.name }}
</button>
    </div>

    <div class="bg-white p-6 shadow rounded-b-md border border-gray-300">
      <div v-if="selectedTab === 'generali'"><slot name="generali" /></div>
      <div v-if="selectedTab === 'residenza'"><slot name="residenza" /></div>
      <div v-if="selectedTab === 'contatto'"><slot name="contatto" /></div>
      <div v-if="selectedTab === 'fiscali'"><slot name="fiscali" /></div>
      <div v-if="selectedTab === 'professione'"><slot name="professione" /></div>
      <div v-if="selectedTab === 'librosoci'"><slot name="librosoci" /></div>
      <div v-if="selectedTab === 'sicurezza'"><slot name="sicurezza" /></div>
      <div v-if="selectedTab === 'progetti'"><slot name="progetti" /></div>
      <div v-if="selectedTab === 'attivita'"><slot name="attivita" /></div>
      <div v-if="selectedTab === 'notespese'"><slot name="notespese" /></div>
      <div v-if="selectedTab === 'giornate'"><slot name="giornate" /></div>
      <div v-if="selectedTab === 'contratti'"><slot name="contratti" /></div>
      <div v-if="selectedTab === 'sorveglianza'"><slot name="sorveglianza" /></div>
      <div v-if="selectedTab === 'movimenti'"><slot name="movimenti" /></div>
      <div v-if="selectedTab === 'solleciti'"><slot name="solleciti" /></div>
      <div v-if="selectedTab === 'rientro'"><slot name="rientro" /></div>
      <div v-if="selectedTab === 'accordi'"><slot name="accordi" /></div>
      <div v-if="selectedTab === 'legale'"><slot name="legale" /></div>
    </div>
  </div>
</template>
