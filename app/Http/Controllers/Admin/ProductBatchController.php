<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Batch;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ProductBatchController extends Controller
{
    public function index()
    {
        $batches = Batch::with('supplier')->get(); // Eager load the supplier
        return inertia('admin/batches/Index', [
            'batches' => $batches,
        ]);
    }
    public function show($id)
    {
        $batch = Batch::with([
            'batchItems' => function ($query) {
                $query->with([
                    'product' => function ($productQuery) {
                        $productQuery->select(
                            'id',
                            'name',
                            'sku',
                            'barcode',
                            'unit_id',
                            'description',
                            'selling_price',
                            'image_url'
                        );
                    },
                    'product.unit' => function ($unitQuery) {
                        $unitQuery->select('id', 'name');
                    },
                    'createdBy' => function ($userQuery) {
                        $userQuery->select('id', 'name', 'email');
                    },
                    'updatedBy' => function ($userQuery) {
                        $userQuery->select('id', 'name', 'email');
                    },
                    'purchaseOrderItem' => function ($poItemQuery) {
                        $poItemQuery->select(
                            'id',
                            'ordered_quantity',
                            'unit_cost'
                        );
                    }
                ])->select(
                    'id',
                    'batch_id',
                    'product_id',
                    'purchase_order_item_id',
                    'ordered_quantity',
                    'received_quantity',
                    'remaining_quantity',
                    'current_quantity',
                    'purchase_price',
                    'total_amount',
                    'manufacturing_date',
                    'expiry_date',
                    'inventory_status',
                    'created_by',
                    'updated_by',
                    'created_at',
                    'updated_at'
                );
            },
            'supplier' => function ($query) {
                $query->select('id', 'name', 'contact_person', 'email', 'phone', 'address');
            },
            'purchaseOrder' => function ($query) {
                $query->select(
                    'id',
                    'po_number',
                    'order_date',
                    'expected_delivery_date',
                    'actual_delivery_date',
                    'total_amount'
                );
            },
            'createdBy' => function ($query) {
                $query->select('id', 'name', 'email');
            },
            'updatedBy' => function ($query) {
                $query->select('id', 'name', 'email');
            },
        ])->select(
            'id',
            'batch_number',
            'purchase_order_id',
            'supplier_id',
            'received_date',
            'invoice_number',
            'total_amount',
            'payment_status',
            'paid_amount',
            'receipt_status',
            'is_partial_receipt',
            'notes',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at'
        )->findOrFail($id);
        return Inertia::render('admin/batches/Show', [
            'batch' => $batch,
        ]);
    }
}
