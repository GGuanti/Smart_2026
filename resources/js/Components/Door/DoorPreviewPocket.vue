<script setup>
import { computed } from "vue";

const props = defineProps({
  antaUrl: { type: String, default: "" },

  // 0 = chiusa, 100 = tutta aperta
  progress: { type: Number, default: 40 },

  // "pocket" = interno muro | "external" = esterno muro
  mode: { type: String, default: "pocket" },

  // direzione scorrimento (porta che va a sinistra o a destra)
  direction: { type: String, default: "left" }, // left|right

  // quote maniglia
  doorHeightMm: { type: Number, default: 2100 },
  handleHeightMm: { type: Number, default: 1000 },

  // dimensione preview
  previewW: { type: Number, default: 380 },
  previewH: { type: Number, default: 560 },

  // pocket in % del vano
  pocketWidthPct: { type: Number, default: 58 },

  // cornice esterna
  framePad: { type: Number, default: 16 },

  // profondità muro (spessore spalla)
  jambDepth: { type: Number, default: 10 },

  // gap tra anta e vano
  reveal: { type: Number, default: 6 },

  // larghezza anta in % del vano
  doorWidthPct: { type: Number, default: 92 },
});

// clamp progress 0..100
const p = computed(() => Math.max(0, Math.min(100, Number(props.progress) || 0)));

const innerW = computed(() => props.previewW - props.framePad * 2);
const innerH = computed(() => props.previewH - props.framePad * 2);

// “vano” (luce porta) leggermente più piccolo per fare cornice/spalle
const openingW = computed(() => Math.round(innerW.value * 0.92));
const openingH = computed(() => Math.round(innerH.value * 0.92));
const openingX = computed(() => Math.round((innerW.value - openingW.value) / 2));
const openingY = computed(() => Math.round((innerH.value - openingH.value) / 2));

const doorW = computed(() => Math.round(openingW.value * (props.doorWidthPct / 100)));
const doorH = computed(() => openingH.value);

// pocket width in px (solo pocket)
const pocketW = computed(() => Math.round(openingW.value * (props.pocketWidthPct / 100)));

// quanto può scorrere l’anta (px)
const maxSlide = computed(() => {
  // se pocket: può entrare nel pocket (limitata)
  if (props.mode === "pocket") return Math.max(0, pocketW.value);
  // se esterno: può scorrere oltre il vano (effetto esterno muro)
  return Math.max(0, Math.round(openingW.value * 0.9));
});

// slide px
const slidePx = computed(() => Math.round((p.value / 100) * maxSlide.value));

// trasformazione anta
const doorStyle = computed(() => {
  const dir = props.direction === "right" ? 1 : -1;
  return {
    transform: `translateX(${dir * slidePx.value}px)`,
  };
});

// maniglia: quota -> px sul pannello (usa doorH, non previewH)
const handleTopPx = computed(() => {
  const h = props.doorHeightMm || 2100;
  const fromTopMm = Math.max(0, Math.min(h, h - props.handleHeightMm));
  return Math.round((fromTopMm / h) * doorH.value);
});

// posizioni
const openingLeft = computed(() => props.framePad + openingX.value);
const openingTop = computed(() => props.framePad + openingY.value);

// pocket: se direction left, pocket a sinistra; se right, pocket a destra
const pocketLeft = computed(() => (props.direction === "right" ? openingLeft.value + openingW.value - pocketW.value : openingLeft.value));
</script>

<template>
  <div class="w-full flex justify-center">
    <div
      class="relative overflow-hidden rounded-2xl border bg-white"
      :style="{ width: previewW + 'px', height: previewH + 'px' }"
    >
      <!-- parete -->
      <div class="absolute inset-0 bg-white"></div>

      <!-- cornice esterna -->
      <div
        class="absolute rounded-2xl"
        :style="{
          left: framePad + 'px',
          right: framePad + 'px',
          top: framePad + 'px',
          bottom: framePad + 'px',
          boxShadow: 'inset 0 0 0 1px rgba(0,0,0,.10)',
        }"
      />

      <!-- VANO (luce porta) -->
      <div
        class="absolute rounded-xl overflow-hidden"
        :style="{
          left: openingLeft + 'px',
          top: openingTop + 'px',
          width: openingW + 'px',
          height: openingH + 'px',
          background: 'linear-gradient(180deg, rgba(0,0,0,.06), rgba(0,0,0,0) 35%)',
          boxShadow: 'inset 0 0 0 2px rgba(0,0,0,.12)',
        }"
      >
        <!-- interno stanza -->
        <div class="absolute inset-0 bg-slate-50"></div>

        <!-- battiscopa -->
        <div class="absolute left-0 right-0 bottom-0 h-[12px] bg-slate-200/70"></div>

        <!-- spalla (profondità muro) -->
        <div
          class="absolute top-0 bottom-0"
          :style="{
            left: 0,
            width: jambDepth + 'px',
            background: 'linear-gradient(90deg, rgba(0,0,0,.18), rgba(0,0,0,0))',
            opacity: .35,
          }"
        />
        <div
          class="absolute top-0 bottom-0"
          :style="{
            right: 0,
            width: jambDepth + 'px',
            background: 'linear-gradient(90deg, rgba(0,0,0,0), rgba(0,0,0,.18))',
            opacity: .35,
          }"
        />
        <div
          class="absolute left-0 right-0"
          :style="{
            top: 0,
            height: jambDepth + 'px',
            background: 'linear-gradient(180deg, rgba(0,0,0,.18), rgba(0,0,0,0))',
            opacity: .35,
          }"
        />
      </div>

      <!-- POCKET (interno muro) -->
      <div
        v-if="mode === 'pocket'"
        class="absolute rounded-xl overflow-hidden"
        :style="{
          left: pocketLeft + 'px',
          top: openingTop + 'px',
          width: pocketW + 'px',
          height: openingH + 'px',
        }"
      >
        <div class="absolute inset-0 bg-slate-100"></div>

        <!-- rientro -->
        <div
          class="absolute top-0 bottom-0"
          :style="{
            [direction === 'right' ? 'left' : 'right']: 0,
            width: '2px',
            background: 'rgba(0,0,0,.18)',
          }"
        />

        <!-- ombra profonda -->
        <div
          class="absolute top-0 bottom-0"
          :style="{
            [direction === 'right' ? 'left' : 'right']: 0,
            width: '18px',
            background:
              direction === 'right'
                ? 'linear-gradient(90deg, rgba(0,0,0,.22), rgba(0,0,0,0))'
                : 'linear-gradient(90deg, rgba(0,0,0,0), rgba(0,0,0,.22))',
            opacity: .55,
          }"
        />
      </div>

      <!-- ANTA -->
      <div
        class="absolute rounded-xl"
        :style="{
          left: (openingLeft + (openingW - doorW) / 2) + 'px',
          top: (openingTop + reveal) + 'px',
          width: doorW + 'px',
          height: (openingH - reveal * 2) + 'px',
          ...doorStyle,
        }"
      >
        <!-- pannello texture -->
        <div
          class="absolute inset-0 rounded-xl shadow-2xl overflow-hidden"
          :style="{
            backgroundImage: antaUrl ? `url(${antaUrl})` : 'none',
            backgroundSize: 'cover',
            backgroundPosition: 'center',
          }"
        >
          <!-- bordo -->
          <div class="absolute inset-0 rounded-xl" style="box-shadow: inset 0 0 0 2px rgba(0,0,0,.18);"></div>

          <!-- spessore anta -->
          <div
            class="absolute top-0 bottom-0"
            :style="{
              right: '-10px',
              width: '10px',
              background: 'rgba(0,0,0,.14)',
              borderTopRightRadius: '12px',
              borderBottomRightRadius: '12px',
              opacity: .65,
            }"
          />

          <!-- ombra stacco -->
          <div
            class="absolute top-0 bottom-0 right-[-1px] w-[18px]"
            style="background: linear-gradient(90deg, rgba(0,0,0,0), rgba(0,0,0,.16));"
          />

          <!-- maniglia tonda incassata (metal) -->
          <div
            class="absolute w-[42px] h-[42px] rounded-full"
            :style="{
              top: handleTopPx + 'px',
              // sempre lato “presa” (opposto al pocket)
              right: direction === 'right' ? '18px' : 'auto',
              left: direction === 'left' ? '18px' : 'auto',
              transform: 'translateY(-50%)',
              background:
                'radial-gradient(circle at 30% 28%, rgba(255,255,255,.92), rgba(130,130,130,.35) 45%, rgba(30,30,30,.25) 75%)',
              boxShadow: 'inset 0 10px 18px rgba(0,0,0,.22), 0 10px 18px rgba(0,0,0,.12)',
            }"
          >
            <div
              class="absolute"
              style="
                inset: 9px;
                border-radius: 999px;
                background: rgba(255,255,255,.06);
                box-shadow: inset 0 0 0 2px rgba(0,0,0,.16);
              "
            />
          </div>
        </div>
      </div>

      <!-- ombra a terra -->
      <div
        class="absolute"
        :style="{
          left: openingLeft + 22 + 'px',
          right: openingLeft + 22 + 'px',
          bottom: (previewH - (openingTop + openingH) + 20) + 'px',
          height: '34px',
          filter: 'blur(12px)',
          opacity: .18,
          transform: 'perspective(1200px) rotateX(80deg)',
        }"
      >
        <div class="w-full h-full bg-black rounded-2xl"></div>
      </div>

      <!-- esterno muro: guida a pavimento, molto leggera -->
      <div
        v-if="mode === 'external'"
        class="absolute h-[2px] bg-slate-300/50"
        :style="{
          left: openingLeft + 'px',
          width: openingW + 'px',
          top: (openingTop + openingH - 10) + 'px',
        }"
      />
    </div>
  </div>
</template>

