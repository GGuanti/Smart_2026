<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <Head title="Login" />

  <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100">
    <div class="w-full max-w-md bg-white p-8 rounded shadow">
      <h2 class="text-2xl font-bold mb-6 text-center">Accedi</h2>

      <form @submit.prevent="submit">
        <div class="mb-4">
          <InputLabel for="email" value="Email" />
          <TextInput
            id="email"
            v-model="form.email"
            type="email"
            class="mt-1 block w-full"
            required
            autofocus
            autocomplete="username"
          />
          <InputError class="mt-2" :message="form.errors.email" />
        </div>

        <div class="mb-4">
          <InputLabel for="password" value="Password" />
          <TextInput
            id="password"
            v-model="form.password"
            type="password"
            class="mt-1 block w-full"
            required
            autocomplete="current-password"
          />
          <InputError class="mt-2" :message="form.errors.password" />
        </div>

        <div class="flex items-center justify-between mb-4">
          <label class="flex items-center">
            <Checkbox v-model:checked="form.remember" />
            <span class="ml-2 text-sm text-gray-600">Ricordami</span>
          </label>

          <Link
            v-if="$page.props.canResetPassword"
            :href="route('password.request')"
            class="text-sm text-blue-600 hover:underline"
          >
            Password dimenticata?
          </Link>
        </div>

        <div class="flex flex-col space-y-2">
          <PrimaryButton class="w-full justify-center">Login</PrimaryButton>

          <!-- ✅ Pulsante per registrarsi -->
          <Link
            :href="route('register')"
            class="w-full text-center text-sm text-gray-700 hover:underline mt-2"
          >
            Non hai un account? Registrati
          </Link>
        </div>
      </form>
    </div>
  </div>
</template>
