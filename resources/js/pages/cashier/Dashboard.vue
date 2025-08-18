<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { DollarSign, Package, Plus, Search, User } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

const { props } = usePage();
const { todayOrders, totalStock, currentCashier, currentShift, allProducts, shiftRevenue, shiftBills } = props;

const searchQuery = ref('');
const filteredProducts = ref([]);
const products = ref(allProducts.slice(0, 10));
const productPage = ref(1);
const itemsPerPage = 10;
const loadingProducts = ref(false);
const activeShift = ref(currentShift || 'Không xác định');
const currentShiftRevenue = ref(shiftRevenue.find((s) => s.shift_name === activeShift.value)?.revenue || '0đ');

const searchProducts = () => {
    const trimmedQuery = searchQuery.value.trim().toLowerCase();
    if (trimmedQuery) {
        filteredProducts.value = allProducts.filter(
            (product) => product.name.toLowerCase().includes(trimmedQuery) || product.sku.toLowerCase().includes(trimmedQuery),
        );
    } else {
        filteredProducts.value = [];
    }
};

const createNewOrder = () => {
    window.location.href = route('cashier.pos.index');
};

const addToCart = async (product) => {
    try {
        const response = await axios.post(route('cashier.addToCart'), {
            product_id: product.id,
        });
        alert(response.data.message);
        console.log('Cart updated:', response.data.cart);
    } catch (error) {
        console.error('Error adding to cart:', error);
        alert('Có lỗi khi thêm sản phẩm vào giỏ hàng!');
    }
};

const handleScroll = (tableId, dataRef, pageRef, sourceData, loadingRef) => {
    const table = document.getElementById(tableId);
    if (!table) return;

    const isBottom = table.scrollHeight - table.scrollTop <= table.clientHeight + 50;
    if (isBottom && !loadingRef.value && pageRef.value * itemsPerPage < sourceData.length) {
        loadingRef.value = true;
        setTimeout(() => {
            pageRef.value++;
            const newData = sourceData.slice(0, pageRef.value * itemsPerPage);
            dataRef.value = newData;
            loadingRef.value = false;
        }, 500);
    }
};

onMounted(() => {
    const productsTable = document.getElementById('products-table');
    if (productsTable) {
        productsTable.addEventListener('scroll', () => handleScroll('products-table', products, productPage, allProducts, loadingProducts));
    }
});

onUnmounted(() => {
    const productsTable = document.getElementById('products-table');
    if (productsTable) {
        productsTable.removeEventListener('scroll', () => handleScroll('products-table', products, productPage, allProducts, loadingProducts));
    }
});
</script>

<template>
    <Head title="Dashboard - Tạp Hóa" />

    <CashierLayout>
        <div class="container mx-auto mt-6 px-4">
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-md">
                    <div>
                        <h6 class="text-sm font-medium text-gray-600">Doanh thu {{ activeShift }}</h6>
                        <h4 class="text-xl font-bold text-green-600">{{ currentShiftRevenue }}</h4>
                    </div>
                    <DollarSign class="text-3xl text-green-600" />
                </div>
                <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-md">
                    <div>
                        <h6 class="text-sm font-medium text-gray-600">Đơn hàng trong ca</h6>
                        <h4 class="text-xl font-bold text-blue-600">{{ todayOrders }}</h4>
                    </div>
                    <Package class="text-3xl text-blue-600" />
                </div>
                <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-md">
                    <div>
                        <h6 class="text-sm font-medium text-gray-600">Sản phẩm còn</h6>
                        <h4 class="text-xl font-bold text-yellow-600">{{ totalStock }}</h4>
                    </div>
                    <Package class="text-3xl text-yellow-600" />
                </div>
                <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-md">
                    <div>
                        <h6 class="text-sm font-medium text-gray-600">Nhân viên trực</h6>
                        <h4 class="text-xl font-bold text-teal-600">{{ currentCashier }}</h4>
                    </div>
                    <User class="text-3xl text-teal-600" />
                </div>
            </div>

            <div class="mb-6 flex flex-col gap-4 md:flex-row">
                <div class="flex w-full md:w-1/2">
                    <div class="relative w-full">
                        <Search class="absolute top-1/2 left-3 -translate-y-1/2 transform text-gray-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="w-full rounded-l-lg border border-gray-300 p-2 pl-10 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            placeholder="Tìm sản phẩm theo tên hoặc mã..."
                        />
                    </div>
                    <button @click="searchProducts" class="rounded-r-lg bg-blue-500 px-4 py-2 text-white transition hover:bg-blue-600">Tìm</button>
                </div>
                <button
                    @click="createNewOrder"
                    class="flex w-full items-center justify-center rounded-lg bg-green-500 px-4 py-2 text-white transition hover:bg-green-600 md:w-auto"
                >
                    <Plus class="mr-2" /> Tạo Đơn Mới
                </button>
            </div>

            <div v-if="filteredProducts.length" class="mb-6 rounded-lg bg-white shadow-md">
                <div class="border-b bg-gray-50 p-4">
                    <h6 class="flex items-center text-sm font-medium text-gray-600"><Package class="mr-2" /> Kết quả tìm kiếm</h6>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    <table class="w-full text-left">
                        <thead class="sticky top-0 bg-gray-100">
                            <tr>
                                <th class="p-3 text-sm font-medium text-gray-600">Tên SP</th>
                                <th class="p-3 text-sm font-medium text-gray-600">SL Đã Bán</th>
                                <th class="p-3 text-sm font-medium text-gray-600">SL Còn</th>
                                <th class="p-3 text-sm font-medium text-gray-600">Giá</th>
                                <th class="p-3 text-sm font-medium text-gray-600">Thêm</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in filteredProducts" :key="product.sku" class="border-t hover:bg-gray-50">
                                <td class="p-3 text-sm">{{ product.name }}</td>
                                <td class="p-3 text-sm">{{ product.total_quantity }}</td>
                                <td class="p-3 text-sm">{{ product.stock }}</td>
                                <td class="p-3 text-sm">{{ product.price }}</td>
                                <td class="p-3">
                                    <button @click="addToCart(product)" class="rounded bg-blue-500 px-2 py-1 text-white transition hover:bg-blue-600">
                                        <Plus class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-lg bg-white shadow-md">
                    <div class="border-b bg-gray-50 p-4">
                        <h6 class="flex items-center text-sm font-medium text-gray-600"><Package class="mr-2" /> Hóa đơn {{ activeShift }}</h6>
                    </div>
                    <div id="shift-bills-table" class="max-h-96 overflow-y-auto">
                        <table class="w-full text-left">
                            <thead class="sticky top-0 bg-gray-100">
                                <tr>
                                    <th class="p-3 text-sm font-medium text-gray-600">#</th>
                                    <th class="p-3 text-sm font-medium text-gray-600">Khách hàng</th>
                                    <th class="p-3 text-sm font-medium text-gray-600">Thành tiền</th>
                                    <th class="p-3 text-sm font-medium text-gray-600">Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="bill in shiftBills.find((s) => s.shift_name === activeShift)?.bills || []"
                                    :key="bill.bill_number"
                                    class="border-t"
                                >
                                    <td class="p-3 text-sm">{{ bill.bill_number }}</td>
                                    <td class="p-3 text-sm">{{ bill.customer_name }}</td>
                                    <td class="p-3 text-sm">{{ bill.total_amount }}</td>
                                    <td class="p-3 text-sm">{{ bill.created_at }}</td>
                                </tr>
                                <tr v-if="!shiftBills.find((s) => s.shift_name === activeShift)?.bills?.length" class="border-t">
                                    <td colspan="4" class="p-3 text-center text-sm text-gray-500">Không có hóa đơn trong {{ activeShift }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg bg-white shadow-md">
                    <div class="border-b bg-gray-50 p-4">
                        <h6 class="flex items-center text-sm font-medium text-gray-600"><Package class="mr-2" /> Tất cả sản phẩm</h6>
                    </div>
                    <div id="products-table" class="max-h-96 overflow-y-auto">
                        <table class="w-full text-left">
                            <thead class="sticky top-0 bg-gray-100">
                                <tr>
                                    <th class="p-3 text-sm font-medium text-gray-600">Tên SP</th>
                                    <th class="p-3 text-sm font-medium text-gray-600">SL Đã Bán</th>
                                    <th class="p-3 text-sm font-medium text-gray-600">SL Còn</th>
                                    <th class="p-3 text-sm font-medium text-gray-600">Giá</th>
                                    <th class="p-3 text-sm font-medium text-gray-600">Thêm</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="product in products" :key="product.sku" class="border-t hover:bg-gray-50">
                                    <td class="p-3 text-sm">{{ product.name }}</td>
                                    <td class="p-3 text-sm">{{ product.total_quantity }}</td>
                                    <td class="p-3 text-sm">{{ product.stock }}</td>
                                    <td class="p-3 text-sm">{{ product.price }}</td>
                                    <td class="p-3">
                                        <button
                                            @click="addToCart(product)"
                                            class="rounded bg-blue-500 px-2 py-1 text-white transition hover:bg-blue-600"
                                        >
                                            <Plus class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!products.length" class="border-t">
                                    <td colspan="5" class="p-3 text-center text-sm text-gray-500">Không có sản phẩm</td>
                                </tr>
                                <tr v-if="loadingProducts" class="border-t">
                                    <td colspan="5" class="p-3 text-center text-sm text-gray-500">Đang tải...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </CashierLayout>
</template>

<style scoped>
.grid-cols-2 > div {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.grid-cols-2 > div > div.overflow-y-auto {
    flex-grow: 1;
    max-height: 24rem;
}

table {
    width: 100%;
    table-layout: auto;
}

th,
td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
