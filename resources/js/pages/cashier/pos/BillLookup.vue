<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Search, ChevronLeft, ChevronRight, Eye, EyeOff, UploadCloud, Image } from 'lucide-vue-next';

const props = defineProps({
    bills: {
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
const expandedBill = ref(null);
const previewUrls = ref({}); // Lưu ảnh xem trước theo bill.id

const searchBills = () => {
    router.post(route('cashier.bill.lookup.search'), { query: query.value });
};

const toggleDetails = (billId) => {
    expandedBill.value = expandedBill.value === billId ? null : billId;
};

const getBillDetails = (billId) => {
    const bill = props.bills.data.find((b) => b.id === billId);
    return bill?.details || [];
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(value);
};

const changePage = (page) => {
    router.get(
        route('cashier.bill.lookup'),
        { page, query: query.value },
        { preserveState: true }
    );
};

const handleInlineUpload = (event, bill) => {
    const file = event.target.files[0];
    if (!file) return;

    previewUrls.value[bill.id] = URL.createObjectURL(file);

    const formData = new FormData();
    formData.append('payment_proof', file);

    router.post(
        route('cashier.bill.lookup.proof', { bill: bill.id }),
        formData,
        {
            forceFormData: true,
            onSuccess: () => {
                router.reload();
            },
            onError: (errors) => {
                console.error('Upload error:', errors);
                alert('Có lỗi xảy ra khi tải lên: ' + Object.values(errors).join(', '));
            },
        }
    );
};
</script>

<template>

    <Head title="Tra cứu hóa đơn" />
    <CashierLayout>
        <div class="p-6 max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Tra cứu hóa đơn</h1>

            <div class="mb-8">
                <form @submit.prevent="searchBills" class="flex items-center gap-4 max-w-2xl mx-auto">
                    <div class="relative flex-1">
                        <Search class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-500" />
                        <input v-model="query" type="text"
                            placeholder="Nhập số hóa đơn, tên khách hàng, số điện thoại hoặc số lô..."
                            class="w-full p-3 pl-12 pr-4 bg-white border border-gray-300 rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" />
                    </div>
                    <button type="submit"
                        class="px-6 py-3 bg-blue-600 text-white rounded-full font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                        Tìm kiếm
                    </button>
                </form>
            </div>

            <div v-if="bills && bills.data && bills.data.length" class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Số hóa đơn</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Khách hàng</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Tổng tiền</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Phương thức</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Ngày tạo</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Minh chứng</th>
                            <th class="p-4 text-left text-sm font-semibold text-gray-600">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="bill in bills.data" :key="bill.id" class="border-t hover:bg-gray-50">
                            <td class="p-4">{{ bill.bill_number }}</td>
                            <td class="p-4">{{ bill.customer_name || '-' }}</td>
                            <td class="p-4">{{ formatCurrency(bill.total_amount) }}</td>
                            <td class="p-4">{{ bill.payment_method || '-' }}</td>
                            <td class="p-4">{{ bill.created_at }}</td>
                            <td class="p-4">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2">
                                        <a v-if="bill.payment_proof_url" :href="bill.payment_proof_url" target="_blank"
                                            class="text-blue-600 hover:underline">
                                            <Image class="w-5 h-5 inline-block" />
                                        </a>
                                        <span v-else>-</span>
                                    </div>
                                    <input type="file" :id="'proof-' + bill.id"
                                        class="text-sm file:text-sm file:rounded file:border-0 file:bg-blue-100 file:text-blue-700"
                                        accept="image/*" @change="handleInlineUpload($event, bill)" />
                                    
                                </div>
                            </td>
                            <td class="p-4">
                                <button @click="toggleDetails(bill.id)"
                                    class="text-blue-600 hover:underline focus:outline-none flex items-center gap-1">
                                    <template v-if="expandedBill === bill.id">
                                        <EyeOff class="w-5 h-5 inline-block" />
                                        
                                    </template>
                                    <template v-else>
                                        <Eye class="w-5 h-5 inline-block" />
                                    </template>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="expandedBill" class="bg-gray-50">
                            <td colspan="8" class="p-4">
                                <div>
                                    <h3 class="font-semibold mb-3 text-gray-700">
                                        Chi tiết hóa đơn - {{bills.data.find(b => b.id === expandedBill)?.bill_number
                                        }}
                                    </h3>
                                    <table class="min-w-full bg-white border rounded-lg">
                                        <thead>
                                            <tr class="bg-gray-100">
                                                <th class="p-3 text-left text-sm font-semibold text-gray-600">Sản phẩm
                                                </th>
                                                <th class="p-3 text-left text-sm font-semibold text-gray-600">Số lô</th>
                                                <th class="p-3 text-left text-sm font-semibold text-gray-600">Số lượng
                                                </th>
                                                <th class="p-3 text-left text-sm font-semibold text-gray-600">Đơn giá
                                                </th>
                                                <th class="p-3 text-left text-sm font-semibold text-gray-600">Thành tiền
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="detail in getBillDetails(expandedBill)"
                                                :key="detail.product_name" class="border-t hover:bg-gray-50">
                                                <td class="p-3">{{ detail.product_name }}</td>
                                                <td class="p-3">{{ detail.batch_number || '-' }}</td>
                                                <td class="p-3">{{ detail.quantity }}</td>
                                                <td class="p-3">{{ formatCurrency(detail.unit_price) }}</td>
                                                <td class="p-3">{{ formatCurrency(detail.subtotal) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p v-else class="text-gray-500 text-center mt-6">
                {{ query ? `Không tìm thấy hóa đơn nào khớp với "${query}".` : 'Không có hóa đơn nào để hiển thị.' }}
            </p>

            <div v-if="bills && bills.data && bills.data.length" class="mt-6 flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Hiển thị {{ bills.from }} đến {{ bills.to }} của {{ bills.total }} hóa đơn
                </p>
                <div class="flex items-center gap-2">
                    <button :disabled="!bills.prev_page_url" @click="changePage(bills.current_page - 1)"
                        class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <ChevronLeft class="w-5 h-5 text-gray-600" />
                    </button>
                    <button :disabled="!bills.next_page_url" @click="changePage(bills.current_page + 1)"
                        class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <ChevronRight class="w-5 h-5 text-gray-600" />
                    </button>
                </div>
            </div>
        </div>
    </CashierLayout>
</template>
