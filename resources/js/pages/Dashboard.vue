<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import StatCard from '@/components/dashboard/StatCard.vue'
import RevenueChart from '@/components/dashboard/RevenueChart.vue'
import AverageBillValueChart from '@/components/dashboard/AverageBillValueChart.vue'
import TopSellingProducts from '@/components/dashboard/TopSellingProducts.vue'
import BillCountChart from '@/components/dashboard/BillCountChart.vue'
import CustomerSpendingTimeChart from '@/components/dashboard/CustomerSpendingTimeChart.vue'
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
} from 'chart.js'
import { Line } from 'vue-chartjs'
ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale)


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
]

// Định nghĩa kiểu dữ liệu 
interface DashboardSummary {
    totalRevenue: number
    totalBills: number
    labels: string[]
    revenueData: number[]
    avgLabels: string[]
    avgData: number[]
    topProducts: {
    id: number
    name: string
    sku: string
    total_revenue: number
    total_sold: number
  }[]
    billLabels: string[]
    billData: number[]
    customerSpending: {           
        labels: string[]
        multiData: number[]
        oneData: number[]
    }
}

// Định nghĩa props 
interface PageProps {
    dashboards: DashboardSummary
    [key: string]: any
}

// Lấy dữ liệu và gán kiểu
const page = usePage<PageProps>()
const dashboards = page.props.dashboards

</script>

<template>

    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">

            <!-- Thống kê  -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <StatCard title="Doanh thu" :value="dashboards.totalRevenue" unit="đ" />
                <StatCard title="Đơn hàng" :value="dashboards.totalBills" />
            </div>

            <!-- Biểu đồ doanh thu và doanh thu trung bình -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <RevenueChart :labels="dashboards.labels" :data="dashboards.revenueData" class="w-full" />
                <AverageBillValueChart :labels="dashboards.avgLabels" :data="dashboards.avgData" class="w-full" />
            </div>

            <!-- Biểu đồ top sản phẩm và số lượng đơn hàng -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <TopSellingProducts :products="dashboards.topProducts" />
                <BillCountChart :labels="dashboards.billLabels" :data="dashboards.billData" class="w-full" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <CustomerSpendingTimeChart :labels="dashboards.customerSpending.labels"
                    :multiData="dashboards.customerSpending.multiData" :oneData="dashboards.customerSpending.oneData" class="w-full"/>
            </div>
            
        </div>
    </AppLayout>
</template>
