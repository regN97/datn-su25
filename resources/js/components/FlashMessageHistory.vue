<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
  message: string;
  type?: 'success' | 'error' | 'warning' | 'info';
  duration?: number; // Thời gian hiển thị (ms), mặc định 5000ms
}>();

const isVisible = ref(true);

const typeClasses = {
  success: 'bg-green-100 border-green-500 text-green-700',
  error: 'bg-red-100 border-red-500 text-red-700',
  warning: 'bg-yellow-100 border-yellow-500 text-yellow-700',
  info: 'bg-blue-100 border-blue-500 text-blue-700',
};

const typeIcons = {
  success: '✅',
  error: '❌',
  warning: '⚠️',
  info: 'ℹ️',
};

onMounted(() => {
  if (props.duration !== 0) {
    setTimeout(() => {
      isVisible.value = false;
    }, props.duration || 5000);
  }
});

onUnmounted(() => {
  isVisible.value = false;
});
</script>

<template>
  <div
    v-if="isVisible"
    class="fixed top-4 right-4 max-w-sm w-full p-4 rounded-lg shadow-lg border-l-4 flex items-center gap-3 transition-all duration-300"
    :class="typeClasses[props.type || 'info']"
  >
    <span class="text-lg">{{ typeIcons[props.type || 'info'] }}</span>
    <span class="text-sm">{{ props.message }}</span>
    <button
      @click="isVisible = false"
      class="ml-auto text-gray-500 hover:text-gray-700"
    >
      ✕
    </button>
  </div>
</template>