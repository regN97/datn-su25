<script setup lang="ts">
defineProps<{
  products: {
    id: number
    name: string
    sku: string
    total_revenue: number
    total_sold: number
  }[]
}>()

function formatCurrency(value: number) {
  return value.toLocaleString('vi-VN') + '₫'
}
</script>

<template>
    <div class="bg-white p-4 rounded-xl shadow border border-gray-300 rounded-xl p-4">
        <h2 class="font-semibold mb-4">Top sản phẩm bán chạy nhất</h2>
        <ul class="space-y-4">
            <li v-for="p in products" :key="p.id"
                class="flex justify-between items-start border-b pb-3 last:border-none">
                <div class="flex flex-col">
                    <span class="font-medium text-base">{{ p.name }}</span>
                    <span class="text-sm text-gray-500">SKU: {{ p.sku }}</span>
                </div>
                <div class="flex flex-col">
                    <span class="font-medium text-base">Giá: {{ formatCurrency(p.total_revenue) }}</span>
                    <span class="text-sm text-gray-500">Đã bán: {{ p.total_sold }}</span>
                </div>
            </li>
        </ul>
    </div>
</template>