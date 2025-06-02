<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';

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

const showSuccessModal = ref(false);
const successMessage = ref('');

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
            if (page.props.status === 'success') {
                successMessage.value = page.props.message as string;
                showSuccessModal.value = true;
                resetForm();
            }
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
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <h2 class="text-2xl font-semibold mb-6 text-gray-800 dark:text-gray-200">Thêm nhà cung cấp</h2>

                    <div v-if="Object.keys(page.props.errors).length > 0" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                        <strong class="font-bold">Lỗi nhập liệu! </strong>
                        <span class="block sm:inline">Vui lòng kiểm tra lại các trường dưới đây.</span>
                    </div>

                    <form class="space-y-6" @submit.prevent="submitForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                            <div>
                                <label for="name" class="block mb-2 font-medium text-gray-700 dark:text-gray-300">
                                    Tên nhà cung cấp <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" v-model="form.name"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    :class="{ 'border-red-500': page.props.errors.name }"
                                    placeholder="Nhập tên nhà cung cấp" />
                                <InputError :message="page.props.errors.name" class="mt-1" />
                            </div>

                            <div>
                                <label for="contact_person" class="block mb-2 font-medium text-gray-700 dark:text-gray-300">
                                    Người liên hệ <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="contact_person" v-model="form.contact_person"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    :class="{ 'border-red-500': page.props.errors.contact_person }"
                                    placeholder="Nhập tên người liên hệ" />
                                <InputError :message="page.props.errors.contact_person" class="mt-1" />
                            </div>

                            <div>
                                <label for="email" class="block mb-2 font-medium text-gray-700 dark:text-gray-300">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" id="email" v-model="form.email"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    :class="{ 'border-red-500': page.props.errors.email }"
                                    placeholder="Nhập email" />
                                <InputError :message="page.props.errors.email" class="mt-1" />
                            </div>

                            <div>
                                <label for="phone" class="block mb-2 font-medium text-gray-700 dark:text-gray-300">
                                    Số điện thoại <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" id="phone" v-model="form.phone"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    :class="{ 'border-red-500': page.props.errors.phone }"
                                    placeholder="Nhập số điện thoại" />
                                <InputError :message="page.props.errors.phone" class="mt-1" />
                            </div>

                            <div class="md:col-span-2">
                                <label for="address" class="block mb-2 font-medium text-gray-700 dark:text-gray-300">
                                    Địa chỉ <span class="text-red-500">*</span>
                                </label>
                                <textarea id="address" v-model="form.address"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    :class="{ 'border-red-500': page.props.errors.address }"
                                    rows="3" placeholder="Nhập địa chỉ nhà cung cấp"></textarea>
                                <InputError :message="page.props.errors.address" class="mt-1" />
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4 mt-8">
                            <button type="button" @click="goBack"
                                class="inline-flex items-center px-5 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 ease-in-out dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 dark:hover:bg-gray-500">
                                🙏 Quay lại
                            </button>

                            <button type="submit"
                                class="inline-flex items-center px-5 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 ease-in-out dark:bg-blue-700 dark:hover:bg-blue-800 disabled:bg-blue-300 dark:disabled:bg-blue-500">
                                <span>💾 Lưu</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none p-4">
            <div class="relative w-full max-w-md mx-auto">
                <div class="relative flex flex-col w-full bg-white border-0 rounded-lg shadow-lg outline-none focus:outline-none dark:bg-gray-800">
                    <div class="flex items-center justify-between p-5 border-b border-solid border-gray-300 rounded-t dark:border-gray-700">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Thông báo thành công
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-700 dark:hover:text-white" @click="showSuccessModal = false">
                            <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <div class="relative p-6 text-center">
                        <div class="flex justify-center mb-4">
                            <svg class="w-50 h-50 text-green-500 m-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-4">
                            {{ successMessage }}
                        </p>
                    </div>
                    <div class="flex items-center justify-end p-6 space-x-2 border-t border-solid border-gray-300 rounded-b dark:border-gray-700">
                        <button type="button" @click="goBack" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-600">
                            Quay lại danh sách
                        </button>
                        <button type="button" @click="showSuccessModal = false" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Thêm nhà cung cấp khác
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showSuccessModal" class="opacity-50 fixed inset-0 z-40 bg-black"></div>
    </AppLayout>
</template>

<style lang="scss" scoped></style>