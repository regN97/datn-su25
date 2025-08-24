<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Search, ChevronLeft, ChevronRight, Eye } from 'lucide-vue-next';

const props = defineProps({
    customers: {
        type: Object,
        default: () => ({
            data: [],
            from: null,
            to: null,
            total: 0,
            prev_page_url: null,
            next_page_url: null,
            links: [],
            current_page: 1,
        }),
    },
    query: {
        type: String,
        default: '',
    },
});

const query = ref(props.query || '');

const searchCustomers = () => {
    router.post(route('cashier.customer.lookup.search'), { query: query.value });
};

const changePage = (page) => {
    router.get(
        route('cashier.customer.lookup'),
        { page, query: query.value },
        { preserveState: true }
    );
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(value);
};

const viewCustomerDetails = (customerId) => {
    router.get(route('cashier.customer.show', { customer: customerId }));
};

</script>

<template>
    <Head title="Tra cứu khách hàng" />
    <CashierLayout>
        <div class="p-6 max-w-7xl mx-auto">
            <div class="mb-8">
                <form @submit.prevent="searchCustomers" class="flex items-center gap-4 max-w-2xl mx-auto">
                    <div class="relative flex-1">
                        <Search class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-500" />
                        <input v-model="query" type="text"
                            placeholder="Nhập tên, email hoặc số điện thoại khách hàng..."
                            class="w-full p-3 pl-12 pr-4 bg-white border border-gray-300 rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" />
                    </div>
                    <button type="submit"
                        class="px-6 py-3 bg-blue-600 text-white rounded-full font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                        Tìm kiếm
                    </button>
                </form>
            </div>

            <div v-if="customers && customers.data && customers.data.length" class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">ID</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Tên khách hàng</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Email</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Số điện thoại</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Số dư ví</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Ngày tạo</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="customer in customers.data" :key="customer.id" class="border-t hover:bg-gray-50">
                            <td class="p-4">{{ customer.id }}</td>
                            <td class="p-4">{{ customer.customer_name }}</td>
                            <td class="p-4">{{ customer.email || '-' }}</td>
                            <td class="p-4">{{ customer.phone || '-' }}</td>
                            <td class="p-4">{{ formatCurrency(customer.wallet) }}</td>
                            <td class="p-4">{{ customer.created_at }}</td>
                            <td class="p-4">
                                <button @click="viewCustomerDetails(customer.id)"
                                    class="text-blue-600 hover:underline focus:outline-none flex items-center gap-1">
                                    <Eye class="w-5 h-5 inline-block" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p v-else class="text-gray-500 text-center mt-6">
                {{ query ? `Không tìm thấy khách hàng nào khớp với "${query}".` : 'Không có khách hàng nào để hiển thị.' }}
            </p>

            <div v-if="customers && customers.data && customers.data.length" class="mt-6 flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Hiển thị {{ customers.from }} đến {{ customers.to }} của {{ customers.total }} khách hàng
                </p>
                <div class="flex items-center gap-2">
                    <button :disabled="!customers.prev_page_url" @click="changePage(customers.current_page - 1)"
                        class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <ChevronLeft class="w-5 h-5 text-gray-600" />
                    </button>
                    <button :disabled="!customers.next_page_url" @click="changePage(customers.current_page + 1)"
                        class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <ChevronRight class="w-5 h-5 text-gray-600" />
                    </button>
                </div>
            </div>
        </div>
    </CashierLayout>
</template>