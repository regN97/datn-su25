<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed } from 'vue';
import { ChevronDown, ChevronUp, Plus, Search, Calendar } from 'lucide-vue-next';
import axios from 'axios';
import { debounce } from 'lodash';

const allReturnBills = ref([]);
const expandedReturnBill = ref(null);
const errorMessage = ref('');
const searchQuery = ref('');
const filterDate = ref('');

const fetchAllReturnBills = async () => {
    try {
        const response = await axios.get(route('cashier.returns.all'), {
            params: {
                query: searchQuery.value,
                date: filterDate.value,
            },
        });
        allReturnBills.value = response.data;
        errorMessage.value = '';
    } catch (error) {
        if (error.response && error.response.data && error.response.data.error) {
            errorMessage.value = error.response.data.error;
        } else {
            errorMessage.value = 'Đã xảy ra lỗi khi lấy danh sách đơn trả hàng.';
        }
        console.error("Failed to fetch all return bills:", error);
    }
};

const debouncedFetch = debounce(fetchAllReturnBills, 300);

const toggleDetails = (returnBill) => {
    if (expandedReturnBill.value && expandedReturnBill.value.id === returnBill.id) {
        expandedReturnBill.value = null;
    } else {
        expandedReturnBill.value = returnBill;
    }
};

onMounted(() => {
    fetchAllReturnBills();
});

watch(searchQuery, (newQuery) => {
    debouncedFetch();
});

watch(filterDate, (newDate) => {
    fetchAllReturnBills();
});

const formatCurrency = (value) => {
    if (value == null || isNaN(value)) return '0₫';
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(value);
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('vi-VN', {
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
    });
};

const groupedReturnDetails = computed(() => {
    if (!expandedReturnBill.value) return [];
    
    const groups = {};
    expandedReturnBill.value.details.forEach(detail => {
        const key = detail.p_name;
        if (!groups[key]) {
            groups[key] = {
                p_name: detail.p_name,
                unit_price: detail.unit_price,
                returned_quantity: 0,
                subtotal: 0,
            };
        }
        groups[key].returned_quantity += detail.returned_quantity;
        // Tính lại subtotal để đảm bảo chính xác
        groups[key].subtotal += detail.returned_quantity * detail.unit_price;
    });
    
    return Object.values(groups);
});
</script>

<template>
    <Head title="Danh sách đơn trả hàng" />
    <CashierLayout>
        <div class="p-6 max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Danh sách đơn trả hàng</h1>

            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                <div class="flex-1 w-full flex items-center gap-4">
                    <div class="relative flex-1">
                        <Search class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-500" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Tìm theo mã bill..."
                            class="w-full p-3 pl-12 pr-4 bg-gray-50 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        />
                    </div>
                    <div class="relative w-48">
                        <input
                            v-model="filterDate"
                            type="date"
                            class="w-full p-3 pl-4 pr-12 bg-gray-50 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        />
                        <Calendar class="absolute right-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-500" />
                    </div>
                </div>
                <Link :href="route('cashier.returns.index')" class="px-4 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors flex items-center">
                    <Plus class="w-4 h-4 mr-2" />
                    Thêm mới trả hàng
                </Link>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div v-if="errorMessage" class="mt-4 text-red-500 font-semibold">{{ errorMessage }}</div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-3 text-left text-sm font-semibold text-gray-600">Số đơn trả</th>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600">Số hóa đơn gốc</th>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600">Khách hàng</th>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600">Tổng tiền hoàn trả</th>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600">Ngày tạo</th>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600">Người trả</th>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="allReturnBills.length > 0" v-for="returnBill in allReturnBills" :key="returnBill.id">
                                <tr class="border-t">
                                    <td class="p-3">{{ returnBill.return_bill_number }}</td>
                                    <td class="p-3">{{ returnBill.bill?.bill_number }}</td>
                                    <td class="p-3">{{ returnBill.customer?.customer_name || 'Khách lẻ' }}</td>
                                    <td class="p-3">{{ formatCurrency(returnBill.total_amount_returned) }}</td>
                                    <td class="p-3">{{ formatDate(returnBill.created_at) }}</td>
                                    <td class="p-3">{{ returnBill.cashier?.name || 'N/A' }}</td>
                                    <td class="p-3 text-right">
                                        <button @click="toggleDetails(returnBill)" class="text-blue-600 hover:text-blue-800 font-semibold flex items-center">
                                            Xem chi tiết
                                            <component :is="expandedReturnBill && expandedReturnBill.id === returnBill.id ? ChevronUp : ChevronDown" class="ml-1 w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="expandedReturnBill && expandedReturnBill.id === returnBill.id">
                                    <td colspan="7" class="p-3 bg-gray-50 border-t">
                                        <div class="p-4 rounded-lg">
                                            <h2 class="font-bold text-lg mb-2">Chi tiết đơn trả</h2>
                                            <p class="text-sm text-gray-600 mb-2"><b class="text-sm text-black mb-2">Lý do: </b>{{ returnBill.reason || 'Không có lý do' }}</p>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full border-collapse mt-2">
                                                    <thead>
                                                        <tr class="bg-gray-100">
                                                            <th class="p-3 text-left text-xs font-semibold text-gray-600">Sản phẩm</th>
                                                            <th class="p-3 text-left text-xs font-semibold text-gray-600">Số lượng đã trả</th>
                                                            <th class="p-3 text-left text-xs font-semibold text-gray-600">Giá</th>
                                                            <th class="p-3 text-left text-xs font-semibold text-gray-600">Thành tiền</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="detail in groupedReturnDetails" :key="detail.p_name" class="border-t">
                                                            <td class="p-3">{{ detail.p_name }}</td>
                                                            <td class="p-3">{{ detail.returned_quantity }}</td>
                                                            <td class="p-3">{{ formatCurrency(detail.unit_price) }}</td>
                                                            <td class="p-3">{{ formatCurrency(detail.returned_quantity * detail.unit_price) }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="7" class="p-3 text-center text-gray-500">
                                    Không có đơn trả hàng nào được tìm thấy.
                                    <Link :href="route('cashier.returns.index')" class="text-blue-600 hover:underline">
                                        Tạo đơn trả hàng mới
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </CashierLayout>
</template>