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

        if ($purchase->status === 'paid') {
            return ApiResponse::error(
                message: 'لا يمكن تعديل مشتريات تم تسويتها بالكامل',
                statusCode: 422
            );
        }

        $data = $request->validated();

        if (array_key_exists('quantity', $data) || array_key_exists('unit_price', $data)) {
            $data['total_price'] = ($data['quantity'] ?? $purchase->quantity)
                * ($data['unit_price'] ?? $purchase->unit_price);
        }

        $purchase->update($data);

        if (array_key_exists('total_price', $data)) {
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

        if ($purchase->payment_type === 'credit') {
            $purchase->supplier->decrement('total_dues', $purchase->total_price - $purchase->paid_amount);
        }

        $purchase->batch()->decrement('current_quantity', $purchase->quantity);
        $purchase->delete();

        return ApiResponse::success(
            message: 'تم حذف الشراء بنجاح'
        );
    }
}
