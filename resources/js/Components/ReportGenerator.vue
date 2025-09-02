<template>
  <div class="flex flex-col min-h-screen">
    <div class="p-4 bg-white shadow z-10">
      <button
        @click="generaReport"
        :disabled="loading"
        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition disabled:opacity-50"
      >
        {{ loading ? 'Attendere generazione...' : 'Genera Report PDF' }}
      </button>
    </div>

    <div class="flex-grow w-full">
      <iframe
  v-if="urlPdf"
  :src="urlPdf"
  class="w-screen border-0"
  style="height: calc(100vh - 140px);"
/>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const loading = ref(false)
const urlPdf = ref(null)
const toast = useToast()
let pollInterval = null

const generaReport = async () => {
  loading.value = true
  urlPdf.value = null

  try {
    const response = await axios.post('/report/genera', {
      filtro: {
        tipo: 'clienti', // esempio filtro statico
      },
    })

    const jobId = response.data.id
    toast.info('Report in generazione...')

    pollInterval = setInterval(async () => {
      try {
        const res = await axios.get(`/report/check/${jobId}`)
        if (res.data.ready) {
          clearInterval(pollInterval)
          urlPdf.value = res.data.url
          console.log('📄 URL PDF pronto:', urlPdf.value)
          toast.success('✅ PDF pronto!')
          loading.value = false
        }
      } catch (err) {
        console.error('❌ Errore polling:', err)
        toast.error('Errore nel controllo del file.')
        clearInterval(pollInterval)
        loading.value = false
      }
    }, 2000)
  } catch (error) {
    console.error('❌ Errore POST /report/genera:', error)

    // Stampa errore da Laravel se disponibile
    if (error.response) {
      console.error('Dettagli risposta:', error.response.data)
      toast.error(error.response.data.message || 'Errore dal server.')
    } else {
      toast.error('Errore durante la generazione del report.')
    }

    loading.value = false
  }
}
</script>
