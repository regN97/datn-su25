<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

// Định nghĩa kiểu dữ liệu cho Khách hàng
interface Customer {
    id: number;
    customer_name: string;
    email: string | null;
    phone: string;
    address: string | null;
    wallet: number;
}

const page = usePage<{ customer: Customer }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý khách hàng',
        href: '/admin/customers',
    },
    {
        title: 'Chỉnh sửa khách hàng',
        href: `/admin/customers/${page.props.customer.id}/edit`,
    },
];

// Form dữ liệu cho khách hàng
const form = ref<Customer>({
    id: page.props.customer.id,
    customer_name: page.props.customer.customer_name,
    email: page.props.customer.email,
    phone: page.props.customer.phone,
    address: page.props.customer.address,
    wallet: page.props.customer.wallet,
});

const showSuccessModal = ref(false);
const successMessage = ref('');

const goBack = () => {
    router.visit('/admin/customers');
};

const submitForm = () => {
    router.put(route('admin.customers.update', form.value.id), form.value, {
        onSuccess: () => {
            if (page.props.status === 'success') {
                successMessage.value = page.props.message as string;
                showSuccessModal.value = true;
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
    <Head title="Chỉnh sửa Khách hàng" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <h2 class="mb-6 text-2xl font-semibold text-gray-800 dark:text-gray-200">Chỉnh sửa khách hàng</h2>

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
                                <label for="customer_name" class="mb-2 block font-medium text-gray-700 dark:text-gray-300">
                                    Tên khách hàng <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="customer_name"
                                    v-model="form.customer_name"
                                    class="w-full rounded-md border-gray-300 bg-white p-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    :class="{ 'border-red-500': page.props.errors.customer_name }"
                                    placeholder="Nhập tên khách hàng"
                                />
                                <InputError :message="page.props.errors.customer_name" class="mt-1" />
                            </div>

                            <div>
                                <label for="email" class="mb-2 block font-medium text-gray-700 dark:text-gray-300">
                                    Email
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

                            <div>
                                <label for="wallet" class="mb-2 block font-medium text-gray-700 dark:text-gray-300">
                                    Ví tiền
                                </label>
                                <input
                                    type="number"
                                    id="wallet"
                                    v-model.number="form.wallet"
                                    class="w-full rounded-md border-gray-300 bg-white p-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    :class="{ 'border-red-500': page.props.errors.wallet }"
                                    placeholder="Nhập số tiền trong ví"
                                />
                                <InputError :message="page.props.errors.wallet" class="mt-1" />
                            </div>

                            <div class="md:col-span-2">
                                <label for="address" class="mb-2 block font-medium text-gray-700 dark:text-gray-300">
                                    Địa chỉ
                                </label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    class="w-full rounded-md border-gray-300 bg-white p-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    :class="{ 'border-red-500': page.props.errors.address }"
                                    rows="3"
                                    placeholder="Nhập địa chỉ khách hàng"
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
                                Quay lại
                            </button>

                            <button
                                type="submit"
                                class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors duration-200 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:bg-blue-300 dark:bg-blue-700 dark:hover:bg-blue-800 dark:disabled:bg-blue-500"
                            >
                                <span>Lưu</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div
            v-if="showSuccessModal"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto p-4 outline-none focus:outline-none"
        >
            <div class="relative mx-auto w-full max-w-md">
                <div class="relative flex w-full flex-col rounded-lg border-0 bg-white shadow-lg outline-none focus:outline-none dark:bg-gray-800">
                    <div class="flex items-center justify-between rounded-t border-b border-solid border-gray-300 p-5 dark:border-gray-700">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Thông báo thành công</h3>
                        <button
                            type="button"
                            class="ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-white"
                            @click="showSuccessModal = false"
                        >
                            <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <div class="relative p-6 text-center">
                        <div class="mb-4 flex justify-center">
                            <svg
                                class="m-10 h-50 w-50 text-green-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                        </div>
                        <p class="mb-4 text-lg font-medium text-gray-700 dark:text-gray-300">
                            {{ successMessage }}
                        </p>
                    </div>
                    <div class="flex items-center justify-end space-x-2 rounded-b border-t border-solid border-gray-300 p-6 dark:border-gray-700">
                        <button
                            type="button"
                            @click="goBack"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:ring-4 focus:ring-blue-300 focus:outline-none dark:border-gray-500 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-600"
                        >
                            Quay lại danh sách
                        </button>
                        <button
                            type="button"
                            @click="showSuccessModal = false"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 focus:outline-none dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                        >
                            Tiếp tục chỉnh sửa
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showSuccessModal" class="fixed inset-0 z-40 bg-black opacity-50"></div>
    </AppLayout>
</template>

<style lang="scss" scoped></style>