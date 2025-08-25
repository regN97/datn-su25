<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BatchItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateBatchItemStatus extends Command
{
    protected $signature = 'app:update-batch-item-status';
    protected $description = 'Cập nhật trạng thái cho tất cả batch items';

    public function handle()
    {
        try {
            DB::beginTransaction();
            
            $now = Carbon::now();
            
            // Cập nhật tất cả batch items không phân biệt nguồn
            BatchItem::query()
                ->where('current_quantity', '>', 0)
                ->chunk(100, function($items) use ($now) {
                    foreach ($items as $item) {
                        $newStatus = $this->determineStatus($item, $now);
                        if ($item->inventory_status !== $newStatus) {
                            $item->update(['inventory_status' => $newStatus]);
                        }
                    }
                });

            DB::commit();
            $this->info('Đã cập nhật trạng thái batch items thành công.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Lỗi: ' . $e->getMessage());
        }
    }

    private function determineStatus(BatchItem $item, Carbon $now): string 
    {
        // Check out of stock
        if ($item->current_quantity <= 0) {
            return 'out_of_stock';
        }

        // Check expired
        if ($item->expiry_date && Carbon::parse($item->expiry_date)->lt($now)) {
            return 'expired';
        }

        // Check expiring soon (15 days)
        if ($item->expiry_date && 
            Carbon::parse($item->expiry_date)->between($now, $now->copy()->addDays(30))) {
            return 'expiring_soon';
        }

        // Check low stock
        if ($item->product && 
            $item->current_quantity <= $item->product->min_stock_level) {
            return 'low_stock';
        }

        return 'active';
    }
}