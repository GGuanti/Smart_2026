<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const giornate = computed(() => page.props.giornate || [])
const cliente = computed(() => page.props.cliente)
const intestatario = computed(() => page.props.intestatario)
const committente = computed(() => page.props.committente)
const dataReport = new Date().toLocaleDateString('it-IT')

// Totali
const totaleGiornate = computed(() => giornate.value.length)
const totaleDiaria = computed(() =>
  giornate.value.filter(g => g.Diaria === 'Sì' || g.Diaria === 'SI' || g.Diaria === '1').length
)
const totaleRetribuzione = computed(() =>
  giornate.value.reduce((tot, g) => tot + parseFloat(g.Retribuzione || 0), 0).toFixed(2)
)
</script>

<template>
  <!-- Bottone Stampa -->
  <div class="no-print mb-4 w-[800px] mx-auto text-right">
    <button
      @click="window.print()"
      class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
    >
      🖨️ Stampa
    </button>
  </div>

  <!-- Report -->
  <div class="w-[800px] mx-auto p-10 text-sm text-gray-900 font-sans bg-white">
    <!-- Intestazione -->
    <div class="flex justify-between items-start mb-6">
      <img src="/images/logo_smart.png" alt="Smart Logo" class="h-12" />
      <div class="text-right">
        <h2 class="text-lg font-semibold">Report Giornate</h2>
        <p class="text-xs">{{ dataReport }}</p>
      </div>
    </div>

    <!-- Dati intestatario -->
    <div class="mb-4">
      <p class="font-bold text-base">{{ intestatario?.Nome }}</p>
      <p>
        <span class="font-semibold">Cod. Fiscal:</span> {{ intestatario?.CodFiscale }}<br />
        <span class="font-semibold">Cell:</span> {{ intestatario?.Cell }}<br />
        <span class="font-semibold">Email:</span> {{ intestatario?.Email }}
      </p>
    </div>

    <!-- Dati contratto -->
    <div class="mb-6">
      <p>
        <span class="font-semibold">Tipo contratto:</span> {{ intestatario?.TipoContratto }}<br />
        <span class="font-semibold">Data inizio:</span> {{ intestatario?.DataInizio }}<br />
        <span class="font-semibold">Data fine:</span> {{ intestatario?.DataFine }}
      </p>
    </div>

    <!-- Committente -->
    <div class="mb-2 bg-red-600 text-white font-semibold py-1 px-3">
      Committente: {{ committente }}
    </div>

    <!-- Tabella giornate -->
    <table class="w-full border border-gray-400 text-sm mb-6">
      <thead class="bg-gray-200">
        <tr>
          <th class="border border-gray-400 px-2 py-1 text-left">N. Ordine</th>
          <th class="border border-gray-400 px-2 py-1 text-left">Data</th>
          <th class="border border-gray-400 px-2 py-1 text-right">Retrib. lorda</th>
          <th class="border border-gray-400 px-2 py-1 text-center">Diaria</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="g in giornate" :key="g.id">
          <td class="border border-gray-300 px-2 py-1">{{ g.N_Ordine }}</td>
          <td class="border border-gray-300 px-2 py-1">{{ g.Data }}</td>
          <td class="border border-gray-300 px-2 py-1 text-right">€ {{ parseFloat(g.Retribuzione).toFixed(2) }}</td>
          <td class="border border-gray-300 px-2 py-1 text-center">{{ g.Diaria }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Totali -->
    <div class="text-right text-sm space-y-1">
      <p><strong>Totale giornate:</strong> {{ totaleGiornate }}</p>
      <p><strong>Totale diarie:</strong> {{ totaleDiaria }}</p>
      <p><strong>Totale retr. lorda:</strong> € {{ totaleRetribuzione }}</p>
    </div>

    <!-- Footer -->
    <div class="mt-12 text-xs text-red-700 text-left">
      <p class="font-semibold">Smart soc. coop. impresa sociale</p>
      <p>
        via Casoretto 41/A, Milano (MI) - 20131<br />
        CF e P. IVA IT08934230967<br />
        <a href="https://www.smart.coop" class="text-red-600 underline">www.smart.coop</a>
      </p>
    </div>
  </div>
</template>

<style>
@media print {
  body {
    margin: 0;
    background: white;
  }

  .no-print {
    display: none !important;
  }
}
</style>
