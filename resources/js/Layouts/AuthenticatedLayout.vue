<script setup>
import { ref } from 'vue'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import NavLink from '@/Components/NavLink.vue'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
import { Link, usePage } from '@inertiajs/vue3'

const showingNavigationDropdown = ref(false)
const page = usePage()
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-white border-b border-gray-100 w-full">
      <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <Link :href="route('dashboard')">
                <ApplicationLogo class="block h-9 w-auto text-gray-800" />
              </Link>
            </div>

            <!-- Admin -->
            <div
              v-if="$page.props.auth.user.profilo === 'admin'"
              class="hidden sm:ms-6 sm:flex sm:items-center"
            >
            <Dropdown align="left" width="48">
                <template #trigger>
                  <span class="inline-flex rounded-md">
                    <button
                      class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-transparent rounded-md shadow-sm hover:bg-gray-100"
                    >
                      Report
                      <svg
                        class="ms-2 h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 20 20"
                        stroke="currentColor"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M5 7l5 5 5-5"
                        />
                      </svg>
                    </button>
                  </span>
                </template>
               <template #content>
                  <DropdownLink :href="route('report.index')"> Report</DropdownLink>
                </template>



            </Dropdown>
              <Dropdown align="left" width="48">
                <template #trigger>
                  <span class="inline-flex rounded-md">
                    <button
                      class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-transparent rounded-md shadow-sm hover:bg-gray-100"
                    >
                      Amministrazione
                      <svg
                        class="ms-2 h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 20 20"
                        stroke="currentColor"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M5 7l5 5 5-5"
                        />
                      </svg>
                    </button>
                  </span>
                </template>
                <template #content>
                  <DropdownLink :href="route('users.index')">👥 Utenti</DropdownLink>
                  <DropdownLink :href="route('anagrafica.index', { tipoU: 'U' })">👥 User</DropdownLink>
                  <DropdownLink :href="route('anagrafica.index', { tipoU: 'C' })">👥 Committenti</DropdownLink>
                  <DropdownLink :href="route('anagrafica.index', { tipoU: 'F' })">👥 Fornitori</DropdownLink>

                  <DropdownLink :href="route('articoli.index')">📄 Elenco Articoli</DropdownLink>
                  <DropdownLink :href="route('appointments.calendar')">📅 Calendario</DropdownLink>
                  <DropdownLink :href="route('appointments.calendar')">📅 Giornate</DropdownLink>
                </template>
              </Dropdown>
            </div>

            <!-- Utente base -->
            <div
              v-if="$page.props.auth.user.profilo === 'user' || $page.props.auth.user.profilo === 'admin'"
              class="hidden sm:ms-6 sm:flex sm:items-center"
            >
              <Dropdown align="left" width="48">
                <template #trigger>
                  <span class="inline-flex rounded-md">
                    <button
                      class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-transparent rounded-md shadow-sm hover:bg-gray-100"
                    >
                      Consigliere
                      <svg
                        class="ms-2 h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 20 20"
                        stroke="currentColor"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M5 7l5 5 5-5"
                        />
                      </svg>
                    </button>
                  </span>
                </template>
                <template #content>
                  <DropdownLink :href="route('articoli.index')">📄 Articoli</DropdownLink>
                  <DropdownLink :href="route('appointments.calendar')">📅 Calendario</DropdownLink>
                </template>
              </Dropdown>
            </div>
          </div>

          <!-- Profilo -->
          <div class="hidden sm:ms-6 sm:flex sm:items-center">
            <div class="relative ms-3">
              <Dropdown align="right" width="48">
                <template #trigger>
                  <span class="inline-flex rounded-md">
                    <button
                      class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-transparent rounded-md hover:text-gray-700"
                    >
                      Profilo: {{ $page.props.auth.user.profilo }} {{ $page.props.auth.user.name }}
                      <svg
                        class="ms-2 h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                      >
                        <path
                          fill-rule="evenodd"
                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                          clip-rule="evenodd"
                        />
                      </svg>
                    </button>
                  </span>
                </template>
                <template #content>
                  <DropdownLink :href="route('profile.edit')">Profilo</DropdownLink>
                  <DropdownLink :href="route('logout')" method="post" as="button">Logout</DropdownLink>
                </template>
              </Dropdown>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <header class="bg-white shadow" v-if="$slots.header">
      <div class="w-full py-6 px-4 sm:px-6 lg:px-8">
        <slot name="header" />
      </div>
    </header>

    <!-- Flash message -->
    <div class="w-full mt-4 px-4" v-if="page.props.flash?.success">
      <div class="bg-green-100 text-green-800 p-3 rounded shadow text-sm">
        {{ page.props.flash.success }}
      </div>
    </div>

    <!-- Main content -->
    <main class="w-full px-0 py-0">
      <slot />
    </main>
  </div>
</template>
