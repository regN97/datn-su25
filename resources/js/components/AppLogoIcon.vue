<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
    imageUrl?: string;
}

defineProps<Props>();
</script>

<template>
  <img
    :src="imageUrl"
    :class="className"
    v-bind="$attrs"
    alt="Icon"
  />
</template>

