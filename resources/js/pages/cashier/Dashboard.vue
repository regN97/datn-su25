<script setup>
import CashierLayout from '@/layouts/CashierLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { debounce } from 'lodash';
import { MenuIcon, Trash2Icon, EditIcon } from 'lucide-vue-next';

// --- Danh sách sản phẩm ---
const products = ref([
  { id: 1, name: 'Cà phê đen', price: 25000, image: 'https://d1hjkbq40fs2x4.cloudfront.net/2017-08-21/files/landscape-photography_1645.jpg', category: 'Đồ uống', stock: 100 },
  { id: 2, name: 'Trà sữa trân châu', price: 35000, image: 'https://d1hjkbq40fs2x4.cloudfront.net/2017-08-21/files/landscape-photography_1645.jpg', category: 'Đồ uống', stock: 50 },
  { id: 3, name: 'Bánh mì nướng', price: 20000, image: 'https://d1hjkbq40fs2x4.cloudfront.net/2017-08-21/files/landscape-photography_1645.jpg', category: 'Đồ ăn', stock: 30 },
  ...Array.from({ length: 100 }, (_, i) => ({
    id: i + 4,
    name: `Sản phẩm ${i + 4}`,
    price: 20000 + (i * 1000),
    image: 'https://d1hjkbq40fs2x4.cloudfront.net/2017-08-21/files/landscape-photography_1645.jpg',
    category: i % 2 === 0 ? 'Đồ uống' : 'Đồ ăn',
    stock: 100 - i,
  })),
]);

// --- Core POS States ---
const cartItems = ref([]);
const searchTerm = ref('');
const selectedCategory = ref('Tất cả');
const barcodeInput = ref('');
const showReportModal = ref(false);
const pendingOrders = ref([]);
const orderHistory = ref([]);
const activeOrderId = ref(null);
const nextOrderId = ref(1);
const orderNotes = ref('');
const customers = ref([
  { id: 1, name: 'Nguyễn Văn A', phone: '0901234567', points: 150 },
  { id: 2, name: 'Trần Thị B', phone: '0901234568', points: 200 },
]);
const selectedCustomer = ref(null);
const customerSearchTerm = ref('');
const showSidebar = ref(false);
const activeSidebarTab = ref('filter'); // Unified sidebar tab state
const newCustomerName = ref('');
const newCustomerPhone = ref('');
const showAddCustomerForm = ref(false);
const discountPercentage = ref(0);
const discountAmount = ref(0);
const usePoints = ref(false);
const pointsToRedeem = ref(0);
const maxDiscountPercentage = 50;
const amountReceived = ref(0);
const paymentMethod = ref('cash');
const editingItem = ref(null);
const currentItemNote = ref('');
const currentShift = ref(null);
const initialCashInput = ref(0);
const endingCashInput = ref(0);
const selectedOrderForRefund = ref(null);

// --- Computed Properties ---
const categories = computed(() => ['Tất cả', ...new Set(products.value.map(p => p.category))]);

const filteredProducts = computed(() => {
  let filtered = products.value;
  if (searchTerm.value || selectedCategory.value !== 'Tất cả') {
    const regex = searchTerm.value ? new RegExp(searchTerm.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i') : null;
    filtered = filtered.filter(product => {
      const matchesSearch = !regex || regex.test(product.name) || regex.test(product.category);
      const matchesCategory = selectedCategory.value === 'Tất cả' || product.category === selectedCategory.value;
      return matchesSearch && matchesCategory;
    });
  }
  return filtered.filter(product => product.stock > 0 || cartItems.value.some(item => item.id === product.id));
});

const subtotal = computed(() => cartItems.value.reduce((sum, item) => sum + (item.price * item.quantity), 0));
const taxAmount = computed(() => (subtotal.value * taxRate.value) / 100);
const totalDiscount = computed(() => {
  let calculatedDiscount = 0;
  if (discountPercentage.value > 0) {
    calculatedDiscount += (subtotal.value * Math.min(discountPercentage.value, maxDiscountPercentage)) / 100;
  }
  calculatedDiscount += discountAmount.value;
  if (usePoints.value && selectedCustomer.value?.points > 0) {
    const maxRedeemablePoints = Math.floor(selectedCustomer.value.points);
    const pointsValuePerUnit = 10;
    pointsToRedeem.value = Math.min(pointsToRedeem.value, maxRedeemablePoints);
    calculatedDiscount += pointsToRedeem.value * pointsValuePerUnit;
  } else {
    pointsToRedeem.value = 0;
  }
  return Math.min(calculatedDiscount, subtotal.value * (maxDiscountPercentage / 100));
});
const totalPrice = computed(() => subtotal.value + taxAmount.value - totalDiscount.value);
const changeDue = computed(() => Math.max(0, amountReceived.value - totalPrice.value));
const filteredCustomers = computed(() => {
  if (!customerSearchTerm.value) return customers.value;
  return customers.value.filter(customer =>
    customer.name.toLowerCase().includes(customerSearchTerm.value.toLowerCase()) ||
    customer.phone.includes(customerSearchTerm.value)
  );
});
const salesReport = computed(() => {
  const byCategory = {};
  const byProduct = {};
  orderHistory.value.forEach(order => {
    order.cart.forEach(item => {
      const itemTotal = item.price * item.quantity;
      const product = products.value.find(p => p.id === item.id);
      if (product) {
        byCategory[product.category] = byCategory[product.category] || { total: 0, quantity: 0 };
        byCategory[product.category].total += itemTotal;
        byCategory[product.category].quantity += item.quantity;
      }
      byProduct[item.name] = byProduct[item.name] || { total: 0, quantity: 0 };
      byProduct[item.name].total += itemTotal;
      byProduct[item.name].quantity += item.quantity;
    });
  });
  return { byCategory, byProduct };
});
const taxRate = ref(10);

// --- Watchers ---
watch(selectedCustomer, (newVal) => {
  if (!newVal) {
    usePoints.value = false;
    pointsToRedeem.value = 0;
  }
});

// --- Debounced Search ---
const debouncedSearch = debounce((value) => {
  searchTerm.value = value;
}, 300);

// --- Product & Cart Actions ---
const addToCart = (product) => {
  if (!product) return;
  const productInStock = products.value.find(p => p.id === product.id);
  if (!productInStock || productInStock.stock <= (cartItems.value.find(item => item.id === product.id)?.quantity || 0)) {
    toast.error(`Sản phẩm "${product.name}" đã hết hàng hoặc không đủ số lượng.`);
    return;
  }
  const existingItem = cartItems.value.find(item => item.id === product.id);
  if (existingItem) {
    existingItem.quantity++;
  } else {
    cartItems.value.push({
      ...product,
      quantity: 1,
      itemNotes: '',
    });
  }
  toast.success(`Đã thêm "${product.name}" vào giỏ hàng.`);
};

const increaseQuantity = (item) => {
  const productInStock = products.value.find(p => p.id === item.id);
  if (productInStock && productInStock.stock <= item.quantity) {
    toast.error(`Sản phẩm "${item.name}" đã đạt số lượng tối đa.`);
    return;
  }
  item.quantity++;
};

const decreaseQuantity = (item) => {
  if (item.quantity > 1) {
    item.quantity--;
  } else {
    removeFromCart(item);
  }
};

const removeFromCart = (itemToRemove) => {
  cartItems.value = cartItems.value.filter(item => item.id !== itemToRemove.id);
  toast.success(`Đã xóa "${itemToRemove.name}" khỏi giỏ hàng.`);
};

const scanBarcode = () => {
  const trimmedBarcode = barcodeInput.value.trim();
  if (!trimmedBarcode) {
    toast.error('Vui lòng nhập mã vạch!');
    return;
  }
  const product = products.value.find(p => p.id.toString() === trimmedBarcode);
  if (product) {
    addToCart(product);
    barcodeInput.value = '';
  } else {
    toast.error('Không tìm thấy sản phẩm với mã này!');
    barcodeInput.value = '';
  }
};

// --- Customer Actions ---
const selectCustomer = (customer) => {
  if (customer) {
    selectedCustomer.value = customer;
    activeSidebarTab.value = 'filter';
    showSidebar.value = false;
    toast.success(`Đã chọn khách hàng: ${customer.name}`);
  }
};

const removeSelectedCustomer = () => {
  selectedCustomer.value = null;
  usePoints.value = false;
  pointsToRedeem.value = 0;
  toast.success('Đã xóa khách hàng được chọn.');
};

const addNewCustomer = () => {
  if (!newCustomerName.value || !newCustomerPhone.value) {
    toast.error('Vui lòng nhập đầy đủ tên và số điện thoại khách hàng.');
    return;
  }
  if (!/^\d{10}$/.test(newCustomerPhone.value)) {
    toast.error('Số điện thoại phải có đúng 10 chữ số.');
    return;
  }
  const newId = Math.max(...customers.value.map(c => c.id), 0) + 1;
  customers.value.push({ id: newId, name: newCustomerName.value, phone: newCustomerPhone.value, points: 0 });
  newCustomerName.value = '';
  newCustomerPhone.value = '';
  showAddCustomerForm.value = false;
  toast.success('Khách hàng mới đã được thêm!');
};

// --- Item Note Actions ---
const openItemNote = (item) => {
  editingItem.value = item;
  currentItemNote.value = item?.itemNotes || '';
  activeSidebarTab.value = 'itemNote';
  showSidebar.value = true;
};

const saveItemNote = () => {
  if (editingItem.value) {
    editingItem.value.itemNotes = currentItemNote.value;
    toast.success(`Đã lưu ghi chú cho "${editingItem.value.name}".`);
  }
  activeSidebarTab.value = 'filter';
  showSidebar.value = false;
  editingItem.value = null;
  currentItemNote.value = '';
};

// --- Order Management Actions ---
const newOrder = () => {
  if (cartItems.value.length > 0 && !window.confirm('Bạn có muốn tạo đơn hàng mới? Giỏ hàng hiện tại sẽ bị xóa.')) {
    return;
  }
  resetOrder();
  toast.success('Đã tạo đơn hàng mới.');
};

const parkOrder = () => {
  if (cartItems.value.length === 0) {
    toast.error('Giỏ hàng trống, không thể lưu đơn hàng!');
    return;
  }
  const orderToPark = {
    id: activeOrderId.value || `ORD-${nextOrderId.value++}`,
    cart: [...cartItems.value],
    customer: selectedCustomer.value ? { ...selectedCustomer.value } : null,
    discountPercentage: discountPercentage.value,
    discountAmount: discountAmount.value,
    orderNotes: orderNotes.value,
    subtotal: subtotal.value,
    taxAmount: taxAmount.value,
    totalPrice: totalPrice.value,
    timestamp: new Date().toLocaleString('vi-VN'),
  };
  const index = pendingOrders.value.findIndex(order => order.id === activeOrderId.value);
  if (index !== -1) {
    pendingOrders.value[index] = orderToPark;
    toast.success(`Đơn hàng ${activeOrderId.value} đã được cập nhật.`);
  } else {
    pendingOrders.value.push(orderToPark);
    toast.success(`Đơn hàng ${orderToPark.id} đã được lưu.`);
  }
  resetOrder();
};

const loadOrder = (order) => {
  if (cartItems.value.length > 0 && !window.confirm('Giỏ hàng hiện tại sẽ bị xóa. Bạn có muốn tải đơn hàng này không?')) {
    return;
  }
  activeOrderId.value = order.id;
  cartItems.value = [...order.cart];
  selectedCustomer.value = order.customer ? { ...order.customer } : null;
  discountPercentage.value = order.discountPercentage;
  discountAmount.value = order.discountAmount;
  orderNotes.value = order.orderNotes;
  amountReceived.value = 0;
  paymentMethod.value = 'cash';
  usePoints.value = false;
  pointsToRedeem.value = 0;
  activeSidebarTab.value = 'filter';
  showSidebar.value = false;
  toast.success(`Đã tải đơn hàng ${order.id}.`);
};

const removeParkedOrder = (orderId) => {
  if (window.confirm(`Bạn có chắc chắn muốn xóa đơn hàng ${orderId} khỏi danh sách chờ?`)) {
    pendingOrders.value = pendingOrders.value.filter(order => order.id !== orderId);
    if (activeOrderId.value === orderId) {
      resetOrder();
    }
    toast.success(`Đơn hàng ${orderId} đã được xóa.`);
  }
};

// --- Payment Actions ---
const processPayment = () => {
  if (!currentShift.value) {
    toast.error('Vui lòng bắt đầu ca làm việc trước khi thanh toán!');
    return;
  }
  if (cartItems.value.length === 0) {
    toast.error('Giỏ hàng trống!');
    return;
  }
  if (paymentMethod.value === 'cash' && amountReceived.value < totalPrice.value) {
    toast.error('Số tiền nhận được không đủ!');
    return;
  }
  activeSidebarTab.value = 'paymentConfirmation';
  showSidebar.value = true;
};

const confirmPayment = () => {
  if (!cartItems.value.length) return;
  cartItems.value.forEach(cartItem => {
    const productInStock = products.value.find(p => p.id === cartItem.id);
    if (productInStock) {
      productInStock.stock = Math.max(0, productInStock.stock - cartItem.quantity);
    }
  });
  if (selectedCustomer.value) {
    const pointsEarned = Math.floor(totalPrice.value / 10000);
    const customerIndex = customers.value.findIndex(c => c.id === selectedCustomer.value.id);
    if (customerIndex !== -1) {
      let currentPoints = customers.value[customerIndex].points || 0;
      currentPoints += pointsEarned;
      if (usePoints.value) {
        currentPoints -= pointsToRedeem.value;
      }
      customers.value[customerIndex].points = Math.max(0, currentPoints);
    }
  }
  orderHistory.value.push({
    id: `INV-${Date.now()}`,
    timestamp: new Date().toLocaleString('vi-VN'),
    cart: [...cartItems.value],
    customer: selectedCustomer.value ? { ...selectedCustomer.value } : null,
    subtotal: subtotal.value,
    taxAmount: taxAmount.value,
    totalDiscount: totalDiscount.value,
    totalPrice: totalPrice.value,
    paymentMethod: paymentMethod.value,
    amountReceived: amountReceived.value,
    changeDue: changeDue.value,
    notes: orderNotes.value,
  });
  if (currentShift.value) {
    currentShift.value.totalSales += totalPrice.value;
    currentShift.value.totalTransactions++;
  }
  activeSidebarTab.value = 'receiptPreview';
  resetOrder();
};

// --- Refund Actions ---
const openRefund = (order) => {
  if (order) {
    selectedOrderForRefund.value = order;
    activeSidebarTab.value = 'refund';
    showSidebar.value = true;
  }
};

const confirmRefund = () => {
  if (!selectedOrderForRefund.value) return;
  selectedOrderForRefund.value.cart.forEach(item => {
    const productInStock = products.value.find(p => p.id === item.id);
    if (productInStock) {
      productInStock.stock += item.quantity;
    }
  });
  if (selectedOrderForRefund.value.customer) {
    const customerIndex = customers.value.findIndex(c => c.id === selectedOrderForRefund.value.customer.id);
    if (customerIndex !== -1) {
      const pointsEarned = Math.floor(selectedOrderForRefund.value.totalPrice / 10000);
      customers.value[customerIndex].points = Math.max(0, customers.value[customerIndex].points - pointsEarned);
    }
  }
  if (currentShift.value) {
    currentShift.value.totalSales = Math.max(0, currentShift.value.totalSales - selectedOrderForRefund.value.totalPrice);
    currentShift.value.totalTransactions = Math.max(0, currentShift.value.totalTransactions - 1);
  }
  orderHistory.value = orderHistory.value.filter(order => order.id !== selectedOrderForRefund.value.id);
  toast.success(`Đã hoàn tiền cho đơn hàng ${selectedOrderForRefund.value.id}.`);
  activeSidebarTab.value = 'history';
  selectedOrderForRefund.value = null;
};

// --- Receipt Printing ---
const printReceipt = () => {
  if (cartItems.value.length === 0) {
    toast.error('Giỏ hàng trống, không ফা thể in hóa đơn!');
    return;
  }
  const receiptContent = `
    <div style="font-family: monospace; width: 300px; margin: 0 auto; text-align: center;">
      <h3>Cửa hàng XYZ</h3>
      <p>Địa chỉ: 123 Đường ABC, Quận 1</p>
      <p>Thời gian: ${new Date().toLocaleString('vi-VN')}</p>
      ${selectedCustomer.value ? `<p>Khách hàng: ${selectedCustomer.value.name} (${selectedCustomer.value.phone})</p><p>Điểm tích lũy: ${selectedCustomer.value.points}</p>` : ''}
      <hr style="border: none; border-top: 1px dashed #000; margin: 10px 0;">
      <table style="width: 100%; text-align: left;">
        <tr><th>Sản phẩm</th><th>Số lượng</th><th>Thành tiền</th></tr>
        ${cartItems.value.map(item => `
          <tr>
            <td>${item.name}</td>
            <td>${item.quantity}</td>
            <td>${(item.price * item.quantity).toLocaleString('vi-VN')} VND</td>
          </tr>
          ${item.itemNotes ? `<tr><td colspan="3">Ghi chú: ${item.itemNotes}</td></tr>` : ''}
        `).join('')}
      </table>
      <hr style="border: none; border-top: 1px dashed #000; margin: 10px 0;">
      <p style="text-align: right;">Tổng phụ: ${subtotal.value.toLocaleString('vi-VN')} VND</p>
      <p style="text-align: right;">Thuế VAT (${taxRate.value}%): ${taxAmount.value.toLocaleString('vi-VN')} VND</p>
      ${totalDiscount.value > 0 ? `<p style="text-align: right;">Giảm giá: -${totalDiscount.value.toLocaleString('vi-VN')} VND</p>` : ''}
      <p style="text-align: right; font-weight: bold;">Tổng cộng: ${totalPrice.value.toLocaleString('vi-VN')} VND</p>
      <p style="text-align: right;">Phương thức: ${paymentMethod.value === 'cash' ? 'Tiền mặt' : paymentMethod.value === 'card' ? 'Thẻ ngân hàng' : 'Chuyển khoản'}</p>
      ${paymentMethod.value === 'cash' ? `
        <p style="text-align: right;">Khách đưa: ${amountReceived.value.toLocaleString('vi-VN')} VND</p>
        <p style="text-align: right;">Tiền thừa: ${changeDue.value.toLocaleString('vi-VN')} VND</p>` : ''}
      ${orderNotes.value ? `<p>Ghi chú đơn hàng: ${orderNotes.value}</p>` : ''}
      <hr style="border: none; border-top: 1px dashed #000; margin: 10px 0;">
      <p>Cảm ơn quý khách và hẹn gặp lại!</p>
    </div>
  `;
  const printWindow = window.open('', '_blank');
  printWindow.document.write(`
    <html>
      <head><title>Hóa đơn</title></head>
      <body onload="window.print(); window.close();">${receiptContent}</body>
    </html>
  `);
  printWindow.document.close();
  toast.success('Hóa đơn đã được in!');
};

// --- Utility Functions ---
const resetOrder = () => {
  cartItems.value = [];
  selectedCustomer.value = null;
  discountPercentage.value = 0;
  discountAmount.value = 0;
  orderNotes.value = '';
  amountReceived.value = 0;
  paymentMethod.value = 'cash';
  usePoints.value = false;
  pointsToRedeem.value = 0;
  activeOrderId.value = null;
  toast.success('Đã reset đơn hàng.');
};

const addQuickCash = (amount) => {
  amountReceived.value = Math.max(0, amountReceived.value + amount);
  toast.success(`Đã thêm ${amount.toLocaleString('vi-VN')} VND vào số tiền khách đưa.`);
};

const applyDiscountPreset = (percentage) => {
  discountPercentage.value = percentage;
  discountAmount.value = 0;
  toast.success(`Áp dụng giảm giá ${percentage}%.`);
};

const startShift = () => {
  activeSidebarTab.value = 'shift';
  showSidebar.value = true;
  endingCashInput.value = 0;
};

const confirmStartShift = () => {
  if (initialCashInput.value < 0) {
    toast.error('Số tiền ban đầu không hợp lệ!');
    return;
  }
  currentShift.value = {
    startTime: new Date(),
    initialCash: initialCashInput.value,
    totalSales: 0,
    totalTransactions: 0,
  };
  activeSidebarTab.value = 'filter';
  showSidebar.value = false;
  toast.success(`Ca làm việc đã bắt đầu với số tiền ban đầu: ${initialCashInput.value.toLocaleString('vi-VN')} VND`);
  initialCashInput.value = 0;
};

const endShift = () => {
  if (!currentShift.value) {
    toast.error('Không có ca làm việc nào đang diễn ra!');
    return;
  }
  activeSidebarTab.value = 'shift';
  showSidebar.value = true;
  endingCashInput.value = currentShift.value.initialCash + currentShift.value.totalSales;
};

const confirmEndShift = () => {
  if (!currentShift.value) return;
  const expectedCash = currentShift.value.initialCash + currentShift.value.totalSales;
  const difference = endingCashInput.value - expectedCash;
  const shiftSummary = `
    --- Báo cáo ca làm việc ---
    Bắt đầu: ${currentShift.value.startTime.toLocaleString('vi-VN')}
    Tiền mặt ban đầu: ${currentShift.value.initialCash.toLocaleString('vi-VN')} VND
    Tổng doanh thu: ${currentShift.value.totalSales.toLocaleString('vi-VN')} VND
    Số giao dịch: ${currentShift.value.totalTransactions}
    Tiền mặt dự kiến cuối ca: ${expectedCash.toLocaleString('vi-VN')} VND
    Tiền mặt thực tế cuối ca: ${endingCashInput.value.toLocaleString('vi-VN')} VND
    Chênh lệch: ${difference.toLocaleString('vi-VN')} VND
    ---------------------------
  `;
  toast.info(shiftSummary, { timeout: 5000 });
  currentShift.value = null;
  activeSidebarTab.value = 'filter';
  showSidebar.value = false;
  endingCashInput.value = 0;
};

// --- Sidebar Actions ---
const showCustomerSidebar = () => {
  activeSidebarTab.value = 'customer';
  showSidebar.value = true;
};

const showReportSidebar = () => {
  activeSidebarTab.value = 'salesReport';
  showSidebar.value = true;
};

const showReceiptSidebar = () => {
  activeSidebarTab.value = 'receiptPreview';
  showSidebar.value = true;
};
</script>

<template>
  <Head title="Cashier" />
  <CashierLayout>
    <div class="flex text-xs relative h-[650px]">
      <!-- Unified Sidebar (Right) -->
      <div v-if="showSidebar" class="fixed inset-y-0 right-0 w-80 bg-white shadow-xl z-50 transform transition-transform duration-300">
        <div class="p-3 h-full flex flex-col">
          <div class="flex justify-between items-center mb-2">
            <h3 class="text-sm font-semibold">{{ 
              activeSidebarTab === 'filter' ? 'Bộ lọc' : 
              activeSidebarTab === 'history' ? 'Lịch sử đơn' : 
              activeSidebarTab === 'customer' ? 'Chọn khách hàng' : 
              activeSidebarTab === 'itemNote' ? `Ghi chú cho ${editingItem?.name}` : 
              activeSidebarTab === 'paymentConfirmation' ? 'Xác nhận thanh toán' : 
              activeSidebarTab === 'receiptPreview' ? 'Xem trước hóa đơn' : 
              activeSidebarTab === 'shift' ? (currentShift ? 'Kết thúc ca làm việc' : 'Bắt đầu ca làm việc') : 
              activeSidebarTab === 'salesReport' ? 'Báo cáo doanh thu' : 
              'Hoàn tiền đơn hàng' 
            }}</h3>
            <button @click="showSidebar = false; activeSidebarTab = 'filter'; selectedOrderForRefund = null; editingItem = null; currentItemNote = ''" 
                    class="bg-gray-300 text-gray-800 py-1 px-2 rounded text-xs hover:bg-gray-400">
              Đóng
            </button>
          </div>
          <div class="flex-1 overflow-y-auto">
            <!-- Filter Tab -->
            <div v-if="activeSidebarTab === 'filter'">
              <p class="text-xs font-semibold mb-2">Tổng sản phẩm trong giỏ: {{ cartItems.length }}</p>
              <div class="mb-2">
                <input type="text" @input="debouncedSearch($event.target.value)" placeholder="Tìm sản phẩm/danh mục..."
                       class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" />
              </div>
              <div class="mb-2">
                <select v-model="selectedCategory" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
                  <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                </select>
              </div>
            </div>
            <!-- History Tab -->
            <div v-if="activeSidebarTab === 'history'" class="max-h-96 overflow-y-auto">
              <h4 class="text-xs font-semibold mb-1">Lịch sử đơn hàng</h4>
              <div v-if="orderHistory.length === 0" class="text-gray-500 text-xs text-center py-2">
                Chưa có đơn hàng nào.
              </div>
              <div v-else class="space-y-1">
                <div v-for="order in orderHistory" :key="order.id" class="bg-gray-100 p-1.5 rounded text-[10px] border flex justify-between items-center">
                  <div>
                    <p class="font-medium">{{ order.id }}</p>
                    <p>{{ order.timestamp }}</p>
                    <p>{{ order.totalPrice.toLocaleString('vi-VN') }} VND</p>
                  </div>
                  <button @click="openRefund(order)" class="text-red-500 hover:text-red-700 text-xs">
                    Hoàn tiền
                  </button>
                </div>
              </div>
            </div>
            <!-- Customer Tab -->
            <div v-if="activeSidebarTab === 'customer'">
              <input type="text" v-model="customerSearchTerm" placeholder="Tìm theo tên hoặc SĐT..."
                     class="w-full p-1.5 border border-gray-300 rounded text-xs mb-2 focus:outline-none focus:ring-1 focus:ring-blue-500" />
              <div class="max-h-40 overflow-y-auto border border-gray-200 rounded mb-2">
                <ul v-if="filteredCustomers.length > 0">
                  <li v-for="customer in filteredCustomers" :key="customer.id" @click="selectCustomer(customer)"
                      class="p-1.5 cursor-pointer hover:bg-gray-100 border-b border-gray-100 last:border-b-0 text-xs">
                    {{ customer.name }} ({{ customer.phone }}) - Điểm: {{ customer.points }}
                  </li>
                </ul>
                <p v-else class="p-1.5 text-gray-500 text-center text-xs">Không tìm thấy khách hàng.</p>
              </div>
              <button @click="showAddCustomerForm = !showAddCustomerForm"
                      class="w-full bg-green-500 text-white py-1 rounded hover:bg-green-600 text-xs mb-1">
                {{ showAddCustomerForm ? 'Hủy thêm mới' : 'Thêm khách hàng mới' }}
              </button>
              <div v-if="showAddCustomerForm" class="mb-2 p-1.5 border border-dashed border-gray-300 rounded">
                <input type="text" v-model="newCustomerName" placeholder="Tên khách hàng"
                       class="w-full p-1 border border-gray-300 rounded text-xs mb-1 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                <input type="text" v-model="newCustomerPhone" placeholder="Số điện thoại"
                       class="w-full p-1 border border-gray-300 rounded text-xs mb-1 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                <button @click="addNewCustomer" class="w-full bg-blue-500 text-white py-1 rounded hover:bg-blue-600 text-xs">Lưu khách hàng</button>
              </div>
            </div>
            <!-- Item Note Tab -->
            <div v-if="activeSidebarTab === 'itemNote'">
              <textarea v-model="currentItemNote" rows="2"
                        placeholder="Thêm ghi chú cho mặt hàng này..."
                        class="w-full p-1.5 border border-gray-300 rounded text-xs mb-2 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
              <div class="flex justify-end space-x-1">
                <button @click="activeSidebarTab = 'filter'; editingItem = null; currentItemNote = ''" 
                        class="bg-gray-300 text-gray-800 py-1 px-3 rounded hover:bg-gray-400 text-xs">Hủy</button>
                <button @click="saveItemNote" class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700 text-xs">Lưu</button>
              </div>
            </div>
            <!-- Payment Confirmation Tab -->
            <div v-if="activeSidebarTab === 'paymentConfirmation'" class="text-center">
              <p class="mb-1 text-xs">Tổng phụ: <span class="font-bold">{{ subtotal.toLocaleString('vi-VN') }} VND</span></p>
              <p class="mb-1 text-xs">Thuế VAT ({{ taxRate }}%): <span class="font-bold">{{ taxAmount.toLocaleString('vi-VN') }} VND</span></p>
              <p v-if="totalDiscount > 0" class="mb-1 text-xs">Giảm giá: <span class="font-bold text-green-600">-{{ totalDiscount.toLocaleString('vi-VN') }} VND</span></p>
              <p class="mb-1 text-xs">Tổng cộng: <span class="font-bold text-base text-blue-600">{{ totalPrice.toLocaleString('vi-VN') }} VND</span></p>
              <p v-if="paymentMethod === 'cash'" class="mb-2 text-xs">Khách đưa: <span class="font-bold">{{ amountReceived.toLocaleString('vi-VN') }} VND</span> - Tiền thừa: <span class="font-bold">{{ changeDue.toLocaleString('vi-VN') }} VND</span></p>
              <p v-else class="mb-2 text-xs">Phương thức: <span class="font-bold">{{ paymentMethod === 'card' ? 'Thẻ ngân hàng' : 'Chuyển khoản' }}</span></p>
              <div class="flex justify-around mt-2">
                <button @click="confirmPayment" class="bg-green-600 text-white py-1 px-4 rounded hover:bg-green-700 text-xs">Xác nhận</button>
                <button @click="activeSidebarTab = 'filter'" class="bg-gray-300 text-gray-800 py-1 px-4 rounded hover:bg-gray-400 text-xs">Hủy</button>
              </div>
            </div>
            <!-- Receipt Preview Tab -->
            <div v-if="activeSidebarTab === 'receiptPreview'" class="font-mono text-xs text-center">
              <h3>Cửa hàng XYZ</h3>
              <p>Địa chỉ: 123 Đường ABC, Quận 1</p>
              <p>Thời gian: {{ new Date().toLocaleString('vi-VN') }}</p>
              <p v-if="selectedCustomer">Khách hàng: {{ selectedCustomer.name }} ({{ selectedCustomer.phone }})</p>
              <p v-if="selectedCustomer">Điểm tích lũy: {{ selectedCustomer.points }}</p>
              <hr class="border-none border-t border-dashed border-gray-600 my-2">
              <table class="w-full text-left">
                <tr><th>Sản phẩm</th><th>Số lượng</th><th>Thành tiền</th></tr>
                <tr v-for="item in cartItems" :key="item.id">
                  <td>{{ item.name }}</td>
                  <td>{{ item.quantity }}</td>
                  <td>{{ (item.price * item.quantity).toLocaleString('vi-VN') }} VND</td>
                </tr>
                <tr v-for="item in cartItems" :key="'note-' + item.id" v-if="item.itemNotes">
                  <td colspan="3" class="italic">Ghi chú: {{ item.itemNotes }}</td>
                </tr>
              </table>
              <hr class="border-none border-t border-dashed border-gray-600 my-2">
              <p class="text-right">Tổng phụ: {{ subtotal.toLocaleString('vi-VN') }} VND</p>
              <p class="text-right">Thuế VAT ({{ taxRate }}%): {{ taxAmount.toLocaleString('vi-VN') }} VND</p>
              <p v-if="totalDiscount > 0" class="text-right">Giảm giá: -{{ totalDiscount.toLocaleString('vi-VN') }} VND</p>
              <p class="text-right font-bold">Tổng cộng: {{ totalPrice.toLocaleString('vi-VN') }} VND</p>
              <p class="text-right">Phương thức: {{ paymentMethod === 'cash' ? 'Tiền mặt' : paymentMethod === 'card' ? 'Thẻ ngân hàng' : 'Chuyển khoản' }}</p>
              <p v-if="paymentMethod === 'cash'" class="text-right">Khách đưa: {{ amountReceived.toLocaleString('vi-VN') }} VND</p>
              <p v-if="paymentMethod === 'cash'" class="text-right">Tiền thừa: {{ changeDue.toLocaleString('vi-VN') }} VND</p>
              <p v-if="orderNotes">Ghi chú đơn hàng: {{ orderNotes }}</p>
              <hr class="border-none border-t border-dashed border-gray-600 my-2">
              <p>Cảm ơn quý khách và hẹn gặp lại!</p>
              <div class="flex justify-around mt-2">
                <button @click="printReceipt" class="bg-blue-600 text-white py-1 px-4 rounded hover:bg-blue-700 text-xs">In hóa đơn</button>
                <button @click="activeSidebarTab = 'filter'" class="bg-gray-300 text-gray-800 py-1 px-4 rounded hover:bg-gray-400 text-xs">Đóng</button>
              </div>
            </div>
            <!-- Shift Tab -->
            <div v-if="activeSidebarTab === 'shift'">
              <div v-if="!currentShift">
                <label for="initialCash" class="block text-xs font-medium text-gray-700 mb-0.5">Số tiền mặt ban đầu:</label>
                <input type="number" id="initialCash" v-model.number="initialCashInput" placeholder="Nhập số tiền ban đầu trong két"
                       class="w-full p-1.5 border border-gray-300 rounded text-xs mb-2 focus:outline-none focus:ring-1 focus:ring-blue-500" @input="initialCashInput = Math.max(0, initialCashInput)" />
                <button @click="confirmStartShift" class="w-full bg-blue-600 text-white py-1.5 rounded hover:bg-blue-700 text-xs">Bắt đầu ca</button>
              </div>
              <div v-else>
                <p class="mb-1 text-xs">Doanh thu trong ca: <span class="font-bold">{{ currentShift.totalSales.toLocaleString('vi-VN') }} VND</span></p>
                <p class="mb-1 text-xs">Tiền mặt ban đầu: <span class="font-bold">{{ currentShift.initialCash.toLocaleString('vi-VN') }} VND</span></p>
                <p class="mb-2 text-xs">Tổng tiền mặt dự kiến: <span class="font-bold">{{ (currentShift.initialCash + currentShift.totalSales).toLocaleString('vi-VN') }} VND</span></p>
                <label for="endingCash" class="block text-xs font-medium text-gray-700 mb-0.5">Số tiền mặt thực tế cuối ca:</label>
                <input type="number" id="endingCash" v-model.number="endingCashInput" placeholder="Nhập số tiền thực tế trong két"
                       class="w-full p-1.5 border border-gray-300 rounded text-xs mb-2 focus:outline-none focus:ring-1 focus:ring-blue-500" @input="endingCashInput = Math.max(0, endingCashInput)" />
                <button @click="confirmEndShift" class="w-full bg-orange-600 text-white py-1.5 rounded hover:bg-orange-700 text-xs">Xác nhận kết thúc ca</button>
              </div>
              <button @click="activeSidebarTab = 'filter'" class="mt-2 w-full bg-gray-300 text-gray-800 py-1 rounded hover:bg-gray-400 text-xs">Hủy</button>
            </div>
            <!-- Refund Tab -->
            <div v-if="activeSidebarTab === 'refund'">
              <p class="mb-1 text-xs">Tổng tiền: <span class="font-bold">{{ selectedOrderForRefund?.totalPrice.toLocaleString('vi-VN') }} VND</span></p>
              <p class="mb-2 text-xs">Bạn có chắc chắn muốn hoàn tiền cho đơn hàng này?</p>
              <div class="flex justify-around mt-2">
                <button @click="confirmRefund" class="bg-red-600 text-white py-1 px-4 rounded hover:bg-red-700 text-xs">Xác nhận hoàn tiền</button>
                <button @click="activeSidebarTab = 'history'; selectedOrderForRefund = null" class="bg-gray-300 text-gray-800 py-1 px-4 rounded hover:bg-gray-400 text-xs">Hủy</button>
              </div>
            </div>
            <!-- Sales Report Tab -->
            <div v-if="activeSidebarTab === 'salesReport'">
              <h4 class="text-xs font-semibold mb-1">Theo danh mục</h4>
              <table class="w-full mb-3 text-xs border-collapse">
                <thead>
                  <tr class="bg-gray-100">
                    <th class="p-1.5 text-left">Danh mục</th>
                    <th class="p-1.5 text-right">Số lượng</th>
                    <th class="p-1.5 text-right">Doanh thu</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(data, category) in salesReport.byCategory" :key="category" class="border-b">
                    <td class="p-1.5">{{ category }}</td>
                    <td class="p-1.5 text-right">{{ data.quantity }}</td>
                    <td class="p-1.5 text-right">{{ data.total.toLocaleString('vi-VN') }} VND</td>
                  </tr>
                </tbody>
              </table>
              <h4 class="text-xs font-semibold mb-1">Theo sản phẩm</h4>
              <table class="w-full mb-3 text-xs border-collapse">
                <thead>
                  <tr class="bg-gray-100">
                    <th class="p-1.5 text-left">Sản phẩm</th>
                    <th class="p-1.5 text-right">Số lượng</th>
                    <th class="p-1.5 text-right">Doanh thu</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(data, product) in salesReport.byProduct" :key="product" class="border-b">
                    <td class="p-1.5">{{ product }}</td>
                    <td class="p-1.5 text-right">{{ data.quantity }}</td>
                    <td class="p-1.5 text-right">{{ data.total.toLocaleString('vi-VN') }} VND</td>
                  </tr>
                </tbody>
              </table>
              <button @click="activeSidebarTab = 'filter'" class="w-full bg-gray-300 text-gray-800 py-1.5 rounded hover:bg-gray-400 text-xs">Đóng</button>
            </div>
          </div>
        </div>
      </div>
      <!-- Phần sản phẩm -->
      <div class="w-2/3 bg-white flex flex-col h-full">
        <div class="p-1.5 border-b border-gray-200 shrink-0">
          <div class="flex justify-between items-center mb-1">
            <div class="flex items-center space-x-1">
              <button @click="showSidebar = true; activeSidebarTab = 'filter'" class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs hover:bg-gray-300">
                <MenuIcon class="w-4 h-4" />
              </button>
              <h2 class="text-base font-semibold">Sản phẩm</h2>
            </div>
            <div class="flex items-center space-x-1 text-xs">
              <span v-if="currentShift" class="text-green-600 font-medium">Ca: {{ new Date(currentShift.startTime).toLocaleTimeString('vi-VN') }}</span>
              <button v-if="!currentShift" @click="startShift" class="bg-blue-500 text-white px-2 py-0.5 rounded hover:bg-blue-600">Bắt đầu ca</button>
              <button v-else @click="endShift" class="bg-orange-500 text-white px-2 py-0.5 rounded hover:bg-orange-600">Kết ca</button>
              <button @click="showReportSidebar" class="bg-purple-500 text-white px-2 py-0.5 rounded hover:bg-purple-600">Báo cáo</button>
            </div>
          </div>
          <div class="mb-1">
            <input type="text" v-model="barcodeInput" @keyup.enter="scanBarcode" placeholder="Quét mã vạch..."
                   class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" />
          </div>
        </div>
        <div class="flex-1 overflow-y-auto p-2">
          <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2">
            <div v-for="product in filteredProducts" :key="product.id" @click="addToCart(product)"
                 class="bg-gray-50 p-1.5 rounded shadow-sm hover:shadow cursor-pointer transition-all duration-200 flex flex-col items-center text-center"
                 :class="{ 'opacity-60 cursor-not-allowed': product.stock === 0 }">
              <img :src="product.image" :alt="product.name" class="w-12 h-12 object-cover rounded mb-0.5" />
              <h3 class="text-[10px] font-medium truncate w-full">{{ product.name }}</h3>
              <p class="text-green-600 font-bold text-[10px] mt-0.5">{{ product.price.toLocaleString('vi-VN') }} VND</p>
              <p v-if="product.stock <= 10 && product.stock > 0" class="text-orange-500 text-[10px] font-semibold mt-0.5">Tồn: {{ product.stock }}</p>
              <p v-else-if="product.stock === 0" class="text-red-500 text-[10px] font-semibold mt-0.5">Hết hàng</p>
              <p v-else class="text-gray-500 text-[10px] mt-0.5">Tồn: {{ product.stock }}</p>
            </div>
          </div>
        </div>
      </div>
      <!-- Phần giỏ hàng -->
      <div class="w-1/3 bg-white flex flex-col h-full">
        <div class="p-2 border-b border-gray-200 shrink-0">
          <div class="flex justify-between items-center">
            <h2 class="text-base font-semibold">Giỏ hàng</h2>
            <div class="flex space-x-1">
              <button @click="newOrder" class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs hover:bg-gray-300">Đơn mới</button>
              <button @click="parkOrder" class="bg-yellow-500 text-white px-2 py-0.5 rounded text-xs hover:bg-yellow-600">Lưu đơn</button>
            </div>
          </div>
        </div>
        <div v-if="pendingOrders.length > 0" class="p-2 border-b border-gray-200 shrink-0">
          <h3 class="text-xs font-semibold mb-1">Đơn hàng chờ:</h3>
          <div class="flex flex-wrap gap-1 max-h-16 overflow-y-auto">
            <div v-for="order in pendingOrders" :key="order.id"
                 class="relative bg-gray-100 p-1.5 rounded text-[10px] border cursor-pointer hover:bg-gray-200"
                 :class="{ 'border-blue-500 bg-blue-50': activeOrderId === order.id }" @click="loadOrder(order)">
              <span class="font-medium">{{ order.id }}</span> - {{ order.totalPrice.toLocaleString('vi-VN') }} VND
              <button @click.stop="removeParkedOrder(order.id)"
                      class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-3 h-3 flex items-center justify-center text-[8px]">
                ×
              </button>
            </div>
          </div>
        </div>
        <div class="flex-1 p-2 overflow-y-auto">
          <div v-if="cartItems.length === 0" class="text-gray-500 text-center py-4 text-xs">
            Giỏ hàng trống. Vui lòng thêm sản phẩm!
          </div>
          <div v-else class="max-h-48 overflow-y-auto">
            <div v-for="item in cartItems" :key="item.id"
                 class="flex items-center justify-between border-b border-gray-200 py-1.5">
              <div class="flex items-center flex-1 min-w-0">
                <img :src="item.image" :alt="item.name" class="w-8 h-8 object-cover rounded mr-1.5" />
                <div class="flex-1 truncate">
                  <p class="font-medium text-xs truncate">{{ item.name }}</p>
                  <p class="text-gray-600 text-[10px]">{{ item.price.toLocaleString('vi-VN') }} VND</p>
                  <p v-if="item.itemNotes" class="text-gray-500 text-[10px] italic truncate">Ghi chú: {{ item.itemNotes }}</p>
                </div>
              </div>
              <div class="flex items-center space-x-0.5 ml-1">
                <button @click="decreaseQuantity(item)" class="bg-gray-200 text-gray-700 px-1.5 py-0.25 rounded text-[10px]">-</button>
                <span class="font-semibold text-xs">{{ item.quantity }}</span>
                <button @click="increaseQuantity(item)" class="bg-gray-200 text-gray-700 px-1.5 py-0.25 rounded text-[10px]">+</button>
                <button @click="openItemNote(item)" class="text-blue-500 hover:text-blue-700 ml-0.5 text-xs">
                  <EditIcon class="w-3 h-3" />
                </button>
                <button @click="removeFromCart(item)" class="text-red-500 hover:text-red-700 ml-0.5 text-xs">
                  <Trash2Icon class="w-3 h-3" />
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="p-2 border-t border-gray-200 shrink-0">
          <div class="mb-1">
            <label class="block text-[10px] font-medium text-gray-700 mb-0.5">Khách hàng:</label>
            <div v-if="selectedCustomer" class="flex items-center justify-between p-1.5 border border-blue-300 rounded bg-blue-50">
              <span class="text-xs truncate">{{ selectedCustomer.name }} ({{ selectedCustomer.phone }}) - Điểm: {{ selectedCustomer.points }}</span>
              <button @click="removeSelectedCustomer" class="text-red-500 hover:text-red-700 text-[10px] ml-1">Xóa</button>
            </div>
            <button v-else @click="showCustomerSidebar" class="w-full bg-gray-100 text-gray-700 py-1 rounded border border-gray-300 hover:bg-gray-200 text-xs">
              Chọn khách hàng
            </button>
          </div>
          <div class="flex justify-between items-center mb-1">
            <span class="text-xs font-semibold text-gray-700">Tổng phụ:</span>
            <span class="text-base font-bold text-gray-800">{{ subtotal.toLocaleString('vi-VN') }} VND</span>
          </div>
          <div class="flex justify-between items-center mb-1">
            <span class="text-xs font-semibold text-gray-700">Thuế VAT ({{ taxRate }}%):</span>
            <span class="text-base font-bold text-gray-800">{{ taxAmount.toLocaleString('vi-VN') }} VND</span>
          </div>
          <div class="mb-1">
            <label class="block text-[10px] font-medium text-gray-700 mb-0.5">Giảm giá:</label>
            <div class="flex space-x-1 mb-1">
              <input type="number" v-model.number="discountAmount" min="0" placeholder="Số tiền giảm"
                     class="w-1/2 p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" @input="discountAmount = Math.max(0, discountAmount)" />
              <input v-if="usePoints" type="number" v-model.number="pointsToRedeem" min="0" :max="selectedCustomer?.points || 0"
                     placeholder="Điểm đổi" class="w-16 p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
                     @input="pointsToRedeem = Math.max(0, Math.min(pointsToRedeem, selectedCustomer?.points || 0))" />
            </div>
            <div class="flex flex-wrap gap-1 mb-1">
              <button @click="applyDiscountPreset(5)" class="px-2 py-0.5 bg-gray-200 rounded text-[10px] hover:bg-gray-300">5%</button>
              <button @click="applyDiscountPreset(10)" class="px-2 py-0.5 bg-gray-200 rounded text-[10px] hover:bg-gray-300">10%</button>
              <button @click="applyDiscountPreset(20)" class="px-2 py-0.5 bg-gray-200 rounded text-[10px] hover:bg-gray-300">20%</button>
              <button @click="applyDiscountPreset(0)" class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-[10px] hover:bg-red-200">Xóa</button>
            </div>
            <div v-if="selectedCustomer && selectedCustomer.points > 0" class="flex items-center space-x-1 mb-1">
              <input type="checkbox" id="usePoints" v-model="usePoints" class="h-3 w-3 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
              <label for="usePoints" class="text-xs">Sử dụng điểm ({{ selectedCustomer.points }} điểm)</label>
            </div>
            <p v-if="totalDiscount > 0" class="text-[10px] text-green-600 mt-0.5">
              Giảm: <span class="font-bold">{{ totalDiscount.toLocaleString('vi-VN') }} VND</span>
            </p>
          </div>
          <div class="flex justify-between items-center mb-1">
            <span class="text-sm font-semibold">Tổng cộng:</span>
            <span class="text-lg font-bold text-blue-600">{{ totalPrice.toLocaleString('vi-VN') }} VND</span>
          </div>
          <div class="mb-1">
            <label for="paymentMethod" class="block text-[10px] font-medium text-gray-700 mb-0.5">Phương thức thanh toán:</label>
            <select id="paymentMethod" v-model="paymentMethod" class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
              <option value="cash">Tiền mặt</option>
              <option value="card">Thẻ ngân hàng</option>
              <option value="transfer">Chuyển khoản</option>
            </select>
          </div>
          <div v-if="paymentMethod === 'cash'" class="mb-1">
            <label for="amountReceived" class="block text-[10px] font-medium text-gray-700 mb-0.5">Tiền khách đưa:</label>
            <input type="number" id="amountReceived" v-model.number="amountReceived" placeholder="Nhập số tiền khách đưa"
                   class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500" @input="amountReceived = Math.max(0, amountReceived)" />
            <div class="flex flex-wrap gap-1 mt-1">
              <button @click="addQuickCash(50000)" class="px-2 py-0.5 bg-gray-200 rounded text-[10px] hover:bg-gray-300">50K</button>
              <button @click="addQuickCash(100000)" class="px-2 py-0.5 bg-gray-200 rounded text-[10px] hover:bg-gray-300">100K</button>
              <button @click="addQuickCash(200000)" class="px-2 py-0.5 bg-gray-200 rounded text-[10px] hover:bg-gray-300">200K</button>
              <button @click="addQuickCash(500000)" class="px-2 py-0.5 bg-gray-200 rounded text-[10px] hover:bg-gray-300">500K</button>
              <button @click="amountReceived = totalPrice" class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px] hover:bg-blue-200">Đủ tiền</button>
              <button @click="amountReceived = 0" class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-[10px] hover:bg-red-200">Xóa</button>
            </div>
            <p v-if="amountReceived > 0" class="text-[10px] text-gray-600 mt-1">
              Tiền thừa: <span class="font-bold">{{ changeDue.toLocaleString('vi-VN') }} VND</span>
            </p>
          </div>
          <div class="mb-1">
            <label for="orderNotes" class="block text-[10px] font-medium text-gray-700 mb-0.5">Ghi chú đơn hàng:</label>
            <textarea id="orderNotes" v-model="orderNotes" rows="1" placeholder="Thêm ghi chú chung..."
                      class="w-full p-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
          </div>
          <button @click="processPayment"
                  :disabled="cartItems.length === 0 || (paymentMethod === 'cash' && amountReceived < totalPrice)"
                  class="w-full bg-blue-600 text-white py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
            Thanh toán
          </button>
          <button @click="showReceiptSidebar"
                  :disabled="cartItems.length === 0"
                  class="w-full bg-gray-200 text-gray-700 py-1 rounded text-xs hover:bg-gray-300 mt-1 disabled:opacity-50 disabled:cursor-not-allowed">
            Xem & In hóa đơn
          </button>
        </div>
      </div>
    </div>
  </CashierLayout>
</template>

<style scoped>

</style>