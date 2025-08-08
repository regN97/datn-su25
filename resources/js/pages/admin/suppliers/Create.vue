<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

// Định nghĩa kiểu dữ liệu cho Nhà cung cấp
interface Supplier {
    name: string;
    contact_person: string;
    email: string;
    phone: string;
    address: string | null;
}

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lí nhà cung cấp',
        href: '/admin/suppliers',
    },
    {
        title: 'Thêm nhà cung cấp',
        href: '/admin/suppliers/create',
    },
];

// Form dữ liệu cho nhà cung cấp
const form = ref<Supplier>({
    name: '',
    contact_person: '',
    email: '',
    phone: '',
    address: null,
});

const goBack = () => {
    router.visit('/admin/suppliers');
};

const resetForm = () => {
    form.value = {
        name: '',
        contact_person: '',
        email: '',
        phone: '',
        address: null,
    };
};

const submitForm = () => {
    router.post(route('admin.suppliers.store'), form.value, {
        onSuccess: () => {
            resetForm();
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
};
</script>

<template>
    <Head title="Thêm Nhà cung cấp" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <h2 class="mb-6 text-2xl font-semibold text-gray-800 dark:text-gray-200">Thêm nhà cung cấp</h2>

                    <div
                        v-if="Object.keys(page.props.errors).length > 0"
                        class="relative mb-6 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700"
                        role="alert"
                    >
                        <strong class="font-bold">Lỗi nhập liệu! </strong>
                        <span class="block sm:inline">Vui lòng kiểm tra lại các trường dưới đây.</span>
                    </div>

                    <form class="space-y-6" @submit.prevent="submitForm">
                        <div class="grid grid-cols-1 gap-x-8 gap-y-6 md:grid-cols-2">
                            <div>
                                <label for="name" class="mb-2 block font-medium text-gray-700 dark:text-gray-300">
                                    Tên nhà cung cấp <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    v-model="form.name"
                                    class="w-full rounded-md border-gray-300 bg-white p-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    :class="{ 'border-red-500': page.props.errors.name }"
                                    placeholder="Nhập tên nhà cung cấp"
                                />
                                <InputError :message="page.props.errors.name" class="mt-1" />
                            </div>

                            <div>
                                <label for="contact_person" class="mb-2 block font-medium text-gray-700 dark:text-gray-300">
                                    Người liên hệ <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="contact_person"
                                    v-model="form.contact_person"
                                    class="w-full rounded-md border-gray-300 bg-white p-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    :class="{ 'border-red-500': page.props.errors.contact_person }"
                                    placeholder="Nhập tên người liên hệ"
                                />
                                <InputError :message="page.props.errors.contact_person" class="mt-1" />
                            </div>

                            <div>
                                <label for="email" class="mb-2 block font-medium text-gray-700 dark:text-gray-300">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    v-model="form.email"
                                    class="w-full rounded-md border-gray-300 bg-white p-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    :class="{ 'border-red-500': page.props.errors.email }"
                                    placeholder="Nhập email"
                                />
                                <InputError :message="page.props.errors.email" class="mt-1" />
                            </div>

                            <div>
                                <label for="phone" class="mb-2 block font-medium text-gray-700 dark:text-gray-300">
                                    Số điện thoại <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="tel"
                                    id="phone"
                                    v-model="form.phone"
                                    class="w-full rounded-md border-gray-300 bg-white p-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    :class="{ 'border-red-500': page.props.errors.phone }"
                                    placeholder="Nhập số điện thoại"
                                />
                                <InputError :message="page.props.errors.phone" class="mt-1" />
                            </div>

                            <div class="md:col-span-2">
                                <label for="address" class="mb-2 block font-medium text-gray-700 dark:text-gray-300">
                                    Địa chỉ <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    class="w-full rounded-md border-gray-300 bg-white p-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    :class="{ 'border-red-500': page.props.errors.address }"
                                    rows="3"
                                    placeholder="Nhập địa chỉ nhà cung cấp"
                                ></textarea>
                                <InputError :message="page.props.errors.address" class="mt-1" />
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end space-x-4">
                            <button
                                type="button"
                                @click="goBack"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-200 ease-in-out hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none dark:border-gray-500 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500"
                            >
                                🙏 Quay lại
                            </button>

                            <button
                                type="submit"
                                class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors duration-200 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:bg-blue-300 dark:bg-blue-700 dark:hover:bg-blue-800 dark:disabled:bg-blue-500"
                            >
                                <span>💾 Lưu</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </AppLayout>
</template>

<style lang="scss" scoped></style>
