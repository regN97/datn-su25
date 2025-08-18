<script setup>
import POSKeyboardHandler from '@/components/cashier/POSKeyboardHandler.vue';
<<<<<<< HEAD
=======
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { MenuIcon, BadgeInfo, X, FileText, LogOutIcon, Loader2 } from 'lucide-vue-next';
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
import InputError from '@/components/InputError.vue';
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { BadgeInfo, FileText, Loader2, MenuIcon, X } from 'lucide-vue-next';
import { DateTime } from 'luxon';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

// Lấy props từ Inertia
const { props } = usePage();

// Danh sách ghi chú mặc định cho ca làm việc
const defaultNotes = ['Hoàn thành ca không có sự cố', 'Thiếu tiền mặt', 'Thừa tiền mặt', 'Khách hàng trả lại hàng', 'Khác (vui lòng ghi rõ)'];

// Khởi tạo state
const products = ref(props.products || []);
const customers = ref(props.customers || []);
const hasActiveSession = ref(props.hasActiveSession || false);
const activeShift = ref(props.activeShift || null);
const workShifts = ref(props.workShifts || []);
const searchTerm = ref('');
const selectedCategory = ref('Tất cả');
const showFilterSidebar = ref(false);
const showCustomerSidebar = ref(false);
const showReportSidebar = ref(false);
const showLogoutModal = ref(false);
const showPaymentModal = ref(false);
const billNumber = ref('');
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
const shiftReport = ref(null);
const isLoadingReport = ref(false);
const isLoadingSessionAction = ref(false);
const quickAmounts = [100000, 200000, 500000, 1000000];
const pendingOrders = ref([]);
const interval = ref(null);
const bankQRCode = ref(null);
const isLoadingBankQR = ref(false);
const bankTransactionInfo = ref(null);
const walletAmount = ref(0);


// Forms
const sessionForm = useForm({
    opening_amount: 0,
    closing_amount: 0,
    notes: '',
    selected_default_note: '',
    custom_note: '',
});

const form = useForm({
    customer_name: '',
    email: '',
    phone: '',
    address: null,
    wallet: 0,
    cart: [],
    customer_id: null,
    paymentMethod: 'cash',
    amountReceived: 0,
    orderNotes: '',
    printReceipt: true,
});

const cleanBarcode = (input) => {
    return input.trim().replace(/[\r\n]+/g, ''); // Remove newlines and extra spaces
};
// Computed properties
const categories = computed(() => {
    const productCategories = products.value.map((p) => p.category?.name || 'Không xác định').filter((category) => category !== 'Không xác định');
    return ['Tất cả', ...new Set(productCategories)];
});

const formattedOpenedAt = computed(() => {
    if (!activeShift.value?.opened_at) return 'Chưa bắt đầu';
    const dt = DateTime.fromISO(activeShift.value.opened_at, { zone: 'Asia/Ho_Chi_Minh' });
    return dt.isValid ? dt.toFormat('dd/MM/yyyy HH:mm:ss') : 'Chưa bắt đầu';
});

const cartSubtotal = computed(() => {
    return cart.value.reduce((total, item) => {
        const price = Number(item.price) || 0;
        const quantity = Number(item.quantity) || 0;
        return total + price * quantity;
    }, 0);
});

const cartTax = computed(() => 0); // VAT 0% theo yêu cầu

const cartTotal = computed(() => cartSubtotal.value + cartTax.value);

const change = computed(() => {
    if (form.paymentMethod === 'cash' && form.amountReceived >= cartTotal.value) {
        return form.amountReceived - cartTotal.value;
    }
    return 0;
});

const filteredProducts = computed(() => {
    let filtered = [...products.value];

    if (searchTerm.value.trim()) {
        const regex = new RegExp(searchTerm.value.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
        filtered = filtered.filter((p) => regex.test(p.name) || regex.test(p.category?.name || '') || regex.test(p.sku));
    }

    if (selectedCategory.value !== 'Tất cả') {
        filtered = filtered.filter((p) => p.category?.name === selectedCategory.value);
    }

    if (priceRange.value.min || priceRange.value.max) {
        const min = parseFloat(priceRange.value.min) || -Infinity;
        const max = parseFloat(priceRange.value.max) || Infinity;
        filtered = filtered.filter((p) => p.selling_price >= min && p.selling_price <= max);
    }

    if (stockRange.value.min || stockRange.value.max) {
        const min = parseInt(stockRange.value.min) || -Infinity;
        const max = parseInt(stockRange.value.max) || Infinity;
        filtered = filtered.filter((p) => p.stock_quantity >= min && p.stock_quantity <= max);
    }

    if (sortBy.value !== 'none') {
        filtered.sort((a, b) => {
            const aValue = sortBy.value === 'price' ? a.selling_price : a.stock_quantity;
            const bValue = sortBy.value === 'price' ? b.selling_price : b.stock_quantity;
            return sortOrder.value === 'asc' ? aValue - bValue : bValue - aValue;
        });
    }

    return filtered.sort((a, b) => {
        if (a.stock_quantity === 0 && b.stock_quantity === 0) return 0;
        if (a.stock_quantity === 0) return 1;
        if (b.stock_quantity === 0) return -1;
        return 0;
    });
});

const filteredCustomers = computed(() => {
    if (!customerSearch.value.trim()) return customers.value;
    const regex = new RegExp(customerSearch.value.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
    return customers.value.filter((c) => regex.test(c.customer_name) || (c.email && regex.test(c.email)) || (c.phone && regex.test(c.phone)));
});

// Utility functions
const formatCurrency = (amount) => {
    return (Number(amount) || 0).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
};

const formatDateTime = (dateTime) => {
    if (!dateTime || typeof dateTime !== 'string') return 'Chưa xác định';
    const dt = DateTime.fromISO(dateTime, { zone: 'Asia/Ho_Chi_Minh' });
    return dt.isValid ? dt.toFormat('dd/MM/yyyy HH:mm:ss') : 'Chưa xác định';
};

const autoHideMessage = () => {
    if (successMessage.value || errorMessage.value) {
        setTimeout(() => {
            successMessage.value = '';
            errorMessage.value = '';
            showSuccessModal.value = false;
        }, 5000);
    }
};

const sanitizeHTML = (str) => {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
};

// Shift management
const getSuitableShift = () => {
    const now = DateTime.now().setZone('Asia/Ho_Chi_Minh');
    const currentTime = now.toFormat('HH:mm:ss');
    return workShifts.value.find((shift) => {
        const { start_time, end_time } = shift;
        if (start_time > end_time) {
            return currentTime >= start_time || currentTime <= end_time;
        }
        return currentTime >= start_time && currentTime <= end_time;
    });
};

const fetchWorkShifts = async () => {
    try {
        const response = await axios.get('/cashier/pos/work-shifts');
        workShifts.value = response.data.shifts;
        const suitableShift = getSuitableShift();
        sessionForm.shift_id = suitableShift?.id || '';
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Lỗi tải ca làm việc.';
        autoHideMessage();
    }
};

const fetchShiftReport = async () => {
    if (isLoadingReport.value || !hasActiveSession.value) return;
    isLoadingReport.value = true;
    try {
        const response = await axios.get('/cashier/pos/shift-report', {
            headers: { 'Cache-Control': 'no-cache' },
            params: { detailed: true },
        });
        shiftReport.value = {
            ...response.data,
            report: {
                customers: (response.data.report?.customers || []).map((c) => ({
                    ...c,
                    customer_name: c.customer_name || 'Khách lẻ',
                    last_purchase: c.last_purchase ? formatDateTime(c.last_purchase) : 'N/A',
                    average_purchase: c.average_purchase || 0,
                })),
                top_products: (response.data.report?.top_products || []).map((p) => ({
                    ...p,
                    average_price: p.total_revenue / p.quantity_sold || 0,
                })),
                payment_breakdown: response.data.report?.payment_breakdown || {
                    cash_percentage: 0,
                    card_percentage: 0,
                    transfer_percentage: 0,
                    wallet_percentage: 0,
                },
                hourly_sales: response.data.report?.hourly_sales || [],
            },
        };
        hasActiveSession.value = response.data.hasActiveSession || false;
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Lỗi tải báo cáo ca.';
        showReportSidebar.value = false;
        autoHideMessage();
    } finally {
        isLoadingReport.value = false;
    }
};

const openShift = async () => {
    if (!confirm('Xác nhận mở ca làm việc?')) return;
    isLoadingSessionAction.value = true;
    sessionForm.notes = sessionForm.selected_default_note === 'Khác (vui lòng ghi rõ)' ? sessionForm.custom_note : sessionForm.selected_default_note;
    sessionForm.clearErrors();
    try {
        const response = await axios.post('/cashier/pos/session/start', {
            opening_amount: Number(sessionForm.opening_amount) || 0,
            notes: sessionForm.notes || '',
        });
        hasActiveSession.value = true;
        activeShift.value = {
            ...response.data.activeShift,
            opened_at: response.data.activeShift.opened_at || DateTime.now().toISO(),
            shift_name: response.data.activeShift.shift_name || 'Ca hiện tại',
        };
        successMessage.value = response.data.success || 'Ca đã mở thành công!';
        showSuccessModal.value = true;
        sessionForm.reset();
        autoHideMessage();
        await fetchShiftReport();
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Lỗi mở ca.';
        if (error.response?.data?.errors) {
            Object.values(error.response.data.errors).forEach((err) => (errorMessage.value += ` ${err}`));
        }
        autoHideMessage();
    } finally {
        isLoadingSessionAction.value = false;
    }
};

const closeShift = async () => {
    if (!confirm('Xác nhận đóng ca làm việc?')) return;
    isLoadingSessionAction.value = true;
    sessionForm.notes = sessionForm.selected_default_note === 'Khác (vui lòng ghi rõ)' ? sessionForm.custom_note : sessionForm.selected_default_note;
    sessionForm.clearErrors();
    try {
        const response = await axios.post('/cashier/pos/session/close', {
            closing_amount: Number(sessionForm.closing_amount) || 0,
            notes: sessionForm.notes || '',
        });
        hasActiveSession.value = false;
        activeShift.value = null;
        successMessage.value = response.data.success || 'Ca đã đóng thành công!';
        showSuccessModal.value = true;
        sessionForm.reset();
        shiftReport.value = null;
        autoHideMessage();
        await fetchShiftReport();
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Lỗi đóng ca.';
        if (error.response?.data?.errors) {
            Object.values(error.response.data.errors).forEach((err) => (errorMessage.value += ` ${err}`));
        }
        autoHideMessage();
    } finally {
        isLoadingSessionAction.value = false;
    }
};

const generateShiftReport = async () => {
    if (!hasActiveSession.value) {
        errorMessage.value = 'Không có ca đang mở.';
        autoHideMessage();
        return;
    }
    if (!sessionForm.closing_amount) {
        errorMessage.value = 'Vui lòng nhập số tiền đóng ca.';
        autoHideMessage();
        return;
    }
    if (!confirm('Xác nhận tạo báo cáo ca?')) return;
    isLoadingSessionAction.value = true;
    try {
        const notes =
            sessionForm.selected_default_note === 'Khác (vui lòng ghi rõ)'
                ? sessionForm.custom_note
                : sessionForm.selected_default_note || 'Không có';
        const response = await axios.post('/cashier/pos/shift-report/generate', {
            closing_amount: Number(sessionForm.closing_amount) || 0,
            notes,
        });
        successMessage.value = response.data.message || 'Báo cáo ca đã tạo thành công!';
        showSuccessModal.value = true;
        autoHideMessage();
        await fetchShiftReport();
    } catch (error) {
        errorMessage.value =
            error.response?.data?.errors?.server || Object.values(error.response?.data?.errors || {}).join(' ') || 'Lỗi tạo báo cáo ca.';
        autoHideMessage();
    } finally {
        isLoadingSessionAction.value = false;
    }
};

const createNewOrder = () => {
    cart.value = [];
    selectedCustomer.value = null;
    form.customer_id = null;
    form.orderNotes = '';
    form.paymentMethod = 'cash';
    form.amountReceived = 0;
    form.printReceipt = true;
    successMessage.value = 'Đã tạo đơn hàng mới!';
    showSuccessModal.value = true;
    autoHideMessage();
};
const holdOrder = () => {
    if (cart.value.length === 0) {
        errorMessage.value = 'Giỏ hàng trống. Vui lòng thêm sản phẩm trước khi lưu đơn!';
        autoHideMessage();
        return;
    }
    if (pendingOrders.value.length >= 10) {
        errorMessage.value = 'Đã đạt tối đa 10 đơn hàng chờ. Vui lòng xử lý hoặc xóa đơn hiện có!';
        autoHideMessage();
        return;
    }
    const orderId = pendingOrders.value.length + 1;
    const order = {
        id: orderId,
        cart: [...cart.value],
        customer: selectedCustomer.value ? { ...selectedCustomer.value } : null,
        orderNotes: form.orderNotes || '',
        timestamp: DateTime.now().toISO(),
    };
    pendingOrders.value.push(order);
    localStorage.setItem('pendingOrders', JSON.stringify(pendingOrders.value));
    createNewOrder();
    successMessage.value = `Đã lưu đơn hàng #${orderId}!`;
    showSuccessModal.value = true;
    autoHideMessage();
};
const removeOrder = (orderId) => {
    pendingOrders.value = pendingOrders.value.filter((o) => o.id !== orderId);
    pendingOrders.value = pendingOrders.value.map((order, index) => ({
        ...order,
        id: index + 1,
    }));
    // Cập nhật localStorage
    localStorage.setItem('pendingOrders', JSON.stringify(pendingOrders.value));
    successMessage.value = `Đã xóa đơn hàng #${orderId}!`;
    showSuccessModal.value = true;
    autoHideMessage();
};

const restoreOrder = (orderId) => {
    const order = pendingOrders.value.find((o) => o.id === orderId);
    if (!order) return;
    cart.value = [...order.cart];
    selectedCustomer.value = order.customer ? { ...order.customer } : null;
    form.customer_id = order.customer ? order.customer.id : null;
    form.orderNotes = order.orderNotes;
    // Xóa đơn hàng đã khôi phục khỏi pendingOrders
    pendingOrders.value = pendingOrders.value.filter((o) => o.id !== orderId);
    pendingOrders.value = pendingOrders.value.map((order, index) => ({
        ...order,
        id: index + 1,
    }));
    // Cập nhật localStorage
    localStorage.setItem('pendingOrders', JSON.stringify(pendingOrders.value));
    successMessage.value = `Đã khôi phục đơn hàng #${orderId}!`;
    showSuccessModal.value = true;
    autoHideMessage();
};

const addToCart = async (product) => {
    if (!hasActiveSession.value) {
        errorMessage.value = 'Vui lòng mở ca trước khi thêm sản phẩm.';
        autoHideMessage();
        return;
    }
    if (product.stock_quantity === 0) {
        errorMessage.value = `Sản phẩm ${product.name} đã hết hàng.`;
        autoHideMessage();
        return;
    }
    if (!product.price || isNaN(Number(product.price))) {
        errorMessage.value = `Sản phẩm ${product.name} có giá không hợp lệ.`;
        autoHideMessage();
        return;
    }
    try {
        const response = await axios.get(`/cashier/pos/check-batch/${product.id}`, { params: { quantity: 1 } });
        if (!response.data.hasValidBatch) {
            errorMessage.value = response.data.message || `Sản phẩm ${product.name} không có lô hợp lệ.`;
            products.value = products.value.map((p) => (p.id === product.id ? { ...p, stock_quantity: response.data.availableStock || 0 } : p));
            autoHideMessage();
            return;
        }
        const existingItem = cart.value.find((item) => item.id === product.id);
        if (existingItem) {
            const newQuantity = existingItem.quantity + 1;
            const stockResponse = await axios.get(`/cashier/pos/check-batch/${product.id}`, { params: { quantity: newQuantity } });
            if (stockResponse.data.hasValidBatch) {
                console.log(`Tăng số lượng sản phẩm ${product.name}, số lượng mới: ${newQuantity}`);
                existingItem.quantity = newQuantity;
            } else {
                errorMessage.value = stockResponse.data.message || `Số lượng tối đa cho ${product.name} là ${product.stock_quantity}.`;
                autoHideMessage();
            }
        } else {
            console.log(`Thêm mới sản phẩm ${product.name} vào giỏ hàng`);
            cart.value.push({
                id: product.id,
                name: product.name,
                price: Number(product.price) || 0,
                quantity: 1,
            });
        }
        products.value = products.value.map((p) => (p.id === product.id ? { ...p, stock_quantity: response.data.availableStock || 0 } : p));
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || `Lỗi kiểm tra lô hàng ${product.name}.`;
        autoHideMessage();
    }
};

const updateQuantity = async (productId, quantity) => {
    const item = cart.value.find((item) => item.id === productId);
    if (!item) return;

    const product = products.value.find((p) => p.id === productId);
    try {
        const response = await axios.get(`/cashier/pos/check-batch/${productId}`, { params: { quantity } });
        if (!response.data.hasValidBatch) {
            errorMessage.value = response.data.message || `Không đủ lô cho ${product.name} với số lượng ${quantity}.`;
            removeFromCart(productId);
            products.value = products.value.map((p) => (p.id === productId ? { ...p, stock_quantity: response.data.availableStock || 0 } : p));
            autoHideMessage();
            return;
        }
        if (quantity <= response.data.availableStock && quantity >= 1) {
            item.quantity = quantity;
        } else if (quantity < 1) {
            removeFromCart(productId);
        } else {
            errorMessage.value = `Số lượng tối đa cho ${product.name} là ${response.data.availableStock}.`;
            item.quantity = response.data.availableStock;
            autoHideMessage();
        }
        products.value = products.value.map((p) => (p.id === productId ? { ...p, stock_quantity: response.data.availableStock || 0 } : p));
    } catch (error) {
        if (error.response?.status === 422 && error.response.data?.errors?.server?.includes('Ca làm việc đã hết hạn')) {
            errorMessage.value = error.response.data.errors.server;
        } else {
            errorMessage.value = error.response?.data?.errors?.server || `Lỗi kiểm tra lô ${product.name}.`;
            removeFromCart(productId);
        }
        autoHideMessage();
    }
};

const removeFromCart = (productId) => {
    cart.value = cart.value.filter((item) => item.id !== productId);
};

// Payment handling
const setAmountReceived = (amount) => {
    form.amountReceived = amount;
};
const setExactAmount = () => {
    console.log('Nội dung giỏ hàng:', cart.value);
    console.log('Tổng giỏ hàng:', cartTotal.value);
    form.amountReceived = cartTotal.value;
};

const showPayment = () => {

    for (const item of cart.value) {
        const product = products.value.find(p => p.id === item.id);
        
        // Chuyển đổi sang số và đảm bảo là số hợp lệ
        const stockQty = parseFloat(String(product.stock).replace(',', '.'));
        const itemQty = parseFloat(String(item.quantity).replace(',', '.'));
        
        // Kiểm tra nếu cả hai đều là số hợp lệ và stockQty nhỏ hơn itemQty
        if (!isNaN(stockQty) && !isNaN(itemQty) && itemQty > stockQty) {
            errorMessage.value = `Số lượng ${item.name} trong giỏ hàng vượt quá số lượng trong kho.`;
            autoHideMessage();
            return;
        }
    }

    if (!hasActiveSession.value) {
        errorMessage.value = 'Vui lòng mở ca trước khi thanh toán.';
        autoHideMessage();
        return;
    }
    if (cart.value.length === 0) {
        errorMessage.value = 'Giỏ hàng trống. Vui lòng thêm sản phẩm!';
        autoHideMessage();
        return;
    }
    if (!cart.value.every((item) => item.id && item.quantity >= 1)) {
        errorMessage.value = 'Giỏ hàng chứa sản phẩm không hợp lệ. Vui lòng kiểm tra lại.';
        autoHideMessage();
        return;
    }
    billNumber.value = 'HD' + Date.now();
    showPaymentModal.value = true;
};
const cashAmountForCombined = computed(() => {
    if (form.paymentMethod === 'combined' && selectedCustomer.value) {
        const walletBalance = Number(selectedCustomer.value.wallet) || 0;
        const total = cartTotal.value;
        const walletUsed = Math.min(walletBalance, total, Number(walletAmount.value) || 0);
        return total - walletUsed;
    }
    return 0;
});
const combinedChange = computed(() => {
    if (form.paymentMethod === 'combined' && form.amountReceived >= cashAmountForCombined.value) {
        return form.amountReceived - cashAmountForCombined.value;
    }
    return 0;
});
const submitSale = async () => {
    if (!hasActiveSession.value) {
        errorMessage.value = 'Vui lòng mở ca trước khi thanh toán.';
        autoHideMessage();
        return;
    }
    if (cart.value.length === 0) {
        errorMessage.value = 'Giỏ hàng trống. Vui lòng thêm sản phẩm!';
        autoHideMessage();
        return;
    }
    if (!cart.value.every((item) => item.id && item.quantity >= 1)) {
        errorMessage.value = 'Giỏ hàng chứa sản phẩm không hợp lệ.';
        autoHideMessage();
        return;
    }
    if (form.paymentMethod === 'cash' && Number(form.amountReceived) < cartTotal.value) {
        errorMessage.value = 'Số tiền khách đưa không đủ.';
        autoHideMessage();
        return;
    }
    if (form.paymentMethod === 'wallet' && (!selectedCustomer.value || selectedCustomer.value.wallet < cartTotal.value)) {
        errorMessage.value = 'Số dư ví không đủ hoặc chưa chọn khách hàng.';
        autoHideMessage();
        return;
    }
    if (form.paymentMethod === 'bank_transfer' && !billNumber.value) {
        errorMessage.value = 'Mã hóa đơn (orderId) không hợp lệ cho chuyển khoản ngân hàng.';
        autoHideMessage();
        return;
    }
    if (form.paymentMethod === 'combined') {
        if (!selectedCustomer.value || Number(walletAmount.value) > selectedCustomer.value.wallet) {
            errorMessage.value = 'Số dư ví không đủ hoặc chưa chọn khách hàng.';
            autoHideMessage();
            return;
        }
        if (Number(form.amountReceived) < cashAmountForCombined.value) {
            errorMessage.value = 'Số tiền mặt khách đưa không đủ.';
            autoHideMessage();
            return;
        }
        
    }
    form.cart = cart.value;
    form.customer_id = selectedCustomer.value?.id || null;
    try {
        const payload = {
            cart: form.cart.map((item) => ({ id: item.id, quantity: item.quantity })),
            customer_id: form.customer_id,
            paymentMethod: form.paymentMethod,
            amountReceived: form.paymentMethod === 'cash' ? Number(form.amountReceived) : cartTotal.value,
            orderNotes: form.orderNotes || '',
            discount_amount: 0, // Không có khuyến mãi
        };
        if (form.paymentMethod === 'bank_transfer') {
            payload.orderId = billNumber.value || 'HD' + Date.now();
        }
        if (form.paymentMethod === 'combined') {
            payload.walletAmount = Number(walletAmount.value) || 0;
            payload.cashAmount = Number(form.amountReceived) || 0;
        }
        const response = await axios.post('/cashier/pos/sale', payload);
        // Tải lại danh sách sản phẩm
        const productResponse = await axios.get('/cashier/pos/products');
        products.value = productResponse.data.products || [];
        // Tải lại danh sách khách hàng để cập nhật số dư ví
        const customerResponse = await axios.get('/cashier/pos/customers');
        customers.value = customerResponse.data.customers || [];
        if (selectedCustomer.value) {
            const updatedCustomer = customers.value.find(c => c.id === selectedCustomer.value.id);
            if (updatedCustomer) selectedCustomer.value = updatedCustomer;
        }
        cart.value = [];
        form.reset('cart', 'customer_id', 'paymentMethod', 'amountReceived', 'orderNotes', 'printReceipt');
        walletAmount.value = 0; // Reset wallet amount
        clearCustomer();
        hasActiveSession.value = response.data.hasActiveSession || true;
        await fetchShiftReport();
    } catch (error) {
        if (error.response?.status === 422 && error.response.data?.errors?.server?.includes('Ca làm việc đã hết hạn')) {
            errorMessage.value = error.response.data.errors.server;
        } else {
            errorMessage.value = error.response?.data?.errors?.server || 'Lỗi xử lý thanh toán.';
            if (error.response?.data?.errors) {
                Object.values(error.response.data.errors).forEach((err) => (errorMessage.value += ` ${err}`));
            }
        }
        autoHideMessage();
    }
};

const generateBankQR = async () => {
    if (!hasActiveSession.value) {
        errorMessage.value = 'Vui lòng mở ca trước khi tạo mã QR ngân hàng.';
        autoHideMessage();
        return;
    }
    if (cart.value.length === 0) {
        errorMessage.value = 'Giỏ hàng trống. Vui lòng thêm sản phẩm!';
        autoHideMessage();
        return;
    }

    isLoadingBankQR.value = true;
    bankQRCode.value = null;
    bankTransactionInfo.value = null;

    try {
        const amount = cartTotal.value;
        const billCode = billNumber.value || 'HD' + Date.now();
        const description = `Thanh toan hoa don ${billCode}`;
        const encodedDesc = encodeURIComponent(description);
        const accountName = encodeURIComponent('Nguyen Van Huy');

        const qrCodeUrl = `https://img.vietqr.io/image/MB-0986690271-compact2.png?amount=${amount}&addInfo=${encodedDesc}&accountName=${accountName}`;

        bankQRCode.value = qrCodeUrl;
        bankTransactionInfo.value = {
            bankCode: 'MB',
            accountNo: '0986690271',
            accountName: 'Nguyễn Văn Huy',
            amount,
            description,
        };
        successMessage.value = 'Mã QR ngân hàng đã được tạo!';
        showSuccessModal.value = true;
        autoHideMessage();
    } catch (error) {
        errorMessage.value = 'Lỗi khi tạo mã QR ngân hàng. Vui lòng thử lại.';
        autoHideMessage();
    } finally {
        isLoadingBankQR.value = false;
    }
};

const confirmPayment = async () => {
    if (form.paymentMethod === 'cash' && Number(form.amountReceived) < cartTotal.value) {
        errorMessage.value = 'Số tiền khách đưa không đủ.';
        autoHideMessage();
        return;
    }
    if (form.paymentMethod === 'wallet' && (!selectedCustomer.value || selectedCustomer.value.wallet < cartTotal.value)) {
        errorMessage.value = 'Số dư ví không đủ hoặc chưa chọn khách hàng.';
        autoHideMessage();
        return;
    }
    if (form.paymentMethod === 'bank_transfer' && !bankQRCode.value) {
        errorMessage.value = 'Vui lòng tạo mã QR ngân hàng trước khi xác nhận.';
        autoHideMessage();
        return;
    }
    if (form.paymentMethod === 'combined') {
        if (!selectedCustomer.value || selectedCustomer.value.wallet < walletAmount.value) {
            errorMessage.value = 'Số dư ví không đủ hoặc chưa chọn khách hàng.';
            autoHideMessage();
            return;
        }
        if (Number(form.amountReceived) < cashAmountForCombined.value) {
            errorMessage.value = 'Số tiền mặt khách đưa không đủ.';
            autoHideMessage();
            return;
        }
    }
    try {
        const cartSnapshot = [...cart.value];
        const paymentMethodSnapshot = form.paymentMethod;
        const amountReceivedSnapshot = form.paymentMethod === 'cash' ? form.amountReceived : cartTotal.value;
        const customerSnapshot = { ...selectedCustomer.value };
        await submitSale();
        if (form.printReceipt) {
            printReceipt(cartSnapshot, paymentMethodSnapshot, amountReceivedSnapshot, customerSnapshot);
        }
        showPaymentModal.value = false;
        successMessage.value = 'Thanh toán thành công!';
        showSuccessModal.value = true;
        bankQRCode.value = null;
        bankTransactionInfo.value = null;
        walletAmount.value = 0; // Reset wallet amount
        autoHideMessage();
    } catch (error) {
        if (error.response?.status === 422 && error.response.data?.errors?.server?.includes('Ca làm việc đã hết hạn')) {
            errorMessage.value = error.response.data.errors.server;
        } else {
            errorMessage.value = error.response?.data?.message || error.response?.data?.errors?.server || 'Lỗi thanh toán.';
            if (error.response?.data?.errors) {
                Object.values(error.response.data.errors).forEach((err) => (errorMessage.value += ` ${err}`));
            }
        }
        autoHideMessage();
    }
};

const printReceipt = (
    cartData = cart.value,
    paymentMethod = form.paymentMethod,
    amountReceived = form.amountReceived,
    customer = selectedCustomer.value,
) => {
    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        errorMessage.value = 'Không thể mở cửa sổ in. Vui lòng kiểm tra trình chặn popup.';
        autoHideMessage();
        return;
    }
<<<<<<< HEAD

    const subtotal = cartData.reduce((total, item) => total + item.price * item.quantity, 0);
=======
    const subtotal = cartData.reduce((total, item) => total + (item.price * item.quantity), 0);
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
    const tax = 0;
    const total = subtotal + tax;
    try {
        printWindow.document.write(`
      <html>
        <head>
          <title>Hóa đơn #${sanitizeHTML(billNumber.value)}</title>
          <style>
            @media print {
              @page { size: 80mm auto; margin: 5mm; }
              body {
                font-family: 'Courier New', monospace;
                font-size: 10pt;
                width: 80mm;
                margin: 0;
                padding: 5mm;
                line-height: 1.4;
                color: #000;
                background: #fff;
              }
              .header {
                text-align: center;
                border-bottom: 2px dashed #000;
                padding-bottom: 8px;
                margin-bottom: 8px;
              }
              .store-info {
                font-size: 9pt;
                text-align: center;
                margin-bottom: 8px;
              }
              .bill-info {
                font-size: 9pt;
                margin-bottom: 8px;
              }
              table {
                width: 100%;
                border-collapse: collapse;
                margin: 8px 0;
              }
              th, td {
                padding: 4px 2px;
                font-size: 9pt;
                text-align: left;
              }
              th {
                border-bottom: 1px solid #000;
                font-weight: bold;
              }
              .text-right {
                text-align: right;
              }
              .text-center {
                text-align: center;
              }
              .total-section {
                border-top: 1px dashed #000;
                padding-top: 8px;
                margin-top: 8px;
                font-size: 9pt;
              }
              .total {
                font-size: 10pt;
                font-weight: bold;
              }
              .footer {
                text-align: center;
                font-size: 8pt;
                margin-top: 8px;
                border-top: 2px dashed #000;
                padding-top: 8px;
              }
              .no-print {
                display: none;
              }
            }
            body {
              font-family: 'Courier New', monospace;
              font-size: 10pt;
              width: 80mm;
              margin: 10px auto;
              padding: 5mm;
              line-height: 1.4;
              color: #000;
              background: #fff;
              border: 1px solid #ccc;
              box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            .no-print {
              display: block;
              margin-top: 10px;
              width: 100%;
              padding: 5px;
              background: #007bff;
              color: #fff;
              border: none;
              border-radius: 3px;
              cursor: pointer;
              text-align: center;
            }
          </style>
        </head>
        <body>
          <div class="header">
            <h2 style="margin: 0; font-size: 12pt;">Hóa Đơn Bán Hàng</h2>
            <div class="store-info">
              <p style="margin: 2px 0;">Cửa hàng bán lẻ G7 Mart</p>
              <p style="margin: 2px 0;">Trịnh Văn Bô, Hà Nội</p>
              <p style="margin: 2px 0;">Hotline: 4444 6666</p>
            </div>
          </div>
          <div class="bill-info">
            <p><strong>Mã hóa đơn:</strong> ${sanitizeHTML(billNumber.value)}</p>
            <p><strong>Ngày giờ:</strong> ${sanitizeHTML(formatDateTime(DateTime.now().toISO()))}</p>
            <p><strong>Nhân viên:</strong> ${sanitizeHTML(props.auth?.user?.name || 'N/A')}</p>
            <p><strong>Khách hàng:</strong> ${sanitizeHTML(customer?.customer_name || 'Khách lẻ')}</p>
          </div>
          <table>
            <thead>
              <tr>
                <th style="width: 40%;">Tên hàng</th>
                <th style="width: 15%; text-align: center;">SL</th>
                <th style="width: 25%; text-align: right;">Đơn giá</th>
                <th style="width: 20%; text-align: right;">Thành tiền</th>
              </tr>
            </thead>
            <tbody>
              ${
                  cartData.length > 0
                      ? cartData
                            .map(
                                (item) => `
                <tr>
                  <td>${sanitizeHTML(item.name || 'Unknown')}</td>
                  <td class="text-center">${item.quantity || 0}</td>
                  <td class="text-right">${sanitizeHTML(formatCurrency(item.price || 0))}</td>
                  <td class="text-right">${sanitizeHTML(formatCurrency((item.price || 0) * (item.quantity || 0)))}</td>
                </tr>
              `,
                            )
                            .join('')
                      : `
                <tr>
                  <td colspan="4" class="text-center">Không có sản phẩm</td>
                </tr>
              `
              }
            </tbody>
          </table>
          <div class="total-section">
            <p class="text-right"><strong>Tổng tiền hàng:</strong> ${sanitizeHTML(formatCurrency(subtotal))}</p>
            <p class="text-right"><strong>Giảm giá:</strong> ${sanitizeHTML(formatCurrency(0))}</p>
            <p class="text-right total"><strong>Tổng thanh toán:</strong> ${sanitizeHTML(formatCurrency(total))}</p>
            <p class="text-right"><strong>Phương thức:</strong> ${sanitizeHTML(
<<<<<<< HEAD
                paymentMethod === 'cash'
                    ? 'Tiền mặt'
                    : paymentMethod === 'credit_card'
                      ? 'Thẻ ngân hàng'
                      : paymentMethod === 'bank_transfer'
                        ? 'Chuyển khoản ngân hàng'
                        : 'Ví khách hàng',
            )}</p>
            ${
                paymentMethod === 'cash'
                    ? `
=======
            paymentMethod === 'cash' ? 'Tiền mặt' :
                paymentMethod === 'credit_card' ? 'Thẻ ngân hàng' :
                    paymentMethod === 'bank_transfer' ? 'Chuyển khoản ngân hàng' :
                        paymentMethod === 'wallet' ? 'Ví khách hàng' :
                            'Kết hợp (Ví + Tiền mặt)'
        )}</p>
            ${paymentMethod === 'cash' ? `
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
              <p class="text-right"><strong>Khách đưa:</strong> ${sanitizeHTML(formatCurrency(Number(amountReceived) || 0))}</p>
              <p class="text-right"><strong>Tiền thối:</strong> ${sanitizeHTML(formatCurrency(Number(amountReceived) - total || 0))}</p>
            `
                    : paymentMethod === 'bank_transfer' && bankTransactionInfo.value
                      ? `
              <p class="text-right"><strong>Mô tả:</strong> ${sanitizeHTML(bankTransactionInfo.value.description)}</p>
<<<<<<< HEAD
            `
                      : ''
            }
=======
            ` : paymentMethod === 'combined' ? `
              <p class="text-right"><strong>Thanh toán bằng ví:</strong> ${sanitizeHTML(formatCurrency(Number(walletAmount.value) || 0))}</p>
              <p class="text-right"><strong>Thanh toán bằng tiền mặt:</strong> ${sanitizeHTML(formatCurrency(Number(form.amountReceived) || 0))}</p>
              <p class="text-right"><strong>Tiền thối:</strong> ${sanitizeHTML(formatCurrency(Number(form.amountReceived) - cashAmountForCombined.value || 0))}</p>
            ` : ''}
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
            <p class="text-right"><strong>Ghi chú:</strong> ${sanitizeHTML(form.orderNotes || 'Không có')}</p>
          </div>
          <div class="footer">
            <p>Cảm ơn quý khách!</p>
            <p>Vui lòng kiểm tra hàng trước khi rời đi.</p>
          </div>
          <button class="no-print" onclick="window.print(); window.close();">In hóa đơn</button>
        </body>
      </html>
    `);
        printWindow.document.close();
        printWindow.onload = () => {
            printWindow.focus();
            printWindow.print();
        };
    } catch (error) {
        errorMessage.value = 'Lỗi khi tạo hóa đơn in. Vui lòng thử lại.';
        autoHideMessage();
        printWindow.close();
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
    event.target.src = '/storage/default-product.png';
    product.image = '/storage/default-product.png';
};

const openProductModal = (product) => {
    selectedProduct.value = { ...product };
};

const toggleFilterSidebar = () => {
    showFilterSidebar.value = !showFilterSidebar.value;
    showCustomerSidebar.value = false;
    showReportSidebar.value = false;
};

const toggleCustomerSidebar = () => {
    showCustomerSidebar.value = !showCustomerSidebar.value;
    showFilterSidebar.value = false;
    showReportSidebar.value = false;
};

const toggleReportSidebar = async () => {
    showReportSidebar.value = !showReportSidebar.value;
    showFilterSidebar.value = false;
    showCustomerSidebar.value = false;
    if (showReportSidebar.value && !shiftReport.value && hasActiveSession.value) {
        await fetchShiftReport();
    }
};

const selectCustomer = (customer) => {
    selectedCustomer.value = customer;
    form.customer_id = customer.id;
    showCustomerSidebar.value = false;
    customerSearch.value = '';
};

const clearCustomer = () => {
    selectedCustomer.value = null;
    form.customer_id = null;
};

const toggleAddCustomerForm = () => {
    showAddCustomerForm.value = !showAddCustomerForm.value;
    if (showAddCustomerForm.value) {
        form.reset('customer_name', 'email', 'phone', 'address', 'wallet');
        errorMessage.value = '';
        nextTick(() => document.querySelector('#customer_name')?.focus());
    } else {
        form.clearErrors();
    }
};

const submitNewCustomer = async () => {
    const data = {
        customer_name: form.customer_name?.trim() || '',
        phone: form.phone?.trim() || '',
        email: form.email?.trim() || null,
        address: form.address?.trim() || null,
        wallet: Number(form.wallet) || 0,
    };

    if (!data.customer_name || !data.phone) {
        errorMessage.value = 'Tên và số điện thoại là bắt buộc.';
        autoHideMessage();
        return;
    }

    try {
        const response = await axios.post('/cashier/pos/customers', data);
        customers.value = response.data.customers;
        customerSearch.value = '';
        showCustomerSidebar.value = true;
        successMessage.value = response.data.success || 'Thêm khách hàng thành công!';
        showSuccessModal.value = true;
        toggleAddCustomerForm();
        if (response.data.newCustomer) selectCustomer(response.data.newCustomer);
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Lỗi thêm khách hàng.';
        if (error.response?.data?.errors) {
            Object.values(error.response.data.errors).forEach((err) => (errorMessage.value += ` ${err}`));
        }
        autoHideMessage();
    }
};

const initiateLogout = () => {
    if (hasActiveSession.value) {
        showLogoutModal.value = true;
    } else {
        performLogout(false);
    }
};

const performLogout = async (closeShiftFlag = false) => {
    try {
        if (closeShiftFlag) {
            if (!confirm('Đóng ca trước khi đăng xuất?')) {
                await axios.post('/cashier/logout');
                window.location.href = '/cashier/login';
                return;
            }
            sessionForm.notes =
                sessionForm.selected_default_note === 'Khác (vui lòng ghi rõ)' ? sessionForm.custom_note : sessionForm.selected_default_note;
            await axios.post('/cashier/pos/session/close', {
                closing_amount: Number(sessionForm.closing_amount) || 0,
                notes: sessionForm.notes || '',
            });
            hasActiveSession.value = false;
            activeShift.value = null;
            shiftReport.value = null;
            successMessage.value = 'Ca đã đóng!';
            showSuccessModal.value = true;
            autoHideMessage();
        }
        await axios.post('/cashier/logout');
        window.location.href = '/cashier/login';
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Lỗi đăng xuất.';
        showLogoutModal.value = false;
        autoHideMessage();
    }
};

const closeModalsAndSidebars = () => {
    selectedProduct.value = null;
    showFilterSidebar.value = false;
    showCustomerSidebar.value = false;
    showReportSidebar.value = false;
    showLogoutModal.value = false;
    showPaymentModal.value = false;
    customerSearch.value = '';
    showAddCustomerForm.value = false;
    errorMessage.value = '';
};

const handleKeydown = (event) => {
    if (event.key === 'Escape') closeModalsAndSidebars();
    if (event.key === 'F9') {
        event.preventDefault();
        createNewOrder();
    }
};

const handleClickOutside = (event) => {
    if (selectedProduct.value && !event.target.closest('.modal-content')) selectedProduct.value = null;
    if (showLogoutModal.value && !event.target.closest('.logout-modal-content')) showLogoutModal.value = false;
    if (showPaymentModal.value && !event.target.closest('.modal-content')) showPaymentModal.value = false;
};

const showHelp = () => alert('Chức năng trợ giúp (F1) chưa triển khai.');
const addItem = () => alert('Chức năng thêm sản phẩm (F2) chưa triển khai.');
const focusSearch = () => document.querySelector('input[placeholder*="Tìm kiếm sản phẩm (F3)"]')?.focus();
const reprintReceipt = () => alert('Chức năng in lại hóa đơn (F7) chưa triển khai.');
const removeLastCartItem = () => {
    if (cart.value.length > 0) cart.value.pop();
};
onMounted(async () => {
    console.log('Danh sách sản phẩm:', products.value);
    window.addEventListener('keydown', handleKeydown);
    window.addEventListener('click', handleClickOutside);
    if (hasActiveSession.value) {
        await fetchShiftReport();
    }
    if (props.flash?.success) {
        showSuccessModal.value = true;
        autoHideMessage();
    }
    const savedOrders = localStorage.getItem('pendingOrders');
    pendingOrders.value = savedOrders ? JSON.parse(savedOrders) : [];
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    window.removeEventListener('click', handleClickOutside);
});
const refreshProducts = async () => {
    try {
        const response = await axios.get('/cashier/pos/products');
        products.value = response.data.products || [];
        console.log('Refreshed products:', products.value);
    } catch (error) {
        console.error('Error refreshing products:', error);
        errorMessage.value = 'Lỗi tải danh sách sản phẩm.';
        autoHideMessage();
    }
};
let isProcessingBarcode = false;

const searchByBarcode = async (barcode) => {
    if (isProcessingBarcode) return;
    isProcessingBarcode = true;

    try {
        const response = await axios.get(`/cashier/pos/product/barcode/${encodeURIComponent(barcode)}`, {
            params: { t: Date.now() },
        });
        const product = response.data.product;
        if (product && product.stock_quantity > 0) {
            if (!product.price || isNaN(Number(product.price))) {
                errorMessage.value = `Sản phẩm ${product.name} có giá không hợp lệ.`;
                await refreshProducts();
                autoHideMessage();
                return;
            }
            await addToCart(product);
            searchTerm.value = '';
            successMessage.value = `Đã thêm ${product.name} vào giỏ hàng!`;
            showSuccessModal.value = true;
            autoHideMessage();
            nextTick(() => {
                const searchInput = document.querySelector('input[placeholder*="Tìm kiếm sản phẩm (F3)"]');
                if (searchInput) searchInput.focus();
            });
        } else {
            errorMessage.value = product ? `Sản phẩm ${product.name} đã hết hàng.` : 'Không tìm thấy sản phẩm.';
            await refreshProducts();
            autoHideMessage();
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Lỗi tìm kiếm mã vạch.';
        await refreshProducts();
        autoHideMessage();
    } finally {
        isProcessingBarcode = false;
    }
};

// Cải thiện phần thanh toán kết hợp
watch(() => form.paymentMethod, (newMethod) => {
    if (newMethod === 'combined' && selectedCustomer.value) {
        walletAmount.value = Math.min(selectedCustomer.value.wallet, cartTotal.value); // Tự động set walletAmount tối đa có thể
        form.amountReceived = 0; // Reset tiền mặt
    }
});

watch(walletAmount, (newValue) => {
    const walletBalance = selectedCustomer.value ? Number(selectedCustomer.value.wallet) : 0;
    const maxWallet = Math.min(walletBalance, cartTotal.value);
    if (newValue > maxWallet) {
        walletAmount.value = maxWallet;
        errorMessage.value = 'Số tiền ví không được vượt quá số dư hoặc tổng đơn.';
        autoHideMessage();
    } else if (newValue < 0) {
        walletAmount.value = 0;
    }
    // Tự động cập nhật tiền mặt cần trả khi thay đổi walletAmount
    form.amountReceived = Math.max(form.amountReceived, cashAmountForCombined.value);
});

watch(cashAmountForCombined, (newValue) => {
    if (form.amountReceived < newValue) {
        form.amountReceived = newValue; // Đảm bảo tiền mặt tối thiểu
    }
});

</script>

<template>
    <Head title="Cashier" />
    <CashierLayout>
        <POSKeyboardHandler
            @show-help="showHelp"
            @add-item="addItem"
            @focus-search="focusSearch"
            @select-customer="toggleCustomerSidebar"
            @hold-order="holdOrder"
            @reprint-receipt="reprintReceipt"
            @remove-last-cart-item="removeLastCartItem"
            @checkout="showPaymentModal"
            @logout="initiateLogout"
            :on-checkout="showPayment"
        />
        <div class="relative flex h-[650px] text-xs">
            <!-- Error Message -->
            <div
                v-if="errorMessage"
                class="fixed top-4 left-1/2 z-50 flex -translate-x-1/2 items-center rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700 shadow-lg"
            >
                {{ errorMessage }}
                <button @click="errorMessage = ''" class="ml-2 text-red-900 hover:text-red-700">✖</button>
            </div>
            <!-- Success Message -->
            <div
                v-if="showSuccessModal"
                class="fixed top-4 left-1/2 z-50 flex -translate-x-1/2 items-center rounded-lg bg-green-100 px-4 py-2 text-sm text-green-700 shadow-lg"
            >
                {{ successMessage }}
                <button @click="showSuccessModal = false" class="ml-2 text-green-900 hover:text-green-700">✖</button>
            </div>

            <!-- Filter Sidebar -->
            <div
                :class="{ 'translate-x-full': !showFilterSidebar, 'translate-x-0': showFilterSidebar }"
                class="fixed inset-y-0 right-0 z-50 w-80 transform bg-white shadow-xl transition-transform duration-300"
            >
                <div class="flex h-full flex-col p-3">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-semibold">Bộ lọc</h3>
                        <button @click="toggleFilterSidebar" class="rounded bg-gray-300 px-2 py-1 text-xs text-gray-800 hover:bg-gray-400">
                            Đóng
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <div>
                            <p class="mt-0.5 text-[11px] text-gray-600">Sản phẩm phù hợp: {{ filteredProducts.length }}</p>
                            <div class="mb-2">
<<<<<<< HEAD
                                <label class="mb-1 block text-xs font-medium text-gray-700">Danh mục:</label>
                                <select
                                    v-model="selectedCategory"
                                    class="w-full rounded border border-gray-300 p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                >
                                    <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
=======
                                <label class="block text-xs font-medium text-gray-700 mb-1">Danh mục:</label>
                                <select v-model="selectedCategory"
                                    class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option v-for="category in categories" :key="category" :value="category">{{ category
                                        }}</option>
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="mb-1 block text-xs font-medium text-gray-700">Khoảng giá (VND):</label>
                                <div class="flex space-x-2">
                                    <input
                                        type="number"
                                        v-model="priceRange.min"
                                        placeholder="Từ"
                                        class="w-1/2 rounded border border-gray-300 p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <input
                                        type="number"
                                        v-model="priceRange.max"
                                        placeholder="Đến"
                                        class="w-1/2 rounded border border-gray-300 p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    />
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="mb-1 block text-xs font-medium text-gray-700">Số lượng tồn:</label>
                                <div class="flex space-x-2">
                                    <input
                                        type="number"
                                        v-model="stockRange.min"
                                        placeholder="Từ"
                                        class="w-1/2 rounded border border-gray-300 p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <input
                                        type="number"
                                        v-model="stockRange.max"
                                        placeholder="Đến"
                                        class="w-1/2 rounded border border-gray-300 p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    />
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="mb-1 block text-xs font-medium text-gray-700">Sắp xếp theo:</label>
                                <select
                                    v-model="sortBy"
                                    class="w-full rounded border border-gray-300 p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                >
                                    <option value="none">Không sắp xếp</option>
                                    <option value="price">Giá</option>
                                    <option value="stock">Số lượng tồn</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="mb-1 block text-xs font-medium text-gray-700">Thứ tự:</label>
                                <select
                                    v-model="sortOrder"
                                    class="w-full rounded border border-gray-300 p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                >
                                    <option value="asc">Tăng dần</option>
                                    <option value="desc">Giảm dần</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Customer Sidebar -->
            <div
                :class="{ 'translate-x-full': !showCustomerSidebar, 'translate-x-0': showCustomerSidebar }"
                class="fixed inset-y-0 right-0 z-50 w-80 transform bg-white shadow-xl transition-transform duration-300"
            >
                <div class="flex h-full flex-col p-3">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-semibold">Chọn khách hàng</h3>
                        <button @click="toggleCustomerSidebar" class="rounded bg-gray-300 px-2 py-1 text-xs text-gray-800 hover:bg-gray-400">
                            Đóng
                        </button>
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            v-model="customerSearch"
                            placeholder="Tìm kiếm khách hàng (tên, email, số điện thoại)"
                            class="w-full rounded border border-gray-300 p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>
                    <div class="mb-2">
                        <button @click="toggleAddCustomerForm" class="w-full rounded bg-blue-100 py-1 text-xs text-blue-700 hover:bg-blue-200">
                            {{ showAddCustomerForm ? 'Hủy thêm khách hàng' : 'Thêm khách hàng mới' }}
                        </button>
                    </div>
                    <div v-if="showAddCustomerForm" class="mb-2">
                        <form class="space-y-2" @submit.prevent="submitNewCustomer">
                            <div>
                                <label for="customer_name" class="mb-1 block text-xs font-medium text-gray-700"
                                    >Tên khách hàng <span class="text-red-500">*</span></label
                                >
                                <input
                                    type="text"
                                    id="customer_name"
                                    v-model="form.customer_name"
                                    placeholder="Nhập tên khách hàng"
                                    class="w-full rounded border border-gray-300 p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.customer_name }"
                                />
                                <InputError class="mt-0.5 text-[10px]" :message="form.errors.customer_name" />
                            </div>
                            <div>
                                <label for="email" class="mb-1 block text-xs font-medium text-gray-700">Email</label>
                                <input
                                    type="email"
                                    id="email"
                                    v-model="form.email"
                                    placeholder="Nhập email"
                                    class="w-full rounded border border-gray-300 p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <InputError class="mt-0.5 text-[10px]" :message="form.errors.email" />
                            </div>
                            <div>
                                <label for="phone" class="mb-1 block text-xs font-medium text-gray-700"
                                    >Số điện thoại <span class="text-red-500">*</span></label
                                >
                                <input
                                    type="tel"
                                    id="phone"
                                    v-model="form.phone"
                                    placeholder="Nhập số điện thoại"
                                    class="w-full rounded border border-gray-300 p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.phone }"
                                />
                                <InputError class="mt-0.5 text-[10px]" :message="form.errors.phone" />
                            </div>
                            <div>
                                <label for="address" class="mb-1 block text-xs font-medium text-gray-700">Địa chỉ</label>
                                <input
                                    type="text"
                                    id="address"
                                    v-model="form.address"
                                    placeholder="Nhập địa chỉ khách hàng"
                                    class="w-full rounded border border-gray-300 p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.address }"
                                />
                                <InputError class="mt-0.5 text-[10px]" :message="form.errors.address" />
                            </div>
                            <div class="flex space-x-2">
                                <button
                                    type="button"
                                    @click="toggleAddCustomerForm"
                                    class="w-1/2 rounded bg-gray-300 py-1.5 text-xs text-gray-700 hover:bg-gray-400"
                                >
                                    Hủy
                                </button>
                                <button
                                    type="submit"
                                    class="w-1/2 rounded bg-blue-600 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                                    :disabled="form.processing"
                                >
                                    Lưu khách hàng
                                </button>
                            </div>
                        </form>
                    </div>
                    <div v-else class="flex-1 overflow-y-auto">
                        <div
                            v-for="customer in filteredCustomers"
                            :key="customer.id"
                            class="cursor-pointer border-b border-gray-200 p-2 hover:bg-gray-100"
                            @click="selectCustomer(customer)"
                        >
                            <p class="font-semibold">{{ customer.customer_name }}</p>
                            <p v-if="customer.phone" class="text-[10px] text-gray-600">{{ customer.phone }}</p>
                            <p v-if="customer.address" class="text-[10px] text-gray-600">{{ customer.address }}</p>
                            <p class="text-[10px] text-gray-600">Ví: {{ formatCurrency(customer.wallet) }}</p>
                        </div>
                        <div v-if="filteredCustomers.length === 0" class="py-2 text-center text-[11px] text-gray-500">Không tìm thấy khách hàng</div>
                    </div>
                </div>
            </div>
            <!-- Report Sidebar -->
            <div
                :class="{ 'translate-x-full': !showReportSidebar, 'translate-x-0': showReportSidebar }"
                class="fixed inset-y-0 right-0 z-50 w-80 transform rounded-l-lg bg-gradient-to-b from-blue-50 to-white shadow-2xl transition-transform duration-300"
            >
                <div class="flex h-full flex-col p-4">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="flex items-center text-base font-bold text-gray-800">
                            <FileText class="mr-2 h-5 w-5 text-blue-600" />
                            Báo cáo ca làm việc
                        </h3>
                        <button @click="toggleReportSidebar" class="rounded bg-gray-200 px-2 py-1 text-xs text-gray-800 transition hover:bg-gray-300">
                            Đóng
                        </button>
                    </div>
                    <div class="flex-1 space-y-4 overflow-y-auto">
                        <div v-if="!hasActiveSession" class="space-y-3 rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                            <h4 class="text-xs font-semibold text-gray-800">Mở ca mới</h4>
                            <form @submit.prevent="openShift">
                                <div class="mb-2">
                                    <label for="opening_amount" class="mb-1 block text-xs font-medium text-gray-700">Số tiền mở ca (VND)</label>
                                    <input
                                        type="number"
                                        v-model.number="sessionForm.opening_amount"
                                        class="w-full rounded border border-gray-300 bg-white p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        id="opening_amount"
                                        min="0"
                                        required
                                    />
                                    <InputError class="mt-0.5 text-[10px]" :message="sessionForm.errors.opening_amount" />
                                </div>
                                <div class="mb-2">
                                    <label for="notes" class="mb-1 block text-xs font-medium text-gray-700">Ghi chú</label>
                                    <select
                                        v-model="sessionForm.selected_default_note"
                                        class="w-full rounded border border-gray-300 bg-white p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        id="notes_select"
                                    >
                                        <option value="" disabled>Chọn ghi chú</option>
                                        <option v-for="note in defaultNotes" :key="note" :value="note">{{ note }}</option>
                                    </select>
                                    <textarea
                                        v-if="sessionForm.selected_default_note === 'Khác (vui lòng ghi rõ)'"
                                        v-model="sessionForm.custom_note"
                                        class="mt-1 w-full rounded border border-gray-300 bg-white p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        id="notes_custom"
                                        rows="3"
                                        placeholder="Nhập ghi chú tùy chỉnh"
                                    ></textarea>
                                </div>
                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-green-600 py-2 text-xs font-semibold text-white shadow-md transition hover:bg-green-700"
                                    :disabled="isLoadingSessionAction || sessionForm.processing"
                                >
                                    <Loader2 v-if="isLoadingSessionAction || sessionForm.processing" class="mr-1 inline-block h-4 w-4 animate-spin" />
                                    Mở Ca
                                </button>
                            </form>
                        </div>
                        <div v-else class="space-y-3 rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                            <h4 class="text-xs font-semibold text-gray-800">Thông tin ca hiện tại</h4>
                            <div class="space-y-1 text-[11px]">
                                <p><strong>Ca làm việc:</strong> {{ activeShift?.shift_name || 'Ca hiện tại' }}</p>
                                <p><strong>Bắt đầu:</strong> {{ formattedOpenedAt }}</p>
                            </div>
                            <h4 class="mt-3 text-xs font-semibold text-gray-800">Đóng ca</h4>
                            <form @submit.prevent="closeShift">
                                <div class="mb-2">
                                    <label for="closing_amount" class="mb-1 block text-xs font-medium text-gray-700">Số tiền đóng ca (VND)</label>
                                    <input
                                        type="number"
                                        v-model.number="sessionForm.closing_amount"
                                        class="w-full rounded border border-gray-300 bg-white p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        id="closing_amount"
                                        min="0"
                                        required
                                    />
                                    <InputError class="mt-0.5 text-[10px]" :message="sessionForm.errors.closing_amount" />
                                </div>
                                <div class="mb-2">
                                    <label for="notes" class="mb-1 block text-xs font-medium text-gray-700">Ghi chú</label>
                                    <select
                                        v-model="sessionForm.selected_default_note"
                                        class="w-full rounded border border-gray-300 bg-white p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        id="notes_select"
                                    >
                                        <option value="" disabled>Chọn ghi chú</option>
                                        <option v-for="note in defaultNotes" :key="note" :value="note">{{ note }}</option>
                                    </select>
                                    <textarea
                                        v-if="sessionForm.selected_default_note === 'Khác (vui lòng ghi rõ)'"
                                        v-model="sessionForm.custom_note"
                                        class="mt-1 w-full rounded border border-gray-300 bg-white p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        id="notes_custom"
                                        rows="3"
                                        placeholder="Nhập ghi chú tùy chỉnh"
                                    ></textarea>
                                </div>
                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-red-600 py-2 text-xs font-semibold text-white shadow-md transition hover:bg-red-700"
                                    :disabled="isLoadingSessionAction || sessionForm.processing"
                                >
                                    <Loader2 v-if="isLoadingSessionAction || sessionForm.processing" class="mr-1 inline-block h-4 w-4 animate-spin" />
                                    Đóng Ca
                                </button>
                            </form>
                        </div>
                        <div v-if="isLoadingReport" class="py-4 text-center text-gray-500">
                            <Loader2 class="mx-auto h-5 w-5 animate-spin text-blue-500" />
                            Đang tải báo cáo...
                        </div>
                    </div>
                </div>
            </div>
            <!-- Main Content -->
            <div class="flex h-full w-2/3 flex-col bg-white">
                <div class="border-b border-gray-200 p-1.5">
                    <div class="mb-1 flex items-center justify-between">
                        <div class="flex items-center space-x-1">
                            <!-- Nút mở báo cáo -->
<<<<<<< HEAD
                            <button
                                @click="toggleReportSidebar"
                                class="rounded bg-gray-200 px-2 py-0.5 text-xs text-gray-700 hover:bg-gray-300"
                                title="Xem báo cáo ca"
                            >
                                <FileText class="h-4 w-4" />
                            </button>
                            <!-- Nút mở bộ lọc -->
                            <button
                                @click="toggleFilterSidebar"
                                class="rounded bg-gray-200 px-2 py-0.5 text-xs text-gray-700 hover:bg-gray-300"
                                title="Bộ lọc báo cáo"
                            >
                                <MenuIcon class="h-4 w-4" />
                            </button>
                            <div class="mt-1 flex items-center justify-between text-[11px] text-gray-600">
                                <button
                                    v-if="
                                        searchTerm ||
                                        selectedCategory !== 'Tất cả' ||
                                        priceRange.min ||
                                        priceRange.max ||
                                        stockRange.min ||
                                        stockRange.max ||
                                        sortBy !== 'none'
                                    "
                                    @click="resetFilters"
                                    class="text-blue-500 hover:underline focus:outline-none"
                                >
                                    <X class="mr-1 inline-block h-4 w-4" />
=======
<button @click="toggleReportSidebar"
    class="flex items-center gap-1 bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs hover:bg-blue-200"
    title="Xem báo cáo ca làm việc">
    <FileText class="w-4 h-4" />
    <span>Ca làm</span>
</button>

<!-- Nút mở bộ lọc -->
<button @click="toggleFilterSidebar"
    class="flex items-center gap-1 bg-green-100 text-green-700 px-2 py-1 rounded text-xs hover:bg-green-200"
    title="Bộ lọc dữ liệu báo cáo">
    <MenuIcon class="w-4 h-4" />
    <span>Bộ lọc</span>
</button>

                            <div class="flex justify-between items-center mt-1 text-[11px] text-gray-600">
                                <button v-if="
                                    searchTerm ||
                                    selectedCategory !== 'Tất cả' ||
                                    priceRange.min ||
                                    priceRange.max ||
                                    stockRange.min ||
                                    stockRange.max ||
                                    sortBy !== 'none'
                                " @click="resetFilters" class="text-blue-500 hover:underline focus:outline-none">
                                    <X class="w-4 h-4 inline-block mr-1" />
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
                                </button>
                            </div>
                            <h2 class="text-base font-semibold">Sản phẩm</h2>
                        </div>
                        <div v-if="activeShift" class="text-[11px] text-gray-600">
<<<<<<< HEAD
                            <p><strong>Ca làm việc:</strong> {{ activeShift.shift_name }} (Bắt đầu: {{ formattedOpenedAt }})</p>
=======
                            <p><strong>Ca làm việc:</strong> {{ activeShift.shift_name }} (Bắt đầu: {{ formattedOpenedAt
                                }})</p>
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
                        </div>
                    </div>
                    <div class="mb-1">
                        <input
                            type="text"
                            v-model="searchTerm"
                            placeholder="Tìm kiếm sản phẩm hoặc quét mã vạch (F3)"
                            class="w-full rounded border border-gray-300 p-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            @keydown.enter.prevent="searchByBarcode(cleanBarcode(searchTerm))"
                            @input="console.log('searchTerm:', searchTerm.value)"
                        />
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-2">
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6">
                        <div
                            v-for="product in filteredProducts"
                            :key="product.id"
                            class="relative flex cursor-pointer flex-col items-center rounded-lg bg-gray-50 p-1.5 text-center shadow-sm transition-all duration-200 hover:scale-105 hover:shadow-md active:scale-95"
                            :class="{ 'cursor-not-allowed opacity-60': product.stock === 0 }"
                            @click="product.stock > 0 && addToCart(product)"
                        >
                            <button @click.stop="openProductModal(product)" class="absolute top-1 right-1 z-10 shadow hover:bg-gray-100">
                                <BadgeInfo class="h-3 w-3" />
                            </button>
                            <img
                                :src="product.image"
                                :alt="product.name"
                                class="mb-1 h-12 w-12 rounded object-cover"
                                @error="handleImageError($event, product)"
                            />
                            <h3 class="w-full truncate text-[10px] font-medium">{{ product.name }}</h3>
                            <p v-if="product.stock <= 10 && product.stock > 0" class="mt-0.5 text-[10px] font-semibold text-orange-500">
                                Tồn: {{ product.stock }}
                            </p>
                            <p v-else-if="product.stock === 0" class="mt-0.5 text-[10px] font-semibold text-red-500">Hết hàng</p>
                            <p v-else class="mt-0.5 text-[10px] text-gray-500">Tồn: {{ product.stock }}</p>
                            <p class="mt-0.5 text-[10px]">
                                {{ product.price != null ? formatCurrency(product.price) : 'Giá không xác định' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Cart Section -->
            <div class="flex h-full w-1/3 flex-col bg-white">
                <div class="border-b border-gray-200 p-2">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold">Giỏ hàng</h2>
                        <div class="flex space-x-1">
<<<<<<< HEAD
                            <button class="rounded bg-gray-200 px-2 py-0.5 text-xs text-gray-700 hover:bg-gray-300" @click="newOrder">
=======
                            <button class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs hover:bg-gray-300"
                                @click="createNewOrder">
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
                                Đơn mới (F9)
                            </button>
                            <button class="rounded bg-yellow-500 px-2 py-0.5 text-xs text-white hover:bg-yellow-600" @click="holdOrder">
                                Lưu đơn (F6)
                            </button>
                        </div>
                    </div>
                </div>
                <div class="border-b border-gray-200 p-2">
                    <h3 class="mb-1 text-xs font-semibold">Đơn hàng chờ:</h3>
                    <div class="flex max-h-16 flex-wrap gap-1 overflow-y-auto">
                        <div v-if="pendingOrders.length === 0" class="relative rounded border border-gray-200 bg-gray-100 p-1.5 text-[10px]">
                            Không có đơn hàng chờ
                        </div>
                        <div
                            v-for="order in pendingOrders"
                            :key="order.id"
                            class="relative cursor-pointer rounded-lg border border-gray-200 bg-gray-100 p-2 text-[10px] shadow-sm transition-all hover:bg-gray-200"
                            @click="restoreOrder(order.id)"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">Đơn {{ order.id }}</span>
                                <button @click.stop="removeOrder(order.id)" class="text-xs text-red-600 hover:text-red-800">
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                            <p>{{ order.cart.length }} sản phẩm</p>
                            <p>{{ formatCurrency(order.cart.reduce((sum, item) => sum + item.price * item.quantity, 0)) }}</p>
                            <p class="text-gray-500">{{ formatDateTime(order.timestamp) }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-2">
                    <div v-if="cart.length === 0" class="py-4 text-center text-xs text-gray-500">Giỏ hàng trống. Vui lòng thêm sản phẩm (F2)!</div>
                    <div v-else>
                        <div v-for="item in cart" :key="item.id" class="flex items-center justify-between border-b border-gray-200 p-2">
                            <div class="flex-1">
                                <p class="text-xs font-medium">{{ item.name }}</p>
                                <p class="text-[10px] text-gray-600">
                                    {{ item.price != null ? formatCurrency(item.price) : 'Giá không xác định' }} x {{ item.quantity }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-1">
                                <input
                                    type="number"
                                    v-model.number="item.quantity"
                                    min="1"
                                    :max="products.find((p) => p.id === item.id)?.stock || 0"
                                    class="w-12 rounded border border-gray-300 p-1 text-xs"
                                    @input="updateQuantity(item.id, item.quantity)"
                                />
                                <button @click="removeFromCart(item.id)" class="rounded bg-red-100 px-2 py-0.5 text-xs text-red-700 hover:bg-red-200">
                                    Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-200 p-2">
                    <form @submit.prevent="showPayment">
                        <div class="mb-1">
                            <label class="mb-0.5 block text-[10px] font-medium text-gray-700"> Khách hàng (F4): </label>
                            <div class="flex items-center space-x-1">
                                <button
                                    type="button"
                                    @click="toggleCustomerSidebar"
                                    class="w-full rounded border border-gray-300 bg-gray-100 py-1 text-left text-xs text-gray-700 hover:bg-gray-200"
                                >
                                    {{ selectedCustomer ? selectedCustomer.customer_name : 'Chọn khách hàng' }}
                                </button>
                                <button
                                    type="button"
                                    v-if="selectedCustomer"
                                    @click="clearCustomer"
                                    class="rounded bg-red-100 px-2 py-0.5 text-xs text-red-700 hover:bg-red-200"
                                >
                                    Xóa
                                </button>
                            </div>
                        </div>
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-700">Tổng cộng:</span>
                            <span class="text-base font-bold text-gray-800">
                                {{ formatCurrency(cartTotal) }}
                            </span>
                        </div>
                        <button
                            type="submit"
                            class="w-full rounded bg-green-600 py-1.5 text-xs font-semibold text-white hover:bg-green-700"
                            :disabled="form.processing || cart.length === 0"
                        >
                            <Loader2 v-if="form.processing" class="mr-1 inline-block h-4 w-4 animate-spin" />
                            Thanh toán (F8)
                        </button>
                    </form>
                </div>
            </div>
            <!-- Product Modal -->
            <div
                v-if="selectedProduct"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-md transition-opacity duration-300"
            >
                <div
                    class="modal-content relative w-[90%] max-w-md transform rounded-2xl border border-white/20 bg-white/80 p-6 text-gray-800 shadow-2xl backdrop-blur-lg transition-all duration-300 hover:scale-[1.02]"
                >
                    <button
                        @click="selectedProduct = null"
                        class="absolute top-4 right-4 rounded-full bg-white/50 p-2 text-gray-600 transition-all duration-200 hover:bg-white/80 hover:text-gray-900"
                    >
                        <X class="h-5 w-5" />
                    </button>
                    <div class="flex flex-col items-center">
                        <img
                            :src="selectedProduct.image"
                            :alt="selectedProduct.name"
                            class="mb-4 h-40 w-40 rounded-lg object-cover shadow-md"
                            @error="handleImageError($event, selectedProduct)"
                        />
                        <h3 class="mb-3 text-center text-xl font-bold text-gray-900">{{ selectedProduct.name }}</h3>
                        <div class="w-full space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="font-semibold">Danh mục:</span>
                                <span>{{ selectedProduct.category }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold">Giá:</span>
                                <span>{{ selectedProduct.price != null ? formatCurrency(selectedProduct.price) : 'Giá không xác định' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold">Tồn kho:</span>
                                <span>{{ selectedProduct.stock }}</span>
                            </div>
                        </div>
                        <button
                            @click="selectedProduct = null"
                            class="mt-6 w-full rounded-lg bg-blue-600 py-2 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:bg-blue-700"
                        >
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
            <div
                v-if="showPaymentModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-lg transition-opacity duration-300"
            >
                <div
                    class="modal-content relative w-[95%] max-w-3xl transform rounded-2xl border border-gray-200 bg-white/90 p-8 text-gray-800 shadow-2xl backdrop-blur-xl transition-all duration-300"
                >
                    <button
                        @click="showPaymentModal = false"
                        class="absolute top-4 right-4 text-2xl font-bold text-gray-600 transition-all duration-200 hover:text-red-600"
                    >
                        &times;
                    </button>
                    <div class="max-h-[80vh] space-y-6 overflow-y-auto">
                        <!-- Header -->
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-2xl font-bold text-gray-900">Xác nhận thanh toán</h2>
<<<<<<< HEAD
                            <span class="text-sm text-gray-600"
                                >Nhân viên: <strong>{{ props.auth?.user?.name || 'N/A' }}</strong></span
                            >
=======
                            <span class="text-sm text-gray-600">Nhân viên: <strong>{{ props.auth?.user?.name || 'N/A'
                                    }}</strong></span>
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
                        </div>
                        <!-- Bill Information in Rectangular Frame -->
                        <div class="rounded-lg border border-gray-300 bg-gray-50 p-6 shadow-sm">
                            <h3 class="mb-4 text-center text-lg font-semibold text-gray-800">Hóa đơn bán hàng</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span><strong>Mã hóa đơn:</strong></span>
                                    <span>{{ billNumber || 'HD' + Date.now() }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span><strong>Ngày giờ:</strong></span>
                                    <span>{{ formatDateTime(DateTime.now().toISO()) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span><strong>Khách hàng:</strong></span>
                                    <span>{{ selectedCustomer?.customer_name || 'Khách lẻ' }}</span>
                                </div>
                            </div>
                            <!-- Product List -->
                            <div class="mt-4">
                                <h4 class="mb-2 text-sm font-semibold">Danh sách sản phẩm:</h4>
                                <div class="border-t border-b border-gray-200 py-2">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="bg-gray-100 text-left">
                                                <th class="p-3 font-semibold">Tên hàng</th>
                                                <th class="p-3 text-center font-semibold">Số lượng</th>
                                                <th class="p-3 text-right font-semibold">Đơn giá</th>
                                                <th class="p-3 text-right font-semibold">Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="cart.length === 0" class="border-t border-gray-200">
                                                <td colspan="4" class="p-3 text-center">Không có sản phẩm</td>
                                            </tr>
                                            <tr v-for="item in cart" :key="item.id" class="border-t border-gray-200">
                                                <td class="p-3">{{ item.name }}</td>
                                                <td class="p-3 text-center">{{ item.quantity }}</td>
                                                <td class="p-3 text-right">{{ formatCurrency(item.price) }}</td>
<<<<<<< HEAD
                                                <td class="p-3 text-right">{{ formatCurrency(item.price * item.quantity) }}</td>
=======
                                                <td class="p-3 text-right">{{ formatCurrency(item.price * item.quantity)
                                                    }}</td>
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- Totals -->
                            <div class="mt-4 space-y-2 text-right text-sm">
                                <div class="flex justify-between">
                                    <span class="font-semibold">Tổng tiền hàng:</span>
                                    <span>{{ formatCurrency(cartSubtotal) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Giảm giá:</span>
                                    <span>{{ formatCurrency(0) }}</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold text-green-600">
                                    <span>Tổng cần thanh toán:</span>
                                    <span>{{ formatCurrency(cartTotal) }}</span>
                                </div>
                            </div>
                        </div>
                        <!-- Payment Method -->
                        <div class="space-y-3 text-sm">
                            <label class="block font-semibold">Phương thức thanh toán:</label>
                            <select
                                v-model="form.paymentMethod"
                                class="w-full rounded-lg border border-gray-300 bg-white p-3 text-sm shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="{ 'border-red-500': form.errors.paymentMethod }"
                            >
                                <option value="cash">Tiền mặt</option>
                                <option value="credit_card">Thẻ ngân hàng</option>
                                <option value="bank_transfer">Chuyển khoản ngân hàng</option>
<<<<<<< HEAD
                                <option value="wallet" :disabled="!selectedCustomer || selectedCustomer.wallet < cartTotal">Ví khách hàng</option>
=======
                                

                                <option value="combined" :disabled="!selectedCustomer || selectedCustomer.wallet <= 0">
                                    Kết hợp (Ví + Tiền mặt)
                                </option>
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
                            </select>
                            <InputError class="mt-1 text-[10px]" :message="form.errors.paymentMethod" />
                        </div>
                        <!-- Cash Payment -->
                        <div v-if="form.paymentMethod === 'cash'" class="space-y-3 text-sm">
                            <label class="block font-semibold">Khách đưa:</label>
                            <div class="mb-2 flex flex-wrap gap-2">
                                <button
                                    v-for="amount in quickAmounts"
                                    :key="amount"
                                    @click="setAmountReceived(amount)"
                                    class="rounded-lg bg-blue-100 px-3 py-1.5 text-xs text-blue-700 shadow-sm transition-all hover:bg-blue-200"
                                >
                                    {{ formatCurrency(amount) }}
                                </button>
                                <button
                                    @click="setExactAmount"
                                    class="rounded-lg bg-green-100 px-3 py-1.5 text-xs text-green-700 shadow-sm transition-all hover:bg-green-200"
                                >
                                    Chính xác
                                </button>
                            </div>
                            <input
                                type="number"
                                v-model.number="form.amountReceived"
                                class="w-full rounded-lg border border-gray-300 bg-white p-3 text-sm shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                min="0"
                                placeholder="Nhập số tiền khách đưa"
                                :class="{ 'border-red-500': form.errors.amountReceived }"
                            />
                            <InputError class="mt-1 text-[10px]" :message="form.errors.amountReceived" />
                            <div class="flex justify-between text-sm">
                                <span class="font-semibold">Tiền thối lại:</span>
                                <span class="font-bold text-blue-600">{{ formatCurrency(change) }}</span>
                            </div>
                        </div>
                        <!-- Bank QR Code -->
                        <div v-if="form.paymentMethod === 'bank_transfer'" class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <label class="block font-semibold">Thanh toán ngân hàng:</label>
                                <button
                                    @click="generateBankQR"
                                    class="rounded-lg bg-blue-600 px-4 py-1.5 text-xs text-white shadow-sm transition-all hover:bg-blue-700"
                                    :disabled="isLoadingBankQR"
                                >
                                    <Loader2 v-if="isLoadingBankQR" class="mr-1 inline-block h-4 w-4 animate-spin" />
                                    {{ bankQRCode ? 'Tạo lại mã QR' : 'Tạo mã QR ngân hàng' }}
                                </button>
                            </div>
                            <div v-if="isLoadingBankQR" class="text-center">
                                <Loader2 class="mx-auto h-6 w-6 animate-spin text-blue-500" />
                                <p class="mt-2">Đang tạo mã QR...</p>
                            </div>
                            <div v-else-if="bankQRCode" class="flex flex-col gap-4 md:flex-row">
                                <div class="text-center">
                                    <img :src="bankQRCode" alt="Mã QR ngân hàng" class="mx-auto h-48 w-48 rounded-lg shadow-md" />
                                    <p class="mt-2">Quét mã QR để thanh toán {{ formatCurrency(cartTotal) }}</p>
                                </div>
                                <div v-if="bankTransactionInfo" class="space-y-2 text-sm">
                                    <p><strong>Ngân hàng:</strong> {{ bankTransactionInfo.bankCode }}</p>
                                    <p><strong>Số tài khoản:</strong> {{ bankTransactionInfo.accountNo }}</p>
                                    <p><strong>Chủ tài khoản:</strong> {{ bankTransactionInfo.accountName }}</p>
                                    <p><strong>Số tiền:</strong> {{ formatCurrency(bankTransactionInfo.amount) }}</p>
                                    <p><strong>Nội dung:</strong> {{ bankTransactionInfo.description }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-if="form.paymentMethod === 'combined'" class="space-y-3 text-sm">
                            <div>
                                <label class="block font-semibold">Số tiền từ ví (Tối đa: {{ formatCurrency(selectedCustomer?.wallet || 0) }}):</label>
                                <input type="number" v-model.number="walletAmount" :max="selectedCustomer?.wallet || 0" min="0"
                                    :placeholder="'Nhập số tiền từ ví (tối đa ' + formatCurrency(Math.min(selectedCustomer?.wallet || 0, cartTotal)) + ')'"
                                    class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white shadow-sm" />
                            </div>
                            <div>
                                <label class="block font-semibold">Số tiền mặt cần trả: {{ formatCurrency(cashAmountForCombined) }}</label>
                                <div class="flex flex-wrap gap-2 mb-2">
                                    <button v-for="amount in quickAmounts" :key="amount" @click="form.amountReceived = amount"
                                        class="bg-blue-100 text-blue-700 px-3 py-1.5 rounded-lg text-xs hover:bg-blue-200 transition-all shadow-sm">
                                        {{ formatCurrency(amount) }}
                                    </button>
                                    <button @click="form.amountReceived = cashAmountForCombined"
                                        class="bg-green-100 text-green-700 px-3 py-1.5 rounded-lg text-xs hover:bg-green-200 transition-all shadow-sm">
                                        Chính xác
                                    </button>
                                </div>
                                <input type="number" v-model.number="form.amountReceived"
                                    class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white shadow-sm"
                                    min="0" placeholder="Nhập số tiền khách đưa" />
                                <div class="flex justify-between text-sm mt-2">
                                    <span class="font-semibold">Tiền thối lại:</span>
                                    <span class="font-bold text-blue-600">{{ formatCurrency(combinedChange) }}</span>
                                </div>
                            </div>
                        </div>
                        <!-- Notes -->
                        <div class="space-y-3 text-sm">
                            <label class="block font-semibold">Ghi chú:</label>
                            <textarea
                                v-model="form.orderNotes"
                                class="w-full rounded-lg border border-gray-300 bg-white p-3 text-sm shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                rows="3"
                                placeholder="Ví dụ: Giao tận nơi..."
                            ></textarea>
                            <InputError class="mt-1 text-[10px]" :message="form.errors.orderNotes" />
                        </div>
                        <!-- Print Receipt Option -->
                        <div class="space-y-3 text-sm">
                            <label class="inline-flex items-center space-x-2">
                                <input type="checkbox" v-model="form.printReceipt" class="form-checkbox h-5 w-5 rounded text-blue-600" />
                                <span>In hóa đơn ngay</span>
                            </label>
                        </div>
                        <!-- Footer -->
                        <div class="mt-6 flex justify-end space-x-4">
                            <button
                                @click="showPaymentModal = false"
                                class="rounded-lg bg-gray-200 px-6 py-2 text-sm font-medium text-gray-700 shadow-sm transition-all hover:bg-gray-300"
                            >
                                Hủy
                            </button>
                            <button
                                @click="confirmPayment"
                                class="rounded-lg bg-green-600 px-6 py-2 text-sm font-semibold text-white shadow-sm transition-all hover:bg-green-700"
                                :disabled="form.processing || cart.length === 0"
                            >
                                <Loader2 v-if="form.processing" class="mr-2 inline-block h-5 w-5 animate-spin" />
                                Xác nhận & In
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CashierLayout>
</template>
