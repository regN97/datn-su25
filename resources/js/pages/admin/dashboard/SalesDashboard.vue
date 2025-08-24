<script setup lang="ts">
import AverageBillValueChart from '@/components/dashboard/AverageBillValueChart.vue';
import BillCountChart from '@/components/dashboard/BillCountChart.vue';
import CustomerSpendingTimeChart from '@/components/dashboard/CustomerSpendingTimeChart.vue';
import RevenueChart from '@/components/dashboard/RevenueChart.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import TopSellingProducts from '@/components/dashboard/TopSellingProducts.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CategoryScale, Chart as ChartJS, Legend, LinearScale, LineElement, PointElement, Title, Tooltip } from 'chart.js';
import { ref } from 'vue';

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: 'admin/dashboard/SalesDashboard',
    },
];

// Định nghĩa kiểu dữ liệu
interface DashboardSummary {
    totalRevenue: number;
    totalBills: number;
    labels: string[];
    revenueData: number[];
    avgLabels: string[];
    avgData: number[];
    topProducts: {
        id: number;
        name: string;
        sku: string;
        total_revenue: number;
        total_sold: number;
    }[];
    billLabels: string[];
    billData: number[];
    customerSpending: {
        labels: string[];
        multiData: number[];
        oneData: number[];
    };
}

// Định nghĩa props
interface PageProps {
    dashboards: DashboardSummary;
    [key: string]: any;
}

// Lấy dữ liệu và gán kiểu
const page = usePage<PageProps>();
const dashboards = page.props.dashboards;

const filters = page.props.filters ?? {
    start_date: '',
    end_date: '',
};

const startDate = ref(filters.start_date);
const endDate = ref(filters.end_date);

// 👇 Hàm gọi lại route khi nhấn nút "Lọc"
function applyFilter() {
    if (startDate.value && endDate.value) {
        router.get(
            route('admin.dashboard'),
            {
                start_date: startDate.value,
                end_date: endDate.value,
            },
            {
                preserveState: false,
                preserveScroll: true,
            },
        );
    }
}
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Bộ lọc thời gian -->
            <div class="mb-6 flex flex-wrap items-end gap-4">
                <div>
                    <label for="start-date" class="block text-sm font-medium text-gray-700">Từ ngày</label>
                    <input
                        id="start-date"
                        type="date"
                        v-model="startDate"
                        class="rounded-md border-gray-300 p-2 shadow-sm focus:ring focus:ring-indigo-200"
                    />
                </div>
                <div>
                    <label for="end-date" class="block text-sm font-medium text-gray-700">Đến ngày</label>
                    <input
                        id="end-date"
                        type="date"
                        v-model="endDate"
                        class="rounded-md border-gray-300 p-2 shadow-sm focus:ring focus:ring-indigo-200"
                    />
                </div>
                <div>
                    <button @click="applyFilter" class="rounded-md bg-indigo-600 px-4 py-2 text-white transition hover:bg-indigo-700">Lọc</button>
                </div>
            </div>

            <!-- Thống kê  -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <StatCard title="Doanh thu" :value="dashboards.totalRevenue" unit="đ" />
                <StatCard title="Đơn hàng" :value="dashboards.totalBills" />
            </div>

            <!-- Biểu đồ doanh thu và doanh thu trung bình -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <RevenueChart :labels="dashboards.labels" :data="dashboards.revenueData" class="w-full" />
                <AverageBillValueChart :labels="dashboards.avgLabels" :data="dashboards.avgData" class="w-full" />
            </div>

            <!-- Biểu đồ top sản phẩm và số lượng đơn hàng -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <TopSellingProducts :products="dashboards.topProducts" />
                <BillCountChart :labels="dashboards.billLabels" :data="dashboards.billData" class="w-full" />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <CustomerSpendingTimeChart
                    :labels="dashboards.customerSpending.labels"
                    :multiData="dashboards.customerSpending.multiData"
                    :oneData="dashboards.customerSpending.oneData"
                    class="w-full"
                />
            </div>
        </div>
    </AppLayout>
</template>
