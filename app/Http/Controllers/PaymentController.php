<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = (int) $request->input('per_page', 10);
        $payments = $user->payments()->with(['supplier', 'purchase', 'customer', 'sale'])->paginate($perPage);

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

        if ($data['type'] === 'to_supplier' && isset($data['purchase_id'])) {
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
            if (isset($data['supplier_id']) && $data['supplier_id'] !== $purchase->supplier_id) {
                return ApiResponse::error(
                    message: 'المورد المحدد لا يتطابق مع مورد عملية الشراء',
                    statusCode: 422
                );
            }
        }

        if ($data['type'] === 'from_customer' && isset($data['sale_id'])) {
            $sale = Sale::find($data['sale_id']);
            if (! $sale) {
                return ApiResponse::error(message: 'البيع غير موجود', statusCode: 404);
            }
            if ($sale->status === 'paid') {
                return ApiResponse::error(
                    message: 'لا يمكن إضافة دفع لمبيعات تم تسويتها بالكامل',
                    statusCode: 422
                );
            }
            if ($data['amount'] > $sale->total_price - $sale->paid_amount) {
                return ApiResponse::error(
                    message: 'المبلغ يتجاوز القيمة المتبقية المطلوب تحصيلها',
                    statusCode: 422
                );
            }
            if (isset($data['customer_id']) && $data['customer_id'] !== $sale->customer_id) {
                return ApiResponse::error(
                    message: 'العميل المحدد لا يتطابق مع عميل عملية البيع',
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

        if ($payment->sale_id) {
            $sale = Sale::find($payment->sale_id);
            if ($sale) {
                $sale->increment('paid_amount', $payment->amount);
                $sale->recalculateStatus();
            }
        }

        if ($payment->customer_id) {
            $customer = Customer::find($payment->customer_id);
            if ($customer) {
                $customer->update(['total_debts' => max(0, $customer->total_debts - $payment->amount)]);
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
        $payment = $user->payments()->with(['supplier', 'purchase', 'customer', 'sale'])->find($id);

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

        // When type changes, reset opposite-side IDs to prevent double-effect
        if (array_key_exists('type', $data) && $data['type'] !== $payment->type) {
            if ($data['type'] === 'from_customer') {
                if (! array_key_exists('purchase_id', $data)) {
                    $data['purchase_id'] = null;
                }
                if (! array_key_exists('supplier_id', $data)) {
                    $data['supplier_id'] = null;
                }
            } elseif ($data['type'] === 'to_supplier') {
                if (! array_key_exists('sale_id', $data)) {
                    $data['sale_id'] = null;
                }
                if (! array_key_exists('customer_id', $data)) {
                    $data['customer_id'] = null;
                }
            }
        }

        $oldAmount = $payment->amount;
        $newAmount = $data['amount'] ?? $oldAmount;

        // --- Purchase validation ---
        $oldPurchaseId = $payment->purchase_id;
        $newPurchaseId = $data['purchase_id'] ?? $oldPurchaseId;

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
            $incomingSupplierId = $data['supplier_id'] ?? $payment->supplier_id;
            if ($incomingSupplierId && $incomingSupplierId !== $targetPurchase->supplier_id) {
                return ApiResponse::error(
                    message: 'المورد المحدد لا يتطابق مع مورد عملية الشراء',
                    statusCode: 422
                );
            }
        }

        // --- Sale validation ---
        $oldSaleId = $payment->sale_id;
        $newSaleId = $data['sale_id'] ?? $oldSaleId;

        if ($newSaleId) {
            $targetSale = Sale::find($newSaleId);
            if (! $targetSale) {
                return ApiResponse::error(message: 'البيع غير موجود', statusCode: 404);
            }
            if ($targetSale->status === 'paid') {
                return ApiResponse::error(
                    message: 'لا يمكن تعديل دفع لمبيعات تم تسويتها بالكامل',
                    statusCode: 422
                );
            }
            $projectedPaid = $targetSale->paid_amount;
            if ($newSaleId === $oldSaleId) {
                $projectedPaid = $projectedPaid - $oldAmount + $newAmount;
            } else {
                $projectedPaid += $newAmount;
            }
            if ($projectedPaid > $targetSale->total_price) {
                return ApiResponse::error(
                    message: 'المبلغ يتجاوز القيمة المتبقية المطلوب تحصيلها',
                    statusCode: 422
                );
            }
            $incomingCustomerId = $data['customer_id'] ?? $payment->customer_id;
            if ($incomingCustomerId && $incomingCustomerId !== $targetSale->customer_id) {
                return ApiResponse::error(
                    message: 'العميل المحدد لا يتطابق مع عميل عملية البيع',
                    statusCode: 422
                );
            }
        }

        $oldSupplierId = $payment->supplier_id;
        $newSupplierId = $data['supplier_id'] ?? $oldSupplierId;

        $oldCustomerId = $payment->customer_id;
        $newCustomerId = $data['customer_id'] ?? $oldCustomerId;

        $payment->update($data);

        // --- Adjust purchase paid_amount ---
        if ($oldPurchaseId && $newPurchaseId && $oldPurchaseId !== $newPurchaseId) {
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
            $purchase = Purchase::find($newPurchaseId);
            if ($purchase) {
                $purchase->update(['paid_amount' => max(0, $purchase->paid_amount + $newAmount - $oldAmount)]);
                $purchase->recalculateStatus();
            }
        } elseif ($oldPurchaseId && ! $newPurchaseId) {
            $oldPurchase = Purchase::find($oldPurchaseId);
            if ($oldPurchase) {
                $oldPurchase->update(['paid_amount' => max(0, $oldPurchase->paid_amount - $oldAmount)]);
                $oldPurchase->recalculateStatus();
            }
        } elseif (! $oldPurchaseId && $newPurchaseId) {
            $newPurchase = Purchase::find($newPurchaseId);
            if ($newPurchase) {
                $newPurchase->increment('paid_amount', $newAmount);
                $newPurchase->recalculateStatus();
            }
        }

        // --- Adjust sale paid_amount ---
        if ($oldSaleId && $newSaleId && $oldSaleId !== $newSaleId) {
            $oldSale = Sale::find($oldSaleId);
            if ($oldSale) {
                $oldSale->update(['paid_amount' => max(0, $oldSale->paid_amount - $oldAmount)]);
                $oldSale->recalculateStatus();
            }
            $newSale = Sale::find($newSaleId);
            if ($newSale) {
                $newSale->increment('paid_amount', $newAmount);
                $newSale->recalculateStatus();
            }
        } elseif ($newSaleId && $newAmount !== $oldAmount) {
            $sale = Sale::find($newSaleId);
            if ($sale) {
                $sale->update(['paid_amount' => max(0, $sale->paid_amount + $newAmount - $oldAmount)]);
                $sale->recalculateStatus();
            }
        } elseif ($oldSaleId && ! $newSaleId) {
            $oldSale = Sale::find($oldSaleId);
            if ($oldSale) {
                $oldSale->update(['paid_amount' => max(0, $oldSale->paid_amount - $oldAmount)]);
                $oldSale->recalculateStatus();
            }
        } elseif (! $oldSaleId && $newSaleId) {
            $newSale = Sale::find($newSaleId);
            if ($newSale) {
                $newSale->increment('paid_amount', $newAmount);
                $newSale->recalculateStatus();
            }
        }

        // --- Adjust supplier total_dues ---
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

        // --- Adjust customer total_debts ---
        if ($oldCustomerId) {
            $oldCustomer = Customer::find($oldCustomerId);
            if ($oldCustomer) {
                $oldCustomer->update(['total_debts' => $oldCustomer->total_debts + $oldAmount]);
            }
        }
        if ($newCustomerId) {
            $newCustomer = Customer::find($newCustomerId);
            if ($newCustomer) {
                $newCustomer->update(['total_debts' => max(0, $newCustomer->total_debts - $newAmount)]);
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

        $sale = null;
        if ($payment->sale_id) {
            $sale = Sale::find($payment->sale_id);
            if ($sale && $sale->status === 'paid') {
                return ApiResponse::error(
                    message: 'لا يمكن حذف دفع لمبيعات تم تسويتها بالكامل',
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

        if ($sale) {
            $sale->update(['paid_amount' => max(0, $sale->paid_amount - $payment->amount)]);
            $sale->recalculateStatus();
        }

        if ($payment->customer_id) {
            Customer::find($payment->customer_id)?->increment('total_debts', $payment->amount);
        }

        return ApiResponse::success(
            message: 'تم حذف الدفع بنجاح'
        );
    }
}
