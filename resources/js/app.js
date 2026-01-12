import '../css/app.css'
import './bootstrap'

import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createApp, h } from 'vue'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'

/* ▶️ PrimeVue v3 */
import PrimeVue from 'primevue/config'
import AutoComplete from 'primevue/autocomplete'
import 'primevue/resources/themes/lara-light-blue/theme.css'
import 'primevue/resources/primevue.min.css'
import 'primeicons/primeicons.css'
import "tabulator-tables/dist/css/tabulator.min.css";
import "../css/tabulator-smart.css";
const appName = import.meta.env.VITE_APP_NAME || 'Laravel'

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })

    app.use(plugin)
      .use(ZiggyVue)
      .use(Toast, { timeout: 3000, position: 'top-right' })
      .use(PrimeVue, {
        ripple: true,
        inputStyle: 'outlined',
      })

    // Registra i componenti PrimeVue che usi
    app.component('AutoComplete', AutoComplete)

    app.mount(el)
    return app
  },
  progress: { color: '#4B5563' },
})
