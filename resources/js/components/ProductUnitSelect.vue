<script setup lang="ts">
import axios from 'axios';
import { ChevronDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
    error: undefined,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: number | null): void;
    (e: 'update:units', units: ProductUnit[]): void;
}>();

const searchInput = ref('');
const showDropdown = ref(false);
const isLoading = ref(false);
const errorMsg = ref<string | null>(null);

const filteredUnits = computed(() =>
    !searchInput.value ? props.units : props.units.filter((unit) => unit.name.toLowerCase().includes(searchInput.value.toLowerCase())),
);

function selectUnit(unit: ProductUnit) {
    emit('update:modelValue', unit.id);
    searchInput.value = unit.name;
    showDropdown.value = false;
    errorMsg.value = null;
}

async function addNewUnit() {
    const name = searchInput.value.trim();

    // Client-side validation
    if (!name) {
        errorMsg.value = 'Tên đơn vị không được để trống.';
        return;
    }
    if (name.length > 255) {
        errorMsg.value = 'Tên đơn vị không được vượt quá 255 ký tự.';
        return;
    }
    const existing = props.units.find((unit) => unit.name.toLowerCase() === name.toLowerCase());
    if (existing) {
        errorMsg.value = 'Tên đơn vị đã tồn tại.';
        selectUnit(existing);
        return;
    }

    isLoading.value = true;
    errorMsg.value = null;

    try {
        const response = await axios.post('/admin/units', { name });
        const newUnit = response.data.unit;
        if (newUnit) {
            emit('update:units', [...props.units, newUnit]);
            emit('update:modelValue', newUnit.id);
            searchInput.value = newUnit.name;
            showDropdown.value = false;
        }
    } catch (error: any) {
        if (error.response && error.response.data && error.response.data.errors) {
            errorMsg.value = error.response.data.errors.name?.[0] || 'Có lỗi xảy ra.';
        } else {
            errorMsg.value = 'Có lỗi xảy ra.';
        }
    } finally {
        isLoading.value = false;
    }
}

function handleFocus() {
    showDropdown.value = true;
}

function handleBlur(event: FocusEvent) {
    setTimeout(() => {
        if (!event.relatedTarget || !(event.relatedTarget as HTMLElement).closest('.dropdown-content')) {
            showDropdown.value = false;
        }
    }, 100);
}
</script>

<template>
    <div class="relative font-sans">
        <label class="mb-2 block text-sm font-semibold tracking-wide text-gray-800"> Đơn vị </label>
        <div class="group relative">
            <input
                type="text"
                v-model="searchInput"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pr-12 pl-4 text-sm placeholder-gray-400 shadow-sm transition-all duration-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                :class="{ 'border-red-400 focus:ring-red-400': props.error || errorMsg }"
                placeholder="Tìm hoặc thêm đơn vị mới..."
                @focus="handleFocus"
                @blur="handleBlur"
            />
            <ChevronDown
                class="absolute top-1/2 right-3 h-5 w-5 -translate-y-1/2 text-gray-500 transition-transform duration-300 group-hover:text-indigo-600"
                :class="{ 'rotate-180': showDropdown }"
            />
            <div
                v-if="showDropdown"
                class="dropdown-content absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white shadow-2xl ring-1 ring-gray-200/50"
            >
                <ul class="py-1.5">
                    <li
                        v-for="unit in filteredUnits"
                        :key="unit.id"
                        @click="selectUnit(unit)"
                        class="flex cursor-pointer items-center gap-2 px-4 py-2.5 text-sm text-gray-800 transition-all duration-200 hover:bg-indigo-50 hover:text-indigo-900"
                    >
                        <span class="font-medium">{{ unit.name }}</span>
                    </li>
                    <li
                        v-if="searchInput.trim() && filteredUnits.length === 0"
                        @click="addNewUnit"
                        class="flex cursor-pointer items-center gap-2 px-4 py-2.5 text-sm text-indigo-600 transition-all duration-200 hover:bg-indigo-50"
                    >
                        <span v-if="isLoading" class="h-4 w-4 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent"></span>
                        <span class="font-medium">Thêm "{{ searchInput }}"</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
