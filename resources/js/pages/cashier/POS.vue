<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import POSKeyboardHandler from '@/components/cashier/POSKeyboardHandler.vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { MenuIcon, BadgeInfo, X } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';

const { props } = usePage();
const products = ref(props.products || []);
const customers = ref(props.customers || []);
const searchTerm = ref('');
const selectedCategory = ref('Tất cả');
const showFilterSidebar = ref(false);
const showCustomerSidebar = ref(false);
const priceRange = ref({ min: '', max: '' });
const stockRange = ref({ min: '', max: '' });
const sortBy = ref('none');
const sortOrder = ref('asc');
const selectedProduct = ref(null);
const selectedCustomer = ref(null);
const customerSearch = ref('');
const cart = ref([]);
const showAddCustomerForm = ref(false);
const showSuccessModal = ref(false);
const successMessage = ref(props.flash?.success || '');
const errorMessage = ref(props.errors?.server || '');
const customersKey = ref(0); // Added to force re-render

const form = useForm({
    customer_name: '',
    email: '',
    phone: '',
    address: null,
    wallet: 0,
});

const categories = computed(() => ['Tất cả', ...new Set(products.value.map(p => p.category))]);

const filteredProducts = computed(() => {
    let filtered = products.value;

    if (searchTerm.value) {
        const trimmedSearch = searchTerm.value.trim();
        if (trimmedSearch) {
            const regex = new RegExp(trimmedSearch.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
            filtered = filtered.filter((product) => {
                return regex.test(product.name) || regex.test(product.category);
            });
        }
    }

    if (selectedCategory.value !== 'Tất cả') {
        filtered = filtered.filter((product) => product.category === selectedCategory.value);
    }

    if (priceRange.value.min || priceRange.value.max) {
        filtered = filtered.filter((product) => {
            const price = product.price || 0;
            const min = priceRange.value.min ? parseFloat(priceRange.value.min) : -Infinity;
            const max = priceRange.value.max ? parseFloat(priceRange.value.max) : Infinity;
            return price >= min && price <= max;
        });
    }

    if (stockRange.value.min || stockRange.value.max) {
        filtered = filtered.filter((product) => {
            const stock = product.stock || 0;
            const min = stockRange.value.min ? parseInt(stockRange.value.min) : -Infinity;
            const max = stockRange.value.max ? parseInt(stockRange.value.max) : Infinity;
            return stock >= min && stock <= max;
        });
    }

    if (sortBy.value !== 'none') {
        filtered = [...filtered].sort((a, b) => {
            if (sortBy.value === 'price') {
                const aPrice = a.price || 0;
                const bPrice = b.price || 0;
                return sortOrder.value === 'asc' ? aPrice - bPrice : bPrice - aPrice;
            } else if (sortBy.value === 'stock') {
                const aStock = a.stock || 0;
                const bStock = b.stock || 0;
                return sortOrder.value === 'asc' ? aStock - bStock : bStock - aStock;
            }
            return 0;
        });
    }

    return filtered.sort((a, b) => {
        if (a.stock === 0 && b.stock === 0) return 0;
        if (a.stock === 0) return 1;
        if (b.stock === 0) return -1;
        return 0;
    });
});

const filteredCustomers = computed(() => {
    if (!customerSearch.value) return customers.value;
    const regex = new RegExp(customerSearch.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
    return customers.value.filter((customer) =>
        regex.test(customer.name) || (customer.email && regex.test(customer.email)) || (customer.phone && regex.test(customer.phone))
    );
});

const cartSubtotal = computed(() => {
    return cart.value.reduce((total, item) => total + (item.price * item.quantity), 0);
});

const cartTax = computed(() => {
    return cartSubtotal.value * 0;
});

const cartTotal = computed(() => {
    return cartSubtotal.value + cartTax.value;
});

const addToCart = (product) => {
    if (product.stock === 0) return;
    const existingItem = cart.value.find(item => item.id === product.id);
    if (existingItem) {
        if (existingItem.quantity < product.stock) {
            existingItem.quantity += 1;
        }
    } else {
        cart.value.push({ ...product, quantity: 1 });
    }
};

const removeFromCart = (productId) => {
    cart.value = cart.value.filter(item => item.id !== productId);
};

const updateQuantity = (productId, quantity) => {
    const item = cart.value.find(item => item.id === productId);
    if (item) {
        const product = products.value.find(p => p.id === productId);
        if (quantity <= product.stock && quantity >= 1) {
            item.quantity = quantity;
        } else if (quantity < 1) {
            removeFromCart(productId);
        }
    }
};

const resetFilters = () => {
    searchTerm.value = '';
    selectedCategory.value = 'Tất cả';
    priceRange.value = { min: '', max: '' };
    stockRange.value = { min: '', max: '' };
    sortBy.value = 'none';
    sortOrder.value = 'asc';
};

const handleImageError = (event, product) => {
    event.target.src = '/storage/piclumen-1747750187180.png';
};

const openProductModal = (product) => {
    selectedProduct.value = product;
};

const toggleFilterSidebar = () => {
    showFilterSidebar.value = !showFilterSidebar.value;
    if (showCustomerSidebar.value) showCustomerSidebar.value = false;
};

const toggleCustomerSidebar = () => {
    showCustomerSidebar.value = !showCustomerSidebar.value;
    if (showFilterSidebar.value) showFilterSidebar.value = false;
};

const selectCustomer = (customer) => {
    selectedCustomer.value = customer;
    showCustomerSidebar.value = false;
    customerSearch.value = '';
};

const clearCustomer = () => {
    selectedCustomer.value = null;
};

const closeModalsAndSidebars = () => {
    selectedProduct.value = null;
    showFilterSidebar.value = false;
    showCustomerSidebar.value = false;
    customerSearch.value = '';
    showAddCustomerForm.value = false;
    errorMessage.value = '';
};

const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        closeModalsAndSidebars();
    }
};

const handleClickOutside = (event) => {
    if (selectedProduct.value && !event.target.closest('.modal-content')) {
        selectedProduct.value = null;
    }
};

const showHelp = () => {
    alert('Chức năng trợ giúp (F1) chưa được triển khai.');
};

const addItem = () => {
    alert('Chức năng thêm sản phẩm (F2) chưa được triển khai.');
};

const focusSearch = () => {
    const searchInput = document.querySelector('input[placeholder*="Tìm kiếm sản phẩm (F3)"]');
    if (searchInput) {
        searchInput.focus();
    }
};

const applyDiscount = () => {
    alert('Chức năng áp dụng giảm giá (F5) chưa được triển khai.');
};

const holdOrder = () => {
    alert('Chức năng tạm giữ đơn hàng (F6) chưa được triển khai.');
};

const reprintReceipt = () => {
    alert('Chức năng in lại hóa đơn (F7) chưa được triển khai.');
};

const removeLastCartItem = () => {
    if (cart.value.length > 0) {
        cart.value.pop();
    }
};

const checkout = () => {
    alert('Chức năng thanh toán (F9) chưa được triển khai.');
};

const toggleAddCustomerForm = () => {
    showAddCustomerForm.value = !showAddCustomerForm.value;
    if (showAddCustomerForm.value) {
        form.reset();
        errorMessage.value = '';
        nextTick(() => {
            document.querySelector('#customer_name')?.focus();
        });
    } else {
        form.clearErrors();
    }
};

const submitNewCustomer = () => {
    // Normalize form data
    const submittedData = {
        name: (form.customer_name || '').trim().toLowerCase(),
        phone: (form.phone || '').trim(),
        email: (form.email || '').trim() || null,
        address: (form.address || '').trim() || null,
        wallet: Number(form.wallet) || 0
    };

    if (!submittedData.name || !submittedData.phone) {
        errorMessage.value = 'Tên và số điện thoại là bắt buộc.';
        return;
    }

    form.post('/cashier/pos/customers', {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            console.log('Server response:', JSON.stringify(page.props, null, 2));
            const newCustomer = page.props.newCustomer;
            const updatedCustomers = page.props.customers || customers.value;

            if (updatedCustomers && updatedCustomers.length) {
                // Update customers reactively
                customers.value = [...updatedCustomers];
                customerSearch.value = ''; // Reset search to show all customers
                showCustomerSidebar.value = true; // Ensure sidebar is open
                successMessage.value = page.props.flash?.success || 'Khách hàng đã được thêm thành công!';
                showSuccessModal.value = true;
                toggleAddCustomerForm(); // Close the add customer form

                // Select the new customer
                if (newCustomer && newCustomer.id) {
                    selectedCustomer.value = newCustomer;
                    console.log('Selected new customer from response:', newCustomer);
                } else {
                    console.warn('newCustomer not provided or lacks id, attempting to find by matching fields');
                    // Normalize customer data for comparison
                    const normalizedCustomers = updatedCustomers.map(customer => ({
                        ...customer,
                        name: (customer.name || '').trim().toLowerCase(),
                        phone: (customer.phone || '').trim(),
                        email: (customer.email || '').trim() || null,
                        address: (customer.address || '').trim() || null,
                        wallet: Number(customer.wallet) || 0
                    }));

                    // Find matching customers by name and phone
                    const foundCustomers = normalizedCustomers.filter(customer =>
                        customer.name === submittedData.name &&
                        customer.phone === submittedData.phone
                    );

                    if (foundCustomers.length === 1) {
                        // Single match found
                        selectedCustomer.value = updatedCustomers[normalizedCustomers.indexOf(foundCustomers[0])];
                        console.log('Selected customer by matching name and phone:', selectedCustomer.value);
                    } else if (foundCustomers.length > 1) {
                        // Multiple matches, try to refine with optional fields
                        const bestMatch = foundCustomers.find(customer =>
                            (customer.email || null) === submittedData.email &&
                            (customer.address || null) === submittedData.address &&
                            customer.wallet === submittedData.wallet
                        );
                        if (bestMatch) {
                            selectedCustomer.value = updatedCustomers[normalizedCustomers.indexOf(bestMatch)];
                            console.log('Selected best match with optional fields:', selectedCustomer.value);
                        } else {
                            // Sort by id (descending) to select the newest customer
                            const sortedCustomers = foundCustomers.sort((a, b) => (b.id || 0) - (a.id || 0));
                            selectedCustomer.value = updatedCustomers[normalizedCustomers.indexOf(sortedCustomers[0])];
                            console.warn('Multiple matches found, selected newest by id:', selectedCustomer.value);
                        }
                    } else {
                        console.warn('No matching customer found, attempting to select newest customer');
                        // Sort customers by id (descending) to get the newest
                        const sortedCustomers = updatedCustomers.sort((a, b) => (b.id || 0) - (a.id || 0));
                        selectedCustomer.value = sortedCustomers[0] || null;
                        if (selectedCustomer.value) {
                            console.log('Selected newest customer as fallback:', selectedCustomer.value);
                        } else {
                            console.error('No customers available for fallback');
                            errorMessage.value = 'Không tìm thấy khách hàng mới. Vui lòng thử lại.';
                            selectedCustomer.value = null;
                        }
                    }
                }

                // Force re-render
                customersKey.value += 1;

                // Debug state after a short delay to ensure UI updates
                setTimeout(() => {
                    console.log('Submitted data:', JSON.stringify(submittedData, null, 2));
                    console.log('Updated customers:', JSON.stringify(customers.value, null, 2));
                    console.log('Filtered customers:', JSON.stringify(filteredCustomers.value, null, 2));
                    console.log('Selected customer:', JSON.stringify(selectedCustomer.value, null, 2));
                }, 200);
            } else {
                console.error('Missing or empty customers in response:', page.props);
                errorMessage.value = 'Không thể cập nhật danh sách khách hàng. Vui lòng thử lại.';
            }
        },
        onError: (errors) => {
            errorMessage.value = errors.server || 'Có lỗi khi thêm khách hàng. Vui lòng kiểm tra lại thông tin.';
            console.error('Form errors:', JSON.stringify(errors, null, 2));
        },
        onFinish: () => {
            form.processing = false;
        },
    });
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    window.addEventListener('click', handleClickOutside);
    if (props.flash?.success) {
        showSuccessModal.value = true;
    }
    console.log('Initial customers:', JSON.stringify(customers.value, null, 2));
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    window.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <Head title="Cashier" />
    <CashierLayout>
        <div class="flex text-xs relative h-[650px]">
            <div v-if="errorMessage" class="fixed top-4 left-1/2 transform -translate-x-1/2 bg-red-100 text-red-700 p-3 rounded shadow-lg text-sm z-50">
                {{ errorMessage }}
                <button @click="errorMessage = ''" class="ml-2 text-red-900 hover:text-red-700">✖</button>
            </div>
            <div v-if="showSuccessModal" class="fixed top-4 left-1/2 transform -translate-x-1/2 bg-green-100 text-green-700 p-3 rounded shadow-lg text-sm z-50">
                {{ successMessage }}
                <button @click="showSuccessModal = false" class="ml-2 text-green-900 hover:text-green-700">✖</button>
            </div>
            <div :class="{ 'translate-x-full': !showFilterSidebar, 'translate-x-0': showFilterSidebar }" class="fixed inset-y-0 right-0 w-80 bg-white shadow-xl z-50 transform transition-transform duration-300">
                <div class="p-3 h-full flex flex-col">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-sm font-semibold">Bộ lọc</h3>
                        <button @click="toggleFilterSidebar" class="bg-gray-300 text-gray-800 py-1 px-2 rounded text-xs hover:bg-gray-400">
                            Đóng
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <div>
                            <p class="text-[11px] text-gray-600 mt-0.5">Sản phẩm phù hợp: {{ filteredProducts.length }}</p>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Danh mục:</label>
                                <select v-model="selectedCategory" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
                                    <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Khoảng giá (VND):</label>
                                <div class="flex space-x-2">
                                    <input type="number" v-model="priceRange.min" placeholder="Từ" class="w-1/2 p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                    <input type="number" v-model="priceRange.max" placeholder="Đến" class="w-1/2 p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Số lượng tồn:</label>
                                <div class="flex space-x-2">
                                    <input type="number" v-model="stockRange.min" placeholder="Từ" class="w-1/2 p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                    <input type="number" v-model="stockRange.max" placeholder="Đến" class="w-1/2 p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Sắp xếp theo:</label>
                                <select v-model="sortBy" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
                                    <option value="none">Không sắp xếp</option>
                                    <option value="price">Giá</option>
                                    <option value="stock">Số lượng tồn</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Thứ tự:</label>
                                <select v-model="sortOrder" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
                                    <option value="asc">Tăng dần</option>
                                    <option value="desc">Giảm dần</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div :class="{ 'translate-x-full': !showCustomerSidebar, 'translate-x-0': showCustomerSidebar }" class="fixed inset-y-0 right-0 w-80 bg-white shadow-iron-300 z-50 transform transition-transform duration-300">
                <div class="p-3 h-full flex flex-col">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-sm font-semibold">Chọn khách hàng</h3>
                        <button @click="toggleCustomerSidebar" class="bg-gray-300 text-gray-800 py-1 px-2 rounded text-xs hover:bg-gray-400">
                            Đóng
                        </button>
                    </div>
                    <div class="mb-2">
                        <input type="text" v-model="customerSearch" placeholder="Tìm kiếm khách hàng (tên, email, số điện thoại)" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>
                    <div class="mb-2">
                        <button @click="toggleAddCustomerForm" class="w-full bg-blue-100 text-blue-700 py-1 rounded text-xs hover:bg-blue-200">
                            {{ showAddCustomerForm ? 'Hủy thêm khách hàng' : 'Thêm khách hàng mới' }}
                        </button>
                    </div>
                    <div v-if="showAddCustomerForm" class="mb-2">
                        <form class="space-y-2" @submit.prevent="submitNewCustomer">
                            <div>
                                <label for="customer_name" class="block text-xs font-medium text-gray-700 mb-0.5">
                                    Tên khách hàng <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="customer_name" v-model="form.customer_name" placeholder="Nhập tên khách hàng" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.customer_name }" />
                                <InputError class="mt-0.5" :message="form.errors.customer_name" />
                            </div>
                            <div>
                                <label for="email" class="block text-xs font-medium text-gray-700 mb-0.5">
                                    Email
                                </label>
                                <input type="email" id="email" v-model="form.email" placeholder="Nhập email" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.email }" />
                                <InputError class="mt-0.5" :message="form.errors.email" />
                            </div>
                            <div>
                                <label for="phone" class="block text-xs font-medium text-gray-700 mb-0.5">
                                    Số điện thoại <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" id="phone" v-model="form.phone" placeholder="Nhập số điện thoại" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.phone }" />
                                <InputError class="mt-0.5" :message="form.errors.phone" />
                            </div>
                            <div>
                                <label for="address" class="block text-xs font-medium text-gray-700 mb-0.5">
                                    Địa chỉ
                                </label>
                                <input type="text" id="address" v-model="form.address" placeholder="Nhập địa chỉ khách hàng" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.address }" />
                                <InputError class="mt-0.5" :message="form.errors.address" />
                            </div>
                            <div>
                                <label for="wallet" class="block text-xs font-medium text-gray-700 mb-0.5">
                                    Ví tiền
                                </label>
                                <input type="number" id="wallet" v-model.number="form.wallet" placeholder="Nhập số tiền trong ví" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.wallet }" />
                                <InputError class="mt-0.5" :message="form.errors.wallet" />
                            </div>
                            <div class="flex space-x-2">
                                <button type="button" @click="toggleAddCustomerForm" class="w-1/2 bg-gray-300 text-gray-700 py-1.5 rounded text-xs hover:bg-gray-400">
                                    Hủy
                                </button>
                                <button type="submit" class="w-1/2 bg-blue-600 text-white py-1.5 rounded text-xs font-semibold hover:bg-blue-700" :disabled="form.processing">
                                    Lưu khách hàng
                                </button>
                            </div>
                        </form>
                    </div>
                    <div v-else class="flex-1 overflow-y-auto">
                        <div v-for="customer in filteredCustomers" :key="customer.id" class="p-2 border-b border-gray-200 hover:bg-gray-100 cursor-pointer" @click="selectCustomer(customer)">
                            <p><strong>{{ customer.name }}</strong></p>
                            <p v-if="customer.phone" class="text-[10px] text-gray-600">{{ customer.phone }}</p>
                            <p v-if="customer.address" class="text-[10px] text-gray-600">{{ customer.address }}</p>
                            <p class="text-[10px] text-gray-600">Ví: {{ customer.wallet }} VND</p>
                        </div>
                        <div v-if="filteredCustomers.length === 0" class="text-center text-gray-500 py-2">
                            Không tìm thấy khách hàng
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-2/3 bg-white flex flex-col h-full">
                <div class="p-1.5 border-b border-gray-200 shrink-0">
                    <div class="flex justify-between items-center mb-1">
                        <div class="flex items-center space-x-1">
                            <button @click="toggleFilterSidebar" class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs hover:bg-gray-300">
                                <MenuIcon class="w-4 h-4" />
                            </button>
                            <div class="flex justify-between items-center mt-1 text-[11px] text-gray-600">
                                <button v-if="searchTerm || selectedCategory !== 'Tất cả' || priceRange.min || priceRange.max || stockRange.min || stockRange.max || sortBy !== 'none'" @click="resetFilters" class="text-blue-500 hover:underline focus:outline-none">
                                    <X class="w-4 h-4 inline-block mr-1" />
                                </button>
                            </div>
                            <h2 class="text-base font-semibold">Sản phẩm</h2>
                        </div>
                    </div>
                    <div class="mb-1">
                        <input type="text" v-model="searchTerm" placeholder="Tìm kiếm sản phẩm (F3)" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-2">
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2">
                        <div v-for="product in filteredProducts" :key="product.id" class="relative bg-gray-50 p-1.5 rounded shadow-sm hover:shadow transition-all duration-200 flex flex-col items-center text-center" :class="{ 'opacity-60 cursor-not-allowed': product.stock === 0 }" @click="product.stock > 0 && addToCart(product)">
                            <button @click.stop="openProductModal(product)" class="absolute top-1 right-1 hover:bg-gray-100 z-10 shadow">
                                <BadgeInfo class="w-3 h-3" />
                            </button>
                            <img :src="product.image" :alt="product.name" class="w-12 h-12 object-cover rounded mb-0.5" @error="handleImageError($event, product)" />
                            <h3 class="text-[10px] font-medium truncate w-full">{{ product.name }}</h3>
                            <p v-if="product.stock <= 10 && product.stock > 0" class="text-orange-500 text-[10px] font-semibold mt-0.5">
                                Tồn: {{ product.stock }}
                            </p>
                            <p v-else-if="product.stock === 0" class="text-red-500 text-[10px] font-semibold mt-0.5">Hết hàng</p>
                            <p v-else class="text-gray-500 text-[10px] mt-0.5">Tồn: {{ product.stock }}</p>
                            <p class="text-[10px] mt-0.5">{{ product.price }} VND</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-1/3 bg-white flex flex-col h-full">
                <div class="p-2 border-b border-gray-200 shrink-0">
                    <div class="flex justify-between items-center">
                        <h2 class="text-base font-semibold">Giỏ hàng</h2>
                        <div class="flex space-x-1">
                            <button class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs hover:bg-gray-300" disabled>Đơn mới</button>
                            <button class="bg-yellow-500 text-white px-2 py-0.5 rounded text-xs hover:bg-yellow-600" disabled>Lưu đơn (F6)</button>
                        </div>
                    </div>
                </div>
                <div class="p-2 border-b border-gray-200 shrink-0">
                    <h3 class="text-xs font-semibold mb-1">Đơn hàng chờ:</h3>
                    <div class="flex flex-wrap gap-1 max-h-16 overflow-y-auto">
                        <div class="relative bg-gray-100 p-1.5 rounded text-[10px] border">
                            Không có đơn hàng chờ
                        </div>
                    </div>
                </div>
                <div class="flex-1 p-2 overflow-y-auto">
                    <div v-if="cart.length === 0" class="text-gray-500 text-center py-4 text-xs">
                        Giỏ hàng trống. Vui lòng thêm sản phẩm (F2)!
                    </div>
                    <div v-else>
                        <div v-for="item in cart" :key="item.id" class="flex items-center justify-between p-2 border-b border-gray-200">
                            <div class="flex-1">
                                <p class="text-xs font-medium">{{ item.name }}</p>
                                <p class="text-[10px] text-gray-600">{{ item.price }} VND x {{ item.quantity }}</p>
                            </div>
                            <div class="flex items-center space-x-1">
                                <input type="number" v-model.number="item.quantity" min="1" :max="products.find(p => p.id === item.id).stock" class="w-12 p-1 border border-gray-300 rounded text-xs" @input="updateQuantity(item.id, item.quantity)" />
                                <button @click="removeFromCart(item.id)" class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs hover:bg-red-200">
                                    Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-2 border-t border-gray-200 shrink-0">
                    <div class="mb-1">
                        <label class="block text-[10px] font-medium text-gray-700 mb-0.5">Khách hàng (F4):</label>
                        <div class="flex items-center space-x-1">
                            <button @click="toggleCustomerSidebar" class="w-full bg-gray-100 text-gray-700 py-1 rounded border border-gray-300 hover:bg-gray-200 text-xs text-left">
                                {{ selectedCustomer ? selectedCustomer.name : 'Chọn khách hàng' }}
                            </button>
                            <button v-if="selectedCustomer" @click="clearCustomer" class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs hover:bg-red-200">
                                Xóa
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs font-semibold text-gray-700">Tổng phụ:</span>
                        <span class="text-base font-bold text-gray-800">{{ cartSubtotal }} VND</span>
                    </div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs font-semibold text-gray-700">Thuế VAT (0%):</span>
                        <span class="text-base font-bold text-gray-800">{{ cartTax }} VND</span>
                    </div>
                    <div class="mb-1">
                        <label class="block text-[10px] font-medium text-gray-700 mb-0.5">Giảm giá (F5):</label>
                        <div class="flex space-x-1 mb-1">
                            <input type="number" placeholder="Số tiền giảm" class="w-1/2 p-1.5 border border-gray-300 rounded text-xs focus:outline-none" disabled />
                        </div>
                        <div class="flex flex-wrap gap-1 mb-1">
                            <button class="px-2 py-0.5 bg-gray-200 rounded text-[10px] hover:bg-gray-300" disabled>5%</button>
                            <button class="px-2 py-0.5 bg-gray-200 rounded text-[10px] hover:bg-gray-300" disabled>10%</button>
                            <button class="px-2 py-0.5 bg-gray-200 rounded text-[10px] hover:bg-gray-300" disabled>20%</button>
                            <button class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-[10px] hover:bg-red-200" disabled>Xóa</button>
                        </div>
                    </div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-semibold">Tổng cộng:</span>
                        <span class="text-lg font-bold text-blue-600">{{ cartTotal }} VND</span>
                    </div>
                    <div class="mb-1">
                        <label for="paymentMethod" class="block text-[10px] font-medium text-gray-700 mb-0.5">Phương thức thanh toán:</label>
                        <select id="paymentMethod" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none" disabled>
                            <option value="cash">Tiền mặt</option>
                            <option value="card">Thẻ ngân hàng</option>
                            <option value="transfer">Chuyển khoản</option>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label for="amountReceived" class="block text-[10px] font-medium text-gray-700 mb-0.5">Tiền khách đưa:</label>
                        <input type="number" placeholder="Nhập số tiền khách đưa" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none" disabled />
                        <div class="flex flex-wrap gap-1 mt-1">
                            <button class="px-2 py-0.5 bg-gray-200 rounded text-[10px] hover:bg-gray-300" disabled>50K</button>
                            <button class="px-2 py-0.5 bg-gray-200 rounded text-[10px] hover:bg-gray-300" disabled>100K</button>
                            <button class="px-2 py-0.5 bg-gray-200 rounded text-[10px] hover:bg-gray-300" disabled>200K</button>
                            <button class="px-2 py-0.5 bg-gray-200 rounded text-[10px] hover:bg-gray-300" disabled>500K</button>
                            <button class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px] hover:bg-blue-200" disabled>Đủ tiền</button>
                            <button class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-[10px] hover:bg-red-200" disabled>Xóa</button>
                        </div>
                    </div>
                    <div class="mb-1">
                        <label for="orderNotes" class="block text-[10px] font-medium text-gray-700 mb-0.5">Ghi chú đơn hàng:</label>
                        <textarea id="orderNotes" rows="1" placeholder="Thêm ghi chú chung..." class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none" disabled></textarea>
                    </div>
                    <button class="w-full bg-blue-600 text-white py-2 rounded text-sm font-semibold hover:bg-blue-700 transition duration-200" disabled>
                        Thanh toán (F9)
                    </button>
                    <button class="w-full bg-gray-200 text-gray-700 py-1 rounded text-xs hover:bg-gray-300 mt-1" disabled>
                        Xem & In hóa đơn (F7)
                    </button>
                </div>
            </div>
        </div>
        <div v-if="selectedProduct" class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm bg-black/30">
            <div class="modal-content bg-white/70 backdrop-blur-md border border-white/30 shadow-xl rounded-xl p-4 max-w-sm w-[90%] text-sm text-gray-800 transition transform scale-95 animate-modal relative">
                <button @click="selectedProduct = null" class="absolute top-2 right-2 text-gray-700 hover:text-black text-lg">✖</button>
                <h3 class="text-base font-bold mb-3 text-center">Chi tiết sản phẩm</h3>
                <img :src="selectedProduct.image" :alt="selectedProduct.name" class="w-24 h-24 object-cover mx-auto rounded mb-3 shadow" />
                <div class="space-y-1 text-center">
                    <p><strong>Tên:</strong> {{ selectedProduct.name }}</p>
                    <p><strong>Danh mục:</strong> {{ selectedProduct.category }}</p>
                    <p><strong>Tồn kho:</strong> {{ selectedProduct.stock }}</p>
                    <p><strong>Giá:</strong> {{ selectedProduct.price ? selectedProduct.price + ' VND' : 'Chưa có' }}</p>
                </div>
            </div>
        </div>
        <POSKeyboardHandler :on-help="showHelp" :on-add-item="addItem" :on-search="focusSearch" :on-customer="toggleCustomerSidebar" :on-discount="applyDiscount" :on-hold-order="holdOrder" :on-reprint="reprintReceipt" :on-delete-item="removeLastCartItem" :on-checkout="checkout" />
    </CashierLayout>
</template>