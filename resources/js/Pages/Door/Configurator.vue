<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import axios from "axios";

import DoorPreview from "@/Components/Door/DoorPreview.vue";
import DoorPreviewPocket from "@/Components/Door/DoorPreviewPocket.vue";
import DoorPreviewSlidingValance from "@/Components/Door/DoorPreviewSlidingValance.vue";
import DoorPreviewBifold from "@/Components/Door/DoorPreviewBifold.vue";
import DoorPreviewRoto from "@/Components/Door/DoorPreviewRoto.vue";
import DoorPreviewBattente3DProduct from "@/Components/Door/DoorPreviewBattente3DProduct.vue";

const props = defineProps({
    textures: { type: Array, default: () => [] },
    configs: { type: Array, default: () => [] },
});

const openingType = ref("battente");

const antaTextureId = ref(null);
const telaioTextureId = ref(null);

const angle = ref(25); // battente
const progress = ref(40); // scorrevoli
const shift = ref(30); // rototraslante

const antaUrl = computed(() => {
    const t = props.textures.find((x) => x.id === antaTextureId.value);
    return t?.url ?? "";
});
const telaioUrl = computed(() => {
    const t = props.textures.find((x) => x.id === telaioTextureId.value);
    return t?.url ?? "";
});

const uploadType = ref("anta");
const file = ref(null);

function onPick(e) {
    file.value = e.target.files?.[0] ?? null;
}

async function upload() {
    if (!file.value) return;
    const fd = new FormData();
    fd.append("file", file.value);
    fd.append("type", uploadType.value);
    fd.append("name", file.value.name);

    await axios.post(route("textures.store"), fd, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
    });

    router.reload({ only: ["textures"] });
    file.value = null;
}

function saveConfig() {
    router.post(
        route("door.store"),
        {
            opening_type: openingType.value,
            anta_texture_id: antaTextureId.value,
            telaio_texture_id: telaioTextureId.value,
            params: {
                angle: angle.value,
                progress: progress.value,
                shift: shift.value,
            },
        },
        { preserveScroll: true }
    );
}
</script>

<template>
    <Head title="Configuratore Porte" />

    <AuthenticatedLayout>
        <div class="p-5 space-y-5">
            <div class="text-xl font-semibold">Configuratore Porte</div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- FORM -->
                <div
                    class="rounded-2xl border bg-white p-4 shadow-sm space-y-4"
                >
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm font-semibold"
                                >Tipo apertura</label
                            >
                            <select
                                v-model="openingType"
                                class="mt-1 w-full rounded-xl border px-3 py-2"
                            >
                                <option value="battente">Battente</option>
                                <option value="libro">Libro</option>
                                <option value="rototraslante">
                                    Rototraslante
                                </option>
                                <option value="scorrevole_interno">
                                    Scorrevole interno muro
                                </option>
                                <option value="scorrevole_esterno">
                                    Scorrevole esterno muro
                                </option>
                                <option value="scorrevole_mantovana">
                                    Scorrevole con mantovana
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-semibold"
                                >Upload texture</label
                            >
                            <div class="mt-1 flex gap-2">
                                <select
                                    v-model="uploadType"
                                    class="rounded-xl border px-3 py-2"
                                >
                                    <option value="anta">Anta</option>
                                    <option value="telaio">Telaio</option>
                                </select>
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="onPick"
                                    class="w-full"
                                />
                                <button
                                    @click="upload"
                                    class="rounded-xl border px-3 py-2"
                                >
                                    Carica
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-semibold"
                                >Texture Anta</label
                            >
                            <select
                                v-model.number="antaTextureId"
                                class="mt-1 w-full rounded-xl border px-3 py-2"
                            >
                                <option :value="null">—</option>
                                <option
                                    v-for="t in props.textures.filter(
                                        (x) =>
                                            x.type === 'anta' ||
                                            x.type === 'generic'
                                    )"
                                    :key="t.id"
                                    :value="t.id"
                                >
                                    {{ t.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-semibold"
                                >Texture Telaio</label
                            >
                            <select
                                v-model.number="telaioTextureId"
                                class="mt-1 w-full rounded-xl border px-3 py-2"
                            >
                                <option :value="null">—</option>
                                <option
                                    v-for="t in props.textures.filter(
                                        (x) =>
                                            x.type === 'telaio' ||
                                            x.type === 'generic'
                                    )"
                                    :key="t.id"
                                    :value="t.id"
                                >
                                    {{ t.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Parametri dinamici -->
                    <div v-if="openingType === 'battente'" class="space-y-2">
                        <label class="text-sm font-semibold">Angolo</label>
                        <input
                            type="range"
                            min="0"
                            max="75"
                            v-model.number="angle"
                            class="w-full"
                        />
                        <div class="text-sm text-slate-600">{{ angle }}°</div>
                    </div>

                    <div
                        v-if="openingType.includes('scorrevole')"
                        class="space-y-2"
                    >
                        <label class="text-sm font-semibold"
                            >Apertura (progress)</label
                        >
                        <input
                            type="range"
                            min="0"
                            max="100"
                            v-model.number="progress"
                            class="w-full"
                        />
                        <div class="text-sm text-slate-600">
                            {{ progress }}%
                        </div>
                    </div>

                    <div
                        v-if="openingType === 'rototraslante'"
                        class="space-y-2"
                    >
                        <label class="text-sm font-semibold">Shift</label>
                        <input
                            type="range"
                            min="-50"
                            max="50"
                            v-model.number="shift"
                            class="w-full"
                        />
                        <div class="text-sm text-slate-600">{{ shift }}%</div>
                    </div>

                    <div class="flex gap-2">
                        <button
                            @click="saveConfig"
                            class="rounded-xl bg-black text-white px-4 py-2"
                        >
                            Salva configurazione
                        </button>
                    </div>
                </div>

                <!-- PREVIEW -->
                <div class="rounded-2xl border bg-white p-4 shadow-sm">
                  <!--  <DoorPreview
                        v-if="openingType === 'battente'"
  :anta-url="antaUrl"
  :telaio-url="telaioUrl"
  hinge="left"
  swing="outside"
  :angle="form.angle"
                    /> -->

                    <DoorPreviewBattente3DProduct
                        v-if="openingType === 'battente'"
                        :anta-url="antaUrl"
                        :telaio-url="telaioUrl"
                        hinge="left"
                        swing="outside"
                        :angle="28"
                    />

                    <DoorPreviewBifold
                        v-else-if="openingType === 'libro'"
                        :antaUrl="antaUrl"
                        :telaioUrl="telaioUrl"
                        :gap="2"
                    />

                    <DoorPreviewRoto
                        v-else-if="openingType === 'rototraslante'"
                        :anta-url="antaUrl"
                        :telaio-url="telaioUrl"
                        hinge="left"
                        swing="outside"
                        :angle="28"
                    />
                    <DoorPreviewPocket
                        v-else-if="openingType === 'scorrevole_interno'"
                        :antaUrl="antaUrl"
                        :progress="progress"
                    />

                    <DoorPreviewPocket
                        v-else-if="openingType === 'scorrevole_esterno'"
                        :antaUrl="antaUrl"
                        :progress="progress"
                        mode="external"
                    />

                    <DoorPreviewSlidingValance
                        v-else
                        :antaUrl="antaUrl"
                        :progress="progress"
                        :valanceUrl="telaioUrl"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
