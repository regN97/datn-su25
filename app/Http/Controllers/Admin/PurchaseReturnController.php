<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\purchaseReturn;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;

class PurchaseReturnController extends Controller
{
    public function index()
    {
        $purchaseReturns = PurchaseReturn::with(['supplier:id,name', 'createdBy:id,name'])
            ->latest('return_date')
            ->get();

        return Inertia::render('admin/purchaseReturn/Index', [
            'purchaseReturns' => $purchaseReturns->map(function ($return) {
                return [
                    'id' => $return->id,
                    'return_number' => $return->return_number,
                    'supplier_name' => $return->supplier->name ?? 'Không xác định',
                    'return_date' => Carbon::parse($return->return_date)->format('d/m/Y'), // <-- sửa lỗi tại đây
                    'status' => $return->status,
                    'total_value_returned' => number_format($return->total_value_returned),
                    'created_by' => $return->createdBy->name ?? 'Không xác định',
                ];
            }),
        ]);
    }
    public function show($id)
{
    $purchaseReturn = PurchaseReturn::with([
        'supplier:id,name',
        'createdBy:id,name',
        'items' => function ($query) {
            $query->with('product:id,name,sku');
        }
    ])->findOrFail($id);

    return Inertia::render('admin/purchaseReturn/Show', [
        'purchaseReturn' => [
            'id' => $purchaseReturn->id,
            'return_number' => $purchaseReturn->return_number,
            'return_date' => $purchaseReturn->return_date,
            'status' => $purchaseReturn->status,
            'reason' => $purchaseReturn->reason,
            'total_items_returned' => $purchaseReturn->total_items_returned,
            'total_value_returned' => $purchaseReturn->total_value_returned,
            'supplier_name' => $purchaseReturn->supplier->name ?? '',
            'created_by' => $purchaseReturn->createdBy->name ?? '',
            'items' => $purchaseReturn->items->map(function ($item) {
                return [
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'batch_number' => $item->batch_number,
                    'manufacturing_date' => $item->manufacturing_date,
                    'expiry_date' => $item->expiry_date,
                    'unit_cost' => $item->unit_cost,
                    'quantity_returned' => $item->quantity_returned,
                    'subtotal' => $item->subtotal,
                    'reason' => $item->reason,
                ];
            }),
        ]
    ]);
}
}

?>