<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VNPay Configuration
    |--------------------------------------------------------------------------
    |
    | Các thông số kết nối đến cổng thanh toán VNPay
    | Dùng env() để dễ dàng thay đổi giữa sandbox và production
    |
    */

    // Mã website tại VNPay (TMN Code)
    'tmn_code' => env('VNPAY_TMN_CODE', ''),

    // Chuỗi bí mật do VNPay cung cấp (Hash Secret)
    'hash_secret' => env('VNPAY_HASH_SECRET', ''),

    // URL thanh toán VNPay
    'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),

    // URL nhận kết quả sau khi thanh toán (do VNPay redirect về)
    'return_url' => env('VNPAY_RETURN_URL', 'http://127.0.0.1:8000/cashier/pos/vnpay/callback'),

    // URL nhận thông báo IPN từ VNPay (server-to-server)
    'ipn_url' => env('VNPAY_IPN_URL', 'http://127.0.0.1:8000/api/payments/vnpay/ipn'),

    // URL frontend hiển thị khi thanh toán thành công
    'frontend_success_url' => env('VNPAY_FRONTEND_SUCCESS_URL', 'http://127.0.0.1:8000/cashier/pos'),

    // URL frontend hiển thị khi thanh toán thất bại
    'frontend_failed_url' => env('VNPAY_FRONTEND_FAILED_URL', 'http://127.0.0.1:8000/cashier/pos'),

    // Môi trường: sandbox | production
    'environment' => env('VNPAY_ENVIRONMENT', 'sandbox'),
];
