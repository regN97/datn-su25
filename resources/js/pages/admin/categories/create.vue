<script setup lang="ts">
import { ref } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { useForm, Head, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

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

const newParentName = ref('');

function addNewParentCategory() {
    const name = newParentName.value.trim();
    if (!name) return;
    const tempId = -Date.now();
    categories.value.unshift({ id: tempId, name });
    form.parent_id = tempId;
    newParentName.value = '';
}

function submit() {
    form.post('/admin/categories', { forceFormData: true });
}
</script>

<template>

    <Head title="Thêm danh mục" />
    <AppLayout :breadcrumbs="breadcrumbs">

        <div class="container mx-auto max-w-5xl mt-8">
            <div class="bg-white rounded-xl shadow p-8">
                <div class="mb-6 flex items-center justify-between">
                    <h1 class="text-2xl font-bold">Thêm danh mục</h1>
                    <Link href="/admin/categories"
                        class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700">Quay lại</Link>
                </div>
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Cột trái -->
                        <div class="space-y-4">
                            <div>
                                <label class="font-semibold block mb-1">Tên danh mục <span
                                        class="text-red-500">*</span></label>
                                <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2"
                                    placeholder="Nhập tên danh mục" />
                                <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.name }}
                                </div>
                            </div>
                            <div>
                                <label class="font-semibold block mb-1">Thêm nhanh danh mục cha</label>
                                <div class="flex gap-2">
                                    <input v-model="newParentName" type="text" class="flex-1 border rounded px-3 py-2"
                                        placeholder="Nhập tên danh mục cha mới" />
                                    <button type="button"
                                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition"
                                        @click="addNewParentCategory">
                                        + Thêm
                                    </button>
                                </div>
                                <div class="text-xs text-gray-400 mt-1">Có thể thêm nhanh và chọn danh mục cha</div>
                            </div>
                        </div>
                        <!-- Cột phải -->
                        <div class="space-y-4">
                            <div>
                                <label class="font-semibold block mb-1">Danh mục cha</label>
                                <select v-model="form.parent_id" class="w-full border rounded px-3 py-2">
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
                                <label class="font-semibold block mb-1">Mô tả</label>
                                <textarea v-model="form.description" class="w-full border rounded px-3 py-2" rows="3"
                                    placeholder="Nhập mô tả (không bắt buộc)"></textarea>
                                <div class="text-xs text-gray-400 mt-1 text-right">{{ form.description.length }}/5000 ký
                                    tự</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-8">
                        <Link href="/admin/categories"
                            class="px-6 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700">Quay lại</Link>
                        <button type="submit"
                            class="px-8 py-2 rounded bg-blue-500 text-white font-semibold hover:bg-blue-600 transition">
                            Lưu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
