<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router as inertiaRouter, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

import {
  User,
  Clock,
  Monitor,
  Wifi,
  WifiOff,
  RefreshCw,
  Lock,
  Sun,
  Moon,
  LogOut
} from 'lucide-vue-next';

const isDropdownOpen = ref(false);
const isLocked = ref(false);
const isRefreshing = ref(false);
const currentTime = ref('');
const isOnline = ref(navigator.onLine);
const dropdownRef = ref(null);

let intervalId = null;
let idleTimeout = null;

const page = usePage();
const cashierName = computed(() => page.props.auth?.user?.name || 'Ẩn danh');

const getShiftNameByTime = () => {
  const hour = new Date().getHours();
  if (hour >= 6 && hour < 12) return 'Sáng';
  if (hour >= 12 && hour < 18) return 'Chiều';
  if (hour >= 18 && hour < 22) return 'Tối';
  return 'Đêm';
};
const shiftName = computed(() => getShiftNameByTime());

const cashierInitial = computed(() => {
  const name = cashierName.value || '';
  return name.trim().split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
});

const updateTime = () => {
  const now = new Date();
  const h = now.getHours().toString().padStart(2, '0');
  const m = now.getMinutes().toString().padStart(2, '0');
  const s = now.getSeconds().toString().padStart(2, '0');
  const day = now.toLocaleDateString('vi-VN', { weekday: 'short' });
  const date = now.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
  currentTime.value = `${h}:${m}:${s} | ${day}, ${date}`;
};

const refreshOrder = () => {
  isRefreshing.value = true;
  inertiaRouter.reload({
    preserveScroll: true,
    preserveState: true,
    onFinish: () => {
      isRefreshing.value = false;
    }
  });
};

const lockScreen = () => isLocked.value = true;
const unlockScreen = () => isLocked.value = false;

const handleLogout = () => {
  isDropdownOpen.value = false;
  inertiaRouter.post('/cashier/logout', {}, {
    onFinish: () => {
      localStorage.removeItem('userToken');
    }
  });
};

const toggleDropdown = () => isDropdownOpen.value = !isDropdownOpen.value;

const handleClickOutside = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isDropdownOpen.value = false;
  }
};

const handleGlobalKeydown = (e) => {
  if (e.key === 'Escape' && isDropdownOpen.value) {
    toggleDropdown();
  }
};

const resetIdleTimer = () => {
  clearTimeout(idleTimeout);
  idleTimeout = setTimeout(() => {
    isLocked.value = true;
  }, 5 * 60 * 1000);
};

window.addEventListener('online', () => isOnline.value = true);
window.addEventListener('offline', () => isOnline.value = false);

const isDark = ref(localStorage.getItem('theme') === 'dark');

const applyTheme = () => {
  if (isDark.value) {
    document.documentElement.classList.add('dark');
    localStorage.setItem('theme', 'dark');
  } else {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');
  }
};

const toggleDarkMode = () => {
  isDark.value = !isDark.value;
  applyTheme();
};

onMounted(() => {
  updateTime();
  intervalId = setInterval(updateTime, 1000);

  window.addEventListener('click', handleClickOutside);
  window.addEventListener('keydown', handleGlobalKeydown);

  ['mousemove', 'keydown', 'mousedown', 'touchstart'].forEach(event => {
    window.addEventListener(event, resetIdleTimer);
  });
  resetIdleTimer();
  applyTheme();
});

onUnmounted(() => {
  clearInterval(intervalId);
  window.removeEventListener('click', handleClickOutside);
  window.removeEventListener('keydown', handleGlobalKeydown);

  ['mousemove', 'keydown', 'mousedown', 'touchstart'].forEach(event => {
    window.removeEventListener(event, resetIdleTimer);
  });
  clearTimeout(idleTimeout);
});
// window.addEventListener('keydown', (e) => {
//   if (e.key === 'Insert') {
//     isOnline.value = !isOnline.value;
//     showOfflineMessage.value = !isOnline.value;
//   }
// });
</script>
<template>
  <header
    class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 py-2 px-4 flex items-center justify-between shadow-sm">

    <!-- Bên trái -->
    <div class="flex items-center space-x-4">
      <Link :href="route('cashier.dashboard')" title="Trang chính">
      <div class="text-xl font-semibold tracking-wide text-blue-800">
        <AppLogoIcon imageUrl="/storage/piclumen-1747750187180.png" className="w-12 h-12" />
      </div>
      </Link>

      <div class="flex items-center space-x-2 text-xs">
        <div class="bg-gray-100 px-2 py-1 rounded flex items-center gap-1" title="Tên thu ngân">
          <User class="w-4 h-4" /> {{ cashierName }}
        </div>
        <div class="bg-gray-100 px-2 py-1 rounded flex items-center gap-1" title="Ca làm">
          <Clock class="w-4 h-4" /> Ca: {{ shiftName }}
        </div>
        <div class="bg-gray-100 px-2 py-1 rounded flex items-center gap-1" title="Mã máy POS">
          <Monitor class="w-4 h-4" /> Máy: POS01
        </div>
        <div
          :class="['px-2 py-1 rounded font-semibold flex items-center gap-1', isOnline ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']"
          title="Trạng thái kết nối">
          <component :is="isOnline ? Wifi : WifiOff" class="w-4 h-4" />
          {{ isOnline ? 'Online' : 'Offline' }}
        </div>
      </div>
    </div>

    <!-- Bên phải -->
    <div class="flex items-center space-x-4">
      <span class="px-3 py-1 bg-gray-200 rounded font-mono tracking-wider">{{ currentTime }}</span>

      <button @click="refreshOrder" class="text-blue-600 hover:underline text-sm flex items-center gap-1"
        title="Làm mới đơn hàng">
        <RefreshCw v-if="isRefreshing" class="w-4 h-4 animate-spin" />
        <RefreshCw v-else class="w-4 h-4" />
        <span v-if="!isRefreshing">Làm mới</span>
      </button>

      <button @click="lockScreen" class="text-red-600 hover:underline text-sm flex items-center gap-1"
        title="Khóa màn hình">
        <Lock class="w-4 h-4" /> Khóa
      </button>

      <button @click="toggleDarkMode" class="text-yellow-500 hover:text-yellow-600 text-xl" title="Đổi chế độ sáng/tối">
        <Sun v-if="!isDark" class="w-5 h-5" />
        <Moon v-else class="w-5 h-5" />
      </button>

      <!-- Dropdown -->
      <div class="relative" ref="dropdownRef">
        <button @click="toggleDropdown"
          class="w-8 h-8 bg-blue-600 text-white font-bold uppercase text-sm rounded-full flex items-center justify-center"
          aria-label="Mở menu người dùng">
          {{ cashierInitial }}
        </button>
        <div v-if="isDropdownOpen" class="absolute right-0 mt-1 w-44 bg-white border rounded shadow z-50">
          <div class="px-4 py-2 border-b text-gray-600">{{ cashierName }}</div>
          <button @click="handleLogout"
            class="w-full text-left px-4 py-2 hover:bg-gray-100 text-gray-700 flex items-center gap-1">
            <LogOut class="w-4 h-4" /> Đăng xuất
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- Lớp phủ khóa màn hình -->
  <div v-if="isLocked" @click="unlockScreen"
    class="fixed inset-0 z-50 bg-black bg-opacity-70 flex items-center justify-center text-white text-xl font-semibold cursor-pointer select-none">
    <Lock class="w-6 h-6 mr-2" /> Đã khóa màn hình — Bấm để mở khóa
  </div>
</template>
