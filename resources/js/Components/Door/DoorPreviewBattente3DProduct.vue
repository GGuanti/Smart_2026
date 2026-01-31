<script setup>
import { computed } from "vue";

const props = defineProps({
  antaUrl: { type: String, default: "" },
  telaioUrl: { type: String, default: "" },

  previewW: { type: Number, default: 520 },
  previewH: { type: Number, default: 720 },

  angle: { type: Number, default: 35 }, // 0..80
  hinge: { type: String, default: "left" }, // left|right
  swing: { type: String, default: "outside" }, // inside|outside

  doorHeightMm: { type: Number, default: 2100 },
  handleHeightMm: { type: Number, default: 1000 },

  framePad: { type: Number, default: 22 },

  // cornice / telaio
  jambW: { type: Number, default: 68 },      // larghezza frontale montanti
  headerH: { type: Number, default: 54 },    // architrave visibile
  jambDepth: { type: Number, default: 44 },  // profondità telaio

  // battuta (la rientranza interna)
  stopW: { type: Number, default: 16 },      // spessore battuta
  stopInset: { type: Number, default: 10 },  // quanto rientra

  reveal: { type: Number, default: 8 },      // aria tra anta e telaio
  doorThickness: { type: Number, default: 20 },

  // camera
  cameraTiltX: { type: Number, default: 12 },
  cameraYawY: { type: Number, default: -22 },
  cameraZoom: { type: Number, default: 1.0 },
});

const clamp = (v, a, b) => Math.max(a, Math.min(b, Number(v) || 0));
const angleC = computed(() => clamp(props.angle, 0, 80));

const innerW = computed(() => props.previewW - props.framePad * 2);
const innerH = computed(() => props.previewH - props.framePad * 2);

// area telaio totale
const frameW = computed(() => Math.round(innerW.value));
const frameH = computed(() => Math.round(innerH.value));

// vano luce
const openingW = computed(() => Math.round(frameW.value - props.jambW * 2));
const openingH = computed(() => Math.round(frameH.value - props.headerH - props.jambW * 0.35));

// posizione vano
const openingX = computed(() => props.jambW);
const openingY = computed(() => props.headerH);

// anta
const doorW = computed(() => Math.round(openingW.value - props.reveal * 2));
const doorH = computed(() => Math.round(openingH.value - props.reveal * 2));
const doorX = computed(() => props.framePad + openingX.value + props.reveal);
const doorY = computed(() => props.framePad + openingY.value + props.reveal);

const rotSign = computed(() => {
  const base = props.hinge === "left" ? 1 : -1;
  return props.swing === "inside" ? -base : base;
});

const hingeX = computed(() => (props.hinge === "left" ? 0 : doorW.value));

const sceneTransform = computed(() => {
  const z = props.cameraZoom || 1;
  return `scale(${z}) rotateX(${props.cameraTiltX}deg) rotateY(${props.cameraYawY}deg)`;
});

const doorTransform = computed(() => {
  const rot = rotSign.value * angleC.value;

  // più apri -> più “esce” verso camera
  const outZ = Math.round((angleC.value / 80) * 34) + 6;

  // leggero shift laterale per far vedere il lato interno del telaio (come foto)
  const shiftX = (props.hinge === "left" ? 1 : -1) * Math.round((angleC.value / 80) * 10);

  return `translateX(${shiftX}px) translateZ(${outZ}px) rotateY(${rot}deg)`;
});

const doorBoxStyle = computed(() => ({
  width: doorW.value + "px",
  height: doorH.value + "px",
  transformStyle: "preserve-3d",
  transformOrigin: `${hingeX.value}px 50%`,
  transform: doorTransform.value,
}));

const texAnta = computed(() => (props.antaUrl ? `url(${props.antaUrl})` : "none"));
const texTelaio = computed(() => (props.telaioUrl ? `url(${props.telaioUrl})` : "none"));

// maniglia
const handleTopPx = computed(() => {
  const h = props.doorHeightMm || 2100;
  const fromTopMm = Math.max(0, Math.min(h, h - props.handleHeightMm));
  return Math.round((fromTopMm / h) * doorH.value);
});
const handleSide = computed(() =>
  props.hinge === "left"
    ? { right: "26px", left: "auto" }
    : { left: "26px", right: "auto" }
);

// hinge visible positions
const hinges = computed(() => {
  const y1 = Math.round(doorH.value * 0.18);
  const y2 = Math.round(doorH.value * 0.82);
  return [y1, y2];
});

// background helper (orientamento)
const bgCover = (img) => ({
  backgroundImage: img,
  backgroundSize: "cover",
  backgroundPosition: "center",
});
</script>

<template>
  <div class="w-full flex justify-center">
    <div
      class="relative overflow-hidden rounded-2xl border bg-white"
      :style="{ width: previewW + 'px', height: previewH + 'px' }"
    >
      <!-- fondo tipo studio -->
      <div
        class="absolute inset-0"
        style="
          background:
            radial-gradient(circle at 50% 30%, rgba(0,0,0,.06), rgba(0,0,0,0) 55%),
            linear-gradient(180deg, rgba(0,0,0,.02), rgba(0,0,0,0) 70%),
            #fff;
        "
      />

      <div class="absolute inset-0 flex items-center justify-center" style="perspective: 1700px; transform-style: preserve-3d;">
        <div :style="{ transform: sceneTransform, transformStyle: 'preserve-3d' }">

          <!-- ===== TELAIO (volume + battuta) ===== -->
          <div
            class="relative"
            :style="{ width: frameW + 'px', height: frameH + 'px' }"
          >
            <!-- montante SX -->
            <div class="absolute top-0 left-0" :style="{ width: jambW+'px', height: frameH+'px', transformStyle:'preserve-3d' }">
              <div class="absolute inset-0" :style="{ ...bgCover(texTelaio), boxShadow:'inset 0 0 0 1px rgba(0,0,0,.10)' }" />
              <!-- interno (spalla) -->
              <div class="absolute top-0 left-full"
                :style="{
                  width: jambDepth+'px', height:'100%',
                  transform:'rotateY(90deg)', transformOrigin:'left',
                  ...bgCover(texTelaio),
                  filter:'brightness(.92) contrast(1.02)'
                }"
              />
            </div>

            <!-- montante DX -->
            <div class="absolute top-0 right-0" :style="{ width: jambW+'px', height: frameH+'px', transformStyle:'preserve-3d' }">
              <div class="absolute inset-0" :style="{ ...bgCover(texTelaio), boxShadow:'inset 0 0 0 1px rgba(0,0,0,.10)' }" />
              <div class="absolute top-0 right-full"
                :style="{
                  width: jambDepth+'px', height:'100%',
                  transform:'rotateY(-90deg)', transformOrigin:'right',
                  ...bgCover(texTelaio),
                  filter:'brightness(.92) contrast(1.02)'
                }"
              />
            </div>

            <!-- architrave -->
            <div class="absolute top-0 left-0" :style="{ left: jambW+'px', width:(frameW - jambW*2)+'px', height: headerH+'px', transformStyle:'preserve-3d' }">
              <div class="absolute inset-0" :style="{ ...bgCover(texTelaio), boxShadow:'inset 0 0 0 1px rgba(0,0,0,.10)' }" />
              <div class="absolute top-full left-0"
                :style="{
                  width:'100%', height: jambDepth+'px',
                  transform:'rotateX(-90deg)', transformOrigin:'top',
                  ...bgCover(texTelaio),
                  filter:'brightness(.92) contrast(1.02)'
                }"
              />
            </div>

            <!-- vano interno -->
            <div
              class="absolute"
              :style="{
                left: openingX + 'px',
                top: openingY + 'px',
                width: openingW + 'px',
                height: openingH + 'px',
                background: 'linear-gradient(180deg, rgba(0,0,0,.08), rgba(0,0,0,0) 42%), #fff',
                boxShadow: 'inset 0 0 0 2px rgba(0,0,0,.08)'
              }"
            />

            <!-- battuta (stop) su 3 lati -->
            <!-- lato SX -->
            <div class="absolute"
              :style="{
                left: (openingX + props.stopInset) + 'px',
                top: (openingY + props.stopInset) + 'px',
                width: props.stopW + 'px',
                height: (openingH - props.stopInset*2) + 'px',
                background: 'rgba(0,0,0,.10)',
                opacity: .25
              }"
            />
            <!-- lato DX -->
            <div class="absolute"
              :style="{
                left: (openingX + openingW - props.stopInset - props.stopW) + 'px',
                top: (openingY + props.stopInset) + 'px',
                width: props.stopW + 'px',
                height: (openingH - props.stopInset*2) + 'px',
                background: 'rgba(0,0,0,.10)',
                opacity: .25
              }"
            />
            <!-- alto -->
            <div class="absolute"
              :style="{
                left: (openingX + props.stopInset) + 'px',
                top: (openingY + props.stopInset) + 'px',
                width: (openingW - props.stopInset*2) + 'px',
                height: props.stopW + 'px',
                background: 'rgba(0,0,0,.10)',
                opacity: .25
              }"
            />

            <!-- ===== ANTA 3D ===== -->
            <div class="absolute" :style="{ left: doorX+'px', top: doorY+'px', transformStyle:'preserve-3d' }">
              <div class="relative" :style="doorBoxStyle">
                <!-- FRONT -->
                <div class="absolute inset-0 rounded-lg overflow-hidden"
                  :style="{
                    ...bgCover(texAnta),
                    boxShadow:
                      'inset 0 0 0 2px rgba(0,0,0,.14), 0 24px 36px rgba(0,0,0,.14)'
                  }"
                />
                <!-- BACK -->
                <div class="absolute inset-0 rounded-lg"
                  :style="{
                    transform:`translateZ(-${doorThickness}px) rotateY(180deg)`,
                    ...bgCover(texAnta),
                    filter:'brightness(.97)',
                    boxShadow:'inset 0 0 0 2px rgba(0,0,0,.10)'
                  }"
                />

                <!-- EDGE lato maniglia (più realistico) -->
                <div class="absolute top-0 bottom-0"
                  :style="{
                    width: doorThickness+'px',
                    right: (hinge === 'left') ? `-${doorThickness}px` : 'auto',
                    left:  (hinge === 'right') ? `-${doorThickness}px` : 'auto',
                    transform: (hinge === 'left')
                      ? `rotateY(90deg) translateZ(${doorThickness}px)`
                      : `rotateY(-90deg) translateZ(${doorThickness}px)`,
                    transformOrigin: (hinge === 'left') ? 'left' : 'right',
                    background: 'linear-gradient(180deg, rgba(255,255,255,.16), rgba(0,0,0,.22))',
                    borderRadius: '10px',
                    opacity: .9
                  }"
                />

                <!-- TOP EDGE -->
                <div class="absolute left-0 right-0"
                  :style="{
                    height: doorThickness+'px',
                    top: `-${doorThickness}px`,
                    transform:`rotateX(90deg) translateZ(${doorThickness}px)`,
                    transformOrigin:'top',
                    background:'linear-gradient(90deg, rgba(0,0,0,.16), rgba(255,255,255,.08))',
                    opacity:.75
                  }"
                />

                <!-- cerniere visibili (lato montante) -->
                <div
                  v-for="(yy,idx) in hinges"
                  :key="idx"
                  class="absolute"
                  :style="{
                    top: yy+'px',
                    left: hinge==='left' ? '-8px' : 'auto',
                    right: hinge==='right' ? '-8px' : 'auto',
                    transform: 'translateY(-50%) translateZ(3px)',
                    width: '14px',
                    height: '54px',
                    borderRadius: '6px',
                    background: 'linear-gradient(180deg, rgba(255,255,255,.85), rgba(120,120,120,.35))',
                    boxShadow: '0 10px 14px rgba(0,0,0,.10)'
                  }"
                />

                <!-- maniglia (leva + rosetta più credibile) -->
                <div class="absolute"
                  :style="{
                    top: handleTopPx+'px',
                    ...handleSide,
                    transform:'translateY(-50%) translateZ(4px)'
                  }"
                >
                  <!-- rosetta -->
                  <div
                    class="relative w-[34px] h-[34px] rounded-full"
                    style="
                      background: radial-gradient(circle at 30% 28%, rgba(255,255,255,.98), rgba(150,150,150,.45) 58%, rgba(30,30,30,.25) 85%);
                      box-shadow: inset 0 8px 14px rgba(0,0,0,.18), 0 10px 16px rgba(0,0,0,.10);
                    "
                  />
                  <!-- leva -->
                  <div
                    class="absolute top-1/2"
                    :style="{
                      left: hinge === 'left' ? '28px' : 'auto',
                      right: hinge === 'right' ? '28px' : 'auto',
                      transform: 'translateY(-50%)',
                      width: '84px',
                      height: '10px',
                      borderRadius: '999px',
                      background: 'linear-gradient(180deg, rgba(255,255,255,.95), rgba(120,120,120,.35))',
                      boxShadow: '0 12px 16px rgba(0,0,0,.12)'
                    }"
                  />
                </div>

                <!-- ombra di contatto sul lato cerniere (mancava, fa tanto) -->
                <div
                  class="absolute top-0 bottom-0"
                  :style="{
                    width: '18px',
                    left: hinge==='left' ? '0px' : 'auto',
                    right: hinge==='right' ? '0px' : 'auto',
                    background: 'linear-gradient(90deg, rgba(0,0,0,.18), rgba(0,0,0,0))',
                    opacity: .55,
                    pointerEvents:'none'
                  }"
                />
              </div>
            </div>

            <!-- ombra a terra coerente (sotto anta) -->
            <div
              class="absolute"
              :style="{
                left: (openingX + 40) + 'px',
                top: (openingY + openingH + 28) + 'px',
                width: (openingW - 80) + 'px',
                height: '60px',
                filter: 'blur(18px)',
                opacity: .16,
                transform: `skewX(-18deg)`,
                transformOrigin: 'center'
              }"
            >
              <div class="w-full h-full bg-black rounded-2xl"></div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>
