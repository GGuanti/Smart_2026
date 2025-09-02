<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: '', // 🔹 nuovo campo ruolo
});

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <Head title="Register" />

  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-md bg-white p-8 rounded shadow">
      <h2 class="text-2xl font-bold text-center mb-6">Registrazione</h2>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <InputLabel for="name" value="Nome" />
          <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
          <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div>
          <InputLabel for="email" value="Email" />
          <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
          <InputError class="mt-2" :message="form.errors.email" />
        </div>

        <div>
          <InputLabel for="password" value="Password" />
          <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" required />
          <InputError class="mt-2" :message="form.errors.password" />
        </div>

        <div>
          <InputLabel for="password_confirmation" value="Conferma Password" />
          <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" required />
          <InputError class="mt-2" :message="form.errors.password_confirmation" />
        </div>

        <!-- 🔽 Select ruolo -->
        <div>
          <InputLabel for="profilo" value="Ruolo" />
          <select id="profilo" v-model="form.profilo" required class="mt-1 block w-full border-gray-300 rounded shadow-sm">
            <option disabled value="">-- Seleziona un ruolo --</option>
            <option value="admin">Amministratore</option>
            <option value="consigliere">Consigliere</option>
            <option value="user">Utente</option>
          </select>
          <InputError class="mt-2" :message="form.errors.profilo" />
        </div>

        <div class="flex items-center justify-between">
          <Link :href="route('login')" class="text-sm text-blue-600 hover:underline">Hai già un account?</Link>
          <PrimaryButton class="ml-4">Registrati</PrimaryButton>
        </div>
      </form>
    </div>
  </div>
</template>
