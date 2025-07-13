<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

interface Option {
    label: string;
    value: number | string;
}

const props = defineProps<{
    modelValue: (number | string)[]; // V-model binding: array of selected values
    options: Option[]; // All available options
    placeholder?: string; // Placeholder for the search input
    noResultsText?: string; // Text to display when no search results
    noOptionsText?: string; // Text to display when no options are available
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: (number | string)[]): void;
}>();

const searchTerm = ref('');
const showDropdown = ref(false);
const inputRef = ref<HTMLInputElement | null>(null);
const dropdownRef = ref<HTMLDivElement | null>(null);
const activeOptionIndex = ref(-1); // For keyboard navigation

// Tạo một ID duy nhất để thay thế _uid
const uniqueId = `multi-select-${Math.random().toString(36).substring(2, 9)}`;

// Filtered options based on search term and not already selected
const filteredOptions = computed(() => {
    const lowerCaseSearchTerm = searchTerm.value.toLowerCase();
    const selectedValues = new Set(props.modelValue);

    return props.options.filter(
        (option) =>
            !selectedValues.has(option.value) && // Only show unselected options
            option.label.toLowerCase().includes(lowerCaseSearchTerm),
    );
});

// Options that are currently selected (for displaying tags)
const selectedOptions = computed(() => {
    return props.options.filter((option) => props.modelValue.includes(option.value));
});

// Handle selecting an option
const selectOption = (option: Option) => {
    if (!props.modelValue.includes(option.value)) {
        const newSelection = [...props.modelValue, option.value];
        emit('update:modelValue', newSelection);
    }
    searchTerm.value = ''; // Clear search term after selection
    showDropdown.value = false; // Hide dropdown after selection
    activeOptionIndex.value = -1; // Reset active option
    nextTick(() => {
        inputRef.value?.focus(); // Keep focus on input for quick re-selection
    });
};

// Handle removing a selected option
const removeOption = (value: number | string) => {
    const newSelection = props.modelValue.filter((item) => item !== value);
    emit('update:modelValue', newSelection);
    // Optionally re-focus input if it was the last tag removed
    if (newSelection.length === 0) {
        nextTick(() => inputRef.value?.focus());
    }
};

// Toggle dropdown visibility
const toggleDropdown = () => {
    showDropdown.value = !showDropdown.value;
    if (showDropdown.value) {
        activeOptionIndex.value = -1; // Reset when opening
        nextTick(() => inputRef.value?.focus());
    }
};

// Handle keyboard events
const handleKeydown = (event: KeyboardEvent) => {
    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            if (!showDropdown.value) {
                showDropdown.value = true;
                activeOptionIndex.value = 0; // Focus first option when opening
            } else {
                activeOptionIndex.value = (activeOptionIndex.value + 1) % filteredOptions.value.length;
            }
            scrollToActiveOption();
            break;
        case 'ArrowUp':
            event.preventDefault();
            if (!showDropdown.value) {
                showDropdown.value = true;
                activeOptionIndex.value = filteredOptions.value.length - 1; // Focus last option when opening
            } else {
                activeOptionIndex.value = (activeOptionIndex.value - 1 + filteredOptions.value.length) % filteredOptions.value.length;
            }
            scrollToActiveOption();
            break;
        case 'Enter':
            event.preventDefault();
            if (showDropdown.value && activeOptionIndex.value !== -1) {
                selectOption(filteredOptions.value[activeOptionIndex.value]);
            } else if (searchTerm.value && filteredOptions.value.length > 0) {
                // If there's search term and options, select the first one if Enter is pressed
                selectOption(filteredOptions.value[0]);
            } else if (!showDropdown.value && selectedOptions.value.length === 0) {
                // If dropdown is closed and no selection, open it
                showDropdown.value = true;
            }
            break;
        case 'Escape':
            showDropdown.value = false;
            activeOptionIndex.value = -1;
            inputRef.value?.blur(); // Remove focus from input
            break;
        case 'Backspace':
            if (searchTerm.value === '' && selectedOptions.value.length > 0) {
                // If search input is empty and user presses backspace, remove the last selected tag
                removeOption(selectedOptions.value[selectedOptions.value.length - 1].value);
            }
            break;
    }
};

// Scroll to the active option in the dropdown
const scrollToActiveOption = () => {
    nextTick(() => {
        const activeElement = dropdownRef.value?.querySelector('.multi-select-option.active');
        if (activeElement) {
            activeElement.scrollIntoView({ block: 'nearest' });
        }
    });
};

// Close dropdown when clicking outside
const onClickOutside = (event: MouseEvent) => {
    if (inputRef.value && dropdownRef.value) {
        const isClickInsideComponent = inputRef.value.contains(event.target as Node) || dropdownRef.value.contains(event.target as Node);
        if (!isClickInsideComponent) {
            showDropdown.value = false;
            searchTerm.value = ''; // Clear search term when closing
        }
    }
};

onMounted(() => {
    document.addEventListener('click', onClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', onClickOutside);
});

// Watch for changes in selected values to potentially close dropdown
watch(
    () => props.modelValue,
    () => {
        if (filteredOptions.value.length === 0 && searchTerm.value === '') {
            // showDropdown.value = false; // Keep dropdown open if user is actively searching
        }
    },
);

// Optional: Debounce search term if options list is very large
// import { debounce } from 'lodash'; // You might need to install lodash
// const debouncedSearch = debounce((value: string) => {
//   searchTerm.value = value;
// }, 300);
// In template: @input="debouncedSearch($event.target.value)"
</script>

<template>
    <div class="relative w-full">
        <div
            class="multi-select-wrapper flex w-full cursor-text flex-wrap items-center rounded-md border border-gray-300 bg-white p-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
            :class="{ 'border-blue-500 ring ring-blue-200 dark:ring-blue-800': showDropdown }"
            @click="toggleDropdown"
            aria-haspopup="listbox"
            :aria-expanded="showDropdown"
            role="combobox"
        >
            <div
                v-for="option in selectedOptions"
                :key="option.value"
                class="multi-select-tag mr-2 mb-1 flex items-center rounded bg-blue-100 px-2.5 py-0.5 text-sm font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
            >
                <span>{{ option.label }}</span>
                <button
                    type="button"
                    @click.stop="removeOption(option.value)"
                    class="ml-1 text-blue-500 hover:text-blue-700 focus:outline-none dark:text-blue-300 dark:hover:text-blue-100"
                    :aria-label="`Xóa ${option.label}`"
                >
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="relative min-w-[50px] flex-grow">
                <input
                    ref="inputRef"
                    type="text"
                    v-model="searchTerm"
                    @focus="showDropdown = true"
                    @input="showDropdown = true"
                    @keydown="handleKeydown"
                    :placeholder="selectedOptions.length === 0 ? placeholder || 'Tìm kiếm...' : ''"
                    class="multi-select-input m-0 w-full flex-grow border-none bg-transparent p-0 text-gray-700 outline-none focus:ring-0 dark:text-gray-200"
                    autocomplete="off"
                    role="textbox"
                    aria-autocomplete="list"
                    :aria-controls="`multi-select-list-${uniqueId}`"
                />
            </div>
        </div>

        <div
            v-if="showDropdown"
            ref="dropdownRef"
            class="multi-select-dropdown absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-gray-300 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-700"
            role="listbox"
            :id="`multi-select-list-${uniqueId}`"
        >
            <ul v-if="filteredOptions.length > 0">
                <li
                    v-for="(option, index) in filteredOptions"
                    :key="option.value"
                    @click="selectOption(option)"
                    class="multi-select-option cursor-pointer px-4 py-2 text-gray-800 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600"
                    :class="{ 'active bg-blue-50 dark:bg-blue-800': index === activeOptionIndex }"
                    role="option"
                    :aria-selected="index === activeOptionIndex"
                >
                    {{ option.label }}
                </li>
            </ul>
            <p v-else class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                {{ searchTerm ? noResultsText || 'Không tìm thấy kết quả.' : noOptionsText || 'Không có tùy chọn nào.' }}
            </p>
        </div>
    </div>
</template>

<style scoped>
/* Base styling for elements, compatible with Tailwind CSS */
.multi-select-wrapper {
    min-height: 42px; /* Consistent height */
    box-sizing: border-box;
}

.multi-select-input {
    /* Styles for the actual text input inside the wrapper */
    min-width: 20px; /* Allow it to shrink, but not too much */
}

.multi-select-dropdown {
    /* Ensures the dropdown is positioned correctly */
    top: 100%; /* Positions dropdown below the wrapper */
    left: 0;
    right: 0;
}

/* Optional: If you want custom focus states */
.multi-select-input:focus {
    outline: none; /* Remove default browser outline */
    /* Add custom focus styles if needed */
}

/* Hide scrollbar for a cleaner look if desired, but keep functionality */
/* For Webkit browsers (Chrome, Safari) */
.multi-select-dropdown::-webkit-scrollbar {
    width: 6px;
}

.multi-select-dropdown::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.2);
    border-radius: 3px;
}

.multi-select-dropdown::-webkit-scrollbar-track {
    background-color: rgba(0, 0, 0, 0.1);
}
</style>
