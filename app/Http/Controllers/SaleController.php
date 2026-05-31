<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sale\StoreSaleRequest;
use App\Http\Requests\Sale\UpdateSaleRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function store(StoreSaleRequest $request)
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
            return ApiResponse::error(message: 'لا يمكن البيع من دفعة مغلقة', statusCode: 422);
        }
        if ($batch->current_quantity < $data['quantity']) {
            return ApiResponse::error(
                message: 'الكمية المطلوبة غير متوفرة في الدفعة',
                statusCode: 422
            );
        }

        $sale = Sale::create($data);

        if ($data['payment_type'] === 'cash') {
            Payment::create([
                'user_id' => $user->id,
                'type' => 'from_customer',
                'customer_id' => $sale->customer_id,
                'sale_id' => $sale->id,
                'amount' => $sale->total_price,
                'payment_date' => $sale->sale_date,
                'payment_method' => 'cash',
            ]);
        } else {
            $sale->customer->increment('total_debts', $sale->total_price);
        }

        $sale->batch()->decrement('current_quantity', $sale->quantity);

        return ApiResponse::success(
            data: $sale,
            message: 'تم تسجيل عملية البيع بنجاح',
            statusCode: 201
        );
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $sales = $user->sales()->with(['customer', 'batch'])->get();

        return ApiResponse::success(
            data: $sales,
            message: 'تم جلب المبيعات بنجاح'
        );
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $sale = $user->sales()->with(['customer', 'batch', 'payments'])->find($id);

        if (! $sale) {
            return ApiResponse::error(
                message: 'البيع غير موجود',
                statusCode: 404
            );
        }

        return ApiResponse::success(
            data: $sale,
            message: 'تم جلب البيع بنجاح'
        );
    }

    public function update(UpdateSaleRequest $request, int $id)
    {
        $user = $request->user();
        $sale = $user->sales()->find($id);

        if (! $sale) {
            return ApiResponse::error(
                message: 'البيع غير موجود',
                statusCode: 404
            );
        }

        $data = $request->validated();

        // Handle payment_type change (bypasses paid status lock)
        if (array_key_exists('payment_type', $data) && $data['payment_type'] !== $sale->payment_type) {
            if ($data['payment_type'] === 'cash') {
                $remaining = $sale->total_price - $sale->paid_amount;
                if ($remaining > 0) {
                    Payment::create([
                        'user_id' => $user->id,
                        'type' => 'from_customer',
                        'customer_id' => $sale->customer_id,
                        'sale_id' => $sale->id,
                        'amount' => $remaining,
                        'payment_date' => now(),
                        'payment_method' => 'cash',
                    ]);

                    $sale->increment('paid_amount', $remaining);

                    if ($sale->customer) {
                        $sale->customer->decrement('total_debts', $remaining);
                    }
                }

                $sale->update(['payment_type' => 'cash', 'status' => 'paid']);

                return ApiResponse::success(
                    data: $sale->fresh()->load(['customer', 'batch', 'payments']),
                    message: 'تم تحديث البيع بنجاح'
                );
            } else {
                $sale->payments()
                    ->where('type', 'from_customer')
                    ->where('amount', $sale->paid_amount)
                    ->delete();

                if ($sale->customer) {
                    $sale->customer->increment('total_debts', $sale->total_price);
                }

                $sale->update([
                    'payment_type' => 'credit',
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                ]);

                return ApiResponse::success(
                    data: $sale->fresh()->load(['customer', 'batch', 'payments']),
                    message: 'تم تحديث البيع بنجاح'
                );
            }
        }

        if ($sale->status === 'paid') {
            return ApiResponse::error(
                message: 'لا يمكن تعديل مبيعات تم تسويتها بالكامل',
                statusCode: 422
            );
        }

        if (array_key_exists('customer_id', $data) && $data['customer_id'] !== $sale->customer_id) {
            return ApiResponse::error(
                message: 'لا يمكن تغيير العميل بعد إنشاء عملية البيع',
                statusCode: 422
            );
        }

        if (array_key_exists('batch_id', $data) && $data['batch_id'] !== $sale->batch_id) {
            return ApiResponse::error(
                message: 'لا يمكن تغيير الدفعة بعد إنشاء عملية البيع',
                statusCode: 422
            );
        }

        $oldQuantity = $sale->quantity;
        $oldTotalPrice = $sale->total_price;
        $diff = 0;

        if (array_key_exists('quantity', $data) || array_key_exists('unit_price', $data)) {
            $data['total_price'] = ($data['quantity'] ?? $sale->quantity)
                * ($data['unit_price'] ?? $sale->unit_price);
        }

        if (array_key_exists('total_price', $data) && $data['total_price'] < $sale->paid_amount) {
            return ApiResponse::error(
                message: 'لا يمكن تقليل السعر الإجمالي إلى أقل من المبلغ المدفوع',
                statusCode: 422
            );
        }

        $batch = $sale->batch;
        if (array_key_exists('quantity', $data)) {
            if ($batch && $batch->status === 'closed') {
                return ApiResponse::error(
                    message: 'لا يمكن تعديل الكمية في دفعة مغلقة',
                    statusCode: 422
                );
            }
            $diff = $data['quantity'] - $oldQuantity;
            if ($diff > 0 && $batch && $batch->current_quantity < $diff) {
                return ApiResponse::error(
                    message: 'الكمية المطلوبة غير متوفرة في الدفعة',
                    statusCode: 422
                );
            }
        }

        $sale->update($data);

        if (array_key_exists('quantity', $data) && $diff !== 0) {
            $sale->batch()->decrement('current_quantity', $diff);
        }

        if (array_key_exists('total_price', $data)) {
            $totalDiff = $data['total_price'] - $oldTotalPrice;
            if ($totalDiff !== 0 && $sale->payment_type === 'credit') {
                $sale->customer->increment('total_debts', $totalDiff);
            }

            $sale->recalculateStatus();
        }

        return ApiResponse::success(
            data: $sale,
            message: 'تم تحديث البيع بنجاح'
        );
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $sale = $user->sales()->find($id);

        if (! $sale) {
            return ApiResponse::error(
                message: 'البيع غير موجود',
                statusCode: 404
            );
        }

        if ($sale->payments()->count() > 0) {
            return ApiResponse::error(
                message: 'لا يمكن حذف مبيعات لها مدفوعات مسجلة',
                statusCode: 422
            );
        }

        if ($sale->batch && $sale->batch->status === 'closed') {
            return ApiResponse::error(
                message: 'لا يمكن حذف مبيعات من دفعة مغلقة',
                statusCode: 422
            );
        }

        if ($sale->payment_type === 'credit') {
            $remaining = $sale->total_price - $sale->paid_amount;
            if ($remaining > 0) {
                $sale->customer->decrement('total_debts', $remaining);
            }
        }

        $batch = $sale->batch;
        if ($batch) {
            $batch->increment('current_quantity', $sale->quantity);
        }
        $sale->delete();

        return ApiResponse::success(
            message: 'تم حذف البيع بنجاح'
        );
    }
}
