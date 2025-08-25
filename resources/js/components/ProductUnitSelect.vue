<script setup lang="ts">
import axios from 'axios';
import { ChevronDown } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ProductUnit {
    id: number;
    name: string;
}

interface Props {
    modelValue: number | null;
    units: ProductUnit[];
    error?: string;
}

const props = withDefaults(defineProps<Props>(), {
    units: () => [],
    modelValue: null,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: number | null): void;
    (e: 'update:units', units: ProductUnit[]): void;
}>();

const inputText = ref('');
const showDropdown = ref(false);
const isLoading = ref(false);
const errorMsg = ref<string | null>(null);

// Đồng bộ inputText nếu modelValue thay đổi (đã chọn từ bên ngoài)
watch(
    () => props.modelValue,
    (val) => {
        const selected = props.units.find((u) => u.id === val);
        if (selected) inputText.value = selected.name;
    },
    { immediate: true },
);

// Danh sách lọc đơn vị theo text
const filteredUnits = computed(() =>
    !inputText.value ? props.units : props.units.filter((unit) => unit.name.toLowerCase().includes(inputText.value.toLowerCase())),
);

// Xử lý chọn đơn vị
function selectUnit(unit: ProductUnit) {
    emit('update:modelValue', unit.id);
    inputText.value = unit.name;
    showDropdown.value = false;
    errorMsg.value = null;
}

// Thêm đơn vị mới
async function addNewUnit() {
    const name = inputText.value.trim();
    if (!name) {
        errorMsg.value = 'Tên đơn vị không được để trống.';
        return;
    }
    if (name.length > 255) {
        errorMsg.value = 'Tên đơn vị không được vượt quá 255 ký tự.';
        return;
    }

    const exists = props.units.find((u) => u.name.toLowerCase() === name.toLowerCase());
    if (exists) {
        selectUnit(exists);
        return;
    }

    isLoading.value = true;
    try {
        const res = await axios.post('/admin/units', { name });
        const newUnit: ProductUnit = res.data.unit;
        emit('update:units', [...props.units, newUnit]);
        selectUnit(newUnit);
    } catch (err: any) {
        const msg = err.response?.data?.errors?.name?.[0] || 'Có lỗi xảy ra.';
        errorMsg.value = msg;
    } finally {
        isLoading.value = false;
    }
}

// Ẩn dropdown khi click ngoài
function handleBlur(e: FocusEvent) {
    const relatedTarget = e.relatedTarget as HTMLElement | null;
    if (!relatedTarget || !relatedTarget.closest('.dropdown-content')) {
        showDropdown.value = false;
    }
}
</script>

<template>
    <div class="relative font-sans">
        <label class="mb-2 block text-sm font-semibold text-gray-800">Đơn vị</label>
        <div class="group relative">
            <input
                type="text"
                v-model="inputText"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pr-12 pl-4 text-sm placeholder-gray-400 shadow-sm transition-all duration-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                :class="{ 'border-red-400 focus:ring-red-400': props.error || errorMsg }"
                placeholder="Tìm hoặc thêm đơn vị mới..."
                @focus="showDropdown = true"
                @blur="handleBlur"
                autocomplete="off"
            />
            <ChevronDown class="absolute top-1/2 right-3 h-5 w-5 -translate-y-1/2 text-gray-500" :class="{ 'rotate-180': showDropdown }" />
            <div
                v-if="showDropdown"
                class="dropdown-content absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white shadow-2xl ring-1 ring-gray-200/50"
            >
                <ul class="py-1.5">
                    <li
                        v-for="unit in filteredUnits"
                        :key="unit.id"
                        @mousedown.prevent="selectUnit(unit)"
                        class="cursor-pointer px-4 py-2.5 text-sm text-gray-800 hover:bg-indigo-50 hover:text-indigo-900"
                    >
                        {{ unit.name }}
                    </li>
                    <li
                        v-if="inputText.trim() && filteredUnits.length === 0"
                        @mousedown.prevent="addNewUnit"
                        class="cursor-pointer px-4 py-2.5 text-sm text-indigo-600 hover:bg-indigo-50"
                    >
                        <span
                            v-if="isLoading"
                            class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent"
                        ></span>
                        Thêm "{{ inputText }}"
                    </li>
                </ul>
            </div>
        </div>
        <p v-if="props.error || errorMsg" class="mt-1 text-sm text-red-500">{{ props.error || errorMsg }}</p>
    </div>
</template>
