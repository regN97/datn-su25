<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    totalItems: number;
    itemsPerPage: number;
    currentPage: number;
}>();

const emit = defineEmits<{
    (e: 'update:currentPage', page: number): void;
    (e: 'update:itemsPerPage', perPage: number): void;
}>();

const perPageOptions = [5, 10, 25, 50];

const totalPages = computed(() => Math.ceil(props.totalItems / props.itemsPerPage));

const displayedPages = computed(() => {
    const pages = [];
    const maxPagesToShow = 5; // Số lượng nút trang tối đa hiển thị
    const half = Math.floor(maxPagesToShow / 2);

    let startPage = Math.max(1, props.currentPage - half);
    let endPage = Math.min(totalPages.value, props.currentPage + half);

    // Điều chỉnh nếu ở gần đầu hoặc cuối
    if (endPage - startPage + 1 < maxPagesToShow) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }
    if (startPage === 1 && endPage < totalPages.value) {
        endPage = Math.min(totalPages.value, startPage + maxPagesToShow - 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }
    return pages;
});

function goToPage(page: number) {
    if (page >= 1 && page <= totalPages.value) {
        emit('update:currentPage', page);
    }
}

function changeItemsPerPage(event: Event) {
    emit('update:itemsPerPage', +(event.target as HTMLSelectElement).value);
    // Khi thay đổi items per page, nên reset về trang 1
    emit('update:currentPage', 1);
}
</script>

<template>
    <div class="mt-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <p class="text-sm">
            Hiển thị kết quả từ
            <span class="font-semibold">{{ (currentPage - 1) * itemsPerPage + 1 }}</span>
            -
            <span class="font-semibold">{{ Math.min(currentPage * itemsPerPage, totalItems) }}</span>
            trên tổng <span class="font-semibold">{{ totalItems }}</span>
        </p>
        <div class="flex items-center space-x-2">
            <button class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">
                &larr; Trang trước
            </button>
            <template v-for="page in displayedPages" :key="page">
                <button
                    class="rounded px-3 py-1 text-sm"
                    :class="page === currentPage ? 'bg-gray-200 font-bold' : 'text-gray-500 hover:text-gray-700'"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </button>
            </template>
            <button
                class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700"
                :disabled="currentPage === totalPages"
                @click="goToPage(currentPage + 1)"
            >
                Trang sau &rarr;
            </button>
        </div>
        <div class="flex items-center space-x-2">
            <p class="text-sm">Hiển thị</p>
            <select class="rounded border p-1 text-sm" :value="itemsPerPage" @change="changeItemsPerPage">
                <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
            </select>
            <p class="text-sm">kết quả</p>
        </div>
    </div>
</template>
