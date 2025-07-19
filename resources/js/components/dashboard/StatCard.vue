<script setup>
defineProps({
  title: { type: String, required: true },
  value: { type: [Number, String], required: true },
  unit: { type: String, default: '' }
})

function formatCurrency(num) {
  if (typeof num !== 'number') {
    return num
  }
  return num.toLocaleString('vi-VN')
}
</script>

<template>
  <div class="p-4 rounded-lg shadow bg-white hover:shadow-md transition border border-gray-300 rounded-xl p-4">
    <p class="text-sm text-gray-500">{{ title }}</p>
    <p class="text-2xl font-bold mt-1">
      {{ formatCurrency(value) }}
      <span v-if="unit" class="text-base font-medium">{{ unit }}</span>
    </p>
  </div>
</template>
