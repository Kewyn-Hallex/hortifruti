<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
  inheritAttrs: false,
});

interface Props {
  className?: HTMLAttributes['class'];
  src?: string;
  alt?: string;
  width?: string | number;
  height?: string | number;
}

const props = defineProps<Props>();
</script>

<template>
  <img
    :src="props.src || 'https://joselitoteixeira.com/wp-content/uploads/2025/04/logo-011-768x768.png'"
    :alt="props.alt || 'Logo'"
    :class="props.className"
    :width="props.width"
    :height="props.height"
    v-bind="$attrs"
  />
</template>
