<!-- resources/js/Pages/Esempio/TabbedForms.vue -->
<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { computed, ref, watch } from "vue";

/* ===================== Tabs ===================== */
const activeTab = ref("base"); // "base" | "avanzati"

/* ===================== FORM 1 (20 controlli) ===================== */
const form1 = useForm({
  nome: "",
  cognome: "",
  email: "",
  telefono: "",
  data_nascita: "",
  eta: null,
  genere: "",
  password: "",
  password_confirmation: "",
  accetto_termini: false,
  newsletter: "no", // "si" | "no"
  note: "",
  soddisfazione: 5, // 0..10
  allegato: null, // File
  colore_preferito: "#2563eb",
  orario: "",
  sito_web: "",
  attivo: false,
  codice_interno: "AUTO",
  totale: 1250.0,
});

/* ===================== FORM 2 (20 controlli) ===================== */
const form2 = useForm({
  azione: "",
  ricerca_cliente: "",
  comune: "",
  cap: "",
  provincia: "",
  nazione: "Italia",
  priorita: 40,
  sconto: 0,
  budget: null,
  interessi: [], // multi-select
  token_demo: "token_demo_123",
  canali: {
    email: false,
    sms: false,
    whatsapp: false,
  },
  tipo_cliente: "Privato", // Privato | Azienda | PA
  sito_azienda: "",
  colore_brand: "#22c55e",
  appuntamento: "",
  settimana: "",
  mese: "",
  completezza: 65,
  descrizione: "",
});

/* Contatore descrizione */
const descCount = computed(() => (form2.descrizione || "").length);

/* (Facoltativo) Avviso “modifiche non salvate” */
const dirty1 = ref(false);
const dirty2 = ref(false);

watch(
  () => form1.data(),
  () => (dirty1.value = true),
  { deep: true }
);

watch(
  () => form2.data(),
  () => (dirty2.value = true),
  { deep: true }
);

/* ===================== Submit (DEMO) ===================== */
/**
 * Qui hai due strade:
 * 1) Salvi separatamente (consigliato): route('form1.store') e route('form2.store')
 * 2) Salvi tutto insieme su una rotta unica
 *
 * Io ti metto DEMO con console + reset dirty.
 */

function submitForm1() {
  // Esempio reale:
  // form1.post(route('profilo.base.store'), { onSuccess: () => dirty1.value = false });

  console.log("FORM1 submit", form1.data());
  dirty1.value = false;
}

function submitForm2() {
  // Esempio reale:
  // form2.post(route('profilo.avanzati.store'), { onSuccess: () => dirty2.value = false });

  console.log("FORM2 submit", form2.data());
  dirty2.value = false;
}

/* ===================== Helpers ===================== */
function onFileChange(e) {
  const file = e.target.files?.[0] || null;
  form1.allegato = file;
}

function resetForm1() {
  form1.reset();
  dirty1.value = false;
}
function resetForm2() {
  form2.reset();
  dirty2.value = false;
}
</script>

<template>
  <Head title="Tabbed Forms" />

  <AuthenticatedLayout>
    <div class="mx-auto max-w-7xl space-y-6 p-6">
      <!-- HEADER -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Gestione Profilo</h1>
          <p class="text-sm text-gray-600">
            Pagina Inertia + Vue 3 con tab e 2 form separati
          </p>
        </div>

        <!-- piccolo stato -->
        <div class="text-xs text-gray-500">
          <span v-if="activeTab === 'base'">
            Stato: <span class="font-semibold">{{ dirty1 ? "Modifiche non salvate" : "OK" }}</span>
          </span>
          <span v-else>
            Stato: <span class="font-semibold">{{ dirty2 ? "Modifiche non salvate" : "OK" }}</span>
          </span>
        </div>
      </div>

      <!-- TABS WRAPPER -->
      <div class="rounded-2xl border bg-white shadow-sm">
        <!-- TAB BUTTONS -->
        <div class="flex border-b">
          <button
            type="button"
            class="tab-btn"
            :class="activeTab === 'base' ? 'active-tab' : ''"
            @click="activeTab = 'base'"
          >
            Dati Base
          </button>

          <button
            type="button"
            class="tab-btn"
            :class="activeTab === 'avanzati' ? 'active-tab' : ''"
            @click="activeTab = 'avanzati'"
          >
            Dati Avanzati
          </button>
        </div>

        <!-- TAB CONTENT -->
        <div class="p-6">
          <!-- ===================== TAB 1: FORM 1 ===================== -->
          <div v-show="activeTab === 'base'">
            <form class="space-y-6" @submit.prevent="submitForm1">
              <div class="grid grid-cols-12 gap-4">
                <!-- 1 Nome -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Nome</label>
                  <input v-model="form1.nome" type="text" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <!-- 2 Cognome -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Cognome</label>
                  <input v-model="form1.cognome" type="text" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <!-- 3 Email -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Email</label>
                  <input v-model="form1.email" type="email" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <!-- 4 Telefono -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Telefono</label>
                  <input v-model="form1.telefono" type="tel" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <!-- 5 Data -->
                <div class="col-span-12 md:col-span-4">
                  <label class="text-xs font-semibold text-gray-600">Data nascita</label>
                  <input v-model="form1.data_nascita" type="date" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <!-- 6 Numero -->
                <div class="col-span-12 md:col-span-4">
                  <label class="text-xs font-semibold text-gray-600">Età</label>
                  <input v-model.number="form1.eta" type="number" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <!-- 7 Select -->
                <div class="col-span-12 md:col-span-4">
                  <label class="text-xs font-semibold text-gray-600">Genere</label>
                  <select v-model="form1.genere" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                    <option value="">Seleziona…</option>
                    <option value="Maschio">Maschio</option>
                    <option value="Femmina">Femmina</option>
                    <option value="Altro">Altro</option>
                  </select>
                </div>

                <!-- 8 Password -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Password</label>
                  <input v-model="form1.password" type="password" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <!-- 9 Conferma password -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Conferma Password</label>
                  <input
                    v-model="form1.password_confirmation"
                    type="password"
                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                  />
                </div>

                <!-- 10 Checkbox -->
                <div class="col-span-12 md:col-span-6 flex items-center gap-2">
                  <input v-model="form1.accetto_termini" type="checkbox" class="rounded border-gray-300" />
                  <label class="text-sm text-gray-700">Accetto i termini</label>
                </div>

                <!-- 11 Radio -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Newsletter</label>
                  <div class="mt-1 flex gap-4">
                    <label class="flex items-center gap-1 text-sm">
                      <input v-model="form1.newsletter" type="radio" value="si" /> Sì
                    </label>
                    <label class="flex items-center gap-1 text-sm">
                      <input v-model="form1.newsletter" type="radio" value="no" /> No
                    </label>
                  </div>
                </div>

                <!-- 12 Textarea -->
                <div class="col-span-12">
                  <label class="text-xs font-semibold text-gray-600">Note</label>
                  <textarea v-model="form1.note" rows="3" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></textarea>
                </div>

                <!-- 13 Range -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Livello soddisfazione (0–10)</label>
                  <input v-model.number="form1.soddisfazione" type="range" min="0" max="10" class="w-full" />
                </div>

                <!-- 14 File -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Carica file</label>
                  <input type="file" class="mt-1 w-full text-sm" @change="onFileChange" />
                </div>

                <!-- 15 Colore -->
                <div class="col-span-12 md:col-span-4">
                  <label class="text-xs font-semibold text-gray-600">Colore preferito</label>
                  <input v-model="form1.colore_preferito" type="color" class="mt-1 h-10 w-full rounded" />
                </div>

                <!-- 16 Ora -->
                <div class="col-span-12 md:col-span-4">
                  <label class="text-xs font-semibold text-gray-600">Orario</label>
                  <input v-model="form1.orario" type="time" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <!-- 17 URL -->
                <div class="col-span-12 md:col-span-4">
                  <label class="text-xs font-semibold text-gray-600">Sito web</label>
                  <input v-model="form1.sito_web" type="url" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <!-- 18 Switch -->
                <div class="col-span-12 md:col-span-6 flex items-center gap-3">
                  <input v-model="form1.attivo" type="checkbox" class="peer hidden" />
                  <div class="h-5 w-10 rounded-full bg-gray-300 peer-checked:bg-blue-600 relative">
                    <div class="absolute left-1 top-0.5 h-4 w-4 rounded-full bg-white peer-checked:translate-x-5 transition"></div>
                  </div>
                  <span class="text-sm">Attivo</span>
                </div>

                <!-- 19 Disabilitato -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Codice interno</label>
                  <input v-model="form1.codice_interno" disabled class="mt-1 w-full rounded-lg border bg-gray-100 px-3 py-2 text-sm" />
                </div>

                <!-- 20 Totale -->
                <div class="col-span-12">
                  <div class="rounded-xl bg-slate-50 p-4 text-right">
                    <span class="text-xs text-gray-500">Totale</span>
                    <div class="text-xl font-extrabold">€ {{ Number(form1.totale || 0).toFixed(2) }}</div>
                  </div>
                </div>
              </div>

              <!-- BOTTONI -->
              <div class="flex justify-end gap-3">
                <button type="button" class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-50" @click="resetForm1">
                  Annulla
                </button>
                <button
                  type="submit"
                  class="rounded-lg bg-blue-600 px-6 py-2 text-sm text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                  :disabled="form1.processing"
                >
                  Salva
                </button>
              </div>
            </form>
          </div>

          <!-- ===================== TAB 2: FORM 2 ===================== -->
          <div v-show="activeTab === 'avanzati'">
            <form class="space-y-6" @submit.prevent="submitForm2">
              <!-- HEADER interno -->
              <div class="rounded-2xl border bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <h2 class="text-lg font-bold text-gray-900">Profilo Cliente (Avanzato)</h2>
                    <p class="text-sm text-gray-600">Secondo form con controlli avanzati</p>
                  </div>

                  <!-- 1 Dropdown azioni -->
                  <select v-model="form2.azione" class="rounded-lg border bg-white px-3 py-2 text-sm">
                    <option value="">Azioni…</option>
                    <option value="duplica">Duplica</option>
                    <option value="pdf">Esporta PDF</option>
                    <option value="elimina">Elimina</option>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-12 gap-4 rounded-2xl border bg-white p-4 shadow-sm">
                <!-- 2 Search con prefisso -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Ricerca cliente</label>
                  <div class="mt-1 flex overflow-hidden rounded-lg border">
                    <span class="flex items-center bg-gray-50 px-3 text-gray-500">🔎</span>
                    <input
                      v-model="form2.ricerca_cliente"
                      type="search"
                      class="w-full px-3 py-2 text-sm outline-none"
                      placeholder="Cerca per nome o codice…"
                    />
                  </div>
                </div>

                <!-- 3 Datalist -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Comune (datalist)</label>
                  <input
                    v-model="form2.comune"
                    list="comuni"
                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                    placeholder="Inizia a scrivere…"
                  />
                  <datalist id="comuni">
                    <option value="Roma"></option>
                    <option value="Milano"></option>
                    <option value="Napoli"></option>
                    <option value="Torino"></option>
                  </datalist>
                </div>

                <!-- 4 CAP -->
                <div class="col-span-12 md:col-span-3">
                  <label class="text-xs font-semibold text-gray-600">CAP</label>
                  <input
                    v-model="form2.cap"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]{5}"
                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                    placeholder="00000"
                  />
                  <p class="mt-1 text-xs text-gray-500">5 cifre</p>
                </div>

                <!-- 5 Provincia -->
                <div class="col-span-12 md:col-span-3">
                  <label class="text-xs font-semibold text-gray-600">Provincia</label>
                  <input v-model="form2.provincia" type="text" maxlength="2" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm uppercase" placeholder="RM" />
                </div>

                <!-- 6 Nazione (optgroup) -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Nazione</label>
                  <select v-model="form2.nazione" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                    <optgroup label="Europa">
                      <option>Italia</option>
                      <option>Francia</option>
                      <option>Germania</option>
                    </optgroup>
                    <optgroup label="Altro">
                      <option>USA</option>
                      <option>Canada</option>
                    </optgroup>
                  </select>
                </div>

                <!-- 7 Slider priorità -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Priorità (0–100)</label>
                  <div class="mt-2 flex items-center gap-3">
                    <input v-model.number="form2.priorita" type="range" min="0" max="100" class="w-full" />
                    <span class="rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-700">{{ form2.priorita }}</span>
                  </div>
                </div>

                <!-- 8 Stepper sconto -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Sconto (%)</label>
                  <input v-model.number="form2.sconto" type="number" min="0" max="50" step="0.5" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <!-- 9 Budget con prefisso -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Budget</label>
                  <div class="mt-1 flex overflow-hidden rounded-lg border">
                    <span class="flex items-center bg-gray-50 px-3 text-gray-500">€</span>
                    <input v-model.number="form2.budget" type="number" step="0.01" class="w-full px-3 py-2 text-sm outline-none" placeholder="0,00" />
                  </div>
                </div>

                <!-- 10 Multi-select -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Interessi (multi-select)</label>
                  <select v-model="form2.interessi" multiple class="mt-1 h-28 w-full rounded-lg border px-3 py-2 text-sm">
                    <option value="Preventivi">Preventivi</option>
                    <option value="Assistenza">Assistenza</option>
                    <option value="Contratti">Contratti</option>
                    <option value="Fatture">Fatture</option>
                    <option value="Newsletter">Newsletter</option>
                  </select>
                  <p class="mt-1 text-xs text-gray-500">Ctrl/⌘ per selezioni multiple</p>
                </div>

                <!-- 11 Hidden -->
                <input type="hidden" :value="form2.token_demo" />

                <!-- 12 Checkbox group -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Canali contatto</label>
                  <div class="mt-2 space-y-2">
                    <label class="flex items-center gap-2 text-sm">
                      <input v-model="form2.canali.email" type="checkbox" class="rounded border-gray-300" /> Email
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                      <input v-model="form2.canali.sms" type="checkbox" class="rounded border-gray-300" /> SMS
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                      <input v-model="form2.canali.whatsapp" type="checkbox" class="rounded border-gray-300" /> WhatsApp
                    </label>
                  </div>
                </div>

                <!-- 13 Radio group -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Tipo cliente</label>
                  <div class="mt-2 grid grid-cols-3 gap-2">
                    <label class="flex items-center gap-2 rounded-lg border p-2 text-sm">
                      <input v-model="form2.tipo_cliente" type="radio" value="Privato" /> Privato
                    </label>
                    <label class="flex items-center gap-2 rounded-lg border p-2 text-sm">
                      <input v-model="form2.tipo_cliente" type="radio" value="Azienda" /> Azienda
                    </label>
                    <label class="flex items-center gap-2 rounded-lg border p-2 text-sm">
                      <input v-model="form2.tipo_cliente" type="radio" value="PA" /> PA
                    </label>
                  </div>
                </div>

                <!-- 14 URL + helper -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Sito aziendale</label>
                  <input v-model="form2.sito_azienda" type="url" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" placeholder="https://..." />
                  <p class="mt-1 text-xs text-gray-500">Inserisci un URL valido</p>
                </div>

                <!-- 15 Color brand -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Colore brand</label>
                  <div class="mt-2 flex items-center gap-3">
                    <input v-model="form2.colore_brand" type="color" class="h-10 w-14 rounded border" />
                    <div class="text-xs text-gray-600">Seleziona il colore</div>
                  </div>
                </div>

                <!-- 16 DateTime-local -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Appuntamento (data+ora)</label>
                  <input v-model="form2.appuntamento" type="datetime-local" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <!-- 17 Week -->
                <div class="col-span-12 md:col-span-3">
                  <label class="text-xs font-semibold text-gray-600">Settimana</label>
                  <input v-model="form2.settimana" type="week" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <!-- 18 Month -->
                <div class="col-span-12 md:col-span-3">
                  <label class="text-xs font-semibold text-gray-600">Mese</label>
                  <input v-model="form2.mese" type="month" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <!-- 19 Meter (solo display) -->
                <div class="col-span-12 md:col-span-6">
                  <label class="text-xs font-semibold text-gray-600">Completezza profilo</label>
                  <div class="mt-2 flex items-center gap-3">
                    <meter min="0" max="100" :value="form2.completezza" class="h-4 w-full"></meter>
                    <span class="text-xs text-gray-600">{{ form2.completezza }}%</span>
                  </div>
                </div>

                <!-- 20 Textarea con contatore -->
                <div class="col-span-12">
                  <label class="text-xs font-semibold text-gray-600">Descrizione (max 200)</label>
                  <textarea
                    v-model="form2.descrizione"
                    maxlength="200"
                    rows="4"
                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                    placeholder="Scrivi una descrizione…"
                  ></textarea>
                  <div class="mt-1 text-right text-xs text-gray-500">{{ descCount }} / 200</div>
                </div>
              </div>

              <!-- BOTTONI -->
              <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-50" @click="resetForm2">
                  Chiudi
                </button>
                <button
                  type="submit"
                  class="rounded-lg bg-blue-600 px-6 py-2 text-sm text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                  :disabled="form2.processing"
                >
                  Salva modifiche
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.tab-btn {
  padding: 0.75rem 1.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280; /* gray-500 */
  border-bottom: 2px solid transparent;
}
.tab-btn:hover {
  color: #2563eb; /* blue-600 */
}
.active-tab {
  color: #2563eb;
  border-bottom-color: #2563eb;
}
</style>
