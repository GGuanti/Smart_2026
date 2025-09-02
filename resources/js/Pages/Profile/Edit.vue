<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
  mustVerifyEmail: Boolean,
  status: String,
  user: Object,
});

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  profilo: props.user.profilo || '',
});

function updateProfile() {
  form.patch(route('profile.update'), {
    preserveScroll: true,
  });
}
</script>

<template>
  <Head title="Modifica Profilo" />

  <section class="max-w-xl mx-auto mt-10 bg-white p-6 rounded shadow">
    <header class="mb-6">
      <h2 class="text-lg font-medium text-gray-900">Profilo</h2>
      <p class="mt-1 text-sm text-gray-600">
        Modifica nome, email e profilo dell'utente.
      </p>
    </header>

    <form @submit.prevent="updateProfile" class="space-y-6">
      <!-- Nome -->
      <div>
        <InputLabel for="name" value="Nome" />
        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" autofocus />
        <InputError class="mt-2" :message="form.errors.name" />
      </div>

      <!-- Email -->
      <div>
        <InputLabel for="email" value="Email" />
        <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" />
        <InputError class="mt-2" :message="form.errors.email" />
      </div>

      <!-- Profilo -->
      <div v-if="$page.props.auth.user.profilo === 'admin'">
        <InputLabel for="profilo" value="Profilo" />
        <select
          id="profilo"
          v-model="form.profilo"
          class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500"
        >
          <option value="">Seleziona profilo</option>
          <option value="admin">Amministratore</option>
          <option value="consigliere">Consigliere</option>
        </select>
        <InputError class="mt-2" :message="form.errors.profilo" />
      </div>

      <div class="flex items-center gap-4">
        <PrimaryButton :disabled="form.processing">Salva</PrimaryButton>
        <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Salvato!</p>
      </div>
    </form>
  </section>
</template>
