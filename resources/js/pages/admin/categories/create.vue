<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const categories = ref(page.props.categories as { id: number; name: string }[]);
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lí danh mục',
        href: '/admin/categories',
    },
    {
        title: 'Thêm danh mục',
        href: '/admin/categories/create',
    },
];
const form = useForm({
    name: '',
    parent_id: null as number | null,
    description: '',
    is_active: true,
    image_url: '',
    image_file: null as File | null,
    image_type: 'url',
});

function submit() {
    form.post('/admin/categories', { forceFormData: true });
}
</script>

<template>
    <Head title="Thêm danh mục" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto mt-8 max-w-5xl">
            <div class="rounded-xl bg-white p-8 shadow">
                <div class="mb-6 flex items-center justify-between">
                    <h1 class="text-2xl font-bold">Thêm danh mục</h1>
                </div>
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                        <!-- Cột trái -->
                        <div class="space-y-4">
                            <div>
                                <label class="mb-1 block font-semibold">Tên danh mục <span class="text-red-500">*</span></label>
                                <input v-model="form.name" type="text" class="w-full rounded border px-3 py-2" placeholder="Nhập tên danh mục" />
                                <div v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.name }}
                                </div>
                            </div>
                        </div>
                        <!-- Cột phải -->
                        <div class="space-y-4">
                            <div>
                                <label class="mb-1 block font-semibold">Danh mục cha</label>
                                <select v-model="form.parent_id" class="w-full rounded border px-3 py-2">
                                    <option :value="null">— Không có —</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }}
                                    </option>
                                </select>
                                <!-- <div v-if="form.errors.parent_id" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.parent_id }}
                                </div> -->
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold">Mô tả</label>
                                <textarea
                                    v-model="form.description"
                                    class="w-full rounded border px-3 py-2"
                                    rows="3"
                                    placeholder="Nhập mô tả (không bắt buộc)"
                                ></textarea>
                                <div class="mt-1 text-right text-xs text-gray-400">{{ form.description.length }}/5000 ký tự</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end gap-2">
                        <Link href="/admin/categories" class="rounded bg-gray-200 px-6 py-2 text-gray-700 hover:bg-gray-300">Quay lại</Link>
                        <button type="submit" class="rounded bg-blue-500 px-8 py-2 font-semibold text-white transition hover:bg-blue-600">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
