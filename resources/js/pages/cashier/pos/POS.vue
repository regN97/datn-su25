<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import POSKeyboardHandler from '@/components/cashier/POSKeyboardHandler.vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { MenuIcon, BadgeInfo, X, FileText, LogOutIcon, Loader2 } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import axios from 'axios';
import { DateTime } from 'luxon';

const { props } = usePage();

// Khởi tạo trạng thái từ props
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
const customersKey = ref(0);
const shiftReport = ref(null);
const isLoadingReport = ref(false);
const isLoadingSessionAction = ref(false);
const showShiftExpiredModal = ref(false);
const showShiftEndingSoonModal = ref(false); // Modal cảnh báo ca gần hết giờ
const shiftEndingSoonMessage = ref('');
const shiftAutoCloseTimeout = ref(null); // Timeout để tự động đóng ca
const quickAmounts = [100000, 200000, 500000, 1000000];

// Form quản lý ca làm việc
const sessionForm = useForm({
    opening_amount: 0,
    closing_amount: 0,
    notes: '',
    shift_id: '',
});

// Form quản lý khách hàng và thanh toán
const form = useForm({
    customer_name: '',
    email: '',
    phone: '',
    address: null,
    wallet: 0,
    cart: [],
    customer_id: null,
    paymentMethod: 'cash',
    amountReceived: '',
    orderNotes: '',
    couponCode: null,
});

// Tính toán danh sách danh mục
const categories = computed(() => ['Tất cả', ...new Set(products.value.map(p => p.category))]);

// Định dạng ngày giờ
const formattedOpenedAt = computed(() => {
    if (!activeShift.value?.opened_at) return 'N/A';
    return formatDateTime(activeShift.value.opened_at);
});

// Tìm ca làm việc phù hợp
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

// Kiểm tra ca làm việc hết hạn hoặc gần hết giờ
const checkShiftExpiration = () => {
    if (!activeShift.value || !hasActiveSession.value) return;

    const now = DateTime.now().setZone('Asia/Ho_Chi_Minh');
    const currentTime = now.toFormat('HH:mm:ss');
    const shift = workShifts.value.find(s => s.id === activeShift.value.shift_id);

    if (shift) {
        const { start_time, end_time, name } = shift;
        const startTime = DateTime.fromFormat(start_time, 'HH:mm:ss', { zone: 'Asia/Ho_Chi_Minh' });
        const endTime = DateTime.fromFormat(end_time, 'HH:mm:ss', { zone: 'Asia/Ho_Chi_Minh' });
        let isExpired = false;
        let timeUntilEnd;

        // Tính thời gian còn lại đến khi hết ca
        if (start_time > end_time) {
            // Ca làm việc kéo dài qua ngày (ví dụ: 22:00 - 06:00)
            isExpired = currentTime > end_time && currentTime < start_time;
            timeUntilEnd = endTime > now ? endTime.diff(now, 'minutes').minutes : endTime.plus({ days: 1 }).diff(now, 'minutes').minutes;
        } else {
            isExpired = currentTime > end_time;
            timeUntilEnd = endTime.diff(now, 'minutes').minutes;
        }

        // Kiểm tra nếu ca gần hết giờ (còn dưới 10 phút)
        if (timeUntilEnd <= 10 && timeUntilEnd > 0 && !showShiftExpiredModal.value && !showShiftEndingSoonModal.value) {
            shiftEndingSoonMessage.value = `Ca làm việc "${name}" sẽ kết thúc sau ${Math.ceil(timeUntilEnd)} phút.`;
            showShiftEndingSoonModal.value = true;
            autoHideMessage();
        }

        // Kiểm tra nếu ca đã hết giờ
        if (isExpired && !showShiftExpiredModal.value) {
            errorMessage.value = `Ca làm việc "${name}" đã hết giờ. Bạn có muốn đóng ca ngay bây giờ?`;
            showShiftExpiredModal.value = true;
            showShiftEndingSoonModal.value = false;
            autoHideMessage();

            // Tự động đóng ca sau 1 giờ nếu vẫn chưa đóng
            if (shiftAutoCloseTimeout.value) {
                clearTimeout(shiftAutoCloseTimeout.value);
            }
            shiftAutoCloseTimeout.value = setTimeout(async () => {
                await autoCloseShift();
            }, 60 * 60 * 1000); // 1 giờ = 3600000ms
        }
    }
};

// Tự động đóng ca
const autoCloseShift = async () => {
    if (!hasActiveSession.value) return;

    isLoadingSessionAction.value = true;
    sessionForm.closing_amount = 0; // Có thể cần lấy giá trị thực tế từ hệ thống
    sessionForm.notes = 'Tự động đóng ca do quá thời gian 1 giờ sau khi hết ca';

    try {
        const response = await axios.post('/cashier/pos/session/close', {
            closing_amount: Number(sessionForm.closing_amount) || 0,
            notes: sessionForm.notes || '',
        });
        hasActiveSession.value = false;
        activeShift.value = null;
        successMessage.value = response.data.success || 'Ca làm việc đã được tự động đóng thành công!';
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
        errorMessage.value = error.response?.data?.errors?.server || 'Có lỗi khi tự động đóng ca làm việc.';
        autoHideMessage();
    } finally {
        isLoadingSessionAction.value = false;
    }
};

// Lọc sản phẩm
const filteredProducts = computed(() => {
    let filtered = [...products.value];

    if (searchTerm.value.trim()) {
        const regex = new RegExp(searchTerm.value.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
        filtered = filtered.filter(p =>
            regex.test(p.name) ||
            regex.test(p.category) ||
            regex.test(p.sku) ||
            regex.test(p.barcode)
        );
    }

    if (selectedCategory.value !== 'Tất cả') {
        filtered = filtered.filter(p => p.category === selectedCategory.value);
    }

    if (priceRange.value.min || priceRange.value.max) {
        const min = priceRange.value.min ? parseFloat(priceRange.value.min) : -Infinity;
        const max = priceRange.value.max ? parseFloat(priceRange.value.max) : Infinity;
        filtered = filtered.filter(p => (p.price || 0) >= min && (p.price || 0) <= max);
    }

    if (stockRange.value.min || stockRange.value.max) {
        const min = stockRange.value.min ? parseInt(stockRange.value.min) : -Infinity;
        const max = stockRange.value.max ? parseInt(stockRange.value.max) : Infinity;
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

// Lọc khách hàng
const filteredCustomers = computed(() => {
    if (!customerSearch.value.trim()) return customers.value;
    const regex = new RegExp(customerSearch.value.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
    return customers.value.filter(c =>
        regex.test(c.customer_name) ||
        (c.email && regex.test(c.email)) ||
        (c.phone && regex.test(c.phone))
    );
});

// Tính toán giỏ hàng
const cartSubtotal = computed(() =>
    cart.value.reduce((total, item) => total + ((item.price || 0) * item.quantity), 0)
);

const cartTax = computed(() => cartSubtotal.value * 0);

const cartTotal = computed(() => cartSubtotal.value + cartTax.value);

const change = computed(() => {
    if (form.paymentMethod === 'cash' && form.amountReceived >= cartTotal.value) {
        return form.amountReceived - cartTotal.value;
    }
    return 0;
});

// Định dạng tiền tệ
const formatCurrency = (amount) => {
    const validAmount = Number(amount) || 0;
    return validAmount.toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
};

// Định dạng ngày giờ
const formatDateTime = (dateTime) => {
    if (!dateTime || typeof dateTime !== 'string') {
        console.warn(`Invalid DateTime input: ${dateTime}`);
        return 'N/A';
    }
    const dt = DateTime.fromISO(dateTime, { zone: 'Asia/Ho_Chi_Minh' });
    if (!dt.isValid) {
        console.warn(`Invalid DateTime format: ${dateTime}`);
        return 'N/A';
    }
    return dt.toFormat('dd/MM/yyyy HH:mm:ss');
};

// Tự động ẩn thông báo
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

// Lấy danh sách ca làm việc
const fetchWorkShifts = async () => {
    try {
        const response = await axios.get('/cashier/pos/work-shifts');
        workShifts.value = response.data.shifts;
        const suitableShift = getSuitableShift();
        sessionForm.shift_id = suitableShift ? suitableShift.id : '';
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Không thể tải danh sách ca làm việc.';
        autoHideMessage();
    }
};

// Lấy báo cáo ca
const fetchShiftReport = async () => {
    if (isLoadingReport.value || !hasActiveSession.value) return;
    isLoadingReport.value = true;
    try {
        const response = await axios.get('/cashier/pos/shift-report', {
            headers: { 'Cache-Control': 'no-cache' },
        });
        shiftReport.value = response.data;
        hasActiveSession.value = response.data.hasActiveSession || false;
        if (shiftReport.value.report?.customers) {
            shiftReport.value.report.customers = shiftReport.value.report.customers.map(customer => ({
                ...customer,
                customer_name: customer.customer_name || 'Khách lẻ',
            }));
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Không thể tải báo cáo ca.';
        showReportSidebar.value = false;
        autoHideMessage();
    } finally {
        isLoadingReport.value = false;
    }
};

// Mở ca làm việc
const openShift = async () => {
    if (!sessionForm.shift_id) {
        errorMessage.value = 'Vui lòng chọn ca làm việc phù hợp.';
        autoHideMessage();
        return;
    }
    if (!confirm('Bạn có chắc chắn muốn mở ca làm việc?')) return;
    isLoadingSessionAction.value = true;
    sessionForm.clearErrors();
    try {
        const response = await axios.post('/cashier/pos/session/start', {
            opening_amount: Number(sessionForm.opening_amount) || 0,
            notes: sessionForm.notes || '',
            shift_id: sessionForm.shift_id,
        });
        hasActiveSession.value = response.data.hasActiveSession || true;
        activeShift.value = response.data.activeShift;
        successMessage.value = response.data.success || 'Ca làm việc đã được mở thành công!';
        showSuccessModal.value = true;
        showShiftExpiredModal.value = false;
        sessionForm.reset();
        autoHideMessage();
        await fetchShiftReport();
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Có lỗi khi mở ca làm việc.';
        if (error.response?.data?.errors) {
            Object.values(error.response.data.errors).forEach(err => {
                errorMessage.value += ` ${err}`;
            });
        }
        autoHideMessage();
    } finally {
        isLoadingSessionAction.value = false;
    }
};

// Đóng ca làm việc
const closeShift = async () => {
    if (!confirm('Bạn có chắc chắn muốn đóng ca làm việc?')) return;
    isLoadingSessionAction.value = true;
    sessionForm.clearErrors();
    try {
        const response = await axios.post('/cashier/pos/session/close', {
            closing_amount: Number(sessionForm.closing_amount) || 0,
            notes: sessionForm.notes || '',
        });
        hasActiveSession.value = false;
        activeShift.value = null;
        successMessage.value = response.data.success || 'Ca làm việc đã được đóng thành công!';
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
        errorMessage.value = error.response?.data?.errors?.server || 'Có lỗi khi đóng ca làm việc.';
        if (error.response?.data?.errors) {
            Object.values(error.response.data.errors).forEach(err => {
                errorMessage.value += ` ${err}`;
            });
        }
        autoHideMessage();
    } finally {
        isLoadingSessionAction.value = false;
    }
};

// Tạo báo cáo ca
const generateShiftReport = async () => {
    if (!hasActiveSession.value) {
        errorMessage.value = 'Không có ca làm việc đang mở.';
        autoHideMessage();
        return;
    }
    if (!confirm('Bạn có chắc chắn muốn tạo báo cáo ca?')) return;
    isLoadingSessionAction.value = true;
    try {
        const response = await axios.post('/cashier/pos/shift-report/generate');
        successMessage.value = response.data.message || 'Báo cáo ca đã được tạo thành công!';
        showSuccessModal.value = true;
        autoHideMessage();
        await fetchShiftReport();
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Không thể tạo báo cáo ca.';
        autoHideMessage();
    } finally {
        isLoadingSessionAction.value = false;
    }
};

// Đăng xuất
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
            if (!confirm('Bạn có chắc chắn muốn đóng ca trước khi đăng xuất?')) {
                await axios.post('/cashier/logout');
                window.location.href = '/cashier/login';
                return;
            }
            await axios.post('/cashier/pos/session/close', {
                closing_amount: Number(sessionForm.closing_amount) || 0,
                notes: sessionForm.notes || '',
            });
            hasActiveSession.value = false;
            activeShift.value = null;
            shiftReport.value = null;
            successMessage.value = 'Ca làm việc đã được đóng thành công!';
            showSuccessModal.value = true;
            autoHideMessage();
        }
        await axios.post('/cashier/logout');
        window.location.href = '/cashier/login';
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Có lỗi khi đăng xuất.';
        showLogoutModal.value = false;
        autoHideMessage();
    }
};

// Thêm sản phẩm vào giỏ hàng
const addToCart = async (product) => {
    if (!hasActiveSession.value) {
        errorMessage.value = 'Vui lòng mở ca trước khi thêm sản phẩm vào giỏ hàng.';
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
        if (!response.data.hasValidBatch) {
            errorMessage.value = response.data.message || `Không có lô hàng hợp lệ cho sản phẩm ${product.name}.`;
            autoHideMessage();
            return;
        }
        const existingItem = cart.value.find(item => item.id === product.id);
        if (existingItem) {
            const newQuantity = existingItem.quantity + 1;
            const stockResponse = await axios.get(`/cashier/pos/check-batch/${product.id}`, { params: { quantity: newQuantity } });
            if (stockResponse.data.hasValidBatch) {
                existingItem.quantity = newQuantity;
            } else {
                errorMessage.value = `Số lượng tối đa cho sản phẩm ${product.name} là ${product.stock}.`;
                autoHideMessage();
            }
        } else {
            cart.value.push({ ...product, quantity: 1 });
        }
        const productIndex = products.value.findIndex(p => p.id === product.id);
        if (productIndex !== -1) {
            products.value[productIndex].stock = response.data.availableStock || 0;
        }
    } catch (error) {
        if (error.response?.status === 422 && error.response.data?.errors?.server.includes('Ca làm việc đã hết hạn')) {
            showShiftExpiredModal.value = true;
            errorMessage.value = error.response.data.errors.server;
        } else {
            errorMessage.value = error.response?.data?.errors?.server || `Lỗi khi kiểm tra lô hàng cho sản phẩm ${product.name}.`;
        }
        autoHideMessage();
    }
};

// Cập nhật số lượng sản phẩm trong giỏ hàng
const updateQuantity = async (productId, quantity) => {
    const item = cart.value.find(item => item.id === productId);
    if (!item) return;

    const product = products.value.find(p => p.id === productId);
    try {
        const response = await axios.get(`/cashier/pos/check-batch/${productId}`, { params: { quantity } });
        if (!response.data.hasValidBatch) {
            errorMessage.value = response.data.message || `Không đủ lô hàng hợp lệ cho sản phẩm ${product.name} với số lượng ${quantity}.`;
            removeFromCart(productId);
            autoHideMessage();
            return;
        }
        if (quantity <= product.stock && quantity >= 1) {
            item.quantity = quantity;
        } else if (quantity < 1) {
            removeFromCart(productId);
        } else {
            errorMessage.value = `Số lượng tối đa cho sản phẩm ${product.name} là ${product.stock}.`;
            item.quantity = product.stock;
            autoHideMessage();
        }
        const productIndex = products.value.findIndex(p => p.id === productId);
        if (productIndex !== -1) {
            products.value[productIndex].stock = response.data.availableStock || 0;
        }
    } catch (error) {
        if (error.response?.status === 422 && error.response.data?.errors?.server.includes('Ca làm việc đã hết hạn')) {
            showShiftExpiredModal.value = true;
            errorMessage.value = error.response.data.errors.server;
        } else {
            errorMessage.value = error.response?.data?.errors?.server || `Lỗi khi kiểm tra lô hàng cho sản phẩm ${product.name}.`;
            removeFromCart(productId);
        }
        autoHideMessage();
    }
};

// Xóa sản phẩm khỏi giỏ hàng
const removeFromCart = (productId) => {
    cart.value = cart.value.filter(item => item.id !== productId);
};

// Đặt số tiền nhận
const setAmountReceived = (amount) => {
    form.amountReceived = amount;
};

// Đặt số tiền chính xác
const setExactAmount = () => {
    form.amountReceived = cartTotal.value;
};

// Đặt lại bộ lọc
const resetFilters = () => {
    searchTerm.value = '';
    selectedCategory.value = 'Tất cả';
    priceRange.value = { min: '', max: '' };
    stockRange.value = { min: '', max: '' };
    sortBy.value = 'none';
    sortOrder.value = 'asc';
};

// Xử lý lỗi hình ảnh
const handleImageError = (event, product) => {
    event.target.src = '/storage/default-product.png';
    product.image = '/storage/default-product.png';
};

// Mở modal chi tiết sản phẩm
const openProductModal = (product) => {
    selectedProduct.value = { ...product };
};

// Chuyển đổi sidebar lọc
const toggleFilterSidebar = () => {
    showFilterSidebar.value = !showFilterSidebar.value;
    showCustomerSidebar.value = false;
    showReportSidebar.value = false;
};

// Chuyển đổi sidebar khách hàng
const toggleCustomerSidebar = () => {
    showCustomerSidebar.value = !showCustomerSidebar.value;
    showFilterSidebar.value = false;
    showReportSidebar.value = false;
};

// Chuyển đổi sidebar báo cáo
const toggleReportSidebar = async () => {
    showReportSidebar.value = !showReportSidebar.value;
    showFilterSidebar.value = false;
    showCustomerSidebar.value = false;
    if (showReportSidebar.value && !shiftReport.value && hasActiveSession.value) {
        await fetchShiftReport();
    }
};

// Chọn khách hàng
const selectCustomer = (customer) => {
    selectedCustomer.value = customer;
    form.customer_id = customer.id;
    showCustomerSidebar.value = false;
    customerSearch.value = '';
};

// Xóa khách hàng đã chọn
const clearCustomer = () => {
    selectedCustomer.value = null;
    form.customer_id = null;
};

// Đóng tất cả modal và sidebar
const closeModalsAndSidebars = () => {
    selectedProduct.value = null;
    showFilterSidebar.value = false;
    showCustomerSidebar.value = false;
    showReportSidebar.value = false;
    showLogoutModal.value = false;
    customerSearch.value = '';
    showAddCustomerForm.value = false;
    errorMessage.value = '';
    showShiftExpiredModal.value = false;
    showShiftEndingSoonModal.value = false;
};

// Xử lý sự kiện phím
const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        closeModalsAndSidebars();
    }
};

// Xử lý click ngoài modal
const handleClickOutside = (event) => {
    if (selectedProduct.value && !event.target.closest('.modal-content')) {
        selectedProduct.value = null;
    }
    if (showLogoutModal.value && !event.target.closest('.logout-modal-content')) {
        showLogoutModal.value = false;
    }
    if (showShiftExpiredModal.value && !event.target.closest('.shift-expired-modal-content')) {
        showShiftExpiredModal.value = false;
    }
    if (showShiftEndingSoonModal.value && !event.target.closest('.shift-ending-soon-modal-content')) {
        showShiftEndingSoonModal.value = false;
    }
};

// Xử lý phím tắt
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
    submitSale();
};

// Chuyển đổi form thêm khách hàng
const toggleAddCustomerForm = () => {
    showAddCustomerForm.value = !showAddCustomerForm.value;
    if (showAddCustomerForm.value) {
        form.reset('customer_name', 'email', 'phone', 'address', 'wallet');
        errorMessage.value = '';
        nextTick(() => {
            document.querySelector('#customer_name')?.focus();
        });
    } else {
        form.clearErrors();
    }
};

// Thêm khách hàng mới
const submitNewCustomer = async () => {
    const submittedData = {
        customer_name: (form.customer_name || '').trim(),
        phone: (form.phone || '').trim(),
        email: (form.email || '').trim() || null,
        address: (form.address || '').trim() || null,
        wallet: Number(form.wallet) || 0,
    };

    if (!submittedData.customer_name || !submittedData.phone) {
        errorMessage.value = 'Tên và số điện thoại là bắt buộc.';
        autoHideMessage();
        return;
    }

    try {
        const response = await axios.post('/cashier/pos/customers', submittedData);
        customers.value = response.data.customers;
        customerSearch.value = '';
        showCustomerSidebar.value = true;
        successMessage.value = response.data.success || 'Khách hàng đã được thêm thành công!';
        showSuccessModal.value = true;
        toggleAddCustomerForm();
        if (response.data.newCustomer) {
            selectCustomer(response.data.newCustomer);
        }
        customersKey.value += 1;
        autoHideMessage();
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.server || 'Có lỗi khi thêm khách hàng.';
        if (error.response?.data?.errors) {
            Object.values(error.response.data.errors).forEach(err => {
                errorMessage.value += ` ${err}`;
            });
        }
        autoHideMessage();
    }
};

// Gửi giao dịch bán hàng
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
    if (form.paymentMethod === 'cash' && form.amountReceived < cartTotal.value) {
        errorMessage.value = 'Số tiền khách đưa không đủ.';
        autoHideMessage();
        return;
    }
    if (form.paymentMethod === 'wallet' && (!selectedCustomer.value || selectedCustomer.value.wallet < cartTotal.value)) {
        errorMessage.value = 'Số dư ví khách hàng không đủ hoặc chưa chọn khách hàng.';
        autoHideMessage();
        return;
    }

    form.cart = cart.value;
    form.customer_id = selectedCustomer.value ? selectedCustomer.value.id : null;

    try {
        const response = await axios.post('/cashier/pos/sale', {
            cart: form.cart.map(item => ({
                id: item.id,
                quantity: item.quantity,
            })),
            customer_id: form.customer_id,
            paymentMethod: form.paymentMethod,
            amountReceived: form.paymentMethod === 'cash' ? Number(form.amountReceived) : cartTotal.value,
            orderNotes: form.orderNotes || '',
            couponCode: form.couponCode || null,
        });
        cart.value = [];
        form.reset('cart', 'customer_id', 'paymentMethod', 'amountReceived', 'orderNotes', 'couponCode');
        clearCustomer();
        successMessage.value = response.data.success || 'Thanh toán thành công!';
        showSuccessModal.value = true;
        products.value = response.data.products || products.value;
        hasActiveSession.value = response.data.hasActiveSession || true;
        autoHideMessage();
        await fetchShiftReport();
    } catch (error) {
        if (error.response?.status === 422 && error.response.data?.errors?.server.includes('Ca làm việc đã hết hạn')) {
            showShiftExpiredModal.value = true;
            errorMessage.value = error.response.data.errors.server;
        } else {
            errorMessage.value = error.response?.data?.errors?.server || 'Có lỗi khi xử lý thanh toán.';
            if (error.response?.data?.errors) {
                Object.values(error.response.data.errors).forEach(err => {
                    errorMessage.value += ` ${err}`;
                });
            }
        }
        autoHideMessage();
    }
};

// Sự kiện mounted
onMounted(async () => {
    window.addEventListener('keydown', handleKeydown);
    window.addEventListener('click', handleClickOutside);
    await fetchWorkShifts();
    if (hasActiveSession.value) {
        await fetchShiftReport();
        checkShiftExpiration();
        setInterval(checkShiftExpiration, 60000); // Kiểm tra mỗi phút
    }
    if (props.flash?.success) {
        showSuccessModal.value = true;
        autoHideMessage();
    }
});

// Sự kiện unmounted
onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    window.removeEventListener('click', handleClickOutside);
    if (shiftAutoCloseTimeout.value) {
        clearTimeout(shiftAutoCloseTimeout.value);
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
            @apply-discount="applyDiscount"
            @hold-order="holdOrder"
            @reprint-receipt="reprintReceipt"
            @remove-last-cart-item="removeLastCartItem"
            @checkout="checkout"
            @logout="initiateLogout"
        />
        <div class="flex text-xs relative h-[650px]">
            <!-- Error Message -->
            <div v-if="errorMessage" class="fixed top-4 left-1/2 -translate-x-1/2 bg-red-100 text-red-700 px-4 py-2 rounded-lg shadow-lg text-sm z-50 flex items-center">
                {{ errorMessage }}
                <button @click="errorMessage = ''" class="ml-2 text-red-900 hover:text-red-700">✖</button>
            </div>
            <!-- Success Message -->
            <div v-if="showSuccessModal" class="fixed top-4 left-1/2 -translate-x-1/2 bg-green-100 text-green-700 px-4 py-2 rounded-lg shadow-lg text-sm z-50 flex items-center">
                {{ successMessage }}
                <button @click="showSuccessModal = false" class="ml-2 text-green-900 hover:text-green-700">✖</button>
            </div>
            <!-- Shift Ending Soon Modal -->
            <div v-if="showShiftEndingSoonModal" class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm bg-black/30">
                <div class="bg-white/70 backdrop-blur-md border border-white/30 shadow-xl rounded-xl p-4 max-w-sm w-[90%] text-sm text-gray-800 transition transform scale-95 animate-pulse shift-ending-soon-modal-content">
                    <button @click="showShiftEndingSoonModal = false" class="absolute top-2 right-2 text-gray-700 hover:text-black text-lg">✖</button>
                    <h3 class="text-base font-bold mb-3 text-center">Cảnh báo ca làm việc</h3>
                    <p class="mb-3 text-center">{{ shiftEndingSoonMessage }}</p>
                    <div class="flex space-x-2">
                        <button @click="showShiftEndingSoonModal = false" class="w-full bg-gray-300 text-gray-700 py-1.5 rounded text-xs hover:bg-gray-400">Đóng</button>
                    </div>
                </div>
            </div>
            <!-- Shift Expired Modal -->
            <div v-if="showShiftExpiredModal" class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm bg-black/30">
                <div class="bg-white/70 backdrop-blur-md border border-white/30 shadow-xl rounded-xl p-4 max-w-sm w-[90%] text-sm text-gray-800 transition transform scale-95 animate-pulse shift-expired-modal-content">
                    <button @click="showShiftExpiredModal = false" class="absolute top-2 right-2 text-gray-700 hover:text-black text-lg">✖</button>
                    <h3 class="text-base font-bold mb-3 text-center">Ca làm việc đã hết hạn</h3>
                    <p class="mb-3 text-center">{{ errorMessage }}</p>
                    <div class="flex space-x-2">
                        <button @click="closeShift" class="w-full bg-blue-600 text-white py-1.5 rounded text-xs font-semibold hover:bg-blue-700">Đóng ca</button>
                    </div>
                </div>
            </div>
            <!-- Logout Modal -->
            <div v-if="showLogoutModal" class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm bg-black/30">
                <div class="bg-white/70 backdrop-blur-md border border-white/30 shadow-xl rounded-xl p-4 max-w-sm w-[90%] text-sm text-gray-800 transition transform scale-95 animate-pulse logout-modal-content">
                    <button @click="showLogoutModal = false" class="absolute top-2 right-2 text-gray-700 hover:text-black text-lg">✖</button>
                    <h3 class="text-base font-bold mb-3 text-center">Xác nhận đăng xuất</h3>
                    <p class="mb-3 text-center">Bạn có muốn đóng ca làm việc trước khi đăng xuất?</p>
                    <form @submit.prevent="performLogout(true)">
                        <div class="mb-2">
                            <label for="logout_closing_amount" class="block text-xs font-medium text-gray-700 mb-1">Số tiền đóng ca (VND)</label>
                            <input type="number" v-model.number="sessionForm.closing_amount" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" id="logout_closing_amount" min="0" required />
                            <InputError class="mt-0.5 text-[10px]" :message="sessionForm.errors.closing_amount" />
                        </div>
                        <div class="mb-2">
                            <label for="logout_notes" class="block text-xs font-medium text-gray-700 mb-1">Ghi chú</label>
                            <textarea v-model="sessionForm.notes" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" id="logout_notes" rows="3"></textarea>
                        </div>
                        <div class="flex space-x-2">
                            <button type="button" @click="performLogout(false)" class="w-1/2 bg-gray-300 text-gray-700 py-1.5 rounded text-xs hover:bg-gray-400" :disabled="isLoadingSessionAction">Đăng xuất không đóng ca</button>
                            <button type="submit" class="w-1/2 bg-blue-600 text-white py-1.5 rounded text-xs font-semibold hover:bg-blue-700" :disabled="isLoadingSessionAction || sessionForm.processing">Đóng ca và đăng xuất</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Filter Sidebar -->
            <div :class="{ 'translate-x-full': !showFilterSidebar, 'translate-x-0': showFilterSidebar }" class="fixed inset-y-0 right-0 w-80 bg-white shadow-xl z-50 transform transition-transform duration-300">
                <div class="p-3 h-full flex flex-col">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-sm font-semibold">Bộ lọc</h3>
                        <button @click="toggleFilterSidebar" class="bg-gray-300 text-gray-800 py-1 px-2 rounded text-xs hover:bg-gray-400">Đóng</button>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <div>
                            <p class="text-[11px] text-gray-600 mt-0.5">Sản phẩm phù hợp: {{ filteredProducts.length }}</p>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Danh mục:</label>
                                <select v-model="selectedCategory" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Khoảng giá (VND):</label>
                                <div class="flex space-x-2">
                                    <input type="number" v-model="priceRange.min" placeholder="Từ" class="w-1/2 p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                    <input type="number" v-model="priceRange.max" placeholder="Đến" class="w-1/2 p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Số lượng tồn:</label>
                                <div class="flex space-x-2">
                                    <input type="number" v-model="stockRange.min" placeholder="Từ" class="w-1/2 p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                    <input type="number" v-model="stockRange.max" placeholder="Đến" class="w-1/2 p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Sắp xếp theo:</label>
                                <select v-model="sortBy" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="none">Không sắp xếp</option>
                                    <option value="price">Giá</option>
                                    <option value="stock">Số lượng tồn</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Thứ tự:</label>
                                <select v-model="sortOrder" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="asc">Tăng dần</option>
                                    <option value="desc">Giảm dần</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Customer Sidebar -->
            <div :class="{ 'translate-x-full': !showCustomerSidebar, 'translate-x-0': showCustomerSidebar }" class="fixed inset-y-0 right-0 w-80 bg-white shadow-xl z-50 transform transition-transform duration-300">
                <div class="p-3 h-full flex flex-col">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-sm font-semibold">Chọn khách hàng</h3>
                        <button @click="toggleCustomerSidebar" class="bg-gray-300 text-gray-800 py-1 px-2 rounded text-xs hover:bg-gray-400">Đóng</button>
                    </div>
                    <div class="mb-2">
                        <input type="text" v-model="customerSearch" placeholder="Tìm kiếm khách hàng (tên, email, số điện thoại)" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div class="mb-2">
                        <button @click="toggleAddCustomerForm" class="w-full bg-blue-100 text-blue-700 py-1 rounded text-xs hover:bg-blue-200">{{ showAddCustomerForm ? 'Hủy thêm khách hàng' : 'Thêm khách hàng mới' }}</button>
                    </div>
                    <div v-if="showAddCustomerForm" class="mb-2">
                        <form class="space-y-2" @submit.prevent="submitNewCustomer">
                            <div>
                                <label for="customer_name" class="block text-xs font-medium text-gray-700 mb-1">Tên khách hàng <span class="text-red-500">*</span></label>
                                <input type="text" id="customer_name" v-model="form.customer_name" placeholder="Nhập tên khách hàng" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.customer_name }" />
                                <InputError class="mt-0.5 text-[10px]" :message="form.errors.customer_name" />
                            </div>
                            <div>
                                <label for="email" class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" id="email" v-model="form.email" placeholder="Nhập email" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.email }" />
                                <InputError class="mt-0.5 text-[10px]" :message="form.errors.email" />
                            </div>
                            <div>
                                <label for="phone" class="block text-xs font-medium text-gray-700 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
                                <input type="tel" id="phone" v-model="form.phone" placeholder="Nhập số điện thoại" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.phone }" />
                                <InputError class="mt-0.5 text-[10px]" :message="form.errors.phone" />
                            </div>
                            <div>
                                <label for="address" class="block text-xs font-medium text-gray-700 mb-1">Địa chỉ</label>
                                <input type="text" id="address" v-model="form.address" placeholder="Nhập địa chỉ khách hàng" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.address }" />
                                <InputError class="mt-0.5 text-[10px]" :message="form.errors.address" />
                            </div>
                            <div>
                                <label for="wallet" class="block text-xs font-medium text-gray-700 mb-1">Ví tiền</label>
                                <input type="number" id="wallet" v-model.number="form.wallet" placeholder="Nhập số tiền trong ví" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-500': form.errors.wallet }" />
                                <InputError class="mt-0.5 text-[10px]" :message="form.errors.wallet" />
                            </div>
                            <div class="flex space-x-2">
                                <button type="button" @click="toggleAddCustomerForm" class="w-1/2 bg-gray-300 text-gray-700 py-1.5 rounded text-xs hover:bg-gray-400">Hủy</button>
                                <button type="submit" class="w-1/2 bg-blue-600 text-white py-1.5 rounded text-xs font-semibold hover:bg-blue-700" :disabled="form.processing">Lưu khách hàng</button>
                            </div>
                        </form>
                    </div>
                    <div v-else class="flex-1 overflow-y-auto">
                        <div v-for="customer in filteredCustomers" :key="customer.id" class="p-2 border-b border-gray-200 hover:bg-gray-100 cursor-pointer" @click="selectCustomer(customer)">
                            <p class="font-semibold">{{ customer.customer_name }}</p>
                            <p v-if="customer.phone" class="text-[10px] text-gray-600">{{ customer.phone }}</p>
                            <p v-if="customer.address" class="text-[10px] text-gray-600">{{ customer.address }}</p>
                            <p class="text-[10px] text-gray-600">Ví: {{ formatCurrency(customer.wallet) }}</p>
                        </div>
                        <div v-if="filteredCustomers.length === 0" class="text-center text-gray-500 py-2 text-[11px]">Không tìm thấy khách hàng</div>
                    </div>
                </div>
            </div>
            <!-- Report Sidebar -->
            <div :class="{ 'translate-x-full': !showReportSidebar, 'translate-x-0': showReportSidebar }" class="fixed inset-y-0 right-0 w-80 bg-gradient-to-b from-blue-50 to-white shadow-2xl z-50 transform transition-transform duration-300 rounded-l-lg">
                <div class="p-4 h-full flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-base font-bold text-gray-800 flex items-center">
                            <FileText class="w-5 h-5 mr-2 text-blue-600" />
                            Báo cáo ca làm việc
                        </h3>
                        <button @click="toggleReportSidebar" class="bg-gray-200 text-gray-800 py-1 px-2 rounded text-xs hover:bg-gray-300 transition">Đóng</button>
                    </div>
                    <div class="flex-1 overflow-y-auto space-y-4">
                        <div class="space-y-3">
                            <button v-if="!hasActiveSession" @click="openShift" class="w-full bg-green-600 text-white py-2 rounded-lg text-xs font-semibold hover:bg-green-700 transition shadow-md" :disabled="isLoadingSessionAction || sessionForm.processing">
                                <Loader2 v-if="isLoadingSessionAction" class="animate-spin h-4 w-4 inline-block mr-1" />
                                Mở Ca
                            </button>
                            <button @click="generateShiftReport" class="w-full bg-blue-600 text-white py-2 rounded-lg text-xs font-semibold hover:bg-blue-700 transition shadow-md" :disabled="!hasActiveSession || isLoadingSessionAction">
                                <Loader2 v-if="isLoadingSessionAction" class="animate-spin h-4 w-4 inline-block mr-1" />
                                Tạo Báo cáo
                            </button>
                            <button v-if="hasActiveSession" @click="closeShift" class="w-full bg-red-600 text-white py-2 rounded-lg text-xs font-semibold hover:bg-red-700 transition shadow-md" :disabled="isLoadingSessionAction || sessionForm.processing">
                                <Loader2 v-if="isLoadingSessionAction" class="animate-spin h-4 w-4 inline-block mr-1" />
                                Đóng Ca
                            </button>
                            <button @click="initiateLogout" class="w-full bg-gray-600 text-white py-2 rounded-lg text-xs font-semibold hover:bg-gray-700 transition shadow-md">
                                <LogOutIcon class="w-4 h-4 inline-block mr-1" />
                                Đăng xuất
                            </button>
                        </div>
                        <div v-if="!hasActiveSession" class="space-y-3 bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                            <h4 class="text-xs font-semibold text-gray-800">Mở ca mới</h4>
                            <form @submit.prevent="openShift">
                                <div class="mb-2">
                                    <label for="shift_id" class="block text-xs font-medium text-gray-700 mb-1">Chọn ca làm việc <span class="text-red-500">*</span></label>
                                    <select
                                        v-model="sessionForm.shift_id"
                                        class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                        id="shift_id"
                                        required
                                    >
                                        <option value="" disabled>Chọn ca</option>
                                        <option v-for="shift in workShifts" :key="shift.id" :value="shift.id" :disabled="!shift.is_suitable">
                                            {{ shift.name }} ({{ shift.start_time }} - {{ shift.end_time }}) {{ shift.is_suitable ? '' : '(Hết giờ)' }}
                                        </option>
                                    </select>
                                    <InputError class="mt-0.5 text-[10px]" :message="sessionForm.errors.shift_id" />
                                </div>
                                <div class="mb-2">
                                    <label for="opening_amount" class="block text-xs font-medium text-gray-700 mb-1">Số tiền mở ca (VND)</label>
                                    <input
                                        type="number"
                                        v-model.number="sessionForm.opening_amount"
                                        class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                        id="opening_amount"
                                        min="0"
                                        required
                                    />
                                    <InputError class="mt-0.5 text-[10px]" :message="sessionForm.errors.opening_amount" />
                                </div>
                                <div class="mb-2">
                                    <label for="notes" class="block text-xs font-medium text-gray-700 mb-1">Ghi chú</label>
                                    <textarea
                                        v-model="sessionForm.notes"
                                        class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                        id="notes"
                                        rows="3"
                                    ></textarea>
                                </div>
                                <button
                                    type="submit"
                                    class="w-full bg-green-600 text-white py-2 rounded-lg text-xs font-semibold hover:bg-green-700 transition shadow-md"
                                    :disabled="isLoadingSessionAction || sessionForm.processing"
                                >
                                    <Loader2 v-if="isLoadingSessionAction || sessionForm.processing" class="animate-spin h-4 w-4 inline-block mr-1" />
                                    Mở Ca
                                </button>
                            </form>
                        </div>
                        <div v-else class="space-y-3 bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                            <h4 class="text-xs font-semibold text-gray-800">Thông tin ca hiện tại</h4>
                            <div class="text-[11px] space-y-1">
                                <p><strong>Ca làm việc:</strong> {{ activeShift?.shift_name || 'N/A' }}</p>
                                <p><strong>Thời gian ca:</strong> {{ activeShift?.shift_time ? `${activeShift.shift_time.start_time} - ${activeShift.shift_time.end_time}` : 'N/A' }}</p>
                                <p><strong>Bắt đầu:</strong> {{ formattedOpenedAt }}</p>
                            </div>
                            <h4 class="text-xs font-semibold text-gray-800 mt-3">Đóng ca</h4>
                            <form @submit.prevent="closeShift">
                                <div class="mb-2">
                                    <label for="closing_amount" class="block text-xs font-medium text-gray-700 mb-1">Số tiền đóng ca (VND)</label>
                                    <input
                                        type="number"
                                        v-model.number="sessionForm.closing_amount"
                                        class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                        id="closing_amount"
                                        min="0"
                                        required
                                    />
                                    <InputError class="mt-0.5 text-[10px]" :message="sessionForm.errors.closing_amount" />
                                </div>
                                <div class="mb-2">
                                    <label for="notes" class="block text-xs font-medium text-gray-700 mb-1">Ghi chú</label>
                                    <textarea
                                        v-model="sessionForm.notes"
                                        class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                        id="notes"
                                        rows="3"
                                    ></textarea>
                                </div>
                                <button
                                    type="submit"
                                    class="w-full bg-red-600 text-white py-2 rounded-lg text-xs font-semibold hover:bg-red-700 transition shadow-md"
                                    :disabled="isLoadingSessionAction || sessionForm.processing"
                                >
                                    <Loader2 v-if="isLoadingSessionAction || sessionForm.processing" class="animate-spin h-4 w-4 inline-block mr-1" />
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
                                    <p><strong>Thời gian ca:</strong> {{ shiftReport.session?.shift_time ? `${shiftReport.session.shift_time.start_time} - ${shiftReport.session.shift_time.end_time}` : 'N/A' }}</p>
                                    <p><strong>Bắt đầu:</strong> {{ shiftReport.session?.opened_at ? formatDateTime(shiftReport.session.opened_at) : 'N/A' }}</p>
                                    <p><strong>Tiền mở ca:</strong> {{ shiftReport.session?.opening_amount ? formatCurrency(shiftReport.session.opening_amount) : 'Chưa mở ca' }}</p>
                                    <p><strong>Tiền đóng ca:</strong> {{ shiftReport.session?.closing_amount ? formatCurrency(shiftReport.session.closing_amount) : 'Chưa đóng ca' }}</p>
                                    <p><strong>Thời gian đóng ca:</strong> {{ shiftReport.session?.closed_at ? formatDateTime(shiftReport.session.closed_at) : 'Chưa đóng ca' }}</p>
                                    <p>
                                        <strong>Chênh lệch (Đóng - Mở):</strong>
                                        <span :class="{
                                            'text-green-600': shiftReport.session?.difference > 0,
                                            'text-red-600': shiftReport.session?.difference < 0,
                                            'text-gray-600': shiftReport.session?.difference === 0
                                        }">
                                            {{ shiftReport.session?.difference ? formatCurrency(shiftReport.session.difference) : 'N/A' }}
                                        </span>
                                    </p>
                                    <p><strong>Ghi chú:</strong> {{ shiftReport.session?.notes || 'Không có' }}</p>
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                                <h4 class="text-xs font-semibold text-gray-800 mb-2">Thống kê doanh thu</h4>
                                <div class="text-[11px] space-y-1">
                                    <p><strong>Tổng doanh thu:</strong> {{ formatCurrency(shiftReport.report?.total_sales || 0) }}</p>
                                    <p><strong>Tiền mặt:</strong> {{ formatCurrency(shiftReport.report?.total_cash || 0) }}</p>
                                    <p><strong>Thẻ ngân hàng:</strong> {{ formatCurrency(shiftReport.report?.total_card || 0) }}</p>
                                    <p><strong>Chuyển khoản:</strong> {{ formatCurrency(shiftReport.report?.total_transfer || 0) }}</p>
                                    <p><strong>Ví khách hàng:</strong> {{ formatCurrency(shiftReport.report?.total_wallet || 0) }}</p>
                                    <p><strong>Số hóa đơn:</strong> {{ shiftReport.report?.bill_count || 0 }}</p>
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded-lg shadow-sm border border-gray-200" v-if="shiftReport.report?.customers?.length">
                                <h4 class="text-xs font-semibold text-gray-800 mb-2">Khách hàng</h4>
                                <div class="text-[11px] space-y-2">
                                    <div v-for="customer in shiftReport.report.customers" :key="customer.customer_id || customer.customer_name" class="border-b border-gray-100 pb-2">
                                        <p><strong>Tên:</strong> {{ customer.customer_name || 'Khách lẻ' }}</p>
                                        <p><strong>Tổng chi tiêu:</strong> {{ formatCurrency(customer.total_amount || 0) }}</p>
                                        <p><strong>Số hóa đơn:</strong> {{ customer.bill_count || 0 }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center text-gray-500 py-2 text-[11px]">
                                Không có dữ liệu khách hàng
                            </div>
                        </div>
                        <div v-else class="text-center text-gray-500 py-4 text-[11px] bg-white p-3 rounded-lg shadow-sm border border-gray-200">
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
                            <button
                                @click="toggleFilterSidebar"
                                class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs hover:bg-gray-300"
                            >
                                <MenuIcon class="w-4 h-4" />
                            </button>
                            <button
                                @click="toggleReportSidebar"
                                class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs hover:bg-gray-300"
                            >
                                <FileText class="w-4 h-4" />
                            </button>
                            <button
                                @click="initiateLogout"
                                class="bg-red-600 text-white px-2 py-0.5 rounded text-xs hover:bg-red-700"
                            >
                                <LogOutIcon class="w-4 h-4" />
                            </button>
                            <div
                                class="flex justify-between items-center mt-1 text-[11px] text-gray-600"
                            >
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
                                    <X class="w-4 h-4 inline-block mr-1" />
                                </button>
                            </div>
                            <h2 class="text-base font-semibold">Sản phẩm</h2>
                        </div>
                        <div v-if="activeShift" class="text-[11px] text-gray-600">
                            <p><strong>Ca làm việc:</strong> {{ activeShift.shift_name }} (Bắt đầu: {{ formattedOpenedAt }})</p>
                        </div>
                    </div>
                    <div class="mb-1">
                        <input
                            type="text"
                            v-model="searchTerm"
                            placeholder="Tìm kiếm sản phẩm (F3)"
                            class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-2">
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2">
                        <div
                            v-for="product in filteredProducts"
                            :key="product.id"
                            class="relative bg-gray-50 p-1.5 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 flex flex-col items-center text-center cursor-pointer hover:scale-105 active:scale-95"
                            :class="{ 'opacity-60 cursor-not-allowed': product.stock === 0 }"
                            @click="product.stock > 0 && addToCart(product)"
                        >
                            <button
                                @click.stop="openProductModal(product)"
                                class="absolute top-1 right-1 hover:bg-gray-100 z-10 shadow"
                            >
                                <BadgeInfo class="w-3 h-3" />
                            </button>
                            <img
                                :src="product.image"
                                :alt="product.name"
                                class="w-12 h-12 object-cover rounded mb-0.5"
                                @error="handleImageError($event, product)"
                            />
                            <h3 class="text-[10px] font-medium truncate w-full">{{ product.name }}</h3>
                            <p
                                v-if="product.stock <= 10 && product.stock > 0"
                                class="text-orange-500 text-[10px] font-semibold mt-0.5"
                            >
                                Tồn: {{ product.stock }}
                            </p>
                            <p
                                v-else-if="product.stock === 0"
                                class="text-red-500 text-[10px] font-semibold mt-0.5"
                            >
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
                            <button
                                class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs hover:bg-gray-300"
                                @click="cart = []"
                            >
                                Đơn mới
                            </button>
                            <button
                                class="bg-yellow-500 text-white px-2 py-0.5 rounded text-xs hover:bg-yellow-600"
                                @click="holdOrder"
                            >
                                Lưu đơn (F6)
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-2 border-b border-gray-200">
                    <h3 class="text-xs font-semibold mb-1">Đơn hàng chờ:</h3>
                    <div class="flex flex-wrap gap-1 max-h-16 overflow-y-auto">
                        <div class="relative bg-gray-100 p-1.5 rounded text-[10px] border">
                            Không có đơn hàng chờ
                        </div>
                    </div>
                </div>
                <div class="flex-1 p-2 overflow-y-auto">
                    <div
                        v-if="cart.length === 0"
                        class="text-gray-500 text-center py-4 text-xs"
                    >
                        Giỏ hàng trống. Vui lòng thêm sản phẩm (F2)!
                    </div>
                    <div v-else>
                        <div
                            v-for="item in cart"
                            :key="item.id"
                            class="flex items-center justify-between p-2 border-b border-gray-200"
                        >
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
                                    :max="products.find(p => p.id === item.id)?.stock || 0"
                                    class="w-12 p-1 border border-gray-300 rounded text-xs"
                                    @input="updateQuantity(item.id, item.quantity)"
                                />
                                <button
                                    @click="removeFromCart(item.id)"
                                    class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs hover:bg-red-200"
                                >
                                    Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-2 border-t border-gray-200">
                    <form @submit.prevent="submitSale">
                        <div class="mb-1">
                            <label class="block text-[10px] font-medium text-gray-700 mb-0.5">
                                Khách hàng (F4):
                            </label>
                            <div class="flex items-center space-x-1">
                                <button
                                    type="button"
                                    @click="toggleCustomerSidebar"
                                    class="w-full bg-gray-100 text-gray-700 py-1 rounded border border-gray-300 hover:bg-gray-200 text-xs text-left"
                                >
                                    {{ selectedCustomer ? selectedCustomer.customer_name : 'Chọn khách hàng' }}
                                </button>
                                <button
                                    type="button"
                                    v-if="selectedCustomer"
                                    @click="clearCustomer"
                                    class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs hover:bg-red-200"
                                >
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
                        <div class="mb-1">
                            <label class="block text-[10px] font-medium text-gray-700 mb-0.5">
                                Phương thức thanh toán:
                            </label>
                            <select
                                v-model="form.paymentMethod"
                                class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :class="{ 'border-red-500': form.errors.paymentMethod }"
                            >
                                <option value="cash">Tiền mặt</option>
                                <option value="credit_card">Thẻ ngân hàng</option>
                                <option value="bank_transfer">Chuyển khoản</option>
                                <option
                                    value="wallet"
                                    :disabled="!selectedCustomer || selectedCustomer.wallet < cartTotal"
                                >
                                    Ví khách hàng
                                </option>
                            </select>
                            <InputError class="mt-0.5 text-[10px]" :message="form.errors.paymentMethod" />
                        </div>
                        <div v-if="form.paymentMethod === 'cash'" class="mb-1">
                            <label
                                for="amountReceived"
                                class="block text-[10px] font-medium text-gray-700 mb-0.5"
                            >
                                Số tiền khách đưa (VND):
                            </label>
                            <div class="flex flex-wrap gap-1 mb-1">
                                <button
                                    v-for="amount in quickAmounts"
                                    :key="amount"
                                    @click="setAmountReceived(amount)"
                                    class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs hover:bg-gray-300"
                                >
                                    {{ formatCurrency(amount) }}
                                </button>
                            </div>
                            <input
                                type="number"
                                id="amountReceived"
                                v-model.number="form.amountReceived"
                                class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                                min="0"
                                :class="{ 'border-red-500': form.errors.amountReceived }"
                            />
                            <InputError class="mt-0.5 text-[10px]" :message="form.errors.amountReceived" />
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-xs font-semibold text-gray-700">Tiền thối:</span>
                                <span class="text-base font-bold text-gray-800">
                                    {{ formatCurrency(change) }}
                                </span>
                            </div>
                        </div>
                        <div class="mb-1">
                            <label
                                for="orderNotes"
                                class="block text-[10px] font-medium text-gray-700 mb-0.5"
                            >
                                Ghi chú:
                            </label>
                            <textarea
                                id="orderNotes"
                                v-model="form.orderNotes"
                                class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                                rows="3"
                                :class="{ 'border-red-500': form.errors.orderNotes }"
                            ></textarea>
                            <InputError class="mt-0.5 text-[10px]" :message="form.errors.orderNotes" />
                        </div>
                        <button
                            type="submit"
                            class="w-full bg-green-600 text-white py-1.5 rounded text-xs font-semibold hover:bg-green-700"
                            :disabled="form.processing || cart.length === 0"
                        >
                            <Loader2 v-if="form.processing" class="animate-spin h-4 w-4 inline-block mr-1" />
                            Thanh toán (F8)
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Product Modal -->
            <div
                v-if="selectedProduct"
                class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-md bg-black/40 transition-opacity duration-300"
            >
                <div
                    class="relative bg-white/80 backdrop-blur-lg border border-white/20 shadow-2xl rounded-2xl p-6 max-w-md w-[90%] text-gray-800 transform transition-all duration-300 hover:scale-[1.02] modal-content"
                >
                    <button
                        @click="selectedProduct = null"
                        class="absolute top-4 right-4 text-gray-600 hover:text-gray-900 bg-white/50 rounded-full p-2 hover:bg-white/80 transition-all duration-200"
                    >
                        <X class="w-5 h-5" />
                    </button>
                    <div class="flex flex-col items-center">
                        <img
                            :src="selectedProduct.image"
                            :alt="selectedProduct.name"
                            class="w-40 h-40 object-cover rounded-lg mb-4 shadow-md"
                            @error="handleImageError($event, selectedProduct)"
                        />
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
                        <button
                            @click="selectedProduct = null"
                            class="mt-6 w-full bg-blue-600 text-white py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-all duration-200 shadow-md"
                        >
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </CashierLayout>
</template>