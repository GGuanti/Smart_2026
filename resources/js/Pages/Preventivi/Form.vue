<script setup>
/* ===================== Imports ===================== */
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useToast } from "vue-toastification";
import { Save, Trash2, ArrowLeft, Plus } from "lucide-vue-next";
import axios from "axios";

/* ===================== Props ===================== */
const props = defineProps({
    ordine: { type: Object, required: true },
    elementi: { type: Array, default: () => [] },
    modelli: { type: Array, default: () => [] },
    colAnta: { type: Array, default: () => [] },
    colTelaio: { type: Array, default: () => [] },
    soluzioni: { type: Array, default: () => [] },
    maniglie: { type: Array, default: () => [] },
    aperture: { type: Array, default: () => [] },
    tipiTelaio: { type: Array, default: () => [] },
    vetri: { type: Array, default: () => [] },
    assModVetri: { type: Array, default: () => [] },
    cerniere: { type: Array, default: () => [] },
    serrature: { type: Array, default: () => [] },
    imbotte: { type: Array, default: () => [] },
});

/* ===================== UI state ===================== */
const toast = useToast();
const confirmDeleteUid = ref(null);

const showZoom = ref(false);
const zoomSrc = ref("");

function openZoom(src) {
    zoomSrc.value = src;
    showZoom.value = true;
    document.body.style.overflow = "hidden";
}
function closeZoom() {
    showZoom.value = false;
    zoomSrc.value = "";
    document.body.style.overflow = "";
}
function onKey(e) {
    if (e.key === "Escape") closeZoom();
}
onMounted(() => window.addEventListener("keydown", onKey));
onBeforeUnmount(() => window.removeEventListener("keydown", onKey));

function onImgError(e) {
    // fallback immagine se manca il file
    if (e?.target) e.target.src = "/foto/placeholder.jpg";
}

/* ===================== Helpers generici ===================== */
function isEmptyVal(v) {
    return v === null || v === undefined || v === "" || Number.isNaN(v);
}

/**
 * Setta riga[key] al primo valore valido se:
 * - è vuoto
 * - oppure non esiste più nelle options
 */
function ensureFirstValid(riga, key, options, getId, emptyValue = null) {
    const opts = Array.isArray(options) ? options : [];
    if (!opts.length) {
        riga[key] = emptyValue;
        return false;
    }

    const cur = riga[key];

    if (isEmptyVal(cur)) {
        riga[key] = Number(getId(opts[0]));
        return true;
    }

    const ok = opts.some((o) => Number(getId(o)) === Number(cur));
    if (!ok) {
        riga[key] = Number(getId(opts[0]));
        return true;
    }

    return false;
}

function bumpImgKeyOnly(riga) {
    riga._imgKey = (riga._imgKey ?? 0) + 1;
}

/* ===================== Form / righe ===================== */
function newRigaFromElemento(el = null) {
    return {
        uid: crypto?.randomUUID
            ? crypto.randomUUID()
            : String(Date.now() + Math.random()),
        Id: el?.Id ?? el?.ID ?? null,

        DimL: el?.DimL ?? "",
        DimA: el?.DimA ?? "",
        DimSp: el?.DimSp ?? "",
        Qta: el?.Qta ?? 1,

        PrezzoCad: el?.PrezzoCad ?? 0,
        PrezzoMan: el?.PrezzoMan ?? 0,

        NoteMan: el?.NoteMan ?? "",
        PercFile: el?.PercFile ?? "",
        TxtCassMet: el?.TxtCassMet ?? "",

        IdModello: el?.IdModello ?? "",
        IdSoluzione: el?.IdSoluzione ?? "",
        IdColAnta: el?.IdColAnta ?? "",
        IdColTelaio: el?.IdColTelaio ?? "",
        IdManiglia: el?.IdManiglia ?? "",
        IdApertura: el?.IdApertura ?? "",
        IdTipTelaio: el?.IdTipTelaio ?? "",
        IdVetro: el?.IdVetro ?? "",
        IdColFerr: el?.IdColFerr ?? "",
        IdSerratura: el?.IdSerratura ?? "",
        CkTaglioObl: el?.CkTaglioObl ?? "No",
        IdImbotte: el?.IdImbotte ?? "",

        _imgKey: 0,
    };
}

const form = useForm({
    Nordine: props.ordine?.Nordine ?? null,
    righe: props.elementi?.length
        ? props.elementi.map((e) => newRigaFromElemento(e))
        : [newRigaFromElemento()],
});

onMounted(() => {
    if (!form.Nordine && props.ordine?.Nordine)
        form.Nordine = props.ordine.Nordine;
});

/* Enter -> campo successivo (non textarea) */
const focusNext = (event) => {
    const tag = (event.target?.tagName || "").toLowerCase();
    if (tag === "textarea") return;

    event.preventDefault();
    const formEl = event.target.form;
    if (!formEl) return;

    const focusable = Array.from(
        formEl.querySelectorAll(
            'input:not([disabled]):not([type="hidden"]), select:not([disabled]), button:not([disabled])'
        )
    ).filter((el) => el.offsetParent !== null);

    const index = focusable.indexOf(event.target);
    if (index > -1 && focusable[index + 1]) focusable[index + 1].focus();
};

/* ===================== Lookups base ===================== */
const modelliOrdinati = computed(() => {
    return [...(props.modelli ?? [])].sort((a, b) =>
        (a.nome_modello ?? "").localeCompare(b.nome_modello ?? "", "it", {
            sensitivity: "base",
        })
    );
});

function listinoById(idListino) {
    return (
        props.modelli.find((m) => Number(m.id_listino) === Number(idListino)) ||
        null
    );
}

function modelloById(id) {
    return (
        props.modelli.find(
            (m) => Number(m.id_listino ?? m.id ?? m.ID) === Number(id)
        ) || null
    );
}

function modelloNome(m) {
    return (
        m?.nome_modello ??
        m?.NomeModello ??
        m?.nome ??
        m?.Nome ??
        m?.modello ??
        m?.Modello ??
        ""
    )
        .toString()
        .trim();
}

function modelloNomePerRiga(riga) {
    const m = listinoById(riga.IdModello);
    return String(m?.nome_modello ?? "").trim();
}

function filtroTipTelById(id_listino) {
    const m = props.modelli.find(
        (x) => Number(x.id_listino) === Number(id_listino)
    );
    return String(m?.filtro_tit_tel ?? "").trim();
}

function filtroSoluzionePerRiga(riga) {
    const s =
        props.soluzioni.find(
            (x) => Number(x.id_tab_soluzioni) === Number(riga.IdSoluzione)
        ) || null;
    return String(s?.ass_collistino ?? "").trim();
}

function soluzioneCodePerRiga(riga) {
    const s = props.soluzioni.find(
        (x) => Number(x.id_tab_soluzioni) === Number(riga.IdSoluzione)
    );
    return (s?.ass_collistino ?? "").toString().trim().toUpperCase();
}

function filtroCollezionePerRiga(riga) {
    const l = listinoById(riga.IdModello);
    return String(l?.collezione ?? "").trim();
}

/* ===================== Foto ===================== */
function fotoUrlForRiga(riga) {
    const m = modelloById(riga.IdModello);
    const nome = modelloNome(m);
    if (!nome) return "/Foto/placeholder.jpg";
    return `/Foto/${encodeURIComponent(nome)}.jpg?v=${riga._imgKey ?? 0}`;
}

/* ===================== Filtri + opzioni per riga ===================== */
function tipologiaSoluzioniPerRiga(riga) {
    const listino = listinoById(riga.IdModello);
    return (listino?.soluzioni ?? "").toString().trim();
}
function soluzioniPerRiga(riga) {
    const tip = tipologiaSoluzioniPerRiga(riga);
    if (!tip) return [];
    return (Array.isArray(props.soluzioni) ? props.soluzioni : []).filter(
        (s) => String(s.tipologia ?? "").trim() === tip
    );
}

function tipologiaAntaPerRiga(riga) {
    const listino = listinoById(riga.IdModello);
    return (listino?.finiture_anta ?? "").toString().trim();
}
function coloriAntaPerRiga(riga) {
    const tip = tipologiaAntaPerRiga(riga);
    if (!tip) return [];
    return (Array.isArray(props.colAnta) ? props.colAnta : []).filter(
        (x) => String(x.Tipologia ?? x.tipologia ?? "").trim() === tip
    );
}

const SOLUZIONI_SPECIALI = new Set([
    "ESLIDEM1",
    "ESLIDEM2",
    "ESLIDES1",
    "ESLIDES2",
    "SE",
    "SES",
    "SE2M",
    "SE2S",
]);

function isSoluzioneSpeciale(riga) {
    return SOLUZIONI_SPECIALI.has(soluzioneCodePerRiga(riga));
}

function tipologiaTelaioPerRiga(riga) {
    const listino = listinoById(riga.IdModello);
    return (listino?.finiture_telaio ?? "").toString().trim();
}

function finitureTelaioPerRiga(riga) {
    const tip = tipologiaTelaioPerRiga(riga);
    if (!tip) return [];

    let out = props.colTelaio.filter((x) => String(x.Tipologia).trim() === tip);

    if (isSoluzioneSpeciale(riga)) {
        out = out.filter((x) => !String(x.Colore).includes("(S"));
    }

    return out.sort((a, b) => String(a.Colore).localeCompare(String(b.Colore)));
}

function getFiltroman(m) {
    const v =
        m?.Filtroman ??
        m?.filtroman ??
        m?.FiltroMan ??
        m?.filtroMan ??
        m?.filtro_man ??
        m?.FiltroManiglia ??
        "";
    return String(v).trim();
}

function filtroManigliaPerRiga(riga) {
    const l = listinoById(riga.IdModello);
    return (l?.maniglie ?? "").toString().trim();
}

const SOLUZIONI_MANIGLIE_SOLO_ZERO = new Set([
    "LIBS",
    "SE",
    "SES",
    "SE2M",
    "SE2S",
    "SI",
    "SIS",
    "SI2M",
    "SI2S",
    "ESLIDEM1",
    "ESLIDEM2",
    "ESLIDES1",
    "ESLIDES2",
]);

function manigliePerRiga(riga) {
    const sol = soluzioneCodePerRiga(riga);
    const filtro = String(filtroManigliaPerRiga(riga) ?? "").trim();
    const all = Array.isArray(props.maniglie) ? props.maniglie : [];
    if (!all.length) return [];

    const soloZero = SOLUZIONI_MANIGLIE_SOLO_ZERO.has(sol);

    let out = all.filter((m) => {
        const f = getFiltroman(m);
        if (soloZero) return f === "0";
        return f === "0" || f === filtro;
    });

    if (!out.length) out = all.filter((m) => getFiltroman(m) === "0");

    out.sort((a, b) => {
        const fa = getFiltroman(a).localeCompare(getFiltroman(b));
        if (fa !== 0) return fa;
        return String(a.DesMan ?? "").localeCompare(String(b.DesMan ?? ""));
    });

    return out;
}

/* --- token list style Access: Like '%;RT;%' --- */
function hasToken(needleList, token) {
    const v = String(needleList ?? "").trim();
    if (!v) return false;
    if (v === "T") return true;

    const hay = v.startsWith(";") ? v : ";" + v;
    const hay2 = hay.endsWith(";") ? hay : hay + ";";
    const tok = ";" + String(token ?? "").trim() + ";";
    return hay2.includes(tok);
}

function filtroColoreTelaioPerRiga(riga) {
    const all = Array.isArray(props.colTelaio) ? props.colTelaio : [];
    const rec = all.find(
        (x) => Number(x.IdFinTelaio) === Number(riga.IdColTelaio)
    );
    return String(rec?.FiltroTipoTel ?? "").trim();
}

function stipiteOk(stipite, filtroSoluzione, filtroModello) {
    const s = String(stipite ?? "").toLowerCase();
    const sol = String(filtroSoluzione ?? "").toUpperCase();
    const mod = String(filtroModello ?? "").toUpperCase();

    if (sol === "RT" && s.includes("r60")) return false;
    if (mod === "RFC" && s.includes("interna ed esterna")) return false;

    if (mod === "PSP") {
        if (s.includes("r60")) return false;
        if (s.includes("100")) return false;
    }
    return true;
}

function tipiTelaioPerRiga(riga) {
    const all = Array.isArray(props.tipiTelaio) ? props.tipiTelaio : [];

    const filtroTipTel = String(filtroTipTelById(riga.IdModello) ?? "").trim();
    const filtroColore = String(filtroColoreTelaioPerRiga(riga) ?? "").trim();
    const filtroSol = String(filtroSoluzionePerRiga(riga) ?? "").trim();

    const out = all.filter((tt) => {
        const okSol = hasToken(tt.filtro_soluzione, filtroSol);
        const okMod = hasToken(tt.filtro, filtroTipTel);
        const okSti = stipiteOk(tt.stipite, filtroSol, filtroTipTel);

        const okCol = (() => {
            const fcDB = String(tt.filtro_colore ?? "").trim();
            if (fcDB === "T") return true;
            if (!filtroColore) return true;
            return fcDB.toLowerCase().includes(filtroColore.toLowerCase());
        })();

        return okSol && okMod && okSti && okCol;
    });

    out.sort(
        (a, b) => Number(a.id_tipo_telaio ?? 0) - Number(b.id_tipo_telaio ?? 0)
    );
    return out;
}

/* ===================== IMBOTTE (logica VBA) ===================== */
function range48_999(dimSp) {
    dimSp = Number(dimSp ?? 0);
    if (dimSp <= 80) return [48, 80];
    if (dimSp <= 110) return [81, 110];
    if (dimSp <= 130) return [111, 130];
    return [131, 999];
}

function getFiltriTipoTelaio(idTipTelaio) {
    const t = (props.tipiTelaio ?? []).find(
        (x) => x.id_tipo_telaio === idTipTelaio
    );
    return {
        filtro1: (t?.filtro_imbotte ?? "").toString(),
        filtro2: (t?.filtro_imbotte2 ?? "").toString(),
    };
}

function calcolaFiltroImbotte(soluzione, filtro1, filtro2, dimSp) {
    let noImbotte = false;
    let da = null;
    let a = null;

    const grpA = [
        "TELP",
        "TELSI",
        "LibRB",
        "BT",
        "BT2A",
        "BT2S",
        "LIBA",
        "LIBS",
        "TELBT",
    ];
    const grpB = [
        "TELP",
        "SE",
        "SE2M",
        "SE2S",
        "SES",
        "ESLIDEM1",
        "ESLIDEM2",
        "ESLIDES1",
        "ESLIDES2",
    ];
    const grpSI = ["SI", "SIS", "SI2M", "SI2S"];

    if (grpA.includes(soluzione)) {
        if (filtro2 === "" || dimSp <= 47) noImbotte = true;
        else if (filtro2 === "0") {
            if (filtro1 === "4" || filtro1 === "5")
                [da, a] = range48_999(dimSp);
            else if (filtro1 === "") noImbotte = true;
        } else if (["1", "2", "3"].includes(filtro2) && dimSp <= 145)
            noImbotte = true;
        else if (filtro2 === "4" && dimSp <= 140) noImbotte = true;
        else {
            if (["1", "2", "3"].includes(filtro2)) {
                if (dimSp > 145 && dimSp <= 200) [da, a] = [151, 200];
                else if (dimSp >= 201 && dimSp <= 300) [da, a] = [201, 300];
                else if (dimSp >= 301 && dimSp <= 400) [da, a] = [301, 400];
                else if (dimSp >= 401 && dimSp <= 500) [da, a] = [401, 500];
                else if (dimSp >= 501 && dimSp <= 600) [da, a] = [501, 600];
            } else if (filtro2 === "4") {
                if (dimSp >= 141 && dimSp <= 200) [da, a] = [151, 200];
                else if (dimSp >= 201 && dimSp <= 300) [da, a] = [201, 300];
                else if (dimSp >= 301 && dimSp <= 400) [da, a] = [301, 400];
                else if (dimSp >= 401 && dimSp <= 500) [da, a] = [401, 500];
                else if (dimSp >= 501 && dimSp <= 600) [da, a] = [501, 600];
            } else {
                [da, a] = [501, 600];
            }
        }
    }

    if (soluzione === "RT") {
        if (filtro2 === "") noImbotte = true;
        else if (["1", "2", "3"].includes(filtro2) && dimSp <= 125)
            noImbotte = true;
        else if (filtro2 === "4" && dimSp <= 120) noImbotte = true;
        else {
            if (["1", "2", "3"].includes(filtro2)) {
                if (dimSp > 125 && dimSp <= 200) [da, a] = [151, 200];
                else if (dimSp >= 201 && dimSp <= 300) [da, a] = [201, 300];
                else if (dimSp >= 301 && dimSp <= 400) [da, a] = [301, 400];
                else if (dimSp >= 401 && dimSp <= 500) [da, a] = [401, 500];
                else if (dimSp >= 501 && dimSp <= 600) [da, a] = [501, 600];
            } else if (filtro2 === "4") {
                if (dimSp >= 121 && dimSp <= 200) [da, a] = [151, 200];
                else if (dimSp >= 201 && dimSp <= 300) [da, a] = [201, 300];
                else if (dimSp >= 301 && dimSp <= 400) [da, a] = [301, 400];
                else if (dimSp >= 401 && dimSp <= 500) [da, a] = [401, 500];
                else if (dimSp >= 501 && dimSp <= 600) [da, a] = [501, 600];
            } else {
                [da, a] = [501, 600];
            }
        }
    }

    if (grpB.includes(soluzione)) {
        if (filtro2 === "") noImbotte = true;
        else if (["1", "2", "3"].includes(filtro2) && dimSp <= 160)
            noImbotte = true;
        else if (filtro2 === "4" && dimSp <= 150) noImbotte = true;
        else {
            if (["1", "2", "3"].includes(filtro2)) {
                if (dimSp > 160 && dimSp <= 200) [da, a] = [151, 200];
                else if (dimSp >= 201 && dimSp <= 300) [da, a] = [201, 300];
                else if (dimSp >= 301 && dimSp <= 400) [da, a] = [301, 400];
                else if (dimSp >= 401 && dimSp <= 500) [da, a] = [401, 500];
                else if (dimSp >= 501 && dimSp <= 600) [da, a] = [501, 600];
            } else if (filtro2 === "4") {
                if (dimSp >= 121 && dimSp <= 200) [da, a] = [151, 200];
                else if (dimSp >= 201 && dimSp <= 300) [da, a] = [201, 300];
                else if (dimSp >= 301 && dimSp <= 400) [da, a] = [301, 400];
                else if (dimSp >= 401 && dimSp <= 500) [da, a] = [401, 500];
                else if (dimSp >= 501 && dimSp <= 600) [da, a] = [501, 600];
            } else {
                [da, a] = [501, 600];
            }
        }
    }

    if (grpSI.includes(soluzione)) {
        if (filtro2 === "") noImbotte = true;
        else if (filtro2 === "1" && dimSp <= 125) noImbotte = true;
        else if (filtro2 === "2" && dimSp <= 135) noImbotte = true;
        else if (filtro2 === "3" && dimSp <= 125) noImbotte = true;
        else if (filtro2 === "4" && dimSp <= 135) noImbotte = true;
        else {
            if (dimSp > 125 && dimSp <= 130) [da, a] = [125, 130];
            else if (dimSp > 130 && dimSp <= 200) [da, a] = [131, 200];
            else if (dimSp >= 201 && dimSp <= 300) [da, a] = [201, 300];
            else if (dimSp >= 301 && dimSp <= 400) [da, a] = [301, 400];
            else if (dimSp >= 401 && dimSp <= 500) [da, a] = [401, 500];
            else if (dimSp >= 501 && dimSp <= 600) [da, a] = [501, 600];
            else [da, a] = [501, 600];
        }
    }

    return { noImbotte, da, a };
}

function imbottePerRiga(riga) {
    const all = props.imbotte ?? [];
    const idTip = Number(riga.IdTipTelaio ?? 0);

    const soluzione = filtroSoluzionePerRiga(riga);
    const dimSp = Number(riga.DimSp ?? 0);

    if (!idTip || !soluzione) return [];

    const { filtro1, filtro2 } = getFiltriTipoTelaio(idTip);
    const res = calcolaFiltroImbotte(soluzione, filtro1, filtro2, dimSp);

    const isBase = (i) =>
        Number(i.importo ?? 0) === 0 &&
        Number(i.spess_muro_da ?? 0) === 0 &&
        Number(i.spess_muro_a ?? 0) === 0 &&
        String(i.filtro_sistema ?? "") === "T";

    if (res.noImbotte) {
        return all.filter((i) => {
            const sp0 =
                Number(i.spess_muro_da ?? 0) === 0 &&
                Number(i.spess_muro_a ?? 0) === 0;
            const fs = String(i.filtro_sistema ?? "") === "T";
            return sp0 && fs;
        });
    }

    return all
        .filter((i) => {
            if (isBase(i)) return true;

            const da = Number(i.spess_muro_da ?? 0);
            const a = Number(i.spess_muro_a ?? 0);
            const inRange = da >= res.da && a <= res.a;

            const ftt = String(i.filtro_tipo_telaio ?? "");
            const okFiltro = soluzione.includes("SI")
                ? ftt.startsWith("T")
                : ftt.includes(`;${filtro1};`);

            return inRange && okFiltro;
        })
        .sort((x, y) =>
            String(x.des_imbotte ?? "").localeCompare(
                String(y.des_imbotte ?? ""),
                "it",
                {
                    sensitivity: "base",
                }
            )
        );
}

/* ===================== Aperture ===================== */
function aperturePerRiga(riga) {
    const all = Array.isArray(props.aperture) ? props.aperture : [];
    if (!all.length) return [];

    const tipAnta = tipologiaAntaPerRiga(riga);
    if (tipAnta === "FA00")
        return all.filter((a) => Number(a.IdApertura) === 3);
    return all.filter((a) => [1, 2].includes(Number(a.IdApertura)));
}

/* ===================== Cerniere/Ferramenta ===================== */
function pick(obj, keys, fallback = "") {
    for (const k of keys) {
        if (obj && obj[k] !== undefined && obj[k] !== null) return obj[k];
    }
    return fallback;
}

function tokenListHas(list, token) {
    const l = String(list ?? "")
        .trim()
        .toUpperCase();
    const t = String(token ?? "")
        .trim()
        .toUpperCase();
    if (!l || !t) return false;
    if (l === "T") return true;

    const hay = l.startsWith(";") ? l : ";" + l;
    const hay2 = hay.endsWith(";") ? hay : hay + ";";
    return hay2.includes(";" + t + ";");
}

function cernId(c) {
    return pick(c, [
        "id_col_ferr",
        "IdColFerr",
        "IDColFerr",
        "idColFerr",
        "id",
    ]);
}
function cernDes(c) {
    return pick(c, [
        "des_cernira",
        "DesCernira",
        "DesCerniere",
        "des",
        "Descrizione",
    ]);
}
function cernImporto(c) {
    return pick(c, ["importo", "Importo", "prezzo", "Prezzo"]);
}
function cernFiltroSistema(c) {
    return String(
        pick(
            c,
            ["filtro_sistema", "FiltroSistema", "Filtro", "filtroSistema"],
            ""
        )
    ).trim();
}
function cernCollezione(c) {
    return String(
        c?.collezione ??
            c?.Collezione ??
            c?.COLLEZIONE ??
            c?.collez ??
            c?.Collez ??
            ""
    ).trim();
}

const SET_TELP = new Set([
    "TELSI",
    "TELP",
    "TELBT29",
    "TELBT49",
    "TELBT60",
    "RT",
    "LIBA",
    "LIBS",
    "SE",
    "SES",
    "SE2M",
    "SE2S",
    "SI",
    "SIS",
    "SI2M",
    "SI2S",
    "ESLIDEM1",
    "ESLIDEM2",
    "ESLIDES1",
    "ESLIDES2",
]);

function cernierePerRiga(riga) {
    const all = Array.isArray(props.cerniere) ? props.cerniere : [];
    if (!all.length) return [];

    const modelloNomeSel = String(
        modelloNome(modelloById(riga.IdModello)) ?? ""
    ).trim();
    const filtroSoluzione = filtroSoluzionePerRiga(riga);
    const filtroCollezione = filtroCollezionePerRiga(riga);

    if (modelloNomeSel === "BL1" || modelloNomeSel === "BL2") {
        return all
            .filter((c) => cernFiltroSistema(c) === "BL")
            .sort((a, b) =>
                String(cernDes(a)).localeCompare(String(cernDes(b)))
            );
    }

    if (SET_TELP.has(filtroSoluzione) || SET_TELP.has(modelloNomeSel)) {
        return all
            .filter((c) => cernFiltroSistema(c) === "TELP")
            .sort((a, b) =>
                String(cernDes(a)).localeCompare(String(cernDes(b)))
            );
    }

    return all
        .filter((c) => {
            if (cernFiltroSistema(c) !== "T") return false;
            if (!filtroCollezione) return true;
            return tokenListHas(cernCollezione(c), filtroCollezione);
        })
        .sort((a, b) => Number(cernId(a) ?? 0) - Number(cernId(b) ?? 0));
}

/* ===================== Serrature ===================== */
function filtroSerraturaPerRiga(riga) {
    const s = props.soluzioni.find(
        (x) => Number(x.id_tab_soluzioni) === Number(riga.IdSoluzione)
    );
    const v =
        s?.filtro_serr ??
        s?.FiltroSerr ??
        s?.filtroSerr ??
        s?.filtro_serratura ??
        null;
    return v === null || v === undefined ? null : Number(v);
}

function serraturePerRiga(riga) {
    const all = Array.isArray(props.serrature) ? props.serrature : [];
    if (!all.length) return [];

    const mod = modelloNomePerRiga(riga).toUpperCase();
    const filtroSol = filtroSerraturaPerRiga(riga);

    const filtroSistema = mod === "BL1" || mod === "BL2" ? 6 : filtroSol;
    if (filtroSistema === null || Number.isNaN(filtroSistema)) return [];

    return all
        .filter(
            (x) =>
                Number(x.FiltroSistema ?? x.filtro_sistema) ===
                Number(filtroSistema)
        )
        .sort((a, b) =>
            String(a.DesSerratura ?? a.des_serratura ?? "").localeCompare(
                String(b.DesSerratura ?? b.des_serratura ?? "")
            )
        );
}

/* ===================== Vetri ===================== */
function colonnaListVetroPerRiga(riga) {
    const modello = String(modelloNomePerRiga(riga) ?? "")
        .trim()
        .toUpperCase();
    if (!modello) return "";

    const list = Array.isArray(props.assModVetri) ? props.assModVetri : [];

    const ass = list.find((x) => {
        const lista = String(x.des_modello ?? "")
            .toUpperCase()
            .trim();
        if (!lista) return false;

        const hay = lista.startsWith(";") ? lista : ";" + lista;
        const hay2 = hay.endsWith(";") ? hay : hay + ";";
        return hay2.includes(";" + modello + ";");
    });

    return String(ass?.colonna_list_vetro ?? "").trim();
}

function vetriPerRiga(riga) {
    const col = colonnaListVetroPerRiga(riga);

    if (!col) {
        return (props.vetri ?? []).filter(
            (v) => (v.des_vetro ?? "").trim() === "No Vetro"
        );
    }

    return (props.vetri ?? []).filter((v) => {
        const val = v[col];
        return val !== null && val !== "" && !isNaN(Number(val));
    });
}

/* ===================== Cascata select (default primo valore) ===================== */
function ensureSoluzioneValida(riga) {
    if (!riga.IdModello) {
        riga.IdSoluzione = null;
        return;
    }
    ensureFirstValid(
        riga,
        "IdSoluzione",
        soluzioniPerRiga(riga),
        (s) => s.id_tab_soluzioni
    );
}
function ensureAntaValida(riga) {
    if (!riga.IdModello) {
        riga.IdColAnta = null;
        return;
    }
    ensureFirstValid(
        riga,
        "IdColAnta",
        coloriAntaPerRiga(riga),
        (a) => a.IdFinAnta
    );
}
function ensureTelaioValido(riga) {
    if (!riga.IdModello) {
        riga.IdColTelaio = null;
        return;
    }
    ensureFirstValid(
        riga,
        "IdColTelaio",
        finitureTelaioPerRiga(riga),
        (t) => t.IdFinTelaio
    );
}
function ensureTipoTelaioValido(riga) {
    ensureFirstValid(
        riga,
        "IdTipTelaio",
        tipiTelaioPerRiga(riga),
        (tt) => tt.id_tipo_telaio
    );
}
function ensureImbotteValida(riga) {
    ensureFirstValid(
        riga,
        "IdImbotte",
        imbottePerRiga(riga),
        (i) => i.id_imbotte
    );
}
function ensureManigliaValida(riga) {
    if (!riga.IdModello) {
        riga.IdManiglia = null;
        return;
    }
    ensureFirstValid(
        riga,
        "IdManiglia",
        manigliePerRiga(riga),
        (m) => m.IdManiglia
    );
}
function ensureAperturaValida(riga) {
    ensureFirstValid(
        riga,
        "IdApertura",
        aperturePerRiga(riga),
        (a) => a.IdApertura
    );
}
function ensureVetroValido(riga) {
    ensureFirstValid(riga, "IdVetro", vetriPerRiga(riga), (v) => v.id_vetro);
}
function ensureFerramentaValida(riga) {
    ensureFirstValid(riga, "IdColFerr", cernierePerRiga(riga), (c) =>
        cernId(c)
    );
}
function ensureSerraturaValida(riga) {
    ensureFirstValid(
        riga,
        "IdSerratura",
        serraturePerRiga(riga),
        (s) => s.IdSerratura ?? s.id_serratura
    );
}

function cascadeRiga(riga) {
    if (!riga.IdModello) {
        riga.IdSoluzione = null;
        riga.IdColAnta = null;
        riga.IdColTelaio = null;
        riga.IdTipTelaio = null;
        riga.IdImbotte = null;
        riga.IdManiglia = null;
        riga.IdApertura = null;
        riga.IdVetro = null;
        riga.IdColFerr = null;
        riga.IdSerratura = null;
         riga.PrezzoCad = 0;
        return;
    }

    ensureSoluzioneValida(riga);
    ensureAntaValida(riga);
    ensureTelaioValido(riga);

    ensureTipoTelaioValido(riga);
    ensureImbotteValida(riga);

    ensureManigliaValida(riga);
    ensureAperturaValida(riga);

    ensureVetroValido(riga);

    ensureFerramentaValida(riga);
    ensureSerraturaValida(riga);
    syncPrezzoCad(riga);
}

/* ===================== Prezzo ===================== */
async function aggiornaPrezzoCad(riga) {
    const payload = {
        IdModello: riga.IdModello,
        IdColAnta: riga.IdColAnta,
        IdColTelaio: riga.IdColTelaio,
        IdSoluzione: riga.IdSoluzione,
        IdManiglia: riga.IdManiglia,
        IdApertura: riga.IdApertura,
        IdTipTelaio: riga.IdTipTelaio,
        IdVetro: riga.IdVetro,
        IdColFerr: riga.IdColFerr,
        DimL: riga.DimL,
        DimA: riga.DimA,
        DimSp: riga.DimSp,
        IdSerratura: riga.IdSerratura,
        CkTaglioObl: riga.CkTaglioObl,
        IdImbotte: riga.IdImbotte,
        NANTE: 1,
        PrezzoCad: riga.PrezzoCad,
    };
}

async function refreshPrezzo(riga) {
  //  try {
  //      // await aggiornaPrezzoCad(riga);
  //  } catch (e) {
  //      console.log("refreshPrezzo error", e);
  //  }
}

/* ===================== Watchers ===================== */
// 1) cambio modello -> FOTO + cascata + prezzo
watch(
    () => form.righe.map((r) => r.IdModello),
    (newV, oldV) => {
        form.righe.forEach((riga, i) => {
            if (newV?.[i] === oldV?.[i]) return;
            bumpImgKeyOnly(riga);
            cascadeRiga(riga);
             refreshPrezzo(riga);
        });
    },
    { immediate: true }
);

// 2) campi che influenzano filtri/prezzo -> cascata + prezzo (NO foto)
watch(
    () =>
        form.righe.map((r) =>
            [
                r.IdSoluzione,
                r.IdColAnta,
                r.IdColTelaio,
                r.IdManiglia,
                r.IdApertura,
                r.IdTipTelaio,
                r.IdVetro,
                r.IdColFerr,
                r.IdSerratura,
                r.IdImbotte,
                r.DimL,
                r.DimA,
                r.DimSp,
                r.CkTaglioObl,

            ].join("|")
        ),
    (newV, oldV) => {
        form.righe.forEach((riga, i) => {
            if (newV?.[i] === oldV?.[i]) return;
            cascadeRiga(riga);
          refreshPrezzo(riga);
        });
    }
);

/* ===================== Totali ===================== */
const totalePreventivo = computed(() =>
    form.righe.reduce((sum, r) => {
        const q = Number(r.Qta ?? 0);
        const p = Number(r.PrezzoCad ?? 0);
        const m = Number(r.PrezzoMan ?? 0);
        console.log("q", q);
        console.log("p", p);
        console.log("m", m);

        return sum + q * (p + m);
    }, 0)
);

function totaleRiga(r) {
    const q = Number(r.Qta ?? 0);
    const p = Number(r.PrezzoCad ?? 0);
    const m = Number(r.PrezzoMan ?? 0);
    return +(q * (p + m)).toFixed(2);
}

/* ===================== Azioni righe ===================== */
function addRiga() {
    form.righe.push(newRigaFromElemento());
    toast.success("➕ Riga aggiunta", { position: "top-left", timeout: 1200 });
}

function removeRigaLocal(i) {
    form.righe.splice(i, 1);
    toast.info("Riga rimossa (non ancora salvata)", {
        position: "top-left",
        timeout: 1200,
    });
}

function copyRiga(riga, index) {
    const copia = {
        ...JSON.parse(JSON.stringify(riga)),
        uid: crypto?.randomUUID
            ? crypto.randomUUID()
            : String(Date.now() + Math.random()),
        Id: null,
        _imgKey: (riga._imgKey ?? 0) + 1,
    };

    form.righe.splice(index + 1, 0, copia);
    toast.success("📋 Riga copiata", { position: "top-left", timeout: 1200 });
}

function destroyRiga(riga, index) {
    if (!riga.Id) {
        removeRigaLocal(index);
        return;
    }

    if (confirmDeleteUid.value !== riga.uid) {
        confirmDeleteUid.value = riga.uid;

        toast.warning(
            "⚠️ Confermi eliminazione riga? Premi di nuovo Elimina.",
            {
                position: "top-left",
                timeout: 4000,
                closeOnClick: false,
                draggable: false,
                onClose: () => {
                    if (confirmDeleteUid.value === riga.uid)
                        confirmDeleteUid.value = null;
                },
            }
        );
        return;
    }

    router.delete(route("preventivi.destroy", [props.ordine.ID, riga.Id]), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("🗑️ Riga eliminata", {
                position: "top-left",
                timeout: 1800,
            });
            const idx = form.righe.findIndex((x) => x.uid === riga.uid);
            if (idx !== -1) form.righe.splice(idx, 1);
            confirmDeleteUid.value = null;
        },
        onError: (errs) => {
            console.log("DELETE ERR", errs);
            toast.error("❌ Errore durante l’eliminazione", {
                position: "top-left",
                timeout: 3000,
            });
            confirmDeleteUid.value = null;
        },
    });
}
function listinoPorta(riga) {
    const m = listinoById(riga.IdModello);
    if (!m) return 0;
    const col = String(filtroSoluzionePerRiga(riga) || "")
        .trim()
        .toLowerCase();
    if (!col) return 0;

    const map = Object.fromEntries(
        Object.entries(m).map(([k, v]) => [String(k).toLowerCase(), v])
    );
    return Number(map[col] ?? 0) || 0;
}

function tipiTelaioById(IdTipTelaio) {
    return (
        props.tipiTelaio.find(
            (tt) => Number(tt.id_tipo_telaio) === Number(IdTipTelaio)
        ) || null
    );
}
function MaggKitScFM(riga) {
    const tt = tipiTelaioById(riga.IdTipTelaio);
    if (!tt) return 0;
    return Number(tt?.magg_kit_scorr ?? 0) || 0;
}
function MaggManuale(riga) {
    const mm = Number(riga.PrezzoMan ?? 0);
    return Number.isFinite(mm) ? mm : 0;
}


function MaggCstTelP(riga) {
    const tt = tipiTelaioById(riga.IdTipTelaio);
    if (!tt) return 0;
    const col =
        "cst_" &&
        String(filtroSoluzionePerRiga(riga) || "")
            .trim()
            .toLowerCase();
    if (!col) return 0;
    const map = Object.fromEntries(
        Object.entries(tt).map(([k, v]) => [String(k).toLowerCase(), v])
    );
    return Number(map[col] ?? 0) || 0;
}
function ColAntaById(IdColAnta) {
    return (
        props.colAnta.find(
            (fa) => Number(fa.IdFinAnta) === Number(IdColAnta)
        ) || null
    );
}

function MaggColAnta(riga) {
    const fa = ColAntaById(riga.IdColAnta);
    if (!fa) return 0;
    return Number(fa?.MaggAnta ?? 0) || 0;
}
function MaggMan(riga) {
    const ma =
        props.maniglie.find(
            (ma) => Number(ma.IdManiglia) === Number(riga.IdManiglia)
        ) || null;
    if (!ma) return 0;
    return Number(ma?.Importo ?? 0) || 0;
}
function MaggCer(riga) {
    const cr =
        props.cerniere.find(
            (cr) => Number(cr.id_col_ferr) === Number(riga.IdColFerr)
        ) || null;
    if (!cr) return 0;
    return Number(cr?.importo ?? 0) || 0;
}
function MaggSerr(riga) {
    const se =
        props.serrature.find(
            (se) => Number(se.id_serratura) === Number(riga.IdSerratura)
        ) || null;
    if (!se) return 0;
    return Number(se?.importo ?? 0) || 0;
}
function MaggVetro(riga) {
    const col = String(colonnaListVetroPerRiga(riga) || "")
        .trim()
        .toLowerCase();

    //  console.log("MaggVetro col =", col);

    if (!col) return 0;

    const vt =
        props.vetri?.find((v) => Number(v.id_vetro) === Number(riga.IdVetro)) ||
        null;

    if (!vt) {
        console.log("MaggVetro: vetro non trovato", riga.IdVetro);
        return 0;
    }

    // accesso case-insensitive
    const map = Object.fromEntries(
        Object.entries(vt).map(([k, v]) => [String(k).toLowerCase(), v])
    );

    const val = Number(map[col] ?? 0);

    console.log("MaggVetro OK", { col, val });

    return val || 0;
}

function totaleRigaD(riga) {
    return (
        listinoPorta(riga) +
        MaggManuale(riga)+
        MaggKitScFM(riga) +
        MaggCstTelP(riga) +
        MaggColAnta(riga) +
        MaggVetro(riga) +
        MaggMan(riga) +
        MaggSerr(riga) +
        MaggCer(riga)
    );
}
function syncPrezzoCad(riga) {
    // evita NaN, forza numero a 2 decimali
    const val = Number(totaleRigaD(riga) ?? 0);
    riga.PrezzoCad = Number.isFinite(val) ? +val.toFixed(2) : 0;
}
/* ===================== Submit ===================== */
function submitAll() {
    form.Nordine = props.ordine?.Nordine ?? form.Nordine;

    form.post(route("preventivi.store", props.ordine.ID), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("✅ Preventivo salvato", { position: "top-left" });
            router.reload({ only: ["elementi"] });
        },
        onError: () =>
            toast.error("❌ Errore nel salvataggio", { position: "top-left" }),
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Preventivo" />

        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- HEADER -->
                <div
                    class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                >
                    <div class="flex items-center gap-3">
                        <img
                            src="/Logo.jpg"
                            alt="Logo"
                            class="h-10 w-auto hidden md:block"
                        />
                        <div>
                            <div class="flex items-center gap-2">
                                <h1
                                    class="text-2xl font-extrabold tracking-tight"
                                >
                                    Preventivi · Nuovo
                                </h1>
                                <span
                                    class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-semibold"
                                    >ERP</span
                                >
                            </div>
                            <div class="text-sm text-gray-500">
                                Nordine
                                <span class="font-semibold text-gray-700"
                                    >#{{ props.ordine?.Nordine }}</span
                                >
                            </div>
                        </div>
                        <div
                            class="col-span-12 md:col-span-5 rounded-2xl border bg-white shadow-sm p-4"
                        >
                            <div class="text-xs font-semibold text-gray-500">
                                Totale preventivo
                            </div>
                            <div
                                class="mt-1 text-2xl font-extrabold text-gray-900"
                            >
                                € {{ totalePreventivo.toFixed(2) }}
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 justify-end">
                        <Link
                            :href="route('ordini.edit', props.ordine.ID)"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border bg-white hover:bg-gray-50 shadow-sm"
                        >
                            <ArrowLeft class="w-4 h-4" />
                            Torna ordine
                        </Link>

                        <button
                            type="button"
                            @click="addRiga"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-800 text-white hover:bg-slate-900 shadow-sm"
                        >
                            <Plus class="w-4 h-4" />
                            Aggiungi riga
                        </button>

                        <button
                            type="button"
                            :disabled="form.processing"
                            @click="submitAll"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-sm disabled:opacity-50"
                        >
                            <Save class="w-4 h-4" />
                            Salva preventivo
                        </button>
                    </div>
                </div>

                <!-- FORM -->
                <form @submit.prevent="submitAll">
                    <div
                        v-for="(riga, i) in form.righe"
                        :key="riga.uid"
                        class="mb-5 rounded-2xl border bg-white shadow-sm overflow-hidden"
                    >
                        <!-- Riga header -->
                        <div
                            class="px-5 py-3 bg-gradient-to-r from-slate-900 to-slate-700 text-white flex items-center justify-between"
                        >
                            <div class="font-semibold">Riga #{{ i + 1 }}</div>
                            <div class="flex items-center gap-2">
                                <div class="text-xs opacity-90">
                                    Tot riga:
                                    <span class="font-bold"
                                        >€
                                        {{ totaleRigaD(riga).toFixed(2) }}</span
                                    >
                                </div>
                                <!-- 📋 COPIA -->
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-600 hover:bg-slate-700 text-white text-sm"
                                    @click="copyRiga(riga, i)"
                                    title="Copia riga"
                                >
                                    📋 Copia
                                </button>
                                <!-- Elimina -->
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm"
                                    @click="destroyRiga(riga, i)"
                                    :title="
                                        confirmDeleteUid === riga.uid
                                            ? 'Conferma eliminazione'
                                            : 'Elimina riga'
                                    "
                                >
                                    <Trash2 class="w-4 h-4" />
                                    {{
                                        confirmDeleteUid === riga.uid
                                            ? "⚠️ Conferma"
                                            : "🗑️ Elimina"
                                    }}
                                </button>
                            </div>
                        </div>

                        <div class="p-5 grid grid-cols-12 gap-4">
                            <!-- CONFIG -->
                            <div class="col-span-12 lg:col-span-7">
                                <div
                                    class="rounded-2xl border bg-white overflow-hidden"
                                >
                                    <div
                                        class="px-4 py-2 bg-blue-50 border-b text-blue-900 font-semibold flex justify-between"
                                    >
                                        <span>🧩 Configurazione</span>
                                        <span class="text-xs text-blue-700"
                                            >Modello, colori, accessori</span
                                        >
                                    </div>

                                    <div
                                        class="p-3 grid grid-cols-12 gap-x-4 gap-y-1.5"
                                    >
                                        <div class="col-span-12 md:col-span-6">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                            >
                                                Modello
                                            </label>

                                            <select
                                                v-model.number="riga.IdModello"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                                @change="bumpImgKeyOnly(riga)"
                                            >
                                                <option
                                                    v-for="m in modelliOrdinati"
                                                    :key="m.id_listino"
                                                    :value="m.id_listino"
                                                >
                                                    {{ m.nome_modello }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-span-12 md:col-span-6">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Soluzione</label
                                            >
                                            <select
                                                v-model.number="
                                                    riga.IdSoluzione
                                                "
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm focus:ring focus:ring-blue-200"
                                                @keydown.enter.prevent="
                                                    focusNext
                                                "
                                                :disabled="!riga.IdModello"
                                            >
                                                <option
                                                    v-for="s in soluzioniPerRiga(
                                                        riga
                                                    )"
                                                    :key="s.id_tab_soluzioni"
                                                    :value="s.id_tab_soluzioni"
                                                >
                                                    {{ s.soluzione }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-span-12 md:col-span-6">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Colore pannello</label
                                            >
                                            <select
                                                v-model.number="riga.IdColAnta"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                                :disabled="!riga.IdModello"
                                                @keydown.enter.prevent="
                                                    focusNext
                                                "
                                            >
                                                <option
                                                    v-for="x in coloriAntaPerRiga(
                                                        riga
                                                    )"
                                                    :key="x.IdFinAnta"
                                                    :value="x.IdFinAnta"
                                                >
                                                    {{ x.Tipologia }} -
                                                    {{ x.Colore }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-span-12 md:col-span-6">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Colore Telaio</label
                                            >
                                            <select
                                                v-model.number="
                                                    riga.IdColTelaio
                                                "
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                                :disabled="!riga.IdModello"
                                            >
                                                <option
                                                    v-for="x in finitureTelaioPerRiga(
                                                        riga
                                                    )"
                                                    :key="x.IdFinTelaio"
                                                    :value="x.IdFinTelaio"
                                                >
                                                    {{ x.Colore }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-span-12 md:col-span-12">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Tipo Mostrine</label
                                            >
                                            <select
                                                v-model="riga.IdTipTelaio"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                            >
                                                <option
                                                    v-for="tt in tipiTelaioPerRiga(
                                                        riga
                                                    )"
                                                    :key="tt.id_tipo_telaio"
                                                    :value="tt.id_tipo_telaio"
                                                >
                                                    {{ tt.stipite }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-span-12 md:col-span-6">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Imbotte</label
                                            >

                                            <select
                                                v-model.number="riga.IdImbotte"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                            >
                                                <option
                                                    v-for="i in imbottePerRiga(
                                                        riga
                                                    )"
                                                    :key="i.id_imbotte"
                                                    :value="i.id_imbotte"
                                                >
                                                    {{ i.des_imbotte }} — €
                                                    {{
                                                        Number(
                                                            i.importo ?? 0
                                                        ).toFixed(2)
                                                    }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-span-12 md:col-span-6">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Maniglia</label
                                            >
                                            <select
                                                v-model.number="riga.IdManiglia"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                                :disabled="!riga.IdModello"
                                            >
                                                <option
                                                    v-for="m in manigliePerRiga(
                                                        riga
                                                    )"
                                                    :key="m.IdManiglia"
                                                    :value="m.IdManiglia"
                                                >
                                                    {{ m.DesMan }} — €
                                                    {{
                                                        Number(
                                                            m.Importo ?? 0
                                                        ).toFixed(2)
                                                    }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-span-12 md:col-span-6">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Apertura</label
                                            >
                                            <select
                                                v-model.number="riga.IdApertura"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                            >
                                                <option
                                                    v-for="a in aperturePerRiga(
                                                        riga
                                                    )"
                                                    :key="a.IdApertura"
                                                    :value="a.IdApertura"
                                                >
                                                    {{ a.Des }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-span-12 md:col-span-12">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Vetro</label
                                            >
                                            <select
                                                v-model.number="riga.IdVetro"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                            >
                                                <option
                                                    v-for="v in vetriPerRiga(
                                                        riga
                                                    )"
                                                    :key="v.id_vetro"
                                                    :value="v.id_vetro"
                                                >
                                                    {{ v.des_vetro }} — €
                                                    {{
                                                        Number(
                                                            v[
                                                                colonnaListVetroPerRiga(
                                                                    riga
                                                                )
                                                            ] ?? 0
                                                        ).toFixed(2)
                                                    }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-span-12 md:col-span-12">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Cerniere/Ferramenta</label
                                            >
                                            <select
                                                v-model.number="riga.IdColFerr"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                            >
                                                <option
                                                    v-for="c in cernierePerRiga(
                                                        riga
                                                    )"
                                                    :key="cernId(c)"
                                                    :value="cernId(c)"
                                                >
                                                    {{ cernDes(c) }} — €
                                                    {{
                                                        Number(
                                                            cernImporto(c) ?? 0
                                                        ).toFixed(2)
                                                    }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-span-12 md:col-span-12">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Serratura</label
                                            >
                                            <select
                                                v-model.number="
                                                    riga.IdSerratura
                                                "
                                                class="w-full rounded-lg border px-2 py-1.5 text-sm"
                                            >
                                                <option
                                                    v-for="s in serraturePerRiga(
                                                        riga
                                                    )"
                                                    :key="s.id_serratura"
                                                    :value="s.id_serratura"
                                                >
                                                    {{ s.des_serratura }} — €
                                                    {{
                                                        Number(
                                                            s.importo ?? 0
                                                        ).toFixed(2)
                                                    }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-span-12">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Note</label
                                            >
                                            <textarea
                                                v-model="riga.NoteMan"
                                                rows="1"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                                placeholder="Annotazioni lavorazioni..."
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- MISURE & PREZZI -->
                            <div class="col-span-12 lg:col-span-5">
                                <div
                                    class="rounded-2xl border bg-white overflow-hidden"
                                >
                                    <div
                                        class="px-4 py-2 bg-slate-50 border-b text-slate-900 font-semibold flex justify-between"
                                    >
                                        <span>📐 Misure & Prezzi</span>
                                        <span class="text-xs text-slate-500"
                                            >Qta + prezzi sotto immagine</span
                                        >
                                    </div>

                                    <div class="p-4">
                                        <!-- immagine più stretta -->

                                        <div
                                            class="rounded-lg border bg-white p-3 flex items-start gap-3 w-76 mx-auto shadow-sm"
                                        >
                                            <!-- IMMAGINE GRANDE -->
                                            <div class="flex-shrink-0">
                                                <img
                                                    :key="`main-${riga.IdModello}-${riga._imgKey}`"
                                                    :src="fotoUrlForRiga(riga)"
                                                    class="h-48 w-auto object-contain"
                                                    @click="
                                                        openZoom(
                                                            fotoUrlForRiga(riga)
                                                        )
                                                    "
                                                    @error="onImgError"
                                                />
                                            </div>

                                            <!-- THUMBNAILS A DESTRA -->
                                            <div
                                                class="flex gap-3 items-center"
                                            >
                                                <div
                                                    class="flex gap-3 items-center"
                                                >
                                                    <!-- immagine fissa -->
                                                    <img
                                                        src="/foto/Essenze/QR.jpg"
                                                        class="h-20 w-auto object-contain rounded border cursor-pointer hover:scale-105 transition"
                                                        @click="
                                                            openZoom(
                                                                '/foto/Essenze/QR.jpg'
                                                            )
                                                        "
                                                        @error="onImgError"
                                                    />
                                                    <img
                                                        src="/foto/Essenze/QRT.jpg"
                                                        class="h-20 w-auto object-contain rounded border cursor-pointer hover:scale-105 transition"
                                                        @click="
                                                            openZoom(
                                                                '/foto/Essenze/QRT.jpg'
                                                            )
                                                        "
                                                        @error="onImgError"
                                                    />
                                                    <img
                                                        src="/foto/Essenze/QRV.jpg"
                                                        class="h-20 w-auto object-contain rounded border cursor-pointer hover:scale-105 transition"
                                                        @click="
                                                            openZoom(
                                                                '/foto/Essenze/QRV.jpg'
                                                            )
                                                        "
                                                        @error="onImgError"
                                                    />
                                                </div>
                                                <! <img
                                                            :key="`thumb-3-${riga.IdModello}-${riga._imgKey}`"
                                                            :src="fotoUrlAntaRiga(riga)"
                                                            class="h-20 w-auto object-contain rounded border cursor-pointer hover:scale-105 transition"
                                                            @click="openZoom(fotoUrlAntaRiga(riga))"
                                                            @error="onImgError"
                                                        />
                                                sss -->
                                            </div>
                                        </div>

                                        <!-- misure -->
                                        <div
                                            class="mt-4 grid grid-cols-3 gap-3"
                                        >
                                            <div>
                                                <label
                                                    class="text-xs font-semibold text-gray-600"
                                                    >DimL</label
                                                >
                                                <input
                                                    v-model.number="riga.DimL"
                                                    type="number"
                                                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-xs font-semibold text-gray-600"
                                                    >DimA</label
                                                >
                                                <input
                                                    v-model.number="riga.DimA"
                                                    type="number"
                                                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-xs font-semibold text-gray-600"
                                                    >DimSp</label
                                                >
                                                <input
                                                    v-model.number="riga.DimSp"
                                                    type="number"
                                                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                        </div>

                                        <!-- qta e prezzi sotto immagine -->
                                        <div
                                            class="mt-4 grid grid-cols-3 gap-3"
                                        >
                                            <div>
                                                <label
                                                    class="text-xs font-semibold text-gray-600"
                                                    >Qta</label
                                                >
                                                <input
                                                    v-model.number="riga.Qta"
                                                    type="number"
                                                    min="1"
                                                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-xs font-semibold text-gray-600"
                                                    >Prezzo Cad</label
                                                >

                                            <input
                                                v-model.number="riga.PrezzoCad"
                                                type="number"
                                                step="0.01"
                                                readonly
                                                class="mt-1 w-full rounded-lg border px-3 py-2 text-sm bg-slate-100"
                                                @keydown.enter="focusNext"
                                            />
                                            </div>

                                            <div>
                                                <label
                                                    class="text-xs font-semibold text-gray-600"
                                                    >Magg. Manuale</label
                                                >
                                                <input
                                                    v-model.number="
                                                        riga.PrezzoMan
                                                    "
                                                    type="number"
                                                    step="0.01"
                                                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                        </div>

                                        <div
                                            class="mt-4 rounded-xl border bg-slate-50 p-3 flex items-center justify-between"
                                        >
                                            <div class="text-xs text-slate-600">
                                                Totale riga
                                            </div>
                                            <div
                                                class="text-lg font-extrabold text-slate-900"
                                            >
                                                €
                                                {{
                                                    totaleRiga(riga).toFixed(2)
                                                }}
                                            </div>
                                        </div>
                                        <div
                                            class="mt-4 rounded-xl border bg-slate-50 p-3 grid grid-cols-2 gap-y-2"
                                        >
                                            <div class="text-xs text-slate-600">
                                                Listino Porta
                                            </div>
<div class="text-lg font-extrabold text-slate-900 text-right">
                                                 €
                                                {{
                                                    listinoPorta(riga).toFixed(
                                                        2
                                                    )
                                                }}
                                            </div>
                                            <div class="text-xs text-slate-600">
                                                Maggiorazione Manuale
                                            </div>
 <div class="text-lg font-extrabold text-slate-900 text-right">
                                                €
                                                {{
                                                    MaggManuale(riga).toFixed(2)
                                                }}
                                            </div>
                                            <div class="text-xs text-slate-600">
                                                MaggKitScFM
                                            </div>
 <div class="text-lg font-extrabold text-slate-900 text-right">
                                                €
                                                {{
                                                    MaggKitScFM(riga).toFixed(2)
                                                }}
                                            </div>
                                            <div class="text-xs text-slate-600">
                                                MaggCstTelP
                                            </div>
 <div class="text-lg font-extrabold text-slate-900 text-right">
                                                €
                                                {{
                                                    MaggCstTelP(riga).toFixed(2)
                                                }}
                                            </div>
                                            <div class="text-xs text-slate-600">
                                                Magg. Anta
                                            </div>
<div class="text-lg font-extrabold text-slate-900 text-right">
                                                 €
                                                {{
                                                    MaggColAnta(riga).toFixed(2)
                                                }}
                                            </div>
                                            <div class="text-xs text-slate-600">
                                                Magg. Vetro
                                            </div>
 <div class="text-lg font-extrabold text-slate-900 text-right">
                                                €
                                                {{ MaggVetro(riga).toFixed(2) }}
                                            </div>

                                            <div class="text-xs text-slate-600">
                                                Magg. Maniglie
                                            </div>
 <div class="text-lg font-extrabold text-slate-900 text-right">
                                                €
                                                {{ MaggMan(riga).toFixed(2) }}
                                            </div>
                                            <div class="text-xs text-slate-600">
                                                Magg. Serrature
                                            </div>
 <div class="text-lg font-extrabold text-slate-900 text-right">
                                                €
                                                {{ MaggSerr(riga).toFixed(2) }}
                                            </div>
                                            <div class="text-xs text-slate-600">
                                                Magg. Cerniere
                                            </div>
 <div class="text-lg font-extrabold text-slate-900 text-right">
                                                €
                                                {{ MaggCer(riga).toFixed(2) }}
                                            </div>
                                            <div
                                                class="col-span-2 my-2 border-t border-slate-300"
                                            ></div>

                                            <div
                                                class="text-sm font-semibold text-slate-700"
                                            >
                                                Totale riga
                                            </div>
                                            <div
                                                class="text-xl font-extrabold text-blue-700 text-right"
                                            >
                                                €
                                                {{
                                                    totaleRiga(riga).toFixed(2)
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- errori -->
                            <div
                                v-if="Object.keys(form.errors).length"
                                class="col-span-12 rounded-2xl border border-red-200 bg-red-50 p-4"
                            >
                                <div class="font-semibold text-red-800 mb-2">
                                    Errori:
                                </div>
                                <div
                                    v-for="(msg, key) in form.errors"
                                    :key="key"
                                    class="text-sm text-red-700"
                                >
                                    <strong>{{ key }}:</strong> {{ msg }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- footer -->
                    <div class="flex justify-end gap-2 mt-4">
                        <button
                            type="button"
                            @click="addRiga"
                            class="px-4 py-2 rounded-lg bg-slate-800 text-white hover:bg-slate-900"
                        >
                            + Aggiungi riga
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            Salva preventivo
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div
            v-if="showZoom"
            class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center"
            @click.self="closeZoom"
        >
            <img
                :src="zoomSrc"
                class="max-h-[90vh] max-w-[90vw] object-contain shadow-2xl"
            />

            <!-- pulsante chiudi -->
            <button
                class="absolute top-5 right-5 text-white text-3xl font-bold hover:opacity-80"
                @click="closeZoom"
            >
                ×
            </button>
        </div>
    </AuthenticatedLayout>
</template>
