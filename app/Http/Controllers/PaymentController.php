<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Purchase;
use App\Models\Supplier;
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
            if (! $purchase) {
                return ApiResponse::error(message: 'الشراء غير موجود', statusCode: 404);
            }
            if ($purchase->status === 'paid') {
                return ApiResponse::error(
                    message: 'لا يمكن إضافة دفع لمشتريات تم تسويتها بالكامل',
                    statusCode: 422
                );
            }
            if ($data['amount'] > $purchase->total_price - $purchase->paid_amount) {
                return ApiResponse::error(
                    message: 'المبلغ يتجاوز القيمة المتبقية المطلوب سدادها',
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

        if ($payment->supplier_id) {
            $supplier = Supplier::find($payment->supplier_id);
            if ($supplier) {
                $supplier->update(['total_dues' => max(0, $supplier->total_dues - $payment->amount)]);
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

        $data = $request->validated();

        $oldAmount = $payment->amount;
        $oldPurchaseId = $payment->purchase_id;
        $oldSupplierId = $payment->supplier_id;

        $newAmount = $data['amount'] ?? $oldAmount;
        $newPurchaseId = $data['purchase_id'] ?? $oldPurchaseId;
        $newSupplierId = $data['supplier_id'] ?? $oldSupplierId;

        // Block if the linked purchase is paid
        if ($newPurchaseId) {
            $targetPurchase = Purchase::find($newPurchaseId);
            if (! $targetPurchase) {
                return ApiResponse::error(message: 'الشراء غير موجود', statusCode: 404);
            }
            if ($targetPurchase->status === 'paid') {
                return ApiResponse::error(
                    message: 'لا يمكن تعديل دفع لمشتريات تم تسويتها بالكامل',
                    statusCode: 422
                );
            }
            // Prevent overpayment
            $projectedPaid = $targetPurchase->paid_amount;
            if ($newPurchaseId === $oldPurchaseId) {
                $projectedPaid = $projectedPaid - $oldAmount + $newAmount;
            } else {
                $projectedPaid += $newAmount;
            }
            if ($projectedPaid > $targetPurchase->total_price) {
                return ApiResponse::error(
                    message: 'المبلغ يتجاوز القيمة المتبقية المطلوب سدادها',
                    statusCode: 422
                );
            }
        }

        $payment->update($data);

        // Adjust purchase paid_amount
        if ($oldPurchaseId && $newPurchaseId && $oldPurchaseId !== $newPurchaseId) {
            // Purchase changed: unwind old, apply new
            $oldPurchase = Purchase::find($oldPurchaseId);
            if ($oldPurchase) {
                $oldPurchase->update(['paid_amount' => max(0, $oldPurchase->paid_amount - $oldAmount)]);
                $oldPurchase->recalculateStatus();
            }
            $newPurchase = Purchase::find($newPurchaseId);
            if ($newPurchase) {
                $newPurchase->increment('paid_amount', $newAmount);
                $newPurchase->recalculateStatus();
            }
        } elseif ($newPurchaseId && $newAmount !== $oldAmount) {
            // Same purchase, amount changed
            $purchase = Purchase::find($newPurchaseId);
            if ($purchase) {
                $purchase->update(['paid_amount' => max(0, $purchase->paid_amount + $newAmount - $oldAmount)]);
                $purchase->recalculateStatus();
            }
        } elseif ($oldPurchaseId && ! $newPurchaseId) {
            // Purchase removed: unwind old
            $oldPurchase = Purchase::find($oldPurchaseId);
            if ($oldPurchase) {
                $oldPurchase->update(['paid_amount' => max(0, $oldPurchase->paid_amount - $oldAmount)]);
                $oldPurchase->recalculateStatus();
            }
        } elseif (! $oldPurchaseId && $newPurchaseId) {
            // Purchase added: apply new
            $newPurchase = Purchase::find($newPurchaseId);
            if ($newPurchase) {
                $newPurchase->increment('paid_amount', $newAmount);
                $newPurchase->recalculateStatus();
            }
        }

        // Adjust supplier total_dues: unwind old effect, apply new effect
        if ($oldSupplierId) {
            $oldSupplier = Supplier::find($oldSupplierId);
            if ($oldSupplier) {
                $oldSupplier->update(['total_dues' => $oldSupplier->total_dues + $oldAmount]);
            }
        }
        if ($newSupplierId) {
            $newSupplier = Supplier::find($newSupplierId);
            if ($newSupplier) {
                $newSupplier->update(['total_dues' => max(0, $newSupplier->total_dues - $newAmount)]);
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
            $purchase->update(['paid_amount' => max(0, $purchase->paid_amount - $payment->amount)]);
            $purchase->recalculateStatus();
        }

        if ($payment->supplier_id) {
            Supplier::find($payment->supplier_id)?->increment('total_dues', $payment->amount);
        }

        return ApiResponse::success(
            message: 'تم حذف الدفع بنجاح'
        );
    }
}
