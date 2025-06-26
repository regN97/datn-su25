<template>
<header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 py-2 px-4 flex items-center justify-between shadow-sm">
    <!-- Bên trái -->
    <div class="flex items-center space-x-4">
      <div class="text-xl font-semibold tracking-wide text-blue-800">
        <AppLogoIcon imageUrl="/storage/piclumen-1747750187180.png" className="w-12 h-12" />
      </div>
      <div class="flex items-center space-x-2 text-xs">
        <div class="bg-gray-100 px-2 py-1 rounded" title="Tên thu ngân">👤 {{ cashierName }}</div>
        <div class="bg-gray-100 px-2 py-1 rounded" title="Ca làm">🕒 Ca: {{ shiftName }}</div>
        <div class="bg-gray-100 px-2 py-1 rounded" title="Mã máy POS">🖥 Máy: POS01</div>
        <div
          :class="['px-2 py-1 rounded font-semibold', isOnline ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']"
          title="Trạng thái kết nối">
          {{ isOnline ? '🟢 Online' : '🔴 Offline' }}
        </div>
      </div>
    </div>

    <!-- Bên phải -->
    <div class="flex items-center space-x-4">
      <span class="px-3 py-1 bg-gray-200 rounded font-mono tracking-wider">{{ currentTime }}</span>

      <button @click="refreshOrder" class="text-blue-600 hover:underline text-sm flex items-center gap-1" title="Làm mới đơn hàng">
        <span v-if="isRefreshing" class="animate-spin">🔄</span>
        <span v-else>🔄 Làm mới</span>
      </button>

      <button @click="lockScreen" class="text-red-600 hover:underline text-sm" title="Khóa màn hình">🔒 Khóa</button>
 <!-- Toggle Light/Dark mode -->
  <button @click="toggleDarkMode" class="text-yellow-500 hover:text-yellow-600 text-xl" title="Đổi chế độ sáng/tối">
    <span v-if="isDark">🌙</span>
    <span v-else>☀️</span>
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
          <button @click="handleLogout" class="w-full text-left px-4 py-2 hover:bg-gray-100 text-gray-700">
            🚪 Đăng xuất
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- Lớp phủ khóa màn hình -->
  <div v-if="isLocked" @click="unlockScreen"
    class="fixed inset-0 z-50 bg-black bg-opacity-70 flex items-center justify-center text-white text-xl font-semibold cursor-pointer select-none">
    🔒 Đã khóa màn hình — Bấm để mở khóa
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router as inertiaRouter } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

// State
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

// Cập nhật giờ
const updateTime = () => {
  const now = new Date();
  const h = now.getHours().toString().padStart(2, '0');
  const m = now.getMinutes().toString().padStart(2, '0');
  const s = now.getSeconds().toString().padStart(2, '0');
  const day = now.toLocaleDateString('vi-VN', { weekday: 'short' });
  const date = now.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
  currentTime.value = `${h}:${m}:${s} | ${day}, ${date}`;
};

// Làm mới
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

// Khóa & mở màn hình
const lockScreen = () => isLocked.value = true;
const unlockScreen = () => isLocked.value = false;

// Đăng xuất
const handleLogout = () => {
  isDropdownOpen.value = false;
  inertiaRouter.post('/cashier/logout', {}, {
    onFinish: () => {
      localStorage.removeItem('userToken'); // hoặc các key cụ thể
    }
  });
};

// Toggle dropdown
const toggleDropdown = () => isDropdownOpen.value = !isDropdownOpen.value;

// Click ngoài dropdown để đóng
const handleClickOutside = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isDropdownOpen.value = false;
  }
};

// Phím Escape để đóng dropdown
const handleGlobalKeydown = (e) => {
  if (e.key === 'Escape' && isDropdownOpen.value) {
    toggleDropdown();
  }
};

// Tự động khóa màn hình sau 5 phút không hoạt động
const resetIdleTimer = () => {
  clearTimeout(idleTimeout);
  idleTimeout = setTimeout(() => {
    isLocked.value = true;
  }, 5 * 60 * 1000);
};

// Sự kiện mạng
window.addEventListener('online', () => isOnline.value = true);
window.addEventListener('offline', () => isOnline.value = false);

// Lifecycle
onMounted(() => {
  updateTime();
  intervalId = setInterval(updateTime, 1000);

  window.addEventListener('click', handleClickOutside);
  window.addEventListener('keydown', handleGlobalKeydown);

  ['mousemove', 'keydown', 'mousedown', 'touchstart'].forEach(event => {
    window.addEventListener(event, resetIdleTimer);
  });
  resetIdleTimer();
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

  applyTheme(); // ← Gọi khi component mount
});

</script>
