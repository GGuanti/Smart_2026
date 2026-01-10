<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import Checkbox from "@/Components/Checkbox.vue";

// Icone (facoltative) — npm i lucide-vue-next
import { Mail, Lock, LogIn } from "lucide-vue-next";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Login" />

    <!-- Sfondo moderno con gradiente + “blur blobs” -->
    <div class="min-h-screen flex items-center justify-center px-4 bg-gradient-to-br from-slate-50 via-white to-slate-100">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-indigo-200/50 blur-3xl"></div>
            <div class="absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-sky-200/50 blur-3xl"></div>
        </div>

        <!-- Card -->
        <div class="relative w-full max-w-md">
            <div class="rounded-3xl border border-slate-200 bg-white/80 backdrop-blur shadow-xl overflow-hidden">
                <!-- Header gradiente -->
                <div class="px-6 py-6 bg-gradient-to-r from-indigo-600 to-blue-600 text-white">
                    <div class="flex items-center gap-3">
                        <div class="h-11 w-11 rounded-2xl bg-white/15 flex items-center justify-center">
                            <LogIn class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-xl font-extrabold leading-tight">Accedi</h2>
                            <p class="text-xs opacity-90">Inserisci le credenziali per continuare</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Email -->
                        <div>
                            <InputLabel for="email" value="Email" />
                            <div class="mt-1 relative">
                                <Mail class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-10 py-2.5 text-sm shadow-sm
                                           focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none"
                                    placeholder="nome@azienda.it"
                                    required
                                    autocomplete="username"
                                />
                            </div>
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <!-- Password -->
                        <div>
                            <InputLabel for="password" value="Password" />
                            <div class="mt-1 relative">
                                <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                <TextInput
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="w-full !rounded-xl !border-slate-200 !px-10 !py-2.5 !text-sm !shadow-sm
                                           focus:!border-indigo-400 focus:!ring-4 focus:!ring-indigo-100"
                                    placeholder="••••••••"
                                    required
                                    autocomplete="current-password"
                                />
                            </div>
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <!-- Remember + reset -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 select-none">
                                <Checkbox v-model:checked="form.remember" />
                                <span class="text-sm text-slate-600">Ricordami</span>
                            </label>

                            <Link
                                v-if="$page.props.canResetPassword"
                                :href="route('password.request')"
                                class="text-sm font-semibold text-indigo-700 hover:underline"
                            >
                                Password dimenticata?
                            </Link>
                        </div>

                        <!-- CTA -->
                        <div class="pt-2">
                            <PrimaryButton
                                class="w-full justify-center rounded-xl py-3 text-sm font-bold"
                                :disabled="form.processing"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <LogIn class="h-4 w-4" />
                                    {{ form.processing ? "Accesso..." : "Entra" }}
                                </span>
                            </PrimaryButton>

                            <!-- Registrazione disattivata (solo testo) -->
                            <p class="mt-3 text-center text-xs text-slate-500">
                                Accesso riservato. Se non hai le credenziali, contatta l’amministratore.
                            </p>

                            <!-- Se vuoi riattivarla più avanti, togli il commento:
                            <Link
                                :href="route('register')"
                                class="mt-3 block text-center text-sm text-slate-700 hover:underline"
                            >
                                Non hai un account? Registrati
                            </Link>
                            -->
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer mini -->
            <p class="mt-4 text-center text-xs text-slate-500">
                © {{ new Date().getFullYear() }} Smart • Tutti i diritti riservati
            </p>
        </div>
    </div>
</template>
