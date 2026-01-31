<script setup>
import { computed } from "vue";
const props = defineProps({
  antaUrl: String,
  valanceUrl: String, // usa texture telaio come mantovana
  progress: { type: Number, default: 40 },
});

const doorStyle = computed(() => ({
  transform: `translateX(-${props.progress}%)`,
}));
</script>

<template>
  <div class="relative w-[360px] h-[560px] bg-white overflow-hidden">
    <!-- mantovana -->
    <div
      class="absolute top-0 left-0 right-0 h-[56px]"
      :style="{
        backgroundImage: valanceUrl ? `url(${valanceUrl})` : 'none',
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        boxShadow: '0 8px 18px rgba(0,0,0,.12)'
      }"
    />

    <!-- anta -->
    <div
      class="absolute top-[56px] left-0 w-full bottom-0 shadow-xl"
      :style="{
        ...doorStyle,
        backgroundImage: antaUrl ? `url(${antaUrl})` : 'none',
        backgroundSize: 'cover',
        backgroundPosition: 'center',
      }"
    >
      <div
        class="absolute top-1/2 right-[42px] w-[34px] h-[34px] rounded-full bg-slate-300 shadow-inner"
        style="transform: translateY(-50%);"
      />
    </div>
  </div>
</template>
