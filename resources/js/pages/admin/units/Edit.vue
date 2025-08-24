<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <h2 class="mb-6 text-2xl font-semibold text-gray-800 dark:text-gray-200">Sửa đơn vị tính</h2>
                    <form class="w-full space-y-6" @submit.prevent="submit">
                        <div>
                            <label for="name" class="mb-1 block text-sm font-semibold text-gray-700">
                                Tên đơn vị <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="name"
                                v-model="form.name"
                                class="h-12 w-full rounded-md border border-gray-300 px-3 text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                :class="{ 'border-red-500': form.errors.name }"
                                placeholder="Nhập tên đơn vị vào đây"
                            />
                            <div v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</div>
                        </div>
                        <div>
                            <label for="description" class="mb-1 block text-sm font-semibold text-gray-700">Mô tả</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                rows="3"
                            ></textarea>
                            <div v-if="form.errors.description" class="mt-1 text-sm text-red-500">{{ form.errors.description }}</div>
                        </div>
                        <div class="mt-8 flex justify-end gap-2">
                            <button type="button" @click="goBack" class="rounded bg-gray-200 px-6 py-2 text-gray-700 hover:bg-gray-300">
                                Quay lại
                            </button>
                            <button type="submit" class="rounded bg-green-600 px-8 py-2 font-semibold text-white transition hover:bg-green-700">
                                Lưu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
const props = defineProps({ unit: Object });
const form = useForm({ name: props.unit.name, description: props.unit.description });
const breadcrumbs = [
    { title: 'Quản lý đơn vị tính', href: route('admin.units.index') },
    { title: 'Sửa', href: '#' },
];
function submit() {
    form.put(route('admin.units.update', props.unit.id));
}
function goBack() {
    router.visit(route('admin.units.index'));
}
</script>
