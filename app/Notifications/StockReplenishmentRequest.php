<?php

// app/Notifications/StockReplenishmentRequest.php

// app/Notifications/StockReplenishmentRequest.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockReplenishmentRequest extends Notification
{
    use Queueable;

    protected $cashier;
    protected $productData;

    public function __construct($cashier, array $productData)
    {
        $this->cashier = $cashier;
        $this->productData = $productData;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        // Lấy thông tin từ mảng productData
        $productName = $this->productData['name'];
        $productSku = $this->productData['sku'];
        $quantity = $this->productData['quantity'] ?? 1; // Lấy số lượng, mặc định là 1 nếu không có

        return [
            'cashier_name' => $this->cashier->name,
            'product_name' => $productName,
            'sku' => $productSku,
            'quantity' => $quantity,
            'message' => "Nhân viên {$this->cashier->name} đã yêu cầu nhập thêm {$quantity} sản phẩm: {$productName} (SKU: {$productSku}).",
        ];
    }
}
