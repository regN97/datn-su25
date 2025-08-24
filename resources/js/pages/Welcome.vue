<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const showLoginOptions = ref(false);

const toggleLoginOptions = () => {
    showLoginOptions.value = !showLoginOptions.value;
};

const closeLoginOptions = () => {
    showLoginOptions.value = false;
};
</script>

<template>

    <Head title="Welcome to G7 Mart">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div
        class="flex min-h-screen flex-col bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-800 text-black dark:text-gray-100 transition-colors duration-300">
        <!-- Header -->
        <header
            class="w-full px-6 py-4 lg:px-8 lg:py-6 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-sm sticky top-0 z-20">
            <div class="mx-auto max-w-7xl flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-black dark:text-gray-100">
                        G7 Mart
                    </h1>
                </div>

                <!-- Navigation -->
                <nav class="flex items-center gap-4">
                    <template v-if="$page.props.auth?.user">
                        <Link
                            :href="route($page.props.auth.user.role_id === 3 ? 'cashier.dashboard' : 'admin.dashboard')"
                            class="relative px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-full hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600
                                   transition-all duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        Dashboard
                        <span
                            class="absolute inset-0 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 opacity-0 hover:opacity-20 transition-opacity duration-300"></span>
                        </Link>
                    </template>
                    <template v-else>
                        <div class="flex gap-4">
                            <Link :href="route('login')"
                                class="inline-flex items-center px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-full hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600
               transition-all duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Đăng nhập Admin
                            </Link>

                            <Link :href="route('cashier.login')"
                                class="inline-flex items-center px-6 py-2 text-sm font-medium text-white bg-green-600 rounded-full hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600
               transition-all duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Đăng nhập Nhân viên
                            </Link>
                        </div>



                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="flex flex-1 flex-col items-center justify-center px-6 py-16 lg:px-8">
            <div class="mx-auto max-w-7xl text-center">
                <h2 class="text-5xl  tracking-tight text-black dark:text-gray-100 sm:text-6xl animate-fade-in">
                    Chào mừng đến với G7 Mart
                </h2>
                <p class="mt-6 text-lg text-black dark:text-gray-300 max-w-2xl mx-auto">
                    Quản lý cửa hàng bán lẻ của bạn một cách dễ dàng và hiệu quả với hệ thống quản lý hiện đại, tối ưu
                    hóa quy
                    trình kinh doanh.
                </p>

            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full bg-gray-100 dark:bg-gray-900 py-8 text-black dark:text-gray-300">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-3">
                    <div>
                        <h3 class="text-lg font-semibold text-black dark:text-gray-100">G7 Mart</h3>
                        <p class="mt-3 text-sm text-black dark:text-gray-300">
                            Hệ thống quản lý cửa hàng bán lẻ hiện đại, giúp bạn tối ưu hóa quy trình kinh doanh một cách
                            hiệu
                            quả.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-black dark:text-gray-100">Liên kết nhanh</h3>
                        <ul class="mt-3 space-y-3 text-sm">
                            <li>
                                <Link href="#"
                                    class="text-black hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 transition-colors duration-200">
                                Về chúng tôi
                                </Link>
                            </li>
                            <li>
                                <Link href="#"
                                    class="text-black hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 transition-colors duration-200">
                                Liên hệ
                                </Link>
                            </li>
                            <li>
                                <Link href="#"
                                    class="text-black hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 transition-colors duration-200">
                                Chính sách bảo mật
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-black dark:text-gray-100">Liên hệ</h3>
                        <p class="mt-3 text-sm text-black dark:text-gray-300">
                            Email: support@g7mart.com<br />
                            Hotline: (+84) 123 456 789<br />
                            Địa chỉ: 123 Đường ABC, TP. Hồ Chí Minh
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>