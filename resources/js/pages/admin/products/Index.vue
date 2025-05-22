<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage, Link } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/admin/products',
    },
];

type Product = { id: number; name: string; price?: number; description?: string };

const page = usePage<SharedData>();
const products = page.props.products as Product[];
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-5xl mt-10">
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-blue-100">
                <div class="mb-8 flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-blue-700 flex items-center gap-2">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18"/>
                        </svg>
                        Danh sách sản phẩm
                    </h1>
                    <Link
                        href="/admin/products/create"
                        class="rounded-xl bg-gradient-to-r from-green-400 to-blue-500 px-6 py-2 text-white font-semibold shadow hover:from-green-500 hover:to-blue-600 transition"
                    >
                        + Thêm mới
                    </Link>
                </div>
                <div class="overflow-x-auto rounded-xl">
                    <table class="w-full text-left border-separate border-spacing-y-2">
                        <thead>
                            <tr class="bg-blue-50 text-blue-700">
                                <th class="p-3 font-semibold rounded-l-xl">ID</th>
                                <th class="p-3 font-semibold">Tên sản phẩm</th>
                                <th class="p-3 font-semibold">Giá</th>
                                <th class="p-3 font-semibold">Mô tả</th>
                                <th class="p-3 font-semibold rounded-r-xl">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="product in products"
                                :key="product.id"
                                class="bg-white hover:bg-blue-50 transition rounded-xl shadow"
                            >
                                <td class="p-3 rounded-l-xl">{{ product.id }}</td>
                                <td class="p-3">
                                    <Link
                                        :href="`/admin/products/${product.id}/edit`"
                                        class="text-blue-700 font-semibold hover:underline"
                                    >
                                        {{ product.name }}
                                    </Link>
                                </td>
                                <td class="p-3">{{ product.price ? product.price.toLocaleString() + ' ₫' : '—' }}</td>
                                <td class="p-3 truncate max-w-xs" :title="product.description ?? ''">
                                    {{ product.description ?? '—' }}
                                </td>
                                <td class="p-3 rounded-r-xl">
                                    <Link
                                        :href="`/admin/products/${product.id}/edit`"
                                        class="inline-flex items-center gap-1 text-white bg-red-500 hover:bg-red-600 px-3 py-1 rounded-lg transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6-6m2 2l-6 6m-2 2h6"/>
                                        </svg>
                                        Xóa
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="products.length === 0">
                                <td colspan="5" class="text-center text-gray-500 py-6 bg-white rounded-xl shadow">
                                    Không có sản phẩm nào.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
