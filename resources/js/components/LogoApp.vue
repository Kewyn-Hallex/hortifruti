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
    :src="props.src || 'http://[::1]:5173/resources/images/logoH.png'"
    :alt="props.alt || 'Logo'"
    :class="props.className"
    :width="props.width"
    :height="props.height"
    v-bind="$attrs"
  />
</template>
