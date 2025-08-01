<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ProductAddedToCart extends Notification implements ShouldQueue
{
    use Queueable;

    public $product;
    public $user;

    public function __construct($product, $user)
    {
        $this->product = $product;
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['broadcast'];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => "Sản phẩm {$this->product['name']} (SKU: {$this->product['sku']}) đã được thêm vào giỏ hàng bởi {$this->user->name}.",
            'product' => $this->product,
            'user' => $this->user->name,
            'time' => now()->format('H:i:s d/m/Y'),
        ]);
    }
}