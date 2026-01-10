<script setup>
import { ref, computed, watch } from "vue";
import {
    User,
    Home,
    FileText,
    Briefcase,
    BookOpen,
    ShieldCheck,
    FolderKanban,
    Activity,
    Receipt,
    CalendarDays,
    FileSignature,
    HeartPulse,
} from "lucide-vue-next";

const props = defineProps({
    tipoU: { type: String, default: "U" },
    errors: { type: Object, default: () => ({}) },
    tabErrorMap: { type: Object, default: () => ({}) },
    jumpToFirstErrorTab: { type: Boolean, default: true },
    stickyTop: { type: Number, default: 0 },
});

const selectedTab = ref("generali");

/* 🎯 DEFINIZIONE TAB + ICONE */
const tabs = computed(() => {
    if (props.tipoU === "U") {
        return [
            { key: "generali", label: "Generali", icon: User },
            { key: "residenza", label: "Residenza", icon: Home },
            { key: "fiscali", label: "Dati Fiscali", icon: FileText },
            { key: "professione", label: "Professione", icon: Briefcase },
            { key: "librosoci", label: "Libro Soci", icon: BookOpen },
            { key: "sicurezza", label: "Sicurezza", icon: ShieldCheck },
            { key: "progetti", label: "Progetti", icon: FolderKanban },
            { key: "attivita", label: "Attività", icon: Activity },
            { key: "notespese", label: "Note Spese", icon: Receipt },
            { key: "giornate", label: "Giornate", icon: CalendarDays },
            { key: "contratti", label: "Contratti", icon: FileSignature },
            { key: "sorveglianza", label: "Sorveglianza", icon: HeartPulse },
        ];
    }
    return [];
});

/* 🔴 ERRORI PER TAB */
function tabErrorCount(tabKey) {
    const fields = props.tabErrorMap?.[tabKey] ?? [];
    return fields.reduce((n, f) => n + (props.errors?.[f] ? 1 : 0), 0);
}
function tabHasErrors(tabKey) {
    return tabErrorCount(tabKey) > 0;
}

/* 🚀 AUTO-SWITCH AL PRIMO TAB CON ERRORI */
watch(
    () => props.errors,
    (errs) => {
        if (!props.jumpToFirstErrorTab) return;
        if (!errs || !Object.keys(errs).length) return;

        const first = tabs.value.find((t) => tabHasErrors(t.key));
        if (first) selectedTab.value = first.key;
    },
    { deep: true }
);

const is = (k) => selectedTab.value === k;
</script>

<template>
    <div class="tabsRoot">
        <!-- ===== TAB BAR STICKY ===== -->
        <div class="tabsBar" :style="{ top: `${stickyTop}px` }">
            <div class="tabsRow">
                <button
                    v-for="t in tabs"
                    :key="t.key"
                    @click="selectedTab = t.key"
                    class="tabBtn"
                    :class="{
                        active: is(t.key),
                        hasError: tabHasErrors(t.key) && !is(t.key),
                    }"
                >
                    <component
                        :is="t.icon"
                        class="tabIcon"
                        :class="{ activeIcon: is(t.key) }"
                    />

                    <span class="tabLabel">
                        {{ t.label }}

                        <span
                            v-if="tabHasErrors(t.key)"
                            class="tabErrBadge"
                        >
                            {{ tabErrorCount(t.key) }}
                        </span>
                    </span>
                </button>
            </div>
        </div>

        <!-- ===== CONTENUTO ===== -->
        <div class="tabsBody">
            <div class="tabsContent">
                <div v-if="is('generali')"><slot name="generali" /></div>
                <div v-if="is('residenza')"><slot name="residenza" /></div>
                <div v-if="is('fiscali')"><slot name="fiscali" /></div>
                <div v-if="is('professione')"><slot name="professione" /></div>
                <div v-if="is('librosoci')"><slot name="librosoci" /></div>
                <div v-if="is('sicurezza')"><slot name="sicurezza" /></div>
                <div v-if="is('progetti')"><slot name="progetti" /></div>
                <div v-if="is('attivita')"><slot name="attivita" /></div>
                <div v-if="is('notespese')"><slot name="notespese" /></div>
                <div v-if="is('giornate')"><slot name="giornate" /></div>
                <div v-if="is('contratti')"><slot name="contratti" /></div>
                <div v-if="is('sorveglianza')"><slot name="sorveglianza" /></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ROOT */
.tabsRoot {
    display: flex;
    flex-direction: column;
    min-height: 0;
}

/* TAB BAR */
.tabsBar {
    position: sticky;
    z-index: 30;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid #e5e7eb;
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
}

/* ROW */
.tabsRow {
    display: flex;
    gap: 10px;
    padding: 10px 12px;
    overflow-x: auto;
}

/* TAB BUTTON */
.tabBtn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 9999px;
    font-weight: 800;
    font-size: 13px;
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.tabBtn:hover {
    background: #eef2ff;
    color: #1e40af;
}

/* ACTIVE */
.tabBtn.active {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white;
    border-color: transparent;
    box-shadow: 0 12px 25px rgba(37, 99, 235, 0.35);
    transform: translateY(-1px);
}

/* ERROR (non attivo) */
.tabBtn.hasError {
    border-color: #fecaca;
}

/* ICON */
.tabIcon {
    width: 16px;
    height: 16px;
    opacity: 0.85;
}
.activeIcon {
    opacity: 1;
}

/* LABEL */
.tabLabel {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* ERROR BADGE */
.tabErrBadge {
    min-width: 18px;
    height: 18px;
    padding: 0 6px;
    border-radius: 9999px;
    background: #fee2e2;
    color: #b91c1c;
    font-size: 11px;
    font-weight: 900;
    border: 1px solid #fecaca;
}

/* BODY */
.tabsBody {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    background: white;
}
.tabsContent {
    padding: 20px;
}
</style>
