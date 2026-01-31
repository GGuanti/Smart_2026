<script setup>
import { computed } from "vue";
const props = defineProps({
  antaUrl: String,
  progress: { type: Number, default: 40 }, // 0..100
  mode: { type: String, default: "pocket" }, // pocket | external
});

const doorStyle = computed(() => ({
  transform: `translateX(-${props.progress}%)`,
}));

const pocketStyle = computed(() => ({
  width: "58%",
}));
</script>

<template>
  <div class="relative w-[360px] h-[560px] bg-white overflow-hidden">
    <!-- vano/pocket -->
    <div class="absolute inset-y-0 left-0 bg-slate-100" :style="pocketStyle"></div>

    <!-- porta -->
    <div
      class="absolute top-0 left-0 w-full h-full shadow-xl"
      :style="{
        ...doorStyle,
        backgroundImage: antaUrl ? `url(${antaUrl})` : 'none',
        backgroundSize: 'cover',
        backgroundPosition: 'center',
      }"
    >
      <!-- maniglia tonda incassata -->
      <div
        class="absolute top-1/2 right-[42px] w-[34px] h-[34px] rounded-full bg-slate-300 shadow-inner"
        style="transform: translateY(-50%);"
      />
    </div>
  </div>
</template>
