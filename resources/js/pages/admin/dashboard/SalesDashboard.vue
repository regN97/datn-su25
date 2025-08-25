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
        title: 'Sales Dashboard',
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

// Thêm ref để lưu lỗi validation
const dateErrors = ref({
    start: '',
    end: ''
});

// Hàm validate dates
function validateDates(): boolean {
    dateErrors.value = { start: '', end: '' };

    // Kiểm tra ngày bắt đầu được chọn
    if (!startDate.value) {
        dateErrors.value.start = 'Vui lòng chọn ngày bắt đầu';
        return false;
    }

    // Kiểm tra ngày kết thúc được chọn
    if (!endDate.value) {
        dateErrors.value.end = 'Vui lòng chọn ngày kết thúc';
        return false;
    }

    const start = new Date(startDate.value);
    const end = new Date(endDate.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);


    // Kiểm tra ngày bắt đầu không được lớn hơn ngày kết thúc
    if (start > end) {
        dateErrors.value.start = 'Ngày bắt đầu không được lớn hơn ngày kết thúc';
        return false;
    }

    // Kiểm tra khoảng thời gian không được quá 12 tháng
    const monthDiff = (end.getFullYear() - start.getFullYear()) * 12 +
        (end.getMonth() - start.getMonth());
    if (monthDiff > 12) {
        dateErrors.value.end = 'Khoảng thời gian không được quá 12 tháng';
        return false;
    }

    return true;
}

// Sửa lại hàm applyFilter
function applyFilter() {
    if (!validateDates()) {
        return;
    }

    router.get(route('admin.dashboard'), {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: false,
        preserveScroll: true,
    });
}
</script>

<template>

    <Head title="Sales Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Bộ lọc thời gian -->
            <div class="flex flex-wrap gap-4 items-start mb-6">
                <div class="flex flex-col">
                    <label for="start-date" class="block text-lg font-medium text-gray-700">Từ ngày</label>
                    <input id="start-date" type="date" v-model="startDate" :max="new Date().toISOString().split('T')[0]"
                        class="w-100 border-gray-300 rounded-md shadow-sm focus:ring focus:ring-indigo-200 p-2"
                        :class="{ 'border-red-500': dateErrors.start }" />
                    <!-- Thêm div cố định có chiều cao min -->
                    <div class="h-6 mt-1">
                        <p v-if="dateErrors.start" class="text-sm text-red-600">{{ dateErrors.start }}</p>
                    </div>
                </div>

                <div class="flex flex-col">
                    <label for="end-date" class="block text-lg font-medium text-gray-700">Đến ngày</label>
                    <input id="end-date" type="date" v-model="endDate" :max="new Date().toISOString().split('T')[0]"
                        class="w-100 border-gray-300 rounded-md shadow-sm focus:ring focus:ring-indigo-200 p-2"
                        :class="{ 'border-red-500': dateErrors.end }" />
                    <!-- Thêm div cố định có chiều cao min -->
                    <div class="h-6 mt-1">
                        <p v-if="dateErrors.end" class="text-sm text-red-600">{{ dateErrors.end }}</p>
                    </div>
                </div>

                <div class="self-start mt-6">
                    <button @click="applyFilter"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition">
                        Lọc
                    </button>
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
