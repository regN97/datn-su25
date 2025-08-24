<script setup>
defineProps({
    title: { type: String, required: true },
    value: { type: [Number, String], required: true },
    unit: { type: String, default: '' },
});

function formatCurrency(num) {
    if (typeof num !== 'number') {
        return num;
    }
    return num.toLocaleString('vi-VN');
}
</script>

<template>
    <div class="rounded-lg rounded-xl border border-gray-300 bg-white p-4 shadow transition hover:shadow-md">
        <p class="text-sm text-gray-500">{{ title }}</p>
        <p class="mt-1 text-2xl font-bold">
            {{ formatCurrency(value) }}
            <span v-if="unit" class="text-base font-medium">{{ unit }}</span>
        </p>
    </div>
</template>
