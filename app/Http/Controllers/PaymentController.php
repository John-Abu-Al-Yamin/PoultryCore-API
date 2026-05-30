<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentRequest;
use App\Http\Responses\ApiResponse;
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

        $payment = $user->payments()->create($data);

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

        if (!$payment) {
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

        if (!$payment) {
            return ApiResponse::error(
                message: 'الدفع غير موجود',
                statusCode: 404
            );
        }

        $data = $request->validated();
        $payment->update($data);

        return ApiResponse::success(
            data: $payment,
            message: 'تم تحديث الدفع بنجاح'
        );
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $payment = $user->payments()->find($id);

        if (!$payment) {
            return ApiResponse::error(
                message: 'الدفع غير موجود',
                statusCode: 404
            );
        }

        $payment->delete();

        return ApiResponse::success(
            message: 'تم حذف الدفع بنجاح'
        );
    }
}
