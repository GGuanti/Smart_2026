<script setup>
import { computed } from "vue";

const props = defineProps({
  antaUrl: { type: String, default: "" },
  telaioUrl: { type: String, default: "" },

  previewW: { type: Number, default: 520 },
  previewH: { type: Number, default: 720 },

  // apertura in gradi (0..75)
  angle: { type: Number, default: 28 },

  // hinge: lato cerniere (left|right)
  hinge: { type: String, default: "left" },

  // inside/outside (inverte il verso di rotazione)
  swing: { type: String, default: "outside" },

  // quote maniglia (leva + rosetta)
  doorHeightMm: { type: Number, default: 2100 },
  handleHeightMm: { type: Number, default: 1000 },

  // geometria
  framePad: { type: Number, default: 28 },     // bordo esterno attorno al telaio
  jambW: { type: Number, default: 64 },        // larghezza mostrata telaio (frontale)
  jambDepth: { type: Number, default: 28 },    // profondità interna (spessore muro)
  reveal: { type: Number, default: 10 },       // aria tra anta e telaio
  doorThickness: { type: Number, default: 14 } // spessore anta
});

const clamp = (v, a, b) => Math.max(a, Math.min(b, Number(v) || 0));
const angleC = computed(() => clamp(props.angle, 0, 80));

const innerW = computed(() => props.previewW - props.framePad * 2);
const innerH = computed(() => props.previewH - props.framePad * 2);

// vano luce porta
const openingW = computed(() => innerW.value - props.jambW * 2);
const openingH = computed(() => innerH.value - props.jambW * 1.25);

// posizionamento vano
const openingX = computed(() => Math.round((innerW.value - (openingW.value + props.jambW * 2)) / 2));
const openingY = computed(() => Math.round((innerH.value - (openingH.value + props.jambW * 1.25)) / 2));

// anta (dentro il vano)
const doorW = computed(() => Math.round(openingW.value - props.reveal * 2));
const doorH = computed(() => Math.round(openingH.value - props.reveal * 2));

const doorLeft = computed(() => props.framePad + openingX.value + props.jambW + props.reveal);
const doorTop = computed(() => props.framePad + openingY.value + props.jambW + props.reveal);

// verso rotazione
const rotSign = computed(() => {
  // cerniere a sinistra => ruota verso "di te" con segno + (dipende)
  const base = props.hinge === "left" ? 1 : -1;
  return props.swing === "inside" ? -base : base;
});

// hinge x in px rispetto al box anta
const hingeX = computed(() => (props.hinge === "left" ? 0 : doorW.value));

// piccola “uscita” in Z per farla sembrare più 3D (effetto foto)
const zLift = computed(() => Math.round((angleC.value / 80) * 18));

const doorTransform = computed(() => {
  const rot = rotSign.value * angleC.value;
  // translateZ + rotateY attorno a hinge
  return `translateZ(${zLift.value}px) rotateY(${rot}deg)`;
});

const doorStyle = computed(() => ({
  width: doorW.value + "px",
  height: doorH.value + "px",
  transformOrigin: `${hingeX.value}px 50%`,
  transform: doorTransform.value
}));

const bgAnta = computed(() => ({
  backgroundImage: props.antaUrl ? `url(${props.antaUrl})` : "none",
  backgroundSize: "cover",
  backgroundPosition: "center"
}));

const bgTelaio = computed(() => ({
  backgroundImage: props.telaioUrl ? `url(${props.telaioUrl})` : "none",
  backgroundSize: "cover",
  backgroundPosition: "center"
}));

const handleTopPx = computed(() => {
  const h = props.doorHeightMm || 2100;
  const fromTopMm = Math.max(0, Math.min(h, h - props.handleHeightMm));
  return Math.round((fromTopMm / h) * doorH.value);
});

// lato maniglia = opposto alle cerniere
const handleSide = computed(() =>
  props.hinge === "left"
    ? { right: "18px", left: "auto" }
    : { left: "18px", right: "auto" }
);
</script>

<template>
  <div class="w-full flex justify-center">
    <div
      class="relative overflow-hidden rounded-2xl border bg-white"
      :style="{ width: previewW + 'px', height: previewH + 'px' }"
    >
      <!-- SCENA: prospettiva -->
      <div
        class="absolute inset-0"
        style="perspective: 1400px; transform-style: preserve-3d;"
      >
        <!-- sfondo -->
        <div class="absolute inset-0 bg-white"></div>

        <!-- TELAIO: frontale (texture) -->
        <div
          class="absolute rounded-2xl"
          :style="{
            left: framePad + 'px',
            right: framePad + 'px',
            top: framePad + 'px',
            bottom: framePad + 'px',
            ...(telaioUrl ? bgTelaio : {}),
            boxShadow: 'inset 0 0 0 1px rgba(0,0,0,.10)'
          }"
        />

        <!-- VANO INTERNO (stanza) -->
        <div
          class="absolute rounded-xl"
          :style="{
            left: (framePad + openingX + jambW) + 'px',
            top: (framePad + openingY + jambW) + 'px',
            width: openingW + 'px',
            height: openingH + 'px',
            background: 'linear-gradient(180deg, rgba(0,0,0,.06), rgba(0,0,0,0) 45%), #f8fafc',
            boxShadow: 'inset 0 0 0 2px rgba(0,0,0,.10)'
          }"
        />

        <!-- PROFONDITÀ TELAIO (spalle interne) -->
        <div
          class="absolute rounded-xl pointer-events-none"
          :style="{
            left: (framePad + openingX + jambW) + 'px',
            top: (framePad + openingY + jambW) + 'px',
            width: openingW + 'px',
            height: openingH + 'px',
            boxShadow: 'inset 0 0 0 0 rgba(0,0,0,0)'
          }"
        >
          <!-- spalla sinistra -->
          <div
            class="absolute top-0 bottom-0 left-0"
            :style="{
              width: jambDepth + 'px',
              background: 'linear-gradient(90deg, rgba(0,0,0,.22), rgba(0,0,0,0))',
              opacity: .35
            }"
          />
          <!-- spalla destra -->
          <div
            class="absolute top-0 bottom-0 right-0"
            :style="{
              width: jambDepth + 'px',
              background: 'linear-gradient(90deg, rgba(0,0,0,0), rgba(0,0,0,.22))',
              opacity: .35
            }"
          />
          <!-- architrave -->
          <div
            class="absolute left-0 right-0 top-0"
            :style="{
              height: jambDepth + 'px',
              background: 'linear-gradient(180deg, rgba(0,0,0,.22), rgba(0,0,0,0))',
              opacity: .30
            }"
          />
        </div>

        <!-- ANTA 3D -->
        <div
          class="absolute"
          :style="{
            left: doorLeft + 'px',
            top: doorTop + 'px',
            transformStyle: 'preserve-3d'
          }"
        >
          <div class="relative rounded-xl shadow-2xl" :style="doorStyle">
            <!-- faccia frontale -->
            <div
              class="absolute inset-0 rounded-xl overflow-hidden"
              :style="{
                ...bgAnta,
                boxShadow: 'inset 0 0 0 2px rgba(0,0,0,.18)'
              }"
            />

            <!-- bordo spessore anta (lato maniglia) -->
            <div
              class="absolute top-0 bottom-0 rounded-r-xl"
              :style="{
                width: doorThickness + 'px',
                transform: `translateZ(-${doorThickness}px)`,
                // lato opposto alle cerniere
                right: hinge === 'left' ? `-${doorThickness}px` : 'auto',
                left: hinge === 'right' ? `-${doorThickness}px` : 'auto',
                background: 'rgba(0,0,0,.14)',
                opacity: .75
              }"
            />

            <!-- ombra di stacco -->
            <div
              class="absolute top-0 bottom-0 right-[-1px] w-[18px]"
              style="background: linear-gradient(90deg, rgba(0,0,0,0), rgba(0,0,0,.16));"
            />

            <!-- MANIGLIA realistica (leva + rosetta) -->
            <div
              class="absolute"
              :style="{
                top: handleTopPx + 'px',
                ...handleSide,
                transform: 'translateY(-50%)'
              }"
            >
              <!-- rosetta -->
              <div
                class="relative w-[34px] h-[34px] rounded-full"
                style="
                  background: radial-gradient(circle at 30% 28%, rgba(255,255,255,.95), rgba(120,120,120,.35) 55%, rgba(30,30,30,.25) 80%);
                  box-shadow: inset 0 8px 14px rgba(0,0,0,.22), 0 10px 16px rgba(0,0,0,.10);
                "
              />
              <!-- leva -->
              <div
                class="absolute top-1/2"
                :style="{
                  left: hinge === 'left' ? '28px' : 'auto',
                  right: hinge === 'right' ? '28px' : 'auto',
                  transform: 'translateY(-50%)',
                  width: '64px',
                  height: '10px',
                  borderRadius: '999px',
                  background: 'linear-gradient(180deg, rgba(255,255,255,.92), rgba(130,130,130,.35))',
                  boxShadow: '0 10px 14px rgba(0,0,0,.12)'
                }"
              />
            </div>
          </div>
        </div>

        <!-- ombra a pavimento -->
        <div
          class="absolute"
          :style="{
            left: (framePad + openingX + jambW + 18) + 'px',
            width: (openingW - 36) + 'px',
            top: (framePad + openingY + jambW + openingH - 8) + 'px',
            height: '36px',
            filter: 'blur(14px)',
            opacity: .18,
            transform: 'rotateX(80deg)',
            transformOrigin: 'center'
          }"
        >
          <div class="w-full h-full bg-black rounded-2xl"></div>
        </div>
      </div>
    </div>
  </div>
</template>
