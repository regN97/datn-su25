<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VNPayService
{
    private $vnp_TmnCode;
    private $vnp_HashSecret;
    private $vnp_Url;
    private $vnp_Returnurl;
    private $vnp_OrderType;
    private $vnp_Locale;
    private $vnp_CurrCode;

    public function __construct()
    {
        $this->vnp_TmnCode = config('vnpay.tmn_code');
        $this->vnp_HashSecret = config('vnpay.hash_secret');
        $this->vnp_Url = config('vnpay.url');
        $this->vnp_Returnurl = config('vnpay.return_url');
        $this->vnp_OrderType = config('vnpay.order_type', 'billpayment');
        $this->vnp_Locale = config('vnpay.locale', 'vn');
        $this->vnp_CurrCode = config('vnpay.currency', 'VND');
    }

    /**
     * Kiểm tra cấu hình VNPay
     */
    public function checkConfiguration()
    {
        if (empty($this->vnp_TmnCode) || empty($this->vnp_HashSecret) || empty($this->vnp_Url) || empty($this->vnp_Returnurl)) {
            Log::error('VNPay configuration missing', [
                'tmn_code' => !empty($this->vnp_TmnCode),
                'hash_secret' => !empty($this->vnp_HashSecret),
                'url' => !empty($this->vnp_Url),
                'return_url' => !empty($this->vnp_Returnurl),
            ]);
            return false;
        }
        return true;
    }

    /**
     * Tạo URL thanh toán VNPay
     */
    public function createPaymentUrl($txnRef, $amount, $orderInfo, $ipAddr, $bankCode = null)
    {
        try {
            if (!$this->checkConfiguration()) {
                throw new \Exception('Cấu hình VNPay không đầy đủ');
            }

            $inputData = [
                'vnp_Version' => '2.1.0',
                'vnp_TmnCode' => $this->vnp_TmnCode,
                'vnp_Amount' => $amount * 100,
                'vnp_Command' => 'pay',
                'vnp_CreateDate' => Carbon::now('Asia/Ho_Chi_Minh')->format('YmdHis'),
                'vnp_CurrCode' => $this->vnp_CurrCode,
                'vnp_IpAddr' => $ipAddr,
                'vnp_Locale' => $this->vnp_Locale,
                'vnp_OrderInfo' => urlencode($orderInfo),
                'vnp_OrderType' => $this->vnp_OrderType,
                'vnp_ReturnUrl' => url($this->vnp_Returnurl),
                'vnp_TxnRef' => $txnRef,
            ];

            if ($bankCode) {
                $inputData['vnp_BankCode'] = $bankCode;
            }

            ksort($inputData);
            $hashData = http_build_query($inputData, '', '&', PHP_QUERY_RFC3986);
            $vnp_SecureHash = hash_hmac('sha512', $hashData, $this->vnp_HashSecret);
            Log::debug('VNPay createPaymentUrl', [
                'txn_ref' => $txnRef,
                'amount' => $amount,
                'hashData' => $hashData,
                'secureHash' => $vnp_SecureHash,
            ]);

            return $this->vnp_Url . '?' . $hashData . '&vnp_SecureHash=' . $vnp_SecureHash;
        } catch (\Exception $e) {
            Log::error('VNPay createPaymentUrl error', [
                'txn_ref' => $txnRef,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Xác thực callback từ VNPay
     */
    public function verifyCallback($inputData)
    {
        try {
            if (!$this->checkConfiguration()) {
                Log::error('VNPay configuration missing for callback verification');
                return false;
            }

            $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
            unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);
            ksort($inputData);
            $hashData = http_build_query($inputData, '', '&', PHP_QUERY_RFC3986);
            $secureHash = hash_hmac('sha512', $hashData, $this->vnp_HashSecret);
            $isValid = $secureHash === $vnp_SecureHash;

            Log::debug('VNPay verifyCallback', [
                'txn_ref' => $inputData['vnp_TxnRef'] ?? '',
                'hashData' => $hashData,
                'secureHash' => $secureHash,
                'vnp_SecureHash' => $vnp_SecureHash,
                'is_valid' => $isValid,
            ]);

            return $isValid;
        } catch (\Exception $e) {
            Log::error('VNPay verifyCallback error', [
                'txn_ref' => $inputData['vnp_TxnRef'] ?? '',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}