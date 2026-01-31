<script setup>
import { computed } from "vue";

const props = defineProps({
  antaUrl: { type: String, default: "" },
  telaioUrl: { type: String, default: "" }, // opzionale, se vuoi sfondo/telaio
  previewW: { type: Number, default: 360 },
  previewH: { type: Number, default: 560 },

  // angolo piega (0 = chiuso, 70 = molto aperto)
  foldAngle: { type: Number, default: 35 },

  // spazio tra le due ante al centro (px)
  gapPx: { type: Number, default: 2 },

  // quote maniglia
  doorHeightMm: { type: Number, default: 2100 },
  handleHeightMm: { type: Number, default: 1000 },

  // larghezza montante esterno (se vuoi un minimo di telaio)
  framePad: { type: Number, default: 16 },
});

const halfW = computed(() => Math.round((props.previewW - props.framePad * 2 - props.gapPx) / 2));
const innerH = computed(() => props.previewH - props.framePad * 2);

const handleTopPx = computed(() => {
  const h = props.doorHeightMm || 2100;
  const fromTopMm = Math.max(0, Math.min(h, h - props.handleHeightMm));
  return Math.round((fromTopMm / h) * innerH.value);
});

const leftLeafStyle = computed(() => ({
  width: halfW.value + "px",
  height: innerH.value + "px",
  transformOrigin: "right center",
  transform: `perspective(1100px) rotateY(-${props.foldAngle}deg)`,
}));

const rightLeafStyle = computed(() => ({
  width: halfW.value + "px",
  height: innerH.value + "px",
  transformOrigin: "left center",
  transform: `perspective(1100px) rotateY(${props.foldAngle}deg)`,
}));

const bgAnta = computed(() => ({
  backgroundImage: props.antaUrl ? `url(${props.antaUrl})` : "none",
  backgroundSize: "cover",
  backgroundPosition: "center",
}));

const bgTelaio = computed(() => ({
  backgroundImage: props.telaioUrl ? `url(${props.telaioUrl})` : "none",
  backgroundSize: "cover",
  backgroundPosition: "center",
}));
</script>

<template>
  <div class="w-full flex justify-center">
    <div
      class="relative rounded-2xl border bg-white overflow-hidden"
      :style="{ width: previewW + 'px', height: previewH + 'px' }"
    >
      <!-- sfondo/telaio (opzionale) -->
      <div
        class="absolute inset-0"
        :style="telaioUrl ? bgTelaio : { background: 'linear-gradient(180deg, rgba(0,0,0,.05), rgba(0,0,0,0) 45%)' }"
      ></div>

      <!-- vano interno -->
      <div
        class="absolute rounded-xl"
        :style="{
          left: framePad + 'px',
          right: framePad + 'px',
          top: framePad + 'px',
          bottom: framePad + 'px',
          background: 'rgba(255,255,255,.45)',
          boxShadow: 'inset 0 0 0 2px rgba(0,0,0,.12)',
        }"
      />

      <!-- gruppo ante centrato -->
      <div
        class="absolute"
        :style="{
          left: framePad + 'px',
          top: framePad + 'px',
          width: (previewW - framePad * 2) + 'px',
          height: innerH + 'px',
        }"
      >
        <!-- LINEA CENTRALE (gap) - sottile -->
        <div
          class="absolute top-0 bottom-0"
          :style="{
            left: halfW + 'px',
            width: gapPx + 'px',
            background: 'rgba(0,0,0,.10)',
          }"
        />

        <!-- ANTA SINISTRA -->
        <div
          class="absolute top-0"
          :style="{
            left: 0,
            ...leftLeafStyle
          }"
        >
          <div class="absolute inset-0 rounded-xl shadow-xl" :style="bgAnta">
            <!-- bordo -->
            <div class="absolute inset-0 rounded-xl" style="box-shadow: inset 0 0 0 2px rgba(0,0,0,.18);"></div>

            <!-- maniglia tonda incassata: lato esterno (sinistra) -->
            <div
              class="absolute w-[38px] h-[38px] rounded-full"
              :style="{
                top: handleTopPx + 'px',
                left: '18px',
                transform: 'translateY(-50%)',
                background: 'radial-gradient(circle at 30% 30%, rgba(255,255,255,.85), rgba(0,0,0,.18) 70%)',
                boxShadow: 'inset 0 8px 16px rgba(0,0,0,.22), 0 10px 18px rgba(0,0,0,.10)',
              }"
            >
              <div
                class="absolute"
                style="
                  inset: 8px;
                  border-radius: 999px;
                  box-shadow: inset 0 0 0 2px rgba(0,0,0,.15);
                  background: rgba(255,255,255,.06);
                "
              />
            </div>

            <!-- ombra bordo interno (verso il centro) -->
            <div
              class="absolute top-0 bottom-0 right-[-1px] w-[18px]"
              style="background: linear-gradient(90deg, rgba(0,0,0,0), rgba(0,0,0,.12));"
            />
          </div>
        </div>

        <!-- ANTA DESTRA -->
        <div
          class="absolute top-0"
          :style="{
            left: (halfW + gapPx) + 'px',
            ...rightLeafStyle
          }"
        >
          <div class="absolute inset-0 rounded-xl shadow-xl" :style="bgAnta">
            <!-- bordo -->
            <div class="absolute inset-0 rounded-xl" style="box-shadow: inset 0 0 0 2px rgba(0,0,0,.18);"></div>

            <!-- maniglia tonda incassata: lato esterno (destra) -->
            <div
              class="absolute w-[38px] h-[38px] rounded-full"
              :style="{
                top: handleTopPx + 'px',
                right: '18px',
                transform: 'translateY(-50%)',
                background: 'radial-gradient(circle at 30% 30%, rgba(255,255,255,.85), rgba(0,0,0,.18) 70%)',
                boxShadow: 'inset 0 8px 16px rgba(0,0,0,.22), 0 10px 18px rgba(0,0,0,.10)',
              }"
            >
              <div
                class="absolute"
                style="
                  inset: 8px;
                  border-radius: 999px;
                  box-shadow: inset 0 0 0 2px rgba(0,0,0,.15);
                  background: rgba(255,255,255,.06);
                "
              />
            </div>

            <!-- ombra bordo interno (verso il centro) -->
            <div
              class="absolute top-0 bottom-0 left-[-1px] w-[18px]"
              style="background: linear-gradient(90deg, rgba(0,0,0,.12), rgba(0,0,0,0));"
            />
          </div>
        </div>
      </div>

      <!-- ombra a pavimento -->
      <div
        class="absolute left-8 right-8 bottom-6 h-10"
        style="filter: blur(10px); opacity: .18; transform: perspective(1100px) rotateX(80deg);"
      >
        <div class="w-full h-full bg-black rounded-2xl"></div>
      </div>
    </div>
  </div>
</template>
