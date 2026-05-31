<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Purchase;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $payments = $user->payments()->with(['supplier', 'purchase'])->get();

        return ApiResponse::success(
            data: $payments,
            message: 'تم جلب المدفوعات بنجاح'
        );
    }

    public function store(StorePaymentRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();
        $data['user_id'] = $user->id;

        if (isset($data['purchase_id'])) {
            $purchase = Purchase::find($data['purchase_id']);
            if ($purchase && $purchase->status === 'paid') {
                return ApiResponse::error(
                    message: 'لا يمكن إضافة دفع لمشتريات تم تسويتها بالكامل',
                    statusCode: 422
                );
            }
        }

        $payment = $user->payments()->create($data);

        if ($payment->purchase_id) {
            $purchase = Purchase::find($payment->purchase_id);
            if ($purchase) {
                $purchase->increment('paid_amount', $payment->amount);
                $purchase->recalculateStatus();
            }
        }

        return ApiResponse::success(
            data: $payment,
            message: 'تم تسجيل الدفع بنجاح',
            statusCode: 201
        );
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $payment = $user->payments()->with(['supplier', 'purchase'])->find($id);

        if (! $payment) {
            return ApiResponse::error(
                message: 'الدفع غير موجود',
                statusCode: 404
            );
        }

        return ApiResponse::success(
            data: $payment,
            message: 'تم جلب الدفع بنجاح'
        );
    }

    public function update(UpdatePaymentRequest $request, int $id)
    {
        $user = $request->user();
        $payment = $user->payments()->find($id);

        if (! $payment) {
            return ApiResponse::error(
                message: 'الدفع غير موجود',
                statusCode: 404
            );
        }

        if ($payment->purchase_id) {
            $purchase = Purchase::find($payment->purchase_id);
            if ($purchase && $purchase->status === 'paid') {
                return ApiResponse::error(
                    message: 'لا يمكن تعديل دفع لمشتريات تم تسويتها بالكامل',
                    statusCode: 422
                );
            }
        }

        $data = $request->validated();
        $oldAmount = $payment->amount;
        $payment->update($data);

        if ($payment->purchase_id && isset($data['amount'])) {
            $purchase = Purchase::find($payment->purchase_id);
            if ($purchase) {
                $diff = $data['amount'] - $oldAmount;
                $purchase->increment('paid_amount', $diff);
                $purchase->recalculateStatus();
            }
        }

        return ApiResponse::success(
            data: $payment,
            message: 'تم تحديث الدفع بنجاح'
        );
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $payment = $user->payments()->find($id);

        if (! $payment) {
            return ApiResponse::error(
                message: 'الدفع غير موجود',
                statusCode: 404
            );
        }

        $purchase = null;
        if ($payment->purchase_id) {
            $purchase = Purchase::find($payment->purchase_id);
            if ($purchase && $purchase->status === 'paid') {
                return ApiResponse::error(
                    message: 'لا يمكن حذف دفع لمشتريات تم تسويتها بالكامل',
                    statusCode: 422
                );
            }
        }

        $payment->delete();

        if ($purchase) {
            $purchase->decrement('paid_amount', $payment->amount);
            $purchase->recalculateStatus();
        }

        return ApiResponse::success(
            message: 'تم حذف الدفع بنجاح'
        );
    }
}
