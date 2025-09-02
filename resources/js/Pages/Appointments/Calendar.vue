<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import itLocale from '@fullcalendar/core/locales/it';
import { ref, computed, onMounted, watch } from 'vue';

const props = defineProps({
  appointments: Array,
});

const calendarRef = ref(null);
const search = ref('');
const selectedStatus = ref('tutti'); // tutti, active, completed, cancelled

// Computed per filtro combinato
const filteredAppointments = computed(() => {
  return props.appointments.filter(appointment => {
    const matchSearch = !search.value ||
      appointment.title.toLowerCase().includes(search.value.toLowerCase()) ||
      appointment.client?.name?.toLowerCase().includes(search.value.toLowerCase());

    const matchStatus = selectedStatus.value === 'tutti' || appointment.status === selectedStatus.value;

    return matchSearch && matchStatus;
  });
});

// Funzione eventi filtrati
const getFilteredEvents = () =>
  filteredAppointments.value.map(appointment => ({
    id: appointment.id,
    title: appointment.title,
    start: appointment.DataInizio,
    end: appointment.DataFine,
    backgroundColor:
      appointment.status === 'completed'
        ? '#10B981'
        : appointment.status === 'cancelled'
        ? '#EF4444'
        : '#3B82F6',
    extendedProps: {
      client: appointment.client?.name,
      description: appointment.description,
      status: appointment.status,
    },
  }));

const calendarOptions = ref({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locale: itLocale,
  editable: true,
  weekNumbers: true,
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay',
  },
  events: getFilteredEvents(),
  eventClick: (info) => {
    window.location.href = route('appointments.edit', info.event.id);
  },
  eventDrop: (info) => {
    router.put(`/appointments/${info.event.id}/move`, {
      start: info.event.start.toISOString(),
      end: info.event.end ? info.event.end.toISOString() : null,
    }, {
      onError: () => alert('Errore nel salvataggio'),
    });
  },
  dateClick: (info) => {
    router.visit(route('appointments.create'), {
      data: { DataInizio: info.dateStr },
      method: 'get',
    });
  },
});

// Watch per aggiornare eventi nel calendario
watch([filteredAppointments], () => {
  const calendarApi = calendarRef.value?.getApi();
  if (calendarApi) {
    calendarApi.removeAllEvents();
    getFilteredEvents().forEach(event => calendarApi.addEvent(event));
  }
});

onMounted(() => {
  if (window.Echo) {
    window.Echo.channel('appointments')
      .listen('.updated', (e) => {
        const calendarApi = calendarRef.value?.getApi();
        const event = calendarApi.getEventById(e.appointment.id);

        if (event) {
          event.setProp('title', e.appointment.title);
          event.setStart(e.appointment.DataInizio);
          event.setEnd(e.appointment.DataFine);
          event.setExtendedProp('description', e.appointment.description);
          event.setExtendedProp('status', e.appointment.status);
          event.setProp('backgroundColor',
            e.appointment.status === 'completed'
              ? '#10B981'
              : e.appointment.status === 'cancelled'
              ? '#EF4444'
              : '#3B82F6'
          );
        } else {
          calendarApi.addEvent({
            id: e.appointment.id,
            title: e.appointment.title,
            start: e.appointment.DataInizio,
            end: e.appointment.DataFine,
            backgroundColor:
              e.appointment.status === 'completed'
                ? '#10B981'
                : e.appointment.status === 'cancelled'
                ? '#EF4444'
                : '#3B82F6',
            extendedProps: {
              client: e.appointment.client?.name,
              description: e.appointment.description,
              status: e.appointment.status,
            },
          });
        }
      });
  }
});
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Calendario" />

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold">📅 {{$page.props.auth.user.name}} </h2>
            <Link :href="route('appointments.create')" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
              Aggiungi Appuntamento
            </Link>
          </div>

          <!-- 🔍 Filtri -->
          <div class="flex gap-4 mb-4">
            <input
              v-model="search"
              type="text"
              placeholder="Cerca per titolo o cliente..."
              class="border px-3 py-2 rounded w-full"
            />
            <select v-model="selectedStatus" class="border px-3 py-2 rounded">
              <option value="tutti">Tutti</option>
              <option value="scheduled">In programma</option>
              <option value="active">Attivi</option>
              <option value="completed">Completati</option>
              <option value="cancelled">Cancellati</option>
            </select>
          </div>

          <!-- 📅 Calendario -->
          <FullCalendar ref="calendarRef" :options="calendarOptions" class="min-h-[600px]" />
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
