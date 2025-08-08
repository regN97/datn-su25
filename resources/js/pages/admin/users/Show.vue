<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { CheckCircle2, Search, XCircle, ChevronLeft, ChevronRight } from 'lucide-vue-next';

// Định nghĩa giao diện cho dữ liệu người dùng
interface User {
    id: number;
    name: string;
    email: string | null;
    phone_number: string | null;
    address: string | null;
    role?: {
        id: number;
        name: string;
        code: string;
    };
}

// Định nghĩa giao diện cho dữ liệu ca làm việc của người dùng
interface UserShift {
    id: number;
    user_id: number;
    shift_id: number;
    date: string; // Cột 'date'
    status: string;
    check_in: string | null;
    check_out: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

// Định nghĩa giao diện cho dữ liệu ca làm việc chính (WorkShift)
interface WorkShift {
    id: number;
    name: string;
}

// Định nghĩa các props được truyền vào từ controller, bao gồm cả workShifts
const props = defineProps<{
    user: User;
    userShifts: UserShift[];
    workShifts: WorkShift[];
}>();

// --- Trạng thái mới cho việc lọc dữ liệu và phân trang ---
const selectedFilter = ref('all');
const filterDate = ref(new Date().toISOString().substr(0, 10));

const currentPage = ref(1);
const itemsPerPage = ref(10); // Hiển thị 10 mục trên mỗi trang

// Hàm để định dạng lại ngày và giờ (chỉ lấy giờ)
const formatTime = (dateTimeString: string | null): string => {
    if (!dateTimeString) {
        return '-';
    }
    const date = new Date(dateTimeString);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    return `${hours}:${minutes}:${seconds}`;
};

// Hàm để định dạng lại ngày (chỉ lấy ngày)
const formatDate = (dateString: string | null): string => {
    if (!dateString) {
        return '-';
    }
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${day}/${month}/${year}`;
};

// Hàm để tính toán thời gian làm việc
const calculateWorkDuration = (checkIn: string | null, checkOut: string | null): string => {
    if (!checkIn || !checkOut) {
        return '-';
    }
    const start = new Date(checkIn);
    const end = new Date(checkOut);
    const durationInMs = end.getTime() - start.getTime();
    if (durationInMs < 0) {
        return 'Lỗi thời gian';
    }

    const hours = Math.floor(durationInMs / (1000 * 60 * 60));
    const minutes = Math.floor((durationInMs % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((durationInMs % (1000 * 60)) / 1000);

    return `${hours}h ${minutes}m ${seconds}s`;
};

// Hàm để ánh xạ shift_id sang tên ca làm việc bằng cách tìm trong props.workShifts
const getShiftName = (shiftId: number): string => {
    const shift = props.workShifts.find(ws => ws.id === shiftId);
    return shift ? shift.name : 'Khác';
};

// --- Logic lọc dữ liệu ---
const filteredShifts = computed(() => {
    const shiftsToFilter = props.userShifts ?? [];

    if (selectedFilter.value === 'all') {
        return shiftsToFilter;
    }

    const filterDateObj = new Date(filterDate.value);
    let startDate: Date;
    let endDate: Date;

    if (selectedFilter.value === 'day') {
        startDate = new Date(filterDate.value);
        endDate = new Date(filterDate.value);
    } else if (selectedFilter.value === 'week') {
        const dayOfWeek = filterDateObj.getDay();
        const diff = filterDateObj.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
        startDate = new Date(filterDateObj.setDate(diff));
        endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 6);
    } else if (selectedFilter.value === 'month') {
        startDate = new Date(filterDateObj.getFullYear(), filterDateObj.getMonth(), 1);
        endDate = new Date(filterDateObj.getFullYear(), filterDateObj.getMonth() + 1, 0);
    } else {
        return shiftsToFilter;
    }

    // Đặt lại trang về 1 khi bộ lọc thay đổi
    currentPage.value = 1;

    return shiftsToFilter.filter(shift => {
        if (!shift.date) return false;
        const shiftDate = new Date(shift.date);
        shiftDate.setHours(0, 0, 0, 0);

        const filterStartDate = new Date(startDate);
        filterStartDate.setHours(0, 0, 0, 0);

        const filterEndDate = new Date(endDate);
        filterEndDate.setHours(23, 59, 59, 999);

        // Lọc dựa trên trường `date`
        return shiftDate >= filterStartDate && shiftDate <= filterEndDate;
    });
});

// --- Logic phân trang ---
const totalShiftsCount = computed(() => {
    return filteredShifts.value.length;
});

const totalPages = computed(() => {
    return Math.ceil(totalShiftsCount.value / itemsPerPage.value);
});

const paginatedShifts = computed(() => {
    const startIndex = (currentPage.value - 1) * itemsPerPage.value;
    const endIndex = startIndex + itemsPerPage.value;
    return filteredShifts.value.slice(startIndex, endIndex);
});

// Chuyển trang
const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// --- Tính toán tổng hợp dữ liệu sau khi lọc (không thay đổi) ---
const totalWorkDuration = computed(() => {
    let totalDurationInMs = 0;
    filteredShifts.value.forEach(shift => {
        if (shift.check_in && shift.check_out) {
            const start = new Date(shift.check_in);
            const end = new Date(shift.check_out);
            totalDurationInMs += end.getTime() - start.getTime();
        }
    });

    if (totalDurationInMs < 0) {
        return 'Lỗi thời gian';
    }

    const hours = Math.floor(totalDurationInMs / (1000 * 60 * 60));
    const minutes = Math.floor((totalDurationInMs % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((totalDurationInMs % (1000 * 60)) / 1000);

    return `${hours}h ${minutes}m ${seconds}s`;
});

// Sử dụng computed để xử lý dữ liệu ca làm việc sau khi đã được phân trang
const formattedUserShifts = computed(() => {
    return paginatedShifts.value.map(shift => ({
        ...shift,
        shift_name: getShiftName(shift.shift_id),
        check_in_formatted: formatTime(shift.check_in),
        check_out_formatted: formatTime(shift.check_out),
        work_duration: calculateWorkDuration(shift.check_in, shift.check_out),
        shift_date_formatted: formatDate(shift.date),
    }));
});
</script>

<template>
    <Head :title="`Chi tiết người dùng: ${user.name}`" />
    <AppLayout :breadcrumbs="[{ title: 'Quản lý người dùng', href: '/admin/users' }]">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <h1 class="text-2xl font-bold text-gray-800 mb-6">Thông tin chi tiết người dùng</h1>

                    <!-- Thông tin cá nhân -->
                    <div class="rounded-xl bg-white p-6 shadow mb-6">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4">Thông tin cá nhân</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Tên người dùng</p>
                                <p class="mt-1 text-lg font-medium text-gray-900">{{ user.name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Email</p>
                                <p class="mt-1 text-lg font-medium text-gray-900">{{ user.email }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Số điện thoại</p>
                                <p class="mt-1 text-lg font-medium text-gray-900">{{ user.phone_number }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Vai trò</p>
                                <p class="mt-1 text-lg font-medium text-gray-900">{{ user.role?.name ?? 'Không có' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Địa chỉ</p>
                                <p class="mt-1 text-lg font-medium text-gray-900">{{ user.address ?? 'Không có' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Bảng ca làm việc -->
                    <div class="rounded-xl bg-white p-6 shadow">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4">Danh sách ca đã làm</h2>

                        <!-- Bộ lọc thời gian -->
                        <div class="mb-4 flex flex-wrap items-center gap-4">
                            <label class="text-gray-700">Lọc theo:</label>
                            <button
                                @click="selectedFilter = 'all'"
                                :class="{'bg-blue-600 text-white': selectedFilter === 'all', 'bg-gray-200 text-gray-800': selectedFilter !== 'all'}"
                                class="rounded-lg px-4 py-2 text-sm transition-colors"
                            >
                                Tất cả
                            </button>
                            <button
                                @click="selectedFilter = 'week'"
                                :class="{'bg-blue-600 text-white': selectedFilter === 'week', 'bg-gray-200 text-gray-800': selectedFilter !== 'week'}"
                                class="rounded-lg px-4 py-2 text-sm transition-colors"
                            >
                                Tuần này
                            </button>
                            <button
                                @click="selectedFilter = 'month'"
                                :class="{'bg-blue-600 text-white': selectedFilter === 'month', 'bg-gray-200 text-gray-800': selectedFilter !== 'month'}"
                                class="rounded-lg px-4 py-2 text-sm transition-colors"
                            >
                                Tháng này
                            </button>
                            <div class="relative flex items-center">
                                <input
                                    type="date"
                                    v-model="filterDate"
                                    class="rounded-lg border border-gray-300 p-2 text-sm pr-10"
                                />
                                <button
                                    @click="selectedFilter = 'day'"
                                    :class="{'bg-blue-600 text-white': selectedFilter === 'day', 'text-gray-600': selectedFilter !== 'day'}"
                                    class="absolute right-0 top-0 bottom-0 flex items-center justify-center p-2 rounded-r-lg transition-colors"
                                >
                                    <Search class="h-5 w-5" />
                                </button>
                            </div>
                        </div>

                        <!-- Tóm tắt dữ liệu đã lọc -->
                        <div v-if="filteredShifts.length > 0 && selectedFilter !== 'all'" class="mb-4 rounded-xl bg-gray-50 p-4 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Tóm tắt</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-3 bg-white rounded-lg border border-gray-200">
                                    <p class="text-sm font-medium text-gray-500">Tổng số ca làm việc</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ totalShiftsCount }}</p>
                                </div>
                                <div class="p-3 bg-white rounded-lg border border-gray-200">
                                    <p class="text-sm font-medium text-gray-500">Tổng giờ làm việc</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ totalWorkDuration }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="formattedUserShifts.length > 0" class="overflow-x-auto rounded-lg">
                            <table class="min-w-full table-auto border-separate border-spacing-y-3">
                                <thead class="bg-gray-200 text-sm font-semibold text-gray-700 uppercase">
                                    <tr>
                                        <th class="px-6 py-4 text-left">STT</th>
                                        <th class="px-6 py-4 text-left">Ca</th>
                                        <th class="px-6 py-4 text-left">Ngày</th>
                                        <th class="px-6 py-4 text-left">Trạng thái</th>
                                        <th class="px-6 py-4 text-left">Giờ vào</th>
                                        <th class="px-6 py-4 text-left">Giờ ra</th>
                                        <th class="px-6 py-4 text-left">Giờ làm việc</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(shift, index) in formattedUserShifts"
                                        :key="shift.id"
                                        class="rounded-lg border border-gray-200 bg-white shadow-sm transition hover:bg-gray-100"
                                    >
                                        <td class="rounded-l-lg px-6 py-4">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</td>
                                        <td class="px-6 py-4">{{ shift.shift_name }}</td>
                                        <td class="px-6 py-4">{{ shift.shift_date_formatted }}</td>
                                        <td class="px-6 py-4">
                                            <!-- Hiển thị trạng thái dựa trên dữ liệu gốc -->
                                            <span v-if="shift.status === 'COMPLETED'" class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">
                                                <CheckCircle2 class="h-3 w-3" /> Hoàn thành
                                            </span>
                                            <span v-else class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">
                                                <XCircle class="h-3 w-3" /> Đang trong ca làm việc
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">{{ shift.check_in_formatted }}</td>
                                        <td class="px-6 py-4">{{ shift.check_out_formatted }}</td>
                                        <td class="px-6 py-4">{{ shift.work_duration }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <!-- Thanh phân trang -->
                            <div class="mt-4 flex items-center justify-between">
                                <div class="text-sm text-gray-600">
                                    Hiển thị {{ (currentPage - 1) * itemsPerPage + 1 }} đến {{ Math.min(currentPage * itemsPerPage, totalShiftsCount) }} trong số {{ totalShiftsCount }} mục
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="goToPage(currentPage - 1)"
                                        :disabled="currentPage <= 1"
                                        :class="{'bg-gray-200': currentPage <= 1, 'hover:bg-blue-600 hover:text-white bg-blue-500 text-white': currentPage > 1}"
                                        class="rounded-lg p-2 text-sm transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <ChevronLeft class="h-4 w-4" />
                                    </button>
                                    <span class="text-sm font-semibold text-gray-700">
                                        Trang {{ currentPage }} / {{ totalPages }}
                                    </span>
                                    <button
                                        @click="goToPage(currentPage + 1)"
                                        :disabled="currentPage >= totalPages"
                                        :class="{'bg-gray-200': currentPage >= totalPages, 'hover:bg-blue-600 hover:text-white bg-blue-500 text-white': currentPage < totalPages}"
                                        class="rounded-lg p-2 text-sm transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <ChevronRight class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center text-sm text-gray-500">
                            Không có dữ liệu ca làm việc nào.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
