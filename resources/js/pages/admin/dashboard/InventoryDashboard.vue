<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { BarElement, CategoryScale, Chart, Legend, LinearScale, Tooltip } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { Package, Layers, TrendingDown, AlertTriangle, ChartNoAxesCombined, Undo2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { type BreadcrumbItem } from '@/types'
import { Bar } from 'vue-chartjs';
Chart.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend, ChartDataLabels);


const props = defineProps<{
    overviewStats: Array<{ label: string, value: number, icon: string, color: string, unit?: string }>,
    chartData: Array<{ month: string, value: number }>,
    products: Array<{ id: number, name: string, sku: string, selling_price: number, current_stock: number }>,
    slowMovingProducts: Array<{ id: number, name: string, sold: number }>,
    returnStats: {
        totalReturns: number;
        totalReturnValue: number;
        mostReturnedProduct: string;
    },
    // Tách expiringBatchItems ra thành một prop riêng
    expiringBatchItems: Array<{
        id: number;
        product_name: string;
        batch_number: string
        current_quantity: number;
        expiry_date: string;
        days_until_expiry: number;
        estimated_loss: number;
    }>;
    lowStockProducts: Array<{
        id: number;
        name: string;
        sku: string;
        current_stock: number;
        min_stock: number;
        remaining_percent: number;
    }>;
    outOfStockProducts: Array<{
        id: number;
        name: string;
        sku: string;
        min_stock: number;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventory Dashboard',
        href: 'admin/dashboard/InventoryDashboard',
    },
]

const activeTab = ref('expiring'); // Default tab

const chartLabels = props.chartData.map(i => i.month);
const chartValues = props.chartData.map(i => i.value);
const chartOptions = {
    responsive: true,
    plugins: {
        legend: { display: false },
        tooltip: { enabled: true, backgroundColor: '#2563eb', titleColor: '#fff', bodyColor: '#fff' },
        datalabels: {
            anchor: 'end',
            align: 'end',
            color: '#111827',
            font: { weight: 'bold', size: 12 },
            formatter: (value: number) => value.toLocaleString(),
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: { color: '#e5e7eb' },
            ticks: { color: '#6b7280', font: { size: 13 } },
        },
        x: {
            grid: { color: '#f3f4f6' },
            ticks: { color: '#6b7280', font: { size: 13 } },
        },
    },
    layout: { padding: { top: 20 } },
    elements: {
        bar: {
            borderRadius: 6,
            borderSkipped: false,
        },
    },
};
const iconMap = {
    Package,
    Layers,
    TrendingDown,
    AlertTriangle,
};

const productList = ref([...props.products]);

function removeProduct(id: number) {
    if (confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
        productList.value = productList.value.filter((p) => p.id !== id);
    }
}

// Thêm thông tin tổng quan phụ
const totalProducts = productList.value.length;
const totalStockValue = productList.value.reduce((sum, p) => sum + p.selling_price * p.current_stock, 0);

function formatCurrency(value: number | string) {
    const num = Number(value) || 0;
    return num.toLocaleString('vi-VN', {
        style: 'currency',
        currency: 'VND',
        minimumFractionDigits: 0
    });
}
</script>

<template>

    <Head title="Inventory Dashboard " />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-8 bg-gray-50 min-h-screen">
            <!-- Số liệu tổng quan -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-for="stat in props.overviewStats" :key="stat.label"
                    class="rounded-xl border bg-white p-5 flex items-center gap-4 shadow hover:shadow-md transition">
                    <div :class="`rounded-full p-3 text-xl ${stat.color} bg-opacity-10`">
                        <component :is="iconMap[stat.icon as keyof typeof iconMap]" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold">
                            <template v-if="stat.unit === 'VND'">
                                {{ formatCurrency(stat.value) }}
                            </template>
                            <template v-else>
                                {{ stat.value }}
                            </template>
                        </div>
                        <div class="text-xs text-gray-500">{{ stat.label }}</div>
                    </div>
                </div>
            </div>
            <!-- Biểu đồ giá trị tồn kho theo tháng -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <ChartNoAxesCombined class="w-5 h-5 text-blue-500" /> Biểu đồ giá trị tồn kho theo tháng
                </h2>
                <!-- @ts-ignore -->
                <!-- @ts-ignore -->
                <Bar :data="{ labels: chartLabels, datasets: [{ label: 'Giá trị tồn kho', data: chartValues, backgroundColor: '#3b82f6' }] }"
                    :options="chartOptions as any" :plugins="[ChartDataLabels as any]" height="120" />
            </div>
            <!-- Box thống kê mới -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Box 1: Top sản phẩm bán chậm -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <TrendingDown class="w-5 h-5 text-red-500" />
                        Top 10 sản phẩm bán chậm trong 30 ngày gần nhất
                    </h2>
                    <div class="space-y-3">
                        <div v-for="item in props.slowMovingProducts" :key="item.id"
                            class="flex items-center justify-between p-2 rounded hover:bg-gray-50">
                            <span class="text-sm">{{ item.name }}</span>
                            <span class="font-medium text-blue-600 text-sm">{{ item.sold }} sp</span>
                        </div>
                        <div v-if="!props.slowMovingProducts.length" class="text-gray-400 text-center py-4">
                            Không có dữ liệu
                        </div>
                    </div>
                </div>

                <!-- Thay thế 2 box cũ bằng 1 box mới -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <AlertTriangle class="w-5 h-5 text-yellow-500" />
                        Cảnh báo hàng hóa
                    </h2>

                    <!-- Tab buttons -->
                    <div class="flex gap-4 mb-4 border-b">
                        <button @click="activeTab = 'expiring'" :class="[
                            'pb-2 px-1 text-sm font-medium',
                            activeTab === 'expiring'
                                ? 'text-blue-600 border-b-2 border-blue-600'
                                : 'text-gray-500 hover:text-gray-700'
                        ]">
                            Sắp hết hạn ({{ props.expiringBatchItems.length }})
                        </button>
                        <button @click="activeTab = 'low_stock'" :class="[
                            'pb-2 px-1 text-sm font-medium',
                            activeTab === 'low_stock'
                                ? 'text-blue-600 border-b-2 border-blue-600'
                                : 'text-gray-500 hover:text-gray-700'
                        ]">
                            Sản phẩm tồn kho thấp ({{ props.lowStockProducts.length }})
                        </button>
                        <button @click="activeTab = 'out_of_stock'" :class="[
                            'pb-2 px-1 text-sm font-medium',
                            activeTab === 'out_of_stock'
                                ? 'text-blue-600 border-b-2 border-blue-600'
                                : 'text-gray-500 hover:text-gray-700'
                        ]">
                            Sản phẩm hết hàng ({{ props.outOfStockProducts.length }})
                        </button>
                        <button @click="activeTab = 'returns'" :class="[
                            'pb-2 px-1 text-sm font-medium',
                            activeTab === 'returns'
                                ? 'text-blue-600 border-b-2 border-blue-600'
                                : 'text-gray-500 hover:text-gray-700'
                        ]">
                            Trả hàng
                        </button>

                    </div>

                    <!-- Tab content -->
                    <div v-if="activeTab === 'expiring'" class="space-y-3">
                        <div v-for="item in props.expiringBatchItems" :key="item.id" class="p-2 rounded" :class="{
                            'bg-red-50': item.days_until_expiry <= 7,
                            'bg-yellow-50': item.days_until_expiry > 7 && item.days_until_expiry <= 15,
                            'bg-gray-50': item.days_until_expiry > 15
                        }">
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium">{{ item.product_name }}</span>
                                <span class="text-sm">SL: {{ item.current_quantity }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span :class="{
                                    'text-red-600': item.days_until_expiry <= 7,
                                    'text-yellow-600': item.days_until_expiry > 7 && item.days_until_expiry <= 15,
                                    'text-gray-600': item.days_until_expiry > 15
                                }">
                                    Còn {{ item.days_until_expiry }} ngày
                                </span>
                                <span class="text-black-600">Lô: {{ item.batch_number }}</span>
                            </div>
                        </div>
                        <div v-if="!props.expiringBatchItems?.length" class="text-gray-400 text-center py-4">
                            Không có sản phẩm sắp hết hạn
                        </div>
                    </div>

                    <div v-if="activeTab === 'low_stock'" class="space-y-3">
                        <div v-for="item in props.lowStockProducts" :key="item.id" class="p-2 rounded bg-orange-50">
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium">{{ item.name }}</span>
                                <span class="text-sm">SL: {{ item.current_stock }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-500">SKU: {{ item.sku }}</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-orange-500"
                                            :style="{ width: item.remaining_percent + '%' }"></div>
                                    </div>
                                    <span class="text-orange-600">
                                        Tối thiểu: {{ item.min_stock }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-if="!props.lowStockProducts.length" class="text-gray-400 text-center py-4">
                            Không có sản phẩm nào sắp hết hàng
                        </div>
                    </div>

                    <div v-if="activeTab === 'out_of_stock'" class="space-y-3">
                        <div v-for="item in props.outOfStockProducts" :key="item.id" class="p-2 rounded bg-red-50">
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium">{{ item.name }}</span>
                                <span class="text-sm text-red-600">Hết hàng</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">SKU: {{ item.sku }}</span>
                                <span class="text-gray-600">
                                    Tối thiểu: {{ item.min_stock }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="activeTab === 'returns'" class="space-y-3">
                        <div class="p-2 rounded bg-gray-50">
                            <div class="text-sm text-gray-600">Tổng số phiếu trả</div>
                            <div class="text-lg font-semibold">{{ props.returnStats.totalReturns }}</div>
                        </div>
                        <div class="p-2 rounded bg-gray-50">
                            <div class="text-sm text-gray-600">Tổng giá trị trả</div>
                            <div class="text-lg font-semibold text-red-600">
                                {{ formatCurrency(props.returnStats.totalReturnValue) }}
                            </div>
                        </div>
                        <div class="p-2 rounded bg-gray-50">
                            <div class="text-sm text-gray-600">Sản phẩm trả nhiều nhất</div>
                            <div class="text-lg font-semibold">
                                {{ props.returnStats.mostReturnedProduct || 'Không có' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
