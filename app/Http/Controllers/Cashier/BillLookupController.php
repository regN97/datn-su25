<?php

namespace App\Http\Controllers\Cashier;

use App\Models\Bill;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class BillLookupController extends Controller
{
    public function index()
    {
        $bills = Bill::query()
            ->with(['customer', 'details.product', 'details.batch', 'cashier', 'paymentStatus'])
            ->latest()
            ->take(50)
            ->paginate(5);

        $bills->setCollection($bills->getCollection()->map(function ($bill) {
            return [
                'id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'customer_name' => $bill->customer?->customer_name,
                'phone' => $bill->customer?->phone,
                'total_amount' => $bill->total_amount,
                'discount_amount' => $bill->discount_amount ?? 0,
                'payment_method' => $bill->payment_method,
                'payment_status_id' => $bill->payment_status_id,
                'payment_status_name' => $bill->paymentStatus?->name ?? 'Không xác định',
                'payment_proof_url' => $bill->payment_proof_url ? Storage::url($bill->payment_proof_url) : null,
                'cashier_name' => $bill->cashier?->name,
                'created_at' => $bill->created_at->format('d/m/Y H:i'),
                'received_money' => $bill->received_money,
                'change_money' => $bill->change_money,
                'notes' => $bill->notes,
                'details' => $bill->details->map(function ($detail) {
                    return [
                        'product_name' => $detail->p_name,
                        'quantity' => $detail->quantity,
                        'unit_price' => $detail->unit_price,
                        'subtotal' => $detail->subtotal,
                        'batch_number' => $detail->batch?->batch_number,
                    ];
                }),
            ];
        }));

        Log::info('Bills structure (index):', $bills->toArray());

        return Inertia::render('cashier/pos/BillLookup', [
            'bills' => $bills,
            'query' => '',
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string|max:50',
        ]);

        $query = $request->input('query');

        $bills = Bill::query()
            ->with(['customer', 'details.product', 'details.batch', 'cashier', 'paymentStatus'])
            ->when($query, function ($q) use ($query) {
                $q->where('bill_number', 'like', "%{$query}%")
                    ->orWhereHas('customer', function ($q) use ($query) {
                        $q->where('customer_name', 'like', "%{$query}%")
                            ->orWhere('phone', 'like', "%{$query}%");
                    })
                    ->orWhereHas('details.batch', function ($q) use ($query) {
                        $q->where('batch_number', 'like', "%{$query}%");
                    });
            })
            ->latest()
            ->take(50)
            ->paginate(5);

        $bills->setCollection($bills->getCollection()->map(function ($bill) {
            return [
                'id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'customer_name' => $bill->customer?->customer_name,
                'phone' => $bill->customer?->phone,
                'total_amount' => $bill->total_amount,
                'discount_amount' => $bill->discount_amount ?? 0,
                'payment_method' => $bill->payment_method,
                'payment_status_id' => $bill->payment_status_id,
                'payment_status_name' => $bill->paymentStatus?->name ?? 'Không xác định',
                'payment_proof_url' => $bill->payment_proof_url ? Storage::url($bill->payment_proof_url) : null,
                'cashier_name' => $bill->cashier?->name,
                'created_at' => $bill->created_at->format('d/m/Y H:i'),
                'received_money' => $bill->received_money,
                'change_money' => $bill->change_money,
                'notes' => $bill->notes,
                'details' => $bill->details->map(function ($detail) {
                    return [
                        'product_name' => $detail->p_name,
                        'quantity' => $detail->quantity,
                        'unit_price' => $detail->unit_price,
                        'subtotal' => $detail->subtotal,
                        'batch_number' => $detail->batch?->batch_number,
                    ];
                }),
            ];
        }));

        Log::info('Bills structure (search):', $bills->toArray());

        return Inertia::render('cashier/pos/BillLookup', [
            'bills' => $bills,
            'query' => $query,
        ]);
    }

    public function uploadPaymentProof(Request $request, Bill $bill)
    {
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            if ($bill->payment_proof_url) {
                Storage::disk('public')->delete($bill->payment_proof_url);
            }

            $path = $request->file('payment_proof')->store('payment_proofs', 'public');

            $bill->update(['payment_proof_url' => $path]);

            return redirect()->back()->with('success', 'Minh chứng thanh toán đã được tải lên thành công.');

        } catch (\Exception $e) {
            Log::error('Error uploading payment proof: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Không thể tải lên minh chứng thanh toán. Vui lòng thử lại.');
        }
    }

    public function markAsPaid(Request $request, Bill $bill)
{
    try {
        $request->validate([
            'payment_method' => 'required|string|in:cash,card,bank_transfer,vnpay',
        ]);

        if ($bill->payment_status_id !== 1) {
            return redirect()->back()->with('error', 'Hóa đơn không thể đánh dấu là đã thanh toán vì trạng thái hiện tại là ' . ($bill->paymentStatus?->name ?? 'Không xác định') . '.');
        }

        $bill->update([
            'payment_status_id' => 2,
            'payment_method' => $request->input('payment_method'),
            'received_money' => $bill->total_amount,
            'change_money' => 0,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Hóa đơn đã được đánh dấu là đã thanh toán.');

    } catch (\Exception $e) {
        Log::error('Error marking bill as paid: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Không thể đánh dấu hóa đơn là đã thanh toán. Vui lòng thử lại.');
    }
}
}