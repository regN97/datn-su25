<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BatchItem;
use Carbon\Carbon;

class UpdateBatchItemStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-batch-item-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $items = BatchItem::all();

        foreach ($items as $item) {
            // Kiểm tra tồn kho
            if ($item->current_quantity <= 0) {
                $item->inventory_status = 'out_of_stock';
            }
            // Kiểm tra hết hạn
            elseif ($item->expiry_date && Carbon::parse($item->expiry_date)->lt($today)) {
                $item->inventory_status = 'expired';
            }
            // Kiểm tra gần hết (low stock)
            elseif ($item->current_quantity <= $item->product->min_stock_level) { 
                // Có thể thay 5 bằng min_stock_level của sản phẩm
                $item->inventory_status = 'low_stock';
            }
            // Nếu còn hàng bình thường
            else {
                $item->inventory_status = 'active';
            }

            $item->save();
        }

        $this->info('Batch item statuses updated successfully.');
    }
}
