<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Requests\Purchase\UpdatePurchaseRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Payment;
use App\Models\Purchase;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function store(StorePurchaseRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $data['user_id'] = $user->id;
        $data['total_price'] = $data['total_price'] ?? ($data['quantity'] * $data['unit_price']);

        if ($data['payment_type'] === 'cash') {
            $data['paid_amount'] = $data['total_price'];
            $data['status'] = 'paid';
        }

        $batch = $user->batches()->find($data['batch_id']);
        if ($batch->status === 'closed') {
            return ApiResponse::error(message: 'لا يمكن الشراء من دفعة مغلقة', statusCode: 422);
        }

        $purchase = Purchase::create($data);

        if ($data['payment_type'] === 'cash') {
            Payment::create([
                'user_id' => $user->id,
                'type' => 'to_supplier',
                'supplier_id' => $purchase->supplier_id,
                'purchase_id' => $purchase->id,
                'amount' => $purchase->total_price,
                'payment_date' => $purchase->purchase_date,
                'payment_method' => 'cash',
            ]);
        } else {
            $purchase->supplier->increment('total_dues', $purchase->total_price);
        }

        $purchase->batch()->increment('current_quantity', $purchase->quantity);

        return ApiResponse::success(
            data: $purchase,
            message: 'تم تسجيل عملية الشراء بنجاح',
            statusCode: 201
        );
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $purchases = $user->purchases()->with(['supplier', 'batch'])->get();

        return ApiResponse::success(
            data: $purchases,
            message: 'تم جلب المشتريات بنجاح'
        );
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $purchase = $user->purchases()->with(['supplier', 'batch', 'payments'])->find($id);

        if (! $purchase) {
            return ApiResponse::error(
                message: 'الشراء غير موجود',
                statusCode: 404
            );
        }

        return ApiResponse::success(
            data: $purchase,
            message: 'تم جلب الشراء بنجاح'
        );
    }

    public function update(UpdatePurchaseRequest $request, int $id)
    {
        $user = $request->user();
        $purchase = $user->purchases()->find($id);

        if (! $purchase) {
            return ApiResponse::error(
                message: 'الشراء غير موجود',
                statusCode: 404
            );
        }

        $data = $request->validated();

        // Handle payment_type change (bypasses paid status lock)
        if (array_key_exists('payment_type', $data) && $data['payment_type'] !== $purchase->payment_type) {
            if ($data['payment_type'] === 'cash') {
                $remaining = $purchase->total_price - $purchase->paid_amount;
                if ($remaining > 0) {
                    Payment::create([
                        'user_id' => $user->id,
                        'type' => 'to_supplier',
                        'supplier_id' => $purchase->supplier_id,
                        'purchase_id' => $purchase->id,
                        'amount' => $remaining,
                        'payment_date' => now(),
                        'payment_method' => 'cash',
                    ]);

                    $purchase->increment('paid_amount', $remaining);

                    if ($purchase->supplier) {
                        $purchase->supplier->decrement('total_dues', $remaining);
                    }
                }

                $purchase->update(['payment_type' => 'cash', 'status' => 'paid']);

                return ApiResponse::success(
                    data: $purchase->fresh()->load(['supplier', 'batch', 'payments']),
                    message: 'تم تحديث الشراء بنجاح'
                );
            } else {
                $purchase->payments()
                    ->where('type', 'to_supplier')
                    ->where('amount', $purchase->paid_amount)
                    ->delete();

                if ($purchase->supplier) {
                    $purchase->supplier->increment('total_dues', $purchase->total_price);
                }

                $purchase->update([
                    'payment_type' => 'credit',
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                ]);

                return ApiResponse::success(
                    data: $purchase->fresh()->load(['supplier', 'batch', 'payments']),
                    message: 'تم تحديث الشراء بنجاح'
                );
            }
        }

        if ($purchase->status === 'paid') {
            return ApiResponse::error(
                message: 'لا يمكن تعديل مشتريات تم تسويتها بالكامل',
                statusCode: 422
            );
        }

        if (array_key_exists('supplier_id', $data) && $data['supplier_id'] !== $purchase->supplier_id) {
            return ApiResponse::error(
                message: 'لا يمكن تغيير المورد بعد إنشاء عملية الشراء',
                statusCode: 422
            );
        }

        if (array_key_exists('batch_id', $data) && $data['batch_id'] !== $purchase->batch_id) {
            return ApiResponse::error(
                message: 'لا يمكن تغيير الدفعة بعد إنشاء عملية الشراء',
                statusCode: 422
            );
        }

        $oldQuantity = $purchase->quantity;
        $oldTotalPrice = $purchase->total_price;
        $diff = 0;

        if (array_key_exists('quantity', $data) || array_key_exists('unit_price', $data)) {
            $data['total_price'] = ($data['quantity'] ?? $purchase->quantity)
                * ($data['unit_price'] ?? $purchase->unit_price);
        }

        if (array_key_exists('total_price', $data) && $data['total_price'] < $purchase->paid_amount) {
            return ApiResponse::error(
                message: 'لا يمكن تقليل السعر الإجمالي إلى أقل من المبلغ المدفوع',
                statusCode: 422
            );
        }

        $batch = $purchase->batch;
        if (array_key_exists('quantity', $data)) {
            if ($batch && $batch->status === 'closed') {
                return ApiResponse::error(
                    message: 'لا يمكن تعديل الكمية في دفعة مغلقة',
                    statusCode: 422
                );
            }
            $diff = $data['quantity'] - $oldQuantity;
        }

        $purchase->update($data);

        if (array_key_exists('quantity', $data) && $diff !== 0) {
            $purchase->batch()->increment('current_quantity', $diff);
        }

        if (array_key_exists('total_price', $data)) {
            $totalDiff = $data['total_price'] - $oldTotalPrice;
            if ($totalDiff !== 0 && $purchase->payment_type === 'credit') {
                $purchase->supplier->increment('total_dues', $totalDiff);
            }

            $purchase->recalculateStatus();
        }

        return ApiResponse::success(
            data: $purchase,
            message: 'تم تحديث الشراء بنجاح'
        );
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $purchase = $user->purchases()->find($id);

        if (! $purchase) {
            return ApiResponse::error(
                message: 'الشراء غير موجود',
                statusCode: 404
            );
        }

        if ($purchase->payments()->count() > 0) {
            return ApiResponse::error(
                message: 'لا يمكن حذف مشتريات لها مدفوعات مسجلة',
                statusCode: 422
            );
        }

        if ($purchase->batch && $purchase->batch->status === 'closed') {
            return ApiResponse::error(
                message: 'لا يمكن حذف مشتريات من دفعة مغلقة',
                statusCode: 422
            );
        }

        if ($purchase->payment_type === 'credit') {
            $remaining = $purchase->total_price - $purchase->paid_amount;
            if ($remaining > 0) {
                $purchase->supplier->decrement('total_dues', $remaining);
            }
        }

        $batch = $purchase->batch;
        if ($batch) {
            $batch->decrement('current_quantity', min($purchase->quantity, $batch->current_quantity));
        }
        $purchase->delete();

        return ApiResponse::success(
            message: 'تم حذف الشراء بنجاح'
        );
    }
}
