<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Package, 
    ShoppingCart, 
    Users, 
    UserCheck,
    TrendingUp,
    AlertTriangle,
    Plus,
    ArrowRight,
    Clock,
    Activity
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { Chart, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
Chart.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend, ChartDataLabels);

interface OverviewData {
    total_products: number;
    pending_orders: number;
    total_suppliers: number;
    total_users: number;
}

interface ChartData {
    month: string;
    value: number;
}

interface InventoryData {
    name: string;
    stock: number;
    category: string;
}

interface TopProduct {
    name: string;
    total_imported: number;
}

interface RecentActivity {
    type: 'import' | 'return';
    message: string;
    time: string;
    details: string;
    quantity: number;
}

interface Alert {
    id: number;
    name: string;
    current_stock: number;
    min_stock: number;
    category: string;
    urgency: 'high' | 'medium';
}

interface Props {
    overview: OverviewData;
    charts: {
        imports: ChartData[];
        returns: ChartData[];
        inventory: InventoryData[];
        topProducts: TopProduct[];
    };
    recentActivities: RecentActivity[];
    alerts: Alert[];
}

const props = withDefaults(defineProps<Props>(), {
  overview: () => ({
    total_products: 0,
    pending_orders: 0,
    total_suppliers: 0,
    total_users: 0,
  }),
  charts: () => ({
    imports: [],
    returns: [],
    inventory: [],
    topProducts: [],
  }),
  recentActivities: () => [],
  alerts: () => [],
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const overviewCards = [
    {
        title: 'Tổng sản phẩm',
        value: props.overview.total_products,
        icon: Package,
        color: 'bg-blue-500',
        href: '/admin/products'
    },
    {
        title: 'Đơn hàng chờ duyệt',
        value: props.overview.pending_orders,
        icon: ShoppingCart,
        color: 'bg-yellow-500',
        href: '/admin/purchase_orders'
    },
    {
        title: 'Tổng nhà cung cấp',
        value: props.overview.total_suppliers,
        icon: Users,
        color: 'bg-green-500',
        href: '/admin/suppliers'
    },
    {
        title: 'Tổng người dùng',
        value: props.overview.total_users,
        icon: UserCheck,
        color: 'bg-purple-500',
        href: '/admin/users'
    }
];

const quickActions = [
    {
        title: 'Tạo đơn hàng mới',
        description: 'Tạo đơn hàng mua từ nhà cung cấp',
        icon: Plus,
        href: '/admin/purchase-orders/create',
        color: 'bg-blue-500 hover:bg-blue-600'
    },
    {
        title: 'Nhập kho',
        description: 'Nhập hàng vào kho từ đơn hàng',
        icon: Package,
        href: '/admin/batches/create',
        color: 'bg-green-500 hover:bg-green-600'
    },
    {
        title: 'Thêm sản phẩm',
        description: 'Thêm sản phẩm mới vào hệ thống',
        icon: Plus,
        href: '/admin/products/create',
        color: 'bg-purple-500 hover:bg-purple-600'
    },
    {
        title: 'Thêm nhà cung cấp',
        description: 'Thêm nhà cung cấp mới',
        icon: Users,
        href: '/admin/suppliers/create',
        color: 'bg-orange-500 hover:bg-orange-600'
    }
];

const totalOrders = computed(() => props.charts.imports.filter(i => i.value > 0).length);
const avgPerMonth = computed(() => {
  if (!props.charts.imports.length) return 0;
  return Math.round(props.charts.imports.reduce((sum, i) => sum + i.value, 0) / props.charts.imports.length);
});
function goToInventoryDashboard() {
  router.visit('/admin/inventory/dashboard');
}

const importChartLabels = props.charts.imports.map(i => i.month);
const importChartValues = props.charts.imports.map(i => i.value);
const importChartOptions = {
  responsive: true,
  plugins: {
    legend: { display: false },
    tooltip: { enabled: true, backgroundColor: '#2563eb', titleColor: '#fff', bodyColor: '#fff' },
    datalabels: {
      anchor: 'center',
      align: 'center',
      color: '#111827',
      font: { weight: 'bold', size: 12 },
      formatter: function(value: number): string { return Number(value).toLocaleString(); },
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: { color: '#e5e7eb' },
      ticks: { color: '#6b7280', font: { size: 13 } }
    },
    x: {
      grid: { color: '#f3f4f6' },
      ticks: { color: '#6b7280', font: { size: 13 } }
    }
  },
  layout: { padding: { top: 20 } },
  elements: {
    bar: {
      borderRadius: 6,
      borderSkipped: false,
    }
  }
};
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Tổng quan -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Link 
                    v-for="card in overviewCards" 
                    :key="card.title"
                    :href="card.href"
                    class="group relative overflow-hidden rounded-xl border bg-card p-6 transition-all hover:shadow-lg"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">{{ card.title }}</p>
                            <p class="text-3xl font-bold">{{ (card.value ?? 0).toLocaleString() }}</p>
                        </div>
                        <div :class="`${card.color} rounded-lg p-3 text-white`">
                            <component :is="card.icon" class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent to-white/5 opacity-0 transition-opacity group-hover:opacity-100"></div>
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Biểu đồ và dữ liệu -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Biểu đồ nhập hàng -->
                    <div class="flex flex-row gap-0 items-stretch rounded-xl border bg-white p-0 shadow-none overflow-hidden">
                        <!-- Biểu đồ -->
                        <div class="flex-1 flex flex-col justify-end p-8 border-r border-blue-100 min-w-0">
                        <div class="flex items-center justify-between mb-4">
                                <h3 class="text-base font-semibold flex items-center gap-2">
                                    <TrendingUp class="h-5 w-5 text-blue-400" /> Số lượng nhập hàng theo tháng
                                </h3>
                            </div>
                            <div class="relative h-[340px] flex flex-col justify-end">
                                <Bar
                                    :data="{ labels: importChartLabels, datasets: [{ label: 'Giá trị nhập hàng', data: importChartValues, backgroundColor: '#3b82f6' }] }"
                                    :options="importChartOptions as any"
                                    :plugins="[ChartDataLabels]"
                                    height="120"
                                />
                                <div v-if="importChartValues.every(i => i === 0)" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 bg-white/80">
                                    <TrendingUp class="h-8 w-8 mb-2 text-blue-200" />
                                    <span>Không tìm thấy dữ liệu.</span>
                                </div>
                            </div>
                        </div>
                        <!-- Box tổng doanh thu -->
                        <div class="w-72 flex-shrink-0 flex flex-col justify-center items-center bg-white border-l border-blue-100 p-8">
                            <div class="flex items-center gap-2 mb-4">
                                <TrendingUp class="h-5 w-5 text-blue-400" />
                                <span class="text-base font-semibold text-gray-700">Tổng doanh thu</span>
                            </div>
                            <div class="text-2xl font-bold text-blue-700 mb-1">
                                {{ charts.imports.reduce((sum, item) => sum + item.value, 0).toLocaleString() }}
                            </div>
                            <div class="text-xs text-gray-400 mb-4">VND</div>
                            <div class="flex flex-col items-center w-full">
                                <div class="flex justify-between w-full text-xs text-gray-500 mb-1">
                                    <span>Tổng số đơn nhập</span>
                                    <span class="font-semibold text-gray-700">{{ totalOrders }}</span>
                                </div>
                                <div class="flex justify-between w-full text-xs text-gray-500">
                                    <span>Trung bình/tháng</span>
                                    <span class="font-semibold text-gray-700">{{ avgPerMonth.toLocaleString() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sản phẩm bán chạy -->
                    <div class="rounded-xl border bg-card p-6">
                        <h3 class="text-lg font-semibold mb-4">Sản phẩm bán chạy</h3>
                        <div class="space-y-3">
                            <div 
                                v-for="(product, index) in charts.topProducts" 
                                :key="index"
                                class="flex items-center justify-between p-3 rounded-lg bg-muted/50"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-sm font-semibold">
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ product.name }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            Đã nhập: {{ (product.total_imported ?? 0).toLocaleString() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thao tác nhanh và cảnh báo -->
                <div class="space-y-6">
                    <!-- Thao tác nhanh -->
                    <div class="rounded-xl border bg-card p-6">
                        <h3 class="text-lg font-semibold mb-4">Thao tác nhanh</h3>
                        <div class="space-y-3">
                            <Link 
                                v-for="action in quickActions" 
                                :key="action.title"
                                :href="action.href"
                                :class="`${action.color} flex items-center gap-3 p-3 rounded-lg text-white transition-colors`"
                            >
                                <component :is="action.icon" class="h-5 w-5" />
                                <div class="flex-1">
                                    <p class="font-medium">{{ action.title }}</p>
                                    <p class="text-sm opacity-90">{{ action.description }}</p>
                                </div>
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>

                    <!-- Cảnh báo -->
                    <div class="rounded-xl border bg-card p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <AlertTriangle class="h-5 w-5 text-red-500" />
                            <h3 class="text-lg font-semibold">Cảnh báo</h3>
                        </div>
                        <div class="space-y-3 max-h-[300px] overflow-y-auto">
                            <div 
                                v-for="alert in alerts" 
                                :key="alert.id"
                                class="p-3 rounded-lg border-l-4"
                                :class="alert.urgency === 'high' ? 'border-l-red-500 bg-red-50 dark:bg-red-950/20' : 'border-l-yellow-500 bg-yellow-50 dark:bg-yellow-950/20'"
                            >
                                <p class="font-medium text-sm">{{ alert.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    Tồn kho: {{ alert.current_stock }} / {{ alert.min_stock }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Danh mục: {{ alert.category }}
                                </p>
                            </div>
                            <div v-if="alerts.length === 0" class="text-center text-muted-foreground py-4">
                                Không có cảnh báo nào
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hoạt động gần đây -->
            <div class="rounded-xl border bg-card p-6">
                <div class="flex items-center gap-2 mb-4">
                    <Activity class="h-5 w-5 text-muted-foreground" />
                    <h3 class="text-lg font-semibold">Hoạt động gần đây</h3>
                </div>
                <div class="space-y-4">
                    <div 
                        v-for="activity in recentActivities" 
                        :key="`${activity.type}-${activity.time}`"
                        class="flex items-start gap-3 p-3 rounded-lg hover:bg-muted/50 transition-colors"
                    >
                        <div 
                            class="w-2 h-2 rounded-full mt-2"
                            :class="activity.type === 'import' ? 'bg-green-500' : 'bg-red-500'"
                        ></div>
                        <div class="flex-1">
                            <p class="font-medium">{{ activity.message }}</p>
                            <p class="text-sm text-muted-foreground">{{ activity.details }}</p>
                            <p class="text-xs text-muted-foreground">
                                Số lượng: {{ (activity.quantity ?? 0).toLocaleString() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-1 text-xs text-muted-foreground">
                            <Clock class="h-3 w-3" />
                            {{ activity.time }}
                        </div>
                    </div>
                    <div v-if="recentActivities.length === 0" class="text-center text-muted-foreground py-8">
                        Không có hoạt động nào gần đây
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
