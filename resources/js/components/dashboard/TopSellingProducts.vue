<script setup lang="ts">
defineProps<{
    products: {
        id: number;
        name: string;
        sku: string;
        total_revenue: number;
        total_sold: number;
    }[];
}>();

function formatCurrency(value: number) {
    return value.toLocaleString('vi-VN') + '₫';
}
</script>

<template>
    <div class="rounded-xl border border-gray-300 bg-white p-4 shadow">
        <h2 class="mb-4 font-semibold">Top sản phẩm bán chạy nhất</h2>
        <ul class="space-y-4">
            <li v-for="p in products" :key="p.id" class="flex items-start justify-between border-b pb-3 last:border-none">
                <div class="flex flex-col">
                    <span class="text-base font-medium">{{ p.name }}</span>
                    <span class="text-sm text-gray-500">SKU: {{ p.sku }}</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-base font-medium">Giá: {{ formatCurrency(p.total_revenue) }}</span>
                    <span class="text-sm text-gray-500">Đã bán: {{ p.total_sold }}</span>
                </div>
            </li>
        </ul>
    </div>
</template>
