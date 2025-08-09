<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { Search, Plus, DollarSign, Package, User } from 'lucide-vue-next';
import axios from 'axios';

const { props } = usePage();
const {
    todayOrders,
    totalStock,
    currentCashier,
    currentShift,
    allProducts,
    shiftRevenue,
    shiftBills,
} = props;

const searchQuery = ref('');
const filteredProducts = ref([]);
const products = ref(allProducts.slice(0, 10));
const productPage = ref(1);
const itemsPerPage = 10;
const loadingProducts = ref(false);
const activeShift = ref(currentShift || 'Không xác định');
const currentShiftRevenue = ref(
    shiftRevenue.find((s) => s.shift_name === activeShift.value)?.revenue || '0đ'
);

const searchProducts = () => {
    const trimmedQuery = searchQuery.value.trim().toLowerCase();
    if (trimmedQuery) {
        filteredProducts.value = allProducts.filter(
            (product) =>
                product.name.toLowerCase().includes(trimmedQuery) ||
                product.sku.toLowerCase().includes(trimmedQuery)
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
        productsTable.addEventListener('scroll', () =>
            handleScroll('products-table', products, productPage, allProducts, loadingProducts)
        );
    }
});

onUnmounted(() => {
    const productsTable = document.getElementById('products-table');
    if (productsTable) {
        productsTable.removeEventListener('scroll', () =>
            handleScroll('products-table', products, productPage, allProducts, loadingProducts)
        );
    }
});
</script>

<template>
    <Head title="Dashboard - Tạp Hóa" />

    <CashierLayout>
        <div class="container mx-auto mt-6 px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white shadow-md rounded-lg p-4 flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-medium text-gray-600">Doanh thu {{ activeShift }}</h6>
                        <h4 class="text-xl font-bold text-green-600">{{ currentShiftRevenue }}</h4>
                    </div>
                    <DollarSign class="text-3xl text-green-600" />
                </div>
                <div class="bg-white shadow-md rounded-lg p-4 flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-medium text-gray-600">Đơn hàng trong ca</h6>
                        <h4 class="text-xl font-bold text-blue-600">{{ todayOrders }}</h4>
                    </div>
                    <Package class="text-3xl text-blue-600" />
                </div>
                <div class="bg-white shadow-md rounded-lg p-4 flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-medium text-gray-600">Sản phẩm còn</h6>
                        <h4 class="text-xl font-bold text-yellow-600">{{ totalStock }}</h4>
                    </div>
                    <Package class="text-3xl text-yellow-600" />
                </div>
                <div class="bg-white shadow-md rounded-lg p-4 flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-medium text-gray-600">Nhân viên trực</h6>
                        <h4 class="text-xl font-bold text-teal-600">{{ currentCashier }}</h4>
                    </div>
                    <User class="text-3xl text-teal-600" />
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-4 mb-6">
                <div class="flex w-full md:w-1/2">
                    <div class="relative w-full">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="w-full pl-10 p-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Tìm sản phẩm theo tên hoặc mã..."
                        />
                    </div>
                    <button
                        @click="searchProducts"
                        class="bg-blue-500 text-white px-4 py-2 rounded-r-lg hover:bg-blue-600 transition"
                    >
                        Tìm
                    </button>
                </div>
                <button
                    @click="createNewOrder"
                    class="w-full md:w-auto bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition flex items-center justify-center"
                >
                    <Plus class="mr-2" /> Tạo Đơn Mới
                </button>
            </div>

            <div v-if="filteredProducts.length" class="bg-white shadow-md rounded-lg mb-6">
                <div class="p-4 border-b bg-gray-50">
                    <h6 class="text-sm font-medium text-gray-600 flex items-center">
                        <Package class="mr-2" /> Kết quả tìm kiếm
                    </h6>
                </div>
                <div class="overflow-y-auto max-h-96">
                    <table class="w-full text-left">
                        <thead class="bg-gray-100 sticky top-0">
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
                                    <button
                                        @click="addToCart(product)"
                                        class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 transition"
                                    >
                                        <Plus class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white shadow-md rounded-lg">
                    <div class="p-4 border-b bg-gray-50">
                        <h6 class="text-sm font-medium text-gray-600 flex items-center">
                            <Package class="mr-2" /> Hóa đơn {{ activeShift }}
                        </h6>
                    </div>
                    <div id="shift-bills-table" class="overflow-y-auto max-h-96">
                        <table class="w-full text-left">
                            <thead class="bg-gray-100 sticky top-0">
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
                                    <td colspan="4" class="p-3 text-sm text-center text-gray-500">
                                        Không có hóa đơn trong {{ activeShift }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white shadow-md rounded-lg">
                    <div class="p-4 border-b bg-gray-50">
                        <h6 class="text-sm font-medium text-gray-600 flex items-center">
                            <Package class="mr-2" /> Tất cả sản phẩm
                        </h6>
                    </div>
                    <div id="products-table" class="overflow-y-auto max-h-96">
                        <table class="w-full text-left">
                            <thead class="bg-gray-100 sticky top-0">
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
                                            class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 transition"
                                        >
                                            <Plus class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!products.length" class="border-t">
                                    <td colspan="5" class="p-3 text-sm text-center text-gray-500">Không có sản phẩm</td>
                                </tr>
                                <tr v-if="loadingProducts" class="border-t">
                                    <td colspan="5" class="p-3 text-sm text-center text-gray-500">Đang tải...</td>
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