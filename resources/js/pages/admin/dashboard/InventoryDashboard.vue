<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { BarElement, CategoryScale, Chart, Legend, LinearScale, Tooltip } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { AlertTriangle, Layers, Package, TrendingUp } from 'lucide-vue-next';
import { ref } from 'vue';
import { Bar } from 'vue-chartjs';
Chart.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend, ChartDataLabels);

const props = defineProps<{
    overviewStats: Array<{ label: string; value: number; icon: string; color: string; unit?: string }>;
    chartData: Array<{ month: string; value: number }>;
    products: Array<{ id: number; name: string; sku: string; selling_price: number; current_stock: number }>;
    topSellingProducts: Array<{ id: number; name: string; sold: number }>;
    returnStats: {
        totalReturns: number;
        totalReturnValue: number;
        mostReturnedProduct: string;
    };
}>();

const chartLabels = props.chartData.map((i) => i.month);
const chartValues = props.chartData.map((i) => i.value);
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
    TrendingUp,
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
</script>

<template>
    <Head title="Dashboard hiện đại" />
    <AppLayout>
        <div class="min-h-screen space-y-8 bg-gray-50 p-6">
            <!-- Thông tin tổng quan phụ -->
            <div class="mb-2 flex flex-wrap gap-4">
                <div class="flex items-center gap-2 rounded-lg bg-blue-100 px-4 py-2 text-blue-700">
                    <Package class="h-5 w-5" /> Tổng sản phẩm: <span class="font-bold">{{ totalProducts }}</span>
                </div>
                <div class="flex items-center gap-2 rounded-lg bg-green-100 px-4 py-2 text-green-700">
                    <TrendingUp class="h-5 w-5" /> Tổng giá trị tồn kho: <span class="font-bold">{{ totalStockValue.toLocaleString() }}</span> đ
                </div>
            </div>
            <!-- Số liệu tổng quan -->
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div
                    v-for="stat in props.overviewStats"
                    :key="stat.label"
                    class="flex items-center gap-4 rounded-xl border bg-white p-5 shadow transition hover:shadow-md"
                >
                    <div :class="`rounded-full p-3 text-xl ${stat.color} bg-opacity-10`">
                        <component :is="iconMap[stat.icon as keyof typeof iconMap]" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold">
                            {{ stat.value.toLocaleString() }} <span v-if="stat.unit" class="text-xs font-normal">{{ stat.unit }}</span>
                        </div>
                        <div class="text-xs text-gray-500">{{ stat.label }}</div>
                    </div>
                </div>
            </div>
            <!-- Biểu đồ giá trị tồn kho theo tháng -->
            <div class="rounded-xl bg-white p-6 shadow">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                    <TrendingUp class="h-5 w-5 text-blue-500" /> Biểu đồ giá trị tồn kho theo tháng
                </h2>
                <!-- @ts-ignore -->
                <!-- @ts-ignore -->
                <Bar
                    :data="{ labels: chartLabels, datasets: [{ label: 'Giá trị tồn kho', data: chartValues, backgroundColor: '#3b82f6' }] }"
                    :options="chartOptions as any"
                    :plugins="[ChartDataLabels as any]"
                    height="120"
                />
            </div>
            <!-- Box thống kê mới -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Top 5 sản phẩm bán chạy -->
                <div class="rounded-xl bg-white p-6 shadow">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                        <TrendingUp class="h-5 w-5 text-green-500" />Top 5 sản phẩm bán chạy
                    </h2>
                    <ul>
                        <li v-for="item in props.topSellingProducts" :key="item.id" class="flex justify-between border-b py-2 last:border-b-0">
                            <span>{{ item.name }}</span>
                            <span class="font-bold text-blue-600">{{ item.sold }} sp</span>
                        </li>
                    </ul>
                    <div v-if="!props.topSellingProducts.length" class="py-4 text-center text-gray-400">Không có dữ liệu</div>
                </div>
                <!-- Thống kê trả hàng -->
                <div class="rounded-xl bg-white p-6 shadow">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                        <AlertTriangle class="h-5 w-5 text-yellow-500" />Thống kê trả hàng
                    </h2>
                    <div class="mb-2">
                        Tổng số phiếu trả hàng: <span class="font-bold">{{ props.returnStats.totalReturns }}</span>
                    </div>
                    <div class="mb-2">
                        Tổng giá trị trả hàng: <span class="font-bold text-red-600">{{ props.returnStats.totalReturnValue.toLocaleString() }}</span> đ
                    </div>
                    <div>
                        Sản phẩm bị trả nhiều nhất: <span class="font-bold">{{ props.returnStats.mostReturnedProduct || 'Không có' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
