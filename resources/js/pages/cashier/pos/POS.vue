<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import POSKeyboardHandler from '@/components/cashier/POSKeyboardHandler.vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { MenuIcon, BadgeInfo, X, FileText, LogOutIcon, Loader2, ScanLine } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import axios from 'axios';
import { DateTime } from 'luxon';
import { StreamBarcodeReader } from 'vue-barcode-reader';
import QRCode from 'qrcode';

const { props } = usePage();

// Predefined notes list
const defaultNotes = [
    'Hoàn thành ca không có sự cố',
    'Thiếu tiền mặt',
    'Thừa tiền mặt',
    'Khách hàng trả lại hàng',
    'Khác (vui lòng ghi rõ)',
];

// Initialize state
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
const showShiftExpiredModal = ref(false);
const showShiftEndingSoonModal = ref(false);
const shiftEndingSoonMessage = ref('');
const shiftAutoCloseTimeout = ref(null);
const quickAmounts = [100000, 200000, 500000, 1000000];
const showScanner = ref(false);
const manualBarcode = ref('');
const pendingOrders = ref([]);
const interval = ref(null);
// Forms
const sessionForm = useForm({
    opening_amount: 0,
    closing_amount: 0,
    notes: '',
    shift_id: '',
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
    couponCode: null,
    printReceipt: true,
});

// Computed properties
const categories = computed(() => ['Tất cả', ...new Set(products.value.map(p => p.category))]);

const formattedOpenedAt = computed(() => {
    if (!activeShift.value?.opened_at) return 'Chưa bắt đầu';
    const dt = DateTime.fromISO(activeShift.value.opened_at, { zone: 'Asia/Ho_Chi_Minh' });
    return dt.isValid ? dt.toFormat('dd/MM/yyyy HH:mm:ss') : 'Chưa bắt đầu';
});

const cartSubtotal = computed(() => {
    console.log('Computing cartSubtotal:', cart.value); // Debug
    return cart.value.reduce((total, item) => total + ((item.price || 0) * (item.quantity || 0)), 0);
});

const cartTax = computed(() => 0); // 0% VAT

const cartTotal = computed(() => cartSubtotal.value + cartTax.value);

const change = computed(() => {
    if (form.paymentMethod === 'cash' && form.amountReceived >= cartTotal.value) {
        return form.amountReceived - cartTotal.value;
    }
    return 0;
});

const newOrder = () => {
    cart.value = [];
    selectedCustomer.value = null;
    form.customer_id = null;
    form.orderNotes = '';
    form.paymentMethod = 'cash';
    form.amountReceived = 0;
    form.couponCode = null;
    form.printReceipt = true;
    successMessage.value = 'Đã tạo đơn hàng mới!';
    showSuccessModal.value = true;
    autoHideMessage();
};

const filteredProducts = computed(() => {
    let filtered = [...products.value];

    if (searchTerm.value.trim()) {
        const regex = new RegExp(searchTerm.value.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
        filtered = filtered.filter(p =>
            regex.test(p.name) || regex.test(p.category) || regex.test(p.sku) || regex.test(p.barcode)
        );
    }

    if (selectedCategory.value !== 'Tất cả') {
        filtered = filtered.filter(p => p.category === selectedCategory.value);
    }

    if (priceRange.value.min || priceRange.value.max) {
        const min = parseFloat(priceRange.value.min) || -Infinity;
        const max = parseFloat(priceRange.value.max) || Infinity;
        filtered = filtered.filter(p => (p.price || 0) >= min && (p.price || 0) <= max);
    }

    if (stockRange.value.min || stockRange.value.max) {
        const min = parseInt(stockRange.value.min) || -Infinity;
        const max = parseInt(stockRange.value.max) || Infinity;
        filtered = filtered.filter(p => (p.stock || 0) >= min && (p.stock || 0) <= max);
    }

    if (sortBy.value !== 'none') {
        filtered.sort((a, b) => {
            const aValue = sortBy.value === 'price' ? (a.price || 0) : (a.stock || 0);
            const bValue = sortBy.value === 'price' ? (b.price || 0) : (b.stock || 0);
            return sortOrder.value === 'asc' ? aValue - bValue : bValue - aValue;
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
    if (!customerSearch.value.trim()) return customers.value;
    const regex = new RegExp(customerSearch.value.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
    return customers.value.filter(c =>
        regex.test(c.customer_name) || (c.email && regex.test(c.email)) || (c.phone && regex.test(c.phone))
    );
});

// Utility functions
const formatCurrency = (amount) => {
    return (Number(amount) || 0).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
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
    pendingOrders.value.push({
        id: orderId,
        cart: [...cart.value],
        customer: selectedCustomer.value ? { ...selectedCustomer.value } : null,
        orderNotes: form.orderNotes || '',
        timestamp: DateTime.now().toISO(),
    });
    newOrder(); // Clear current order after saving
    successMessage.value = `Đã lưu đơn hàng #${orderId}!`;
    showSuccessModal.value = true;
    autoHideMessage();
};
const removeOrder = (orderId) => {
    pendingOrders.value = pendingOrders.value.filter(o => o.id !== orderId);
    // Reassign IDs to maintain sequential order (1 to N)
    pendingOrders.value = pendingOrders.value.map((order, index) => ({
        ...order,
        id: index + 1,
    }));
    successMessage.value = `Đã xóa đơn hàng #${orderId}!`;
    showSuccessModal.value = true;
    autoHideMessage();
};
const restoreOrder = (orderId) => {
    const order = pendingOrders.value.find(o => o.id === orderId);
    if (!order) return;
    cart.value = [...order.cart];
    selectedCustomer.value = order.customer ? { ...order.customer } : null;
    form.customer_id = order.customer ? order.customer.id : null;
    form.orderNotes = order.orderNotes;
    successMessage.value = `Đã khôi phục đơn hàng #${orderId}!`;
    showSuccessModal.value = true;
    autoHideMessage();
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
            showShiftEndingSoonModal.value = false;
            showShiftExpiredModal.value = false;
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
    return workShifts.value.find(shift => {
        const { start_time, end_time } = shift;
        if (start_time > end_time) {
            return currentTime >= start_time || currentTime <= end_time;
        }
        return currentTime >= start_time && currentTime <= end_time;
    });
};

const checkShiftExpiration = () => {
    if (!activeShift.value || !hasActiveSession.value) return;

    const now = DateTime.now().setZone('Asia/Ho_Chi_Minh');
    const currentTime = now.toFormat('HH:mm:ss');
    const shift = workShifts.value.find(s => s.id === activeShift.value.shift_id);

    if (shift) {
        const { start_time, end_time, name } = shift;
        const endTime = DateTime.fromFormat(end_time, 'HH:mm:ss', { zone: 'Asia/Ho_Chi_Minh' });
        let isExpired = false;
        let timeUntilEnd;

        if (start_time > end_time) {
            isExpired = currentTime > end_time && currentTime < start_time;
            timeUntilEnd = endTime > now ? endTime.diff(now, 'minutes').minutes : endTime.plus({ days: 1 }).diff(now, 'minutes').minutes;
        } else {
            isExpired = currentTime > end_time;
            timeUntilEnd = endTime.diff(now, 'minutes').minutes;
        }

        if (timeUntilEnd <= 10 && timeUntilEnd > 0 && !showShiftExpiredModal.value && !showShiftEndingSoonModal.value) {
            shiftEndingSoonMessage.value = `Ca "${name}" sẽ kết thúc sau ${Math.ceil(timeUntilEnd)} phút.`;
            showShiftEndingSoonModal.value = true;
            autoHideMessage();
        }

        if (isExpired && !showShiftExpiredModal.value) {
            errorMessage.value = `Ca "${name}" đã hết giờ. Bạn muốn đóng ca ngay?`;
            showShiftExpiredModal.value = true;
            showShiftEndingSoonModal.value = false;
            autoHideMessage();

            if (shiftAutoCloseTimeout.value) clearTimeout(shiftAutoCloseTimeout.value);
            shiftAutoCloseTimeout.value = setTimeout(autoCloseShift, 60 * 60 * 1000);
        }
    }
};

const autoCloseShift = async () => {
    if (!hasActiveSession.value) return;

    isLoadingSessionAction.value = true;
    sessionForm.notes = sessionForm.selected_default_note === 'Khác (vui lòng ghi rõ)' ?
        sessionForm.custom_note : sessionForm.selected_default_note;

    try {
        const response = await axios.post('/cashier/pos/session/close', {
            closing_amount: Number(sessionForm.closing_amount) || 0,
            notes: sessionForm.notes || 'Tự động đóng ca do hết giờ',
        });
        hasActiveSession.value = false;
        activeShift.value = null;
        successMessage.value = response.data.success || 'Ca đã được đóng tự động!';
        showSuccessModal.value = true;
        showShiftExpiredModal.value = false;
        showShiftEndingSoonModal.value = false;
        sessionForm.reset();
        shiftReport.value = null;
        autoHideMessage();
        await fetchShiftReport();
        if (shiftAutoCloseTimeout.value) {
            clearTimeout(shiftAutoCloseTimeout.value);
            shiftAutoCloseTimeout.value = null;
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Lỗi đóng ca tự động.';
        autoHideMessage();
    } finally {
        isLoadingSessionAction.value = false;
    }
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
                customers: (response.data.report?.customers || []).map(c => ({
                    ...c,
                    customer_name: c.customer_name || 'Khách lẻ',
                    last_purchase: c.last_purchase ? formatDateTime(c.last_purchase) : 'N/A',
                    average_purchase: c.average_purchase || 0,
                })),
                top_products: (response.data.report?.top_products || []).map(p => ({
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
    if (!sessionForm.shift_id) {
        errorMessage.value = 'Vui lòng chọn ca làm việc.';
        autoHideMessage();
        return;
    }
    if (!confirm('Xác nhận mở ca làm việc?')) return;
    isLoadingSessionAction.value = true;
    sessionForm.notes = sessionForm.selected_default_note === 'Khác (vui lòng ghi rõ)' ?
        sessionForm.custom_note : sessionForm.selected_default_note;
    sessionForm.clearErrors();
    try {
        const response = await axios.post('/cashier/pos/session/start', {
            opening_amount: Number(sessionForm.opening_amount) || 0,
            notes: sessionForm.notes || '',
            shift_id: sessionForm.shift_id,
        });
        hasActiveSession.value = true;
        activeShift.value = {
            ...response.data.activeShift,
            opened_at: response.data.activeShift.opened_at || DateTime.now().toISO(),
        };
        successMessage.value = response.data.success || 'Ca đã mở thành công!';
        showSuccessModal.value = true;
        showShiftExpiredModal.value = false;
        sessionForm.reset();
        autoHideMessage();
        await fetchShiftReport();
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Lỗi mở ca.';
        if (error.response?.data?.errors) {
            Object.values(error.response.data.errors).forEach(err => errorMessage.value += ` ${err}`);
        }
        autoHideMessage();
    } finally {
        isLoadingSessionAction.value = false;
    }
};

const closeShift = async () => {
    if (!confirm('Xác nhận đóng ca làm việc?')) return;
    isLoadingSessionAction.value = true;
    sessionForm.notes = sessionForm.selected_default_note === 'Khác (vui lòng ghi rõ)' ?
        sessionForm.custom_note : sessionForm.selected_default_note;
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
        showShiftExpiredModal.value = false;
        showShiftEndingSoonModal.value = false;
        sessionForm.reset();
        shiftReport.value = null;
        autoHideMessage();
        await fetchShiftReport();
        if (shiftAutoCloseTimeout.value) {
            clearTimeout(shiftAutoCloseTimeout.value);
            shiftAutoCloseTimeout.value = null;
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Lỗi đóng ca.';
        if (error.response?.data?.errors) {
            Object.values(error.response.data.errors).forEach(err => errorMessage.value += ` ${err}`);
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
        const notes = sessionForm.selected_default_note === 'Khác (vui lòng ghi rõ)'
            ? sessionForm.custom_note : sessionForm.selected_default_note || 'Không có';
        const response = await axios.post('/cashier/pos/shift-report/generate', {
            closing_amount: Number(sessionForm.closing_amount) || 0,
            notes,
        });
        successMessage.value = response.data.message || 'Báo cáo ca đã tạo thành công!';
        showSuccessModal.value = true;
        autoHideMessage();
        await fetchShiftReport();
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server ||
            Object.values(error.response?.data?.errors || {}).join(' ') || 'Lỗi tạo báo cáo ca.';
        autoHideMessage();
    } finally {
        isLoadingSessionAction.value = false;
    }
};

const showPayment = () => {
    console.log('showPayment called, cart:', cart.value);
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
    if (!cart.value.every(item => item.id && item.quantity >= 1)) {
        errorMessage.value = 'Giỏ hàng chứa sản phẩm không hợp lệ. Vui lòng kiểm tra lại.';
        autoHideMessage();
        return;
    }
    billNumber.value = 'HD' + Date.now();
    showPaymentModal.value = true;
};


// Cart management
const addToCart = async (product) => {
    console.log('addToCart called, product:', product); // Debug
    if (!hasActiveSession.value) {
        errorMessage.value = 'Vui lòng mở ca trước khi thêm sản phẩm.';
        autoHideMessage();
        return;
    }
    if (product.stock === 0) {
        errorMessage.value = `Sản phẩm ${product.name} đã hết hàng.`;
        autoHideMessage();
        return;
    }
    try {
        const response = await axios.get(`/cashier/pos/check-batch/${product.id}`, { params: { quantity: 1 } });
        console.log('check-batch response:', response.data); // Debug
        if (!response.data.hasValidBatch) {
            errorMessage.value = response.data.message || `Sản phẩm ${product.name} không có lô hợp lệ.`;
            products.value = products.value.map(p => p.id === product.id ? { ...p, stock: response.data.availableStock || 0 } : p);
            autoHideMessage();
            return;
        }
        const existingItem = cart.value.find(item => item.id === product.id);
        if (existingItem) {
            const newQuantity = existingItem.quantity + 1;
            const stockResponse = await axios.get(`/cashier/pos/check-batch/${product.id}`, { params: { quantity: newQuantity } });
            console.log('check-batch for quantity update:', stockResponse.data); // Debug
            if (stockResponse.data.hasValidBatch) {
                existingItem.quantity = newQuantity;
            } else {
                errorMessage.value = stockResponse.data.message || `Số lượng tối đa cho ${product.name} là ${product.stock}.`;
                autoHideMessage();
            }
        } else {
            cart.value.push({ ...product, quantity: 1 });
        }
        products.value = products.value.map(p => p.id === product.id ? { ...p, stock: response.data.availableStock || 0 } : p);
        console.log('Cart after add:', cart.value); // Debug
    } catch (error) {
        console.error('addToCart error:', error); // Debug
        if (error.response?.status === 422 && error.response.data?.errors?.server?.includes('Ca làm việc đã hết hạn')) {
            showShiftExpiredModal.value = true;
            errorMessage.value = error.response.data.errors.server;
        } else {
            errorMessage.value = error.response?.data?.errors?.server || `Lỗi kiểm tra lô hàng ${product.name}.`;
        }
        autoHideMessage();
    }
};

const updateQuantity = async (productId, quantity) => {
    console.log('updateQuantity called, productId:', productId, 'quantity:', quantity); // Debug
    const item = cart.value.find(item => item.id === productId);
    if (!item) return;

    const product = products.value.find(p => p.id === productId);
    try {
        const response = await axios.get(`/cashier/pos/check-batch/${productId}`, { params: { quantity } });
        console.log('check-batch for updateQuantity:', response.data); // Debug
        if (!response.data.hasValidBatch) {
            errorMessage.value = response.data.message || `Không đủ lô cho ${product.name} với số lượng ${quantity}.`;
            removeFromCart(productId);
            products.value = products.value.map(p => p.id === productId ? { ...p, stock: response.data.availableStock || 0 } : p);
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
        products.value = products.value.map(p => p.id === productId ? { ...p, stock: response.data.availableStock || 0 } : p);
        console.log('Cart after update:', cart.value); // Debug
    } catch (error) {
        console.error('updateQuantity error:', error); // Debug
        if (error.response?.status === 422 && error.response.data?.errors?.server?.includes('Ca làm việc đã hết hạn')) {
            showShiftExpiredModal.value = true;
            errorMessage.value = error.response.data.errors.server;
        } else {
            errorMessage.value = error.response?.data?.errors?.server || `Lỗi kiểm tra lô ${product.name}.`;
            removeFromCart(productId);
        }
        autoHideMessage();
    }
};

const removeFromCart = (productId) => {
    console.log('removeFromCart called, productId:', productId); // Debug
    cart.value = cart.value.filter(item => item.id !== productId);
    console.log('Cart after remove:', cart.value); // Debug
};

// Payment handling
const setAmountReceived = (amount) => {
    form.amountReceived = amount;
};

const setExactAmount = () => {
    form.amountReceived = cartTotal.value;
};

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
    if (!cart.value.every(item => item.id && item.quantity >= 1)) {
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

    form.cart = cart.value;
    form.customer_id = selectedCustomer.value?.id || null;

    try {
        const payload = {
            cart: form.cart.map(item => ({ id: item.id, quantity: item.quantity })),
            customer_id: form.customer_id,
            paymentMethod: form.paymentMethod,
            amountReceived: form.paymentMethod === 'cash' ? Number(form.amountReceived) : cartTotal.value,
            orderNotes: form.orderNotes || '',
            couponCode: form.couponCode || null,
        };
        // Add orderId for bank_transfer
        if (form.paymentMethod === 'bank_transfer') {
            payload.orderId = billNumber.value || 'HD' + Date.now();
        }
        console.log('Submitting sale with payload:', payload); // Debug
        const response = await axios.post('/cashier/pos/sale', payload);
        console.log('submitSale response:', response.data);
        cart.value = [];
        form.reset('cart', 'customer_id', 'paymentMethod', 'amountReceived', 'orderNotes', 'couponCode', 'printReceipt');
        clearCustomer();
        products.value = response.data.products || products.value;
        hasActiveSession.value = response.data.hasActiveSession || true;
        await fetchShiftReport();
    } catch (error) {
        console.error('submitSale error:', error);
        if (error.response?.status === 422 && error.response.data?.errors?.server?.includes('Ca làm việc đã hết hạn')) {
            showShiftExpiredModal.value = true;
            errorMessage.value = error.response.data.errors.server;
        } else {
            errorMessage.value = error.response?.data?.errors?.server || 'Lỗi xử lý thanh toán.';
            if (error.response?.data?.errors) {
                Object.values(error.response.data.errors).forEach(err => errorMessage.value += ` ${err}`);
            }
        }
        autoHideMessage();
    }
};

// Filters and UI
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

// Customer management
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
        customersKey.value += 1;
        autoHideMessage();
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Lỗi thêm khách hàng.';
        if (error.response?.data?.errors) {
            Object.values(error.response.data.errors).forEach(err => errorMessage.value += ` ${err}`);
        }
        autoHideMessage();
    }
};

// Logout
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
            sessionForm.notes = sessionForm.selected_default_note === 'Khác (vui lòng ghi rõ)' ?
                sessionForm.custom_note : sessionForm.selected_default_note;
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

// Event handlers
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
    showShiftExpiredModal.value = false;
    showShiftEndingSoonModal.value = false;
};

const handleKeydown = (event) => {
    if (event.key === 'Escape') closeModalsAndSidebars();
    if (event.key === 'F9') {
        event.preventDefault();
        emit('new-order');
    }
};

const handleClickOutside = (event) => {
    if (selectedProduct.value && !event.target.closest('.modal-content')) selectedProduct.value = null;
    if (showLogoutModal.value && !event.target.closest('.logout-modal-content')) showLogoutModal.value = false;
    if (showShiftExpiredModal.value && !event.target.closest('.shift-expired-modal-content')) showShiftExpiredModal.value = false;
    if (showShiftEndingSoonModal.value && !event.target.closest('.shift-ending-soon-modal-content')) showShiftEndingSoonModal.value = false;
    if (showPaymentModal.value && !event.target.closest('.modal-content')) showPaymentModal.value = false;
};

// Keyboard shortcuts
const showHelp = () => alert('Chức năng trợ giúp (F1) chưa triển khai.');
const addItem = () => alert('Chức năng thêm sản phẩm (F2) chưa triển khai.');
const focusSearch = () => document.querySelector('input[placeholder*="Tìm kiếm sản phẩm (F3)"]')?.focus();
const reprintReceipt = () => alert('Chức năng in lại hóa đơn (F7) chưa triển khai.');

const removeLastCartItem = () => {
    if (cart.value.length > 0) cart.value.pop();
};
const checkout = () => showPayment();


onMounted(async () => {
    console.log('onMounted, products:', props.products, 'cart:', cart.value);
    window.addEventListener('keydown', handleKeydown);
    window.addEventListener('click', handleClickOutside);
    await fetchWorkShifts();
    if (hasActiveSession.value) {
        await fetchShiftReport();
        checkShiftExpiration();
        interval.value = setInterval(checkShiftExpiration, 30 * 1000); // Check every 30 seconds
    }
    if (props.flash?.success) {
        showSuccessModal.value = true;
        autoHideMessage();
    }
    pendingOrders.value = [];
});
onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    window.removeEventListener('click', handleClickOutside);
    if (shiftAutoCloseTimeout.value) clearTimeout(shiftAutoCloseTimeout.value);
    stopBarcodeScanner(); // Stop barcode scanner
    if (interval.value) clearInterval(interval.value); // Clear interval using ref
});
const onBarcodeScanned = async (barcode) => {
    if (barcode && showScanner.value) {
        manualBarcode.value = barcode; // Lưu mã vạch vào manualBarcode
        await handleManualBarcode(barcode); // Xử lý mã vạch
        showScanner.value = false; // Đóng modal sau khi quét
    }
};

// Hàm mở modal quét mã vạch
const openScanner = () => {
    if (!hasActiveSession.value) {
        errorMessage.value = 'Vui lòng mở ca trước khi quét mã vạch.';
        autoHideMessage();
        return;
    }
    showScanner.value = true;
    nextTick(() => {
        // Đảm bảo StreamBarcodeReader được khởi tạo sau khi modal hiển thị
        const scannerElement = document.querySelector('#barcode-scanner');
        if (scannerElement) {
            scannerElement.innerHTML = ''; // Xóa nội dung cũ nếu có
        }
    });
};

// Hàm xử lý mã vạch (giữ nguyên từ mã gốc)
const handleManualBarcode = async (barcode) => {
    if (!hasActiveSession.value) {
        errorMessage.value = 'Vui lòng mở ca trước khi quét mã vạch.';
        autoHideMessage();
        return;
    }
    if (!barcode) {
        errorMessage.value = 'Mã vạch không hợp lệ.';
        autoHideMessage();
        return;
    }
    const normalizedBarcode = barcode.trim();
    console.log('Handling barcode:', normalizedBarcode);
    try {
        // Kiểm tra định dạng mã vạch
        const validateResponse = await axios.get(`/cashier/pos/barcode/validate/${normalizedBarcode}`);
        if (!validateResponse.data.isValid) {
            errorMessage.value = validateResponse.data.message || 'Mã vạch không hợp lệ.';
            autoHideMessage();
            return;
        }

        const response = await axios.get(`/cashier/pos/product-by-barcode/${normalizedBarcode}`);
        const { product, hasValidBatch, availableStock, message } = response.data;

        if (!product || !hasValidBatch) {
            errorMessage.value = message || `Không tìm thấy sản phẩm với mã vạch "${normalizedBarcode}".`;
            autoHideMessage();
            return;
        }

        if (availableStock <= 0) {
            errorMessage.value = `Sản phẩm "${product.name}" đã hết hàng.`;
            autoHideMessage();
            return;
        }

        const cartItem = cart.value.find(item => item.id === product.id);
        if (cartItem) {
            const newQuantity = cartItem.quantity + 1;
            const stockResponse = await axios.get(`/cashier/pos/check-batch/${product.id}`, {
                params: { quantity: newQuantity },
            });
            if (stockResponse.data.hasValidBatch) {
                cartItem.quantity = newQuantity;
            } else {
                errorMessage.value = stockResponse.data.message || `Số lượng tối đa cho ${product.name} là ${availableStock}.`;
                autoHideMessage();
                return;
            }
        } else {
            cart.value.push({
                id: product.id,
                name: product.name,
                price: product.price,
                quantity: 1,
                barcode: product.barcode,
            });
        }

        // Cập nhật products.value để đồng bộ tồn kho
        products.value = products.value.map(p =>
            p.id === product.id ? { ...p, stock: availableStock } : p
        );

        manualBarcode.value = '';
        successMessage.value = `Đã thêm "${product.name}" vào giỏ hàng.`;
        autoHideMessage();
    } catch (error) {
        console.error('Error in handleManualBarcode:', error);
        if (error.response?.status === 422 && error.response.data?.errors?.server?.includes('Ca làm việc đã hết hạn')) {
            showShiftExpiredModal.value = true;
            errorMessage.value = error.response.data.errors.server;
        } else {
            errorMessage.value = error.response?.data?.message || `Lỗi khi xử lý mã vạch "${normalizedBarcode}".`;
        }
        autoHideMessage();
    }
};

const stopBarcodeScanner = () => {
    showScanner.value = false;
    manualBarcode.value = ''; // Xóa mã vạch thủ công khi đóng
};





const bankQRCode = ref(null);
const isLoadingBankQR = ref(false);
const bankTransactionInfo = ref(null);

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
        const qrData = `Ngân hàng: VIETCOMBANK\nSố tài khoản: 1234567890\nChủ tài khoản: G7 Mart\nSố tiền: ${cartTotal.value}\nNội dung: Thanh toán hóa đơn ${billNumber.value || 'HD' + Date.now()} tại G7 Mart`;
        const qrCodeUrl = await QRCode.toDataURL(qrData, { width: 200, margin: 1 });

        bankQRCode.value = qrCodeUrl;
        bankTransactionInfo.value = {
            bankCode: 'VIETCOMBANK',
            accountNo: '1234567890',
            accountName: 'G7 Mart',
            amount: cartTotal.value,
            description: `Thanh toán hóa đơn ${billNumber.value || 'HD' + Date.now()} tại G7 Mart`,
        };
        successMessage.value = 'Mã QR ngân hàng đã được tạo!';
        showSuccessModal.value = true;
        autoHideMessage();
    } catch (error) {
        console.error('generateBankQR error:', error);
        errorMessage.value = 'Lỗi khi tạo mã QR ngân hàng. Vui lòng thử lại.';
        autoHideMessage();
    } finally {
        isLoadingBankQR.value = false;
    }
};
// Cập nhật hàm confirmPayment để xử lý chuyển khoản ngân hàng
const confirmPayment = async () => {
    console.log('confirmPayment called, cart:', cart.value, 'form:', form, 'selectedCustomer:', selectedCustomer.value);
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

    try {
        const cartSnapshot = [...cart.value];
        const paymentMethodSnapshot = form.paymentMethod;
        const amountReceivedSnapshot = form.paymentMethod === 'cash' ? form.amountReceived : cartTotal.value;
        const customerSnapshot = { ...selectedCustomer.value };
        console.log('Snapshots:', { cartSnapshot, paymentMethodSnapshot, amountReceivedSnapshot, customerSnapshot });

        await submitSale();
        if (form.printReceipt) {
            printReceipt(cartSnapshot, paymentMethodSnapshot, amountReceivedSnapshot, customerSnapshot);
        }
        showPaymentModal.value = false;
        successMessage.value = 'Thanh toán thành công!';
        showSuccessModal.value = true;
        bankQRCode.value = null;
        bankTransactionInfo.value = null;
        autoHideMessage();
    } catch (error) {
        console.error('confirmPayment error:', error);
        if (error.response?.status === 422 && error.response.data?.errors?.server?.includes('Ca làm việc đã hết hạn')) {
            showShiftExpiredModal.value = true;
            errorMessage.value = error.response.data.errors.server;
        } else {
            errorMessage.value = error.response?.data?.message || error.response?.data?.errors?.server || 'Lỗi thanh toán.';
            if (error.response?.data?.errors) {
                Object.values(error.response.data.errors).forEach(err => errorMessage.value += ` ${err}`);
            }
        }
        autoHideMessage();
    }
};

// Cập nhật printReceipt để hỗ trợ phương thức chuyển khoản ngân hàng
const printReceipt = (cartData = cart.value, paymentMethod = form.paymentMethod, amountReceived = form.amountReceived, customer = selectedCustomer.value) => {
    console.log('printReceipt called, cartData:', cartData, 'paymentMethod:', paymentMethod, 'amountReceived:', amountReceived, 'customer:', customer);
    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        errorMessage.value = 'Không thể mở cửa sổ in. Vui lòng kiểm tra trình chặn popup.';
        autoHideMessage();
        return;
    }

    const subtotal = cartData.reduce((total, item) => total + ((item.price || 0) * (item.quantity || 0)), 0);
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
                            body { font-family: 'Arial', sans-serif; font-size: 10pt; width: 80mm; margin: 0; padding: 5mm; line-height: 1.4; color: #000; }
                            .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 8px; margin-bottom: 8px; }
                            .store-info { font-size: 9pt; text-align: center; margin-bottom: 8px; }
                            .bill-info { font-size: 9pt; margin-bottom: 8px; }
                            table { width: 100%; border-collapse: collapse; margin: 8px 0; }
                            th, td { padding: 4px 2px; font-size: 9pt; text-align: left; }
                            th { border-bottom: 1px solid #000; font-weight: bold; }
                            .text-right { text-align: right; }
                            .text-center { text-align: center; }
                            .total-section { border-top: 1px dashed #000; padding-top: 8px; margin-top: 8px; font-size: 9pt; }
                            .total { font-size: 10pt; font-weight: bold; }
                            .footer { text-align: center; font-size: 8pt; margin-top: 8px; border-top: 2px dashed #000; padding-top: 8px; }
                            .no-print { display: none; }
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
                                <th style="width: 15%; text-align: center;">Số lượng</th>
                                <th style="width: 25%; text-align: right;">Đơn giá</th>
                                <th style="width: 20%; text-align: right;">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${cartData.length > 0 ? cartData.map(item => `
                                <tr>
                                    <td>${sanitizeHTML(item.name || 'Unknown')}</td>
                                    <td class="text-center">${item.quantity || 0}</td>
                                    <td class="text-right">${sanitizeHTML(formatCurrency(item.price || 0))}</td>
                                    <td class="text-right">${sanitizeHTML(formatCurrency((item.price || 0) * (item.quantity || 0)))}</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td colspan="4" class="text-center">Không có sản phẩm</td>
                                </tr>
                            `}
                        </tbody>
                    </table>
                    <div class="total-section">
                        <p class="text-right"><strong>Tổng tiền hàng:</strong> ${sanitizeHTML(formatCurrency(subtotal))}</p>
                        <p class="text-right"><strong>Giảm giá:</strong> ${sanitizeHTML(formatCurrency(0))}</p>
                        <p class="text-right"><strong>Thuế VAT (0%):</strong> ${sanitizeHTML(formatCurrency(tax))}</p>
                        <p class="text-right total"><strong>Tổng thanh toán:</strong> ${sanitizeHTML(formatCurrency(total))}</p>
                        <p class="text-right"><strong>Phương thức:</strong> ${sanitizeHTML(
            paymentMethod === 'cash' ? 'Tiền mặt' :
                paymentMethod === 'credit_card' ? 'Thẻ ngân hàng' :
                    paymentMethod === 'bank_transfer' ? 'Chuyển khoản ngân hàng' :
                        'Ví khách hàng'
        )}</p>
                        ${paymentMethod === 'cash' ? `
                            <p class="text-right"><strong>Khách đưa:</strong> ${sanitizeHTML(formatCurrency(Number(amountReceived) || 0))}</p>
                            <p class="text-right"><strong>Tiền thối:</strong> ${sanitizeHTML(formatCurrency(Number(amountReceived) - total || 0))}</p>
                        ` : paymentMethod === 'bank_transfer' && bankTransactionInfo.value ? `
                            
                            <p class="text-right"><strong>Mô tả:</strong> ${sanitizeHTML(bankTransactionInfo.value.description)}</p>
                        ` : ''}
                        <p class="text-right"><strong>Ghi chú:</strong> ${sanitizeHTML(form.orderNotes || 'Không có')}</p>
                    </div>
                    <div class="footer">
                        <p>Cảm ơn quý khách!</p>
                        <p>Vui lòng kiểm tra hàng trước khi rời đi.</p>
                    </div>
                    <button class="no-print" onclick="window.print(); window.close();" style="margin-top: 10px; width: 100%; padding: 5px; background: #007bff; color: #fff; border: none; border-radius: 3px; cursor: pointer;">
                        In hóa đơn
                    </button>
                </body>
            </html>
        `);
        printWindow.document.close();
    } catch (error) {
        console.error('printReceipt error:', error);
        errorMessage.value = 'Lỗi khi tạo hóa đơn in. Vui lòng thử lại.';
        autoHideMessage();
        printWindow.close();
    }
};
</script>

<template>

    <Head title="Cashier" />
    <CashierLayout>
        <POSKeyboardHandler @show-help="showHelp" @add-item="addItem" @focus-search="focusSearch"
            @select-customer="toggleCustomerSidebar"  @hold-order="holdOrder"
            @reprint-receipt="reprintReceipt" @remove-last-cart-item="removeLastCartItem" @checkout="showPaymentModal"
            @logout="initiateLogout" :on-checkout="showPayment" />
        <div class="flex text-xs relative h-[650px]">
            <!-- Error Message -->
            <div v-if="errorMessage"
                class="fixed top-4 left-1/2 -translate-x-1/2 bg-red-100 text-red-700 px-4 py-2 rounded-lg shadow-lg text-sm z-50 flex items-center">
                {{ errorMessage }}
                <button @click="errorMessage = ''" class="ml-2 text-red-900 hover:text-red-700">✖</button>
            </div>
            <!-- Success Message -->
            <div v-if="showSuccessModal"
                class="fixed top-4 left-1/2 -translate-x-1/2 bg-green-100 text-green-700 px-4 py-2 rounded-lg shadow-lg text-sm z-50 flex items-center">
                {{ successMessage }}
                <button @click="showSuccessModal = false" class="ml-2 text-green-900 hover:text-green-700">✖</button>
            </div>
            <!-- Shift Ending Soon Modal -->
            <div v-if="showShiftEndingSoonModal"
                class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm bg-black/30">
                <div
                    class="bg-white/70 backdrop-blur-md border border-white/30 shadow-xl rounded-xl p-4 max-w-sm w-[90%] text-sm text-gray-800 transition transform scale-95 animate-pulse shift-ending-soon-modal-content">
                    <button @click="showShiftEndingSoonModal = false"
                        class="absolute top-2 right-2 text-gray-700 hover:text-black text-lg">✖</button>
                    <h3 class="text-base font-bold mb-3 text-center">Cảnh báo ca làm việc</h3>
                    <p class="mb-3 text-center">{{ shiftEndingSoonMessage }}</p>
                    <div class="flex space-x-2">
                        <button @click="showShiftEndingSoonModal = false"
                            class="w-full bg-gray-300 text-gray-700 py-1.5 rounded text-xs hover:bg-gray-400">Đóng</button>
                    </div>
                </div>
            </div>
            <!-- Shift Expired Modal -->
            <div v-if="showShiftExpiredModal"
                class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm bg-black/30">
                <div
                    class="bg-white/70 backdrop-blur-md border border-white/30 shadow-xl rounded-xl p-4 max-w-sm w-[90%] text-sm text-gray-800 transition transform scale-95 animate-pulse shift-expired-modal-content">
                    <button @click="showShiftExpiredModal = false"
                        class="absolute top-2 right-2 text-gray-700 hover:text-black text-lg">✖</button>
                    <h3 class="text-base font-bold mb-3 text-center">Ca làm việc đã hết hạn</h3>
                    <p class="mb-3 text-center">{{ errorMessage }}</p>
                    <div class="flex space-x-2">
                        <button @click="closeShift"
                            class="w-full bg-blue-600 text-white py-1.5 rounded text-xs font-semibold hover:bg-blue-700">Đóng
                            ca</button>
                    </div>
                </div>
            </div>
            <!-- Logout Modal -->
            <div v-if="showLogoutModal"
                class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm bg-black/30">
                <div
                    class="bg-white/70 backdrop-blur-md border border-white/30 shadow-xl rounded-xl p-4 max-w-sm w-[90%] text-sm text-gray-800 transition transform scale-95 animate-pulse logout-modal-content">
                    <button @click="showLogoutModal = false"
                        class="absolute top-2 right-2 text-gray-700 hover:text-black text-lg">✖</button>
                    <h3 class="text-base font-bold mb-3 text-center">Xác nhận đăng xuất</h3>
                    <p class="mb-3 text-center">Bạn có muốn đóng ca làm việc trước khi đăng xuất?</p>
                    <form @submit.prevent="performLogout(true)">
                        <div class="mb-2">
                            <label for="logout_closing_amount" class="block text-xs font-medium text-gray-700 mb-1">Số
                                tiền đóng ca (VND)</label>
                            <input type="number" v-model.number="sessionForm.closing_amount"
                                class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                                id="logout_closing_amount" min="0" required />
                            <InputError class="mt-0.5 text-[10px]" :message="sessionForm.errors.closing_amount" />
                        </div>
                        <div class="mb-2">
                            <label for="logout_notes" class="block text-xs font-medium text-gray-700 mb-1">Ghi
                                chú</label>
                            <select v-model="sessionForm.selected_default_note"
                                class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                                id="logout_notes_select">
                                <option value="" disabled>Chọn ghi chú</option>
                                <option v-for="note in defaultNotes" :key="note" :value="note">{{ note }}</option>
                            </select>
                            <textarea v-if="sessionForm.selected_default_note === 'Khác (vui lòng ghi rõ)'"
                                v-model="sessionForm.custom_note"
                                class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 mt-1"
                                id="logout_notes_custom" rows="3" placeholder="Nhập ghi chú tùy chỉnh"></textarea>
                        </div>
                        <div class="flex space-x-2">
                            <button type="button" @click="performLogout(false)"
                                class="w-1/2 bg-gray-300 text-gray-700 py-1.5 rounded text-xs hover:bg-gray-400"
                                :disabled="isLoadingSessionAction">Đăng xuất không đóng ca</button>
                            <button type="submit"
                                class="w-1/2 bg-blue-600 text-white py-1.5 rounded text-xs font-semibold hover:bg-blue-700"
                                :disabled="isLoadingSessionAction || sessionForm.processing">Đóng ca và đăng
                                xuất</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Filter Sidebar -->
            <div :class="{ 'translate-x-full': !showFilterSidebar, 'translate-x-0': showFilterSidebar }"
                class="fixed inset-y-0 right-0 w-80 bg-white shadow-xl z-50 transform transition-transform duration-300">
                <div class="p-3 h-full flex flex-col">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-sm font-semibold">Bộ lọc</h3>
                        <button @click="toggleFilterSidebar"
                            class="bg-gray-300 text-gray-800 py-1 px-2 rounded text-xs hover:bg-gray-400">Đóng</button>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <div>
                            <p class="text-[11px] text-gray-600 mt-0.5">Sản phẩm phù hợp: {{ filteredProducts.length }}
                            </p>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Danh mục:</label>
                                <select v-model="selectedCategory"
                                    class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option v-for="category in categories" :key="category" :value="category">{{ category
                                    }}</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Khoảng giá (VND):</label>
                                <div class="flex space-x-2">
                                    <input type="number" v-model="priceRange.min" placeholder="Từ"
                                        class="w-1/2 p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                    <input type="number" v-model="priceRange.max" placeholder="Đến"
                                        class="w-1/2 p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Số lượng tồn:</label>
                                <div class="flex space-x-2">
                                    <input type="number" v-model="stockRange.min" placeholder="Từ"
                                        class="w-1/2 p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                    <input type="number" v-model="stockRange.max" placeholder="Đến"
                                        class="w-1/2 p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Sắp xếp theo:</label>
                                <select v-model="sortBy"
                                    class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="none">Không sắp xếp</option>
                                    <option value="price">Giá</option>
                                    <option value="stock">Số lượng tồn</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Thứ tự:</label>
                                <select v-model="sortOrder"
                                    class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="asc">Tăng dần</option>
                                    <option value="desc">Giảm dần</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Customer Sidebar -->
            <div :class="{ 'translate-x-full': !showCustomerSidebar, 'translate-x-0': showCustomerSidebar }"
                class="fixed inset-y-0 right-0 w-80 bg-white shadow-xl z-50 transform transition-transform duration-300">
                <div class="p-3 h-full flex flex-col">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-sm font-semibold">Chọn khách hàng</h3>
                        <button @click="toggleCustomerSidebar"
                            class="bg-gray-300 text-gray-800 py-1 px-2 rounded text-xs hover:bg-gray-400">Đóng</button>
                    </div>
                    <div class="mb-2">
                        <input type="text" v-model="customerSearch"
                            placeholder="Tìm kiếm khách hàng (tên, email, số điện thoại)"
                            class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div class="mb-2">
                        <button @click="toggleAddCustomerForm"
                            class="w-full bg-blue-100 text-blue-700 py-1 rounded text-xs hover:bg-blue-200">{{
                                showAddCustomerForm ? 'Hủy thêm khách hàng' : 'Thêm khách hàng mới' }}</button>
                    </div>
                    <div v-if="showAddCustomerForm" class="mb-2">
                        <form class="space-y-2" @submit.prevent="submitNewCustomer">
                            <div>
                                <label for="customer_name" class="block text-xs font-medium text-gray-700 mb-1">Tên
                                    khách hàng <span class="text-red-500">*</span></label>
                                <input type="text" id="customer_name" v-model="form.customer_name"
                                    placeholder="Nhập tên khách hàng"
                                    class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :class="{ 'border-red-500': form.errors.customer_name }" />
                                <InputError class="mt-0.5 text-[10px]" :message="form.errors.customer_name" />
                            </div>
                            <div>
                                <label for="email" class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" id="email" v-model="form.email" placeholder="Nhập email"
                                    class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :class="{ 'border-red-500': form.errors.email }" />
                                <InputError class="mt-0.5 text-[10px]" :message="form.errors.email" />
                            </div>
                            <div>
                                <label for="phone" class="block text-xs font-medium text-gray-700 mb-1">Số điện thoại
                                    <span class="text-red-500">*</span></label>
                                <input type="tel" id="phone" v-model="form.phone" placeholder="Nhập số điện thoại"
                                    class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :class="{ 'border-red-500': form.errors.phone }" />
                                <InputError class="mt-0.5 text-[10px]" :message="form.errors.phone" />
                            </div>
                            <div>
                                <label for="address" class="block text-xs font-medium text-gray-700 mb-1">Địa
                                    chỉ</label>
                                <input type="text" id="address" v-model="form.address"
                                    placeholder="Nhập địa chỉ khách hàng"
                                    class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :class="{ 'border-red-500': form.errors.address }" />
                                <InputError class="mt-0.5 text-[10px]" :message="form.errors.address" />
                            </div>
                            <div>
                                <label for="wallet" class="block text-xs font-medium text-gray-700 mb-1">Ví tiền</label>
                                <input type="number" id="wallet" v-model.number="form.wallet"
                                    placeholder="Nhập số tiền trong ví"
                                    class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :class="{ 'border-red-500': form.errors.wallet }" />
                                <InputError class="mt-0.5 text-[10px]" :message="form.errors.wallet" />
                            </div>
                            <div class="flex space-x-2">
                                <button type="button" @click="toggleAddCustomerForm"
                                    class="w-1/2 bg-gray-300 text-gray-700 py-1.5 rounded text-xs hover:bg-gray-400">Hủy</button>
                                <button type="submit"
                                    class="w-1/2 bg-blue-600 text-white py-1.5 rounded text-xs font-semibold hover:bg-blue-700"
                                    :disabled="form.processing">Lưu khách hàng</button>
                            </div>
                        </form>
                    </div>
                    <div v-else class="flex-1 overflow-y-auto">
                        <div v-for="customer in filteredCustomers" :key="customer.id"
                            class="p-2 border-b border-gray-200 hover:bg-gray-100 cursor-pointer"
                            @click="selectCustomer(customer)">
                            <p class="font-semibold">{{ customer.customer_name }}</p>
                            <p v-if="customer.phone" class="text-[10px] text-gray-600">{{ customer.phone }}</p>
                            <p v-if="customer.address" class="text-[10px] text-gray-600">{{ customer.address }}</p>
                            <p class="text-[10px] text-gray-600">Ví: {{ formatCurrency(customer.wallet) }}</p>
                        </div>
                        <div v-if="filteredCustomers.length === 0" class="text-center text-gray-500 py-2 text-[11px]">
                            Không tìm thấy khách hàng</div>
                    </div>
                </div>
            </div>
            <!-- Report Sidebar -->
            <div :class="{ 'translate-x-full': !showReportSidebar, 'translate-x-0': showReportSidebar }"
                class="fixed inset-y-0 right-0 w-80 bg-gradient-to-b from-blue-50 to-white shadow-2xl z-50 transform transition-transform duration-300 rounded-l-lg">
                <div class="p-4 h-full flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-base font-bold text-gray-800 flex items-center">
                            <FileText class="w-5 h-5 mr-2 text-blue-600" />
                            Báo cáo ca làm việc
                        </h3>
                        <button @click="toggleReportSidebar"
                            class="bg-gray-200 text-gray-800 py-1 px-2 rounded text-xs hover:bg-gray-300 transition">Đóng</button>
                    </div>
                    <div class="flex-1 overflow-y-auto space-y-4">
                        <div class="space-y-3">
                            <button v-if="!hasActiveSession" @click="openShift"
                                class="w-full bg-green-600 text-white py-2 rounded-lg text-xs font-semibold hover:bg-green-700 transition shadow-md"
                                :disabled="isLoadingSessionAction || sessionForm.processing">
                                <Loader2 v-if="isLoadingSessionAction" class="animate-spin h-4 w-4 inline-block mr-1" />
                                Mở Ca
                            </button>
                            <button @click="generateShiftReport"
                                class="w-full bg-blue-600 text-white py-2 rounded-lg text-xs font-semibold hover:bg-blue-700 transition shadow-md"
                                :disabled="!hasActiveSession || isLoadingSessionAction">
                                <Loader2 v-if="isLoadingSessionAction" class="animate-spin h-4 w-4 inline-block mr-1" />
                                Tạo Báo cáo
                            </button>
                            <button v-if="hasActiveSession" @click="closeShift"
                                class="w-full bg-red-600 text-white py-2 rounded-lg text-xs font-semibold hover:bg-red-700 transition shadow-md"
                                :disabled="isLoadingSessionAction || sessionForm.processing">
                                <Loader2 v-if="isLoadingSessionAction" class="animate-spin h-4 w-4 inline-block mr-1" />
                                Đóng Ca
                            </button>
                            <button @click="initiateLogout"
                                class="w-full bg-gray-600 text-white py-2 rounded-lg text-xs font-semibold hover:bg-gray-700 transition shadow-md">
                                <LogOutIcon class="w-4 h-4 inline-block mr-1" />
                                Đăng xuất
                            </button>
                        </div>
                        <div v-if="!hasActiveSession"
                            class="space-y-3 bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                            <h4 class="text-xs font-semibold text-gray-800">Mở ca mới</h4>
                            <form @submit.prevent="openShift">
                                <div class="mb-2">
                                    <label for="shift_id" class="block text-xs font-medium text-gray-700 mb-1">Chọn ca
                                        làm việc <span class="text-red-500">*</span></label>
                                    <select v-model="sessionForm.shift_id"
                                        class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                        id="shift_id" required>
                                        <option value="" disabled>Chọn ca</option>
                                        <option v-for="shift in workShifts" :key="shift.id" :value="shift.id"
                                            :disabled="!shift.is_suitable">
                                            {{ shift.name }} ({{ shift.start_time }} - {{ shift.end_time }}) {{
                                                shift.is_suitable ? '' : '(Hết giờ)' }}
                                        </option>
                                    </select>
                                    <InputError class="mt-0.5 text-[10px]" :message="sessionForm.errors.shift_id" />
                                </div>
                                <div class="mb-2">
                                    <label for="opening_amount" class="block text-xs font-medium text-gray-700 mb-1">Số
                                        tiền mở ca (VND)</label>
                                    <input type="number" v-model.number="sessionForm.opening_amount"
                                        class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                        id="opening_amount" min="0" required />
                                    <InputError class="mt-0.5 text-[10px]"
                                        :message="sessionForm.errors.opening_amount" />
                                </div>
                                <div class="mb-2">
                                    <label for="notes" class="block text-xs font-medium text-gray-700 mb-1">Ghi
                                        chú</label>
                                    <select v-model="sessionForm.selected_default_note"
                                        class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                        id="notes_select">
                                        <option value="" disabled>Chọn ghi chú</option>
                                        <option v-for="note in defaultNotes" :key="note" :value="note">{{ note }}
                                        </option>
                                    </select>
                                    <textarea v-if="sessionForm.selected_default_note === 'Khác (vui lòng ghi rõ)'"
                                        v-model="sessionForm.custom_note"
                                        class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white mt-1"
                                        id="notes_custom" rows="3" placeholder="Nhập ghi chú tùy chỉnh"></textarea>
                                </div>
                                <button type="submit"
                                    class="w-full bg-green-600 text-white py-2 rounded-lg text-xs font-semibold hover:bg-green-700 transition shadow-md"
                                    :disabled="isLoadingSessionAction || sessionForm.processing">
                                    <Loader2 v-if="isLoadingSessionAction || sessionForm.processing"
                                        class="animate-spin h-4 w-4 inline-block mr-1" />
                                    Mở Ca
                                </button>
                            </form>
                        </div>
                        <div v-else class="space-y-3 bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                            <h4 class="text-xs font-semibold text-gray-800">Thông tin ca hiện tại</h4>
                            <div class="text-[11px] space-y-1">
                                <p><strong>Ca làm việc:</strong> {{ activeShift?.shift_name || 'N/A' }}</p>
                                <p><strong>Thời gian ca:</strong> {{ activeShift?.shift_time ?
                                    `${activeShift.shift_time.start_time} - ${activeShift.shift_time.end_time}` : 'N/A'
                                }}</p>
                                <p><strong>Bắt đầu:</strong> {{ formattedOpenedAt }}</p>
                            </div>
                            <h4 class="text-xs font-semibold text-gray-800 mt-3">Đóng ca</h4>
                            <form @submit.prevent="closeShift">
                                <div class="mb-2">
                                    <label for="closing_amount" class="block text-xs font-medium text-gray-700 mb-1">Số
                                        tiền đóng ca (VND)</label>
                                    <input type="number" v-model.number="sessionForm.closing_amount"
                                        class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                        id="closing_amount" min="0" required />
                                    <InputError class="mt-0.5 text-[10px]"
                                        :message="sessionForm.errors.closing_amount" />
                                </div>
                                <div class="mb-2">
                                    <label for="notes" class="block text-xs font-medium text-gray-700 mb-1">Ghi
                                        chú</label>
                                    <select v-model="sessionForm.selected_default_note"
                                        class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                        id="notes_select">
                                        <option value="" disabled>Chọn ghi chú</option>
                                        <option v-for="note in defaultNotes" :key="note" :value="note">{{ note }}
                                        </option>
                                    </select>
                                    <textarea v-if="sessionForm.selected_default_note === 'Khác (vui lòng ghi rõ)'"
                                        v-model="sessionForm.custom_note"
                                        class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white mt-1"
                                        id="notes_custom" rows="3" placeholder="Nhập ghi chú tùy chỉnh"></textarea>
                                </div>
                                <button type="submit"
                                    class="w-full bg-red-600 text-white py-2 rounded-lg text-xs font-semibold hover:bg-red-700 transition shadow-md"
                                    :disabled="isLoadingSessionAction || sessionForm.processing">
                                    <Loader2 v-if="isLoadingSessionAction || sessionForm.processing"
                                        class="animate-spin h-4 w-4 inline-block mr-1" />
                                    Đóng Ca
                                </button>
                            </form>
                        </div>
                        <div v-if="isLoadingReport" class="text-center text-gray-500 py-4">
                            <Loader2 class="animate-spin h-5 w-5 mx-auto text-blue-500" />
                            Đang tải báo cáo...
                        </div>
                        <div v-else-if="shiftReport?.hasActiveSession" class="space-y-4">
                            <div class="bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                                <h4 class="text-xs font-semibold text-gray-800 mb-2">Thông tin ca</h4>
                                <div class="text-[11px] space-y-1">
                                    <p><strong>Ca làm việc:</strong> {{ shiftReport.session?.shift_name || 'N/A' }}</p>
                                    <p><strong>Thời gian ca:</strong> {{ shiftReport.session?.shift_time ?
                                        `${shiftReport.session.shift_time.start_time} -
                                        ${shiftReport.session.shift_time.end_time}` : 'N/A' }}</p>
                                    <p><strong>Bắt đầu:</strong> {{ shiftReport.session?.opened_at ?
                                        formatDateTime(shiftReport.session.opened_at) : 'Chưa mở ca' }}</p>
                                    <p><strong>Tiền mở ca:</strong> {{ shiftReport.session?.opening_amount ?
                                        formatCurrency(shiftReport.session.opening_amount) : 'Chưa mở ca' }}</p>
                                    <p><strong>Tiền đóng ca:</strong> {{ shiftReport.session?.closing_amount ?
                                        formatCurrency(shiftReport.session.closing_amount) : 'Chưa đóng ca' }}</p>
                                    <p><strong>Thời gian đóng ca:</strong> {{ shiftReport.session?.closed_at ?
                                        formatDateTime(shiftReport.session.closed_at) : 'Chưa đóng ca' }}</p>
                                    <p>
                                        <strong>Chênh lệch (Đóng - Mở):</strong>
                                        <span :class="{
                                            'text-green-600': shiftReport.session?.difference > 0,
                                            'text-red-600': shiftReport.session?.difference < 0,
                                            'text-gray-600': shiftReport.session?.difference === 0
                                        }">
                                            {{ shiftReport.session?.difference ?
                                                formatCurrency(shiftReport.session.difference) : 'N/A' }}
                                        </span>
                                    </p>
                                    <p><strong>Ghi chú:</strong> {{ shiftReport.session?.notes || 'Không có' }}</p>
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                                <h4 class="text-xs font-semibold text-gray-800 mb-2">Thống kê doanh thu</h4>
                                <div class="text-[11px] space-y-1">
                                    <p><strong>Tổng doanh thu:</strong> {{
                                        formatCurrency(shiftReport.report?.total_sales || 0) }}</p>
                                    <p><strong>Tiền mặt:</strong> {{ formatCurrency(shiftReport.report?.total_cash || 0)
                                    }}</p>
                                    <p><strong>Thẻ ngân hàng:</strong> {{ formatCurrency(shiftReport.report?.total_card
                                        || 0) }}</p>
                                    <p><strong>Chuyển khoản:</strong> {{
                                        formatCurrency(shiftReport.report?.total_transfer || 0) }}</p>
                                    <p><strong>Ví khách hàng:</strong> {{
                                        formatCurrency(shiftReport.report?.total_wallet || 0) }}</p>
                                    <p><strong>Số hóa đơn:</strong> {{ shiftReport.report?.bill_count || 0 }}</p>
                                    <p><strong>Tổng sản phẩm bán ra:</strong> {{ shiftReport.report?.total_products_sold
                                        || 0 }}</p>
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded-lg shadow-sm border border-gray-200"
                                v-if="shiftReport.report?.top_products?.length">
                                <h4 class="text-xs font-semibold text-gray-800 mb-2">Sản phẩm bán chạy</h4>
                                <div class="text-[11px] space-y-2">
                                    <div v-for="product in shiftReport.report.top_products" :key="product.product_id"
                                        class="border-b border-gray-100 pb-2">
                                        <p><strong>Tên:</strong> {{ product.product_name || 'N/A' }}</p>
                                        <p><strong>Số lượng:</strong> {{ product.quantity_sold || 0 }}</p>
                                        <p><strong>Doanh thu:</strong> {{ formatCurrency(product.total_revenue || 0) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded-lg shadow-sm border border-gray-200"
                                v-if="shiftReport.report?.customers?.length">
                                <h4 class="text-xs font-semibold text-gray-800 mb-2">Khách hàng</h4>
                                <div class="text-[11px] space-y-2">
                                    <div v-for="customer in shiftReport.report.customers"
                                        :key="customer.customer_id || customer.customer_name"
                                        class="border-b border-gray-100 pb-2">
                                        <p><strong>Tên:</strong> {{ customer.customer_name || 'Khách lẻ' }}</p>
                                        <p><strong>Tổng chi tiêu:</strong> {{ formatCurrency(customer.total_amount || 0)
                                        }}</p>
                                        <p><strong>Số hóa đơn:</strong> {{ customer.bill_count || 0 }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center text-gray-500 py-2 text-[11px]">
                                Không có dữ liệu khách hàng
                            </div>
                        </div>
                        <div v-else
                            class="text-center text-gray-500 py-4 text-[11px] bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                            Không có dữ liệu báo cáo ca
                        </div>
                    </div>
                </div>
            </div>
            <!-- Main Content -->
            <div class="w-2/3 bg-white flex flex-col h-full">

                <div class="p-1.5 border-b border-gray-200">
                    <div class="flex justify-between items-center mb-1">
                        <div class="flex items-center space-x-1">
                            <!-- Barcode Scanner Modal -->
                            <div v-if="showScanner"
                                class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-md bg-black/40 transition-opacity duration-300">
                                <div
                                    class="relative bg-white/80 backdrop-blur-lg border border-white/20 shadow-2xl rounded-2xl p-6 max-w-md w-[90%] text-gray-800 transform transition-all duration-300 modal-content">
                                    <button @click="stopBarcodeScanner"
                                        class="absolute top-4 right-4 text-gray-600 hover:text-red-600 text-2xl font-bold transition-all duration-200">
                                        &times;
                                    </button>
                                    <h3 class="text-lg font-bold text-gray-900 mb-4 text-center">Quét mã vạch</h3>
                                    <div id="barcode-scanner" class="w-full h-64 bg-black"></div>
                                    <div class="mt-4 text-center">
                                        <p class="text-sm text-gray-600">Đưa mã vạch vào khung hình để quét</p>
                                        <button @click="stopBarcodeScanner"
                                            class="mt-4 bg-red-600 text-white py-2 px-4 rounded-lg text-sm font-semibold hover:bg-red-700 transition-all shadow-md">
                                            Hủy
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button @click="toggleFilterSidebar" class="_Free2"></button>
                            <button @click="toggleReportSidebar"
                                class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs hover:bg-gray-300">
                                <FileText class="w-4 h-4" />
                            </button>
                            <button @click="toggleFilterSidebar"
                                class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs hover:bg-gray-300">
                                <MenuIcon class="w-4 h-4" />
                            </button>
                            <button @click="openScanner"
                                class="bg-blue-200 text-blue-700 px-2 py-0.5 rounded text-xs hover:bg-blue-300">
                                <ScanLine class="w-4 h-4" />
                            </button>
                            <button @click="initiateLogout"
                                class="bg-red-600 text-white px-2 py-0.5 rounded text-xs hover:bg-red-700">
                                <LogOutIcon class="w-4 h-4" />
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
                                </button>
                            </div>
                            <h2 class="text-base font-semibold">Sản phẩm</h2>
                        </div>
                        <div v-if="activeShift" class="text-[11px] text-gray-600">
                            <p><strong>Ca làm việc:</strong> {{ activeShift.shift_name }} (Bắt đầu: {{ formattedOpenedAt
                            }})</p>
                        </div>
                    </div>
                    <div class="mb-1">
                        <input type="text" v-model="searchTerm" placeholder="Tìm kiếm sản phẩm (F3)"
                            class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-2">
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2">
                        <div v-for="product in filteredProducts" :key="product.id"
                            class="relative bg-gray-50 p-1.5 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 flex flex-col items-center text-center cursor-pointer hover:scale-105 active:scale-95"
                            :class="{ 'opacity-60 cursor-not-allowed': product.stock === 0 }"
                            @click="product.stock > 0 && addToCart(product)">
                            <button @click.stop="openProductModal(product)"
                                class="absolute top-1 right-1 hover:bg-gray-100 z-10 shadow">
                                <BadgeInfo class="w-3 h-3" />
                            </button>
                            <img :src="product.image" :alt="product.name" class="w-12 h-12 object-cover rounded mb-1"
                                @error="handleImageError($event, product)" />
                            <h3 class="text-[10px] font-medium truncate w-full">{{ product.name }}</h3>
                            <p v-if="product.stock <= 10 && product.stock > 0"
                                class="text-orange-500 text-[10px] font-semibold mt-0.5">
                                Tồn: {{ product.stock }}
                            </p>
                            <p v-else-if="product.stock === 0" class="text-red-500 text-[10px] font-semibold mt-0.5">
                                Hết hàng
                            </p>
                            <p v-else class="text-gray-500 text-[10px] mt-0.5">
                                Tồn: {{ product.stock }}
                            </p>
                            <p class="text-[10px] mt-0.5">
                                {{ product.price != null ? formatCurrency(product.price) : 'Giá không xác định' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Cart Section -->
            <div class="w-1/3 bg-white flex flex-col h-full">
                <div class="p-2 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-base font-semibold">Giỏ hàng</h2>
                        <div class="flex space-x-1">
                            <button class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs hover:bg-gray-300"
                                @click="newOrder">
                                Đơn mới (F9)
                            </button>
                            <button class="bg-yellow-500 text-white px-2 py-0.5 rounded text-xs hover:bg-yellow-600"
                                @click="holdOrder">
                                Lưu đơn (F6)
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-2 border-b border-gray-200">
                    <h3 class="text-xs font-semibold mb-1">Đơn hàng chờ:</h3>
                    <div class="flex flex-wrap gap-1 max-h-16 overflow-y-auto">
                        <div v-if="pendingOrders.length === 0"
                            class="relative bg-gray-100 p-1.5 rounded text-[10px] border border-gray-200">
                            Không có đơn hàng chờ
                        </div>
                        <div v-for="order in pendingOrders" :key="order.id"
                            class="relative bg-gray-100 p-2 rounded-lg text-[10px] border border-gray-200 hover:bg-gray-200 cursor-pointer shadow-sm transition-all"
                            @click="restoreOrder(order.id)">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold">Đơn {{ order.id }}</span>
                                <button @click.stop="removeOrder(order.id)"
                                    class="text-red-600 hover:text-red-800 text-xs">
                                    <X class="w-3 h-3" />
                                </button>
                            </div>
                            <p>{{ order.cart.length }} sản phẩm</p>
                            <p>{{formatCurrency(order.cart.reduce((sum, item) => sum + (item.price * item.quantity),
                                0))}}</p>
                            <p class="text-gray-500">{{ formatDateTime(order.timestamp) }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex-1 p-2 overflow-y-auto">
                    <div v-if="cart.length === 0" class="text-gray-500 text-center py-4 text-xs">
                        Giỏ hàng trống. Vui lòng thêm sản phẩm (F2)!
                    </div>
                    <div v-else>
                        <div v-for="item in cart" :key="item.id"
                            class="flex items-center justify-between p-2 border-b border-gray-200">
                            <div class="flex-1">
                                <p class="text-xs font-medium">{{ item.name }}</p>
                                <p class="text-[10px] text-gray-600">
                                    {{ item.price != null ? formatCurrency(item.price) : 'Giá không xác định' }} x {{
                                        item.quantity }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-1">
                                <input type="number" v-model.number="item.quantity" min="1"
                                    :max="products.find(p => p.id === item.id)?.stock || 0"
                                    class="w-12 p-1 border border-gray-300 rounded text-xs"
                                    @input="updateQuantity(item.id, item.quantity)" />
                                <button @click="removeFromCart(item.id)"
                                    class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs hover:bg-red-200">
                                    Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-2 border-t border-gray-200">
                    <form @submit.prevent="showPayment">
                        <div class="mb-1">
                            <label class="block text-[10px] font-medium text-gray-700 mb-0.5">
                                Khách hàng (F4):
                            </label>
                            <div class="flex items-center space-x-1">
                                <button type="button" @click="toggleCustomerSidebar"
                                    class="w-full bg-gray-100 text-gray-700 py-1 rounded border border-gray-300 hover:bg-gray-200 text-xs text-left">
                                    {{ selectedCustomer ? selectedCustomer.customer_name : 'Chọn khách hàng' }}
                                </button>
                                <button type="button" v-if="selectedCustomer" @click="clearCustomer"
                                    class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs hover:bg-red-200">
                                    Xóa
                                </button>
                            </div>
                        </div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-semibold text-gray-700">Tổng phụ:</span>
                            <span class="text-base font-bold text-gray-800">
                                {{ formatCurrency(cartSubtotal) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-semibold text-gray-700">Thuế VAT (0%):</span>
                            <span class="text-base font-bold text-gray-800">
                                {{ formatCurrency(cartTax) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-semibold text-gray-700">Tổng cộng:</span>
                            <span class="text-base font-bold text-gray-800">
                                {{ formatCurrency(cartTotal) }}
                            </span>
                        </div>
                        
                        <button type="submit"
                            class="w-full bg-green-600 text-white py-1.5 rounded text-xs font-semibold hover:bg-green-700"
                            :disabled="form.processing || cart.length === 0">
                            <Loader2 v-if="form.processing" class="animate-spin h-4 w-4 inline-block mr-1" />
                            Thanh toán (F8)
                        </button>
                    </form>
                </div>
            </div>
            <!-- Product Modal -->
            <div v-if="selectedProduct"
                class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-md bg-black/40 transition-opacity duration-300">
                <div
                    class="relative bg-white/80 backdrop-blur-lg border border-white/20 shadow-2xl rounded-2xl p-6 max-w-md w-[90%] text-gray-800 transform transition-all duration-300 hover:scale-[1.02] modal-content">
                    <button @click="selectedProduct = null"
                        class="absolute top-4 right-4 text-gray-600 hover:text-gray-900 bg-white/50 rounded-full p-2 hover:bg-white/80 transition-all duration-200">
                        <X class="w-5 h-5" />
                    </button>
                    <div class="flex flex-col items-center">
                        <img :src="selectedProduct.image" :alt="selectedProduct.name"
                            class="w-40 h-40 object-cover rounded-lg mb-4 shadow-md"
                            @error="handleImageError($event, selectedProduct)" />
                        <h3 class="text-xl font-bold text-gray-900 mb-3 text-center">{{ selectedProduct.name }}</h3>
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
                        <button @click="selectedProduct = null"
                            class="mt-6 w-full bg-blue-600 text-white py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-all duration-200 shadow-md">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
            <div v-if="showScanner" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-full max-w-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold">Quét mã vạch</h2>
                        <button @click="showScanner = false" class="text-gray-600 hover:text-gray-800">
                            <X class="w-6 h-6" />
                        </button>
                    </div>
                    <div class="mb-4">
                        <StreamBarcodeReader @decode="onBarcodeScanned" :torch="false" class="w-full h-64" />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Nhập mã vạch thủ công</label>
                        <input v-model="manualBarcode" type="text"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Nhập mã vạch..." @keyup.enter="handleManualBarcode(manualBarcode)" />
                    </div>
                    <div class="flex justify-end">
                        <button @click="showScanner = false"
                            class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
            <!-- Payment Confirmation Modal -->
            <div v-if="showPaymentModal"
                class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-lg bg-black/50 transition-opacity duration-300">
                <div
                    class="relative bg-white/90 backdrop-blur-xl border border-gray-200 shadow-2xl rounded-2xl p-8 max-w-3xl w-[95%] text-gray-800 transform transition-all duration-300 modal-content">
                    <button @click="showPaymentModal = false"
                        class="absolute top-4 right-4 text-gray-600 hover:text-red-600 text-2xl font-bold transition-all duration-200">
                        &times;
                    </button>
                    <div class="space-y-6 max-h-[80vh] overflow-y-auto">
                        <!-- Header -->
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-2xl font-bold text-gray-900">Xác nhận thanh toán</h2>
                            <span class="text-sm text-gray-600">Nhân viên: <strong>{{ props.auth?.user?.name || 'N/A'
                            }}</strong></span>
                        </div>
                        <!-- Bill Information in Rectangular Frame -->
                        <div class="bg-gray-50 border border-gray-300 rounded-lg p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">Hóa đơn bán hàng</h3>
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
                                <h4 class="font-semibold text-sm mb-2">Danh sách sản phẩm:</h4>
                                <div class="border-t border-b border-gray-200 py-2">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="bg-gray-100 text-left">
                                                <th class="p-3 font-semibold">Tên hàng</th>
                                                <th class="p-3 font-semibold text-center">Số lượng</th>
                                                <th class="p-3 font-semibold text-right">Đơn giá</th>
                                                <th class="p-3 font-semibold text-right">Thành tiền</th>
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
                                                <td class="p-3 text-right">{{ formatCurrency(item.price * item.quantity)
                                                }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- Totals -->
                            <div class="mt-4 text-right space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="font-semibold">Tổng tiền hàng:</span>
                                    <span>{{ formatCurrency(cartSubtotal) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Giảm giá:</span>
                                    <span>{{ formatCurrency(0) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Thuế VAT (0%):</span>
                                    <span>{{ formatCurrency(cartTax) }}</span>
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
                            <select v-model="form.paymentMethod"
                                class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white shadow-sm"
                                :class="{ 'border-red-500': form.errors.paymentMethod }">
                                <option value="cash">Tiền mặt</option>
                                <option value="credit_card">Thẻ ngân hàng</option>
                                <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                                <option value="wallet"
                                    :disabled="!selectedCustomer || selectedCustomer.wallet < cartTotal">
                                    Ví khách hàng
                                </option>
                            </select>
                            <InputError class="mt-1 text-[10px]" :message="form.errors.paymentMethod" />
                        </div>
                        <!-- Cash Payment -->
                        <div v-if="form.paymentMethod === 'cash'" class="space-y-3 text-sm">
                            <label class="block font-semibold">Khách đưa:</label>
                            <div class="flex flex-wrap gap-2 mb-2">
                                <button v-for="amount in quickAmounts" :key="amount" @click="setAmountReceived(amount)"
                                    class="bg-blue-100 text-blue-700 px-3 py-1.5 rounded-lg text-xs hover:bg-blue-200 transition-all shadow-sm">
                                    {{ formatCurrency(amount) }}
                                </button>
                                <button @click="setExactAmount"
                                    class="bg-green-100 text-green-700 px-3 py-1.5 rounded-lg text-xs hover:bg-green-200 transition-all shadow-sm">
                                    Chính xác
                                </button>
                            </div>
                            <input type="number" v-model.number="form.amountReceived"
                                class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white shadow-sm"
                                min="0" placeholder="Nhập số tiền khách đưa"
                                :class="{ 'border-red-500': form.errors.amountReceived }" />
                            <InputError class="mt-1 text-[10px]" :message="form.errors.amountReceived" />
                            <div class="flex justify-between text-sm">
                                <span class="font-semibold">Tiền thối lại:</span>
                                <span class="font-bold text-blue-600">{{ formatCurrency(change) }}</span>
                            </div>
                        </div>
                        <!-- Bank QR Code -->
                        <div v-if="form.paymentMethod === 'bank_transfer'" class="space-y-3 text-sm">
                            <div class="flex justify-between items-center">
                                <label class="block font-semibold">Thanh toán ngân hàng:</label>
                                <button @click="generateBankQR"
                                    class="bg-blue-600 text-white px-4 py-1.5 rounded-lg text-xs hover:bg-blue-700 transition-all shadow-sm"
                                    :disabled="isLoadingBankQR">
                                    <Loader2 v-if="isLoadingBankQR" class="animate-spin h-4 w-4 inline-block mr-1" />
                                    {{ bankQRCode ? 'Tạo lại mã QR' : 'Tạo mã QR ngân hàng' }}
                                </button>
                            </div>
                            <div v-if="isLoadingBankQR" class="text-center">
                                <Loader2 class="animate-spin h-6 w-6 mx-auto text-blue-500" />
                                <p class="mt-2">Đang tạo mã QR...</p>
                            </div>
                            <div v-else-if="bankQRCode" class="flex flex-col md:flex-row gap-4">
                                <div class="text-center">
                                    <img :src="bankQRCode" alt="Mã QR ngân hàng"
                                        class="mx-auto w-48 h-48 rounded-lg shadow-md" />
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
                        <!-- Notes -->
                        <div class="space-y-3 text-sm">
                            <label class="block font-semibold">Ghi chú:</label>
                            <textarea v-model="form.orderNotes"
                                class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white shadow-sm"
                                rows="3" placeholder="Ví dụ: Giao tận nơi..."></textarea>
                            <InputError class="mt-1 text-[10px]" :message="form.errors.orderNotes" />
                        </div>
                        <!-- Print Receipt Option -->
                        <div class="space-y-3 text-sm">
                            <label class="inline-flex items-center space-x-2">
                                <input type="checkbox" v-model="form.printReceipt"
                                    class="form-checkbox h-5 w-5 text-blue-600 rounded" />
                                <span>In hóa đơn ngay</span>
                            </label>
                        </div>
                        <!-- Footer -->
                        <div class="mt-6 flex justify-end space-x-4">
                            <button @click="showPaymentModal = false"
                                class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-300 transition-all shadow-sm">
                                Hủy
                            </button>
                            <button @click="confirmPayment"
                                class="bg-green-600 text-white px-6 py-2 rounded-lg text-sm font-semibold hover:bg-green-700 transition-all shadow-sm"
                                :disabled="form.processing || cart.length === 0">
                                <Loader2 v-if="form.processing" class="animate-spin h-5 w-5 inline-block mr-2" />
                                Xác nhận & In
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CashierLayout>

</template>