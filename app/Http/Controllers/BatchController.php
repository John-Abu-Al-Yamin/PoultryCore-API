<?php

namespace App\Http\Controllers;

use App\Http\Requests\Batch\StoreBatchRequest;
use App\Http\Requests\Batch\UpdateBatchRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

/**
 * 📌 NOTE (Business Rule - Future Improvement):
 *
 * حالياً إغلاق الـ Batch يتم بشكل يدوي من المستخدم (status = closed).
 *
 * لاحقاً سيتم إضافة عمليات:
 * - البيع (Sell)
 * - النفوق (Mortality)
 *
 * وهذه العمليات ستكون مسؤولة عن تقليل current_quantity تلقائياً.
 *
 * وعند وصول current_quantity إلى 0:
 * → سيتم إغلاق الـ Batch تلقائياً (status = closed).
 *
 * حالياً current_quantity قد لا يكون مستخدم بشكل فعلي،
 * لكنه مُجهز للمرحلة القادمة من النظام.
 */
class BatchController extends Controller
{
    public function store(StoreBatchRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $exists = $user->batches()
            ->where('barn_id', $data['barn_id'])
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return ApiResponse::error(
                message: 'لا يمكن إنشاء دفعة جديدة. يوجد دفعة نشطة بالفعل في هذا العنبر.',
                statusCode: 422
            );
        }

        $data['status'] = 'active';

        $batch = $user->batches()->create($data);

        // complete setup
        if ($user->has_completed_setup == false) {
            $user->update([
                'has_completed_setup' => true,
            ]);
        }

        return ApiResponse::success(
            data: $batch,
            message: 'تم إنشاء الدفعة بنجاح',
            statusCode: 201
        );
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $batches = $user->batches()->get();

        return ApiResponse::success(
            data: $batches,
            message: 'تم جلب الدفعات بنجاح'
        );
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $batch = $user->batches()->find($id);

        if (! $batch) {
            return ApiResponse::error(
                message: 'الدفعه غير موجودة',
                statusCode: 404
            );
        }

        return ApiResponse::success(
            data: $batch,
            message: 'تم جلب الدفعه بنجاح'
        );
    }

    public function update(UpdateBatchRequest $request, int $id)
    {
        $data = $request->validated();
        $user = $request->user();
        $batch = $user->batches()->find($id);

        if (! $batch) {
            return ApiResponse::error(
                message: 'الدفعه غير موجودة',
                statusCode: 404
            );
        }

        $batch->update($data);

        return ApiResponse::success(
            data: $batch,
            message: 'تم تحديث الدفعه بنجاح'
        );
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $batch = $user->batches()->find($id);

        if (! $batch) {
            return ApiResponse::error(
                message: 'الدفعه غير موجودة',
                statusCode: 404
            );
        }

        if ($batch->purchases()->count() > 0) {
            return ApiResponse::error(
                message: 'لا يمكن حذف الدفعة لوجود مشتريات مرتبطة بها',
                statusCode: 422
            );
        }

        if ($batch->sales()->count() > 0) {
            return ApiResponse::error(
                message: 'لا يمكن حذف الدفعة لوجود مبيعات مرتبطة بها',
                statusCode: 422
            );
        }

        $batch->delete();

        return ApiResponse::success(
            message: 'تم حذف الدفعه بنجاح'
        );
    }

    public function close(Request $request, int $id)
    {
        $user = $request->user();

        $batch = $user->batches()
            ->where('status', 'active')
            ->find($id);

        if (! $batch) {
            return ApiResponse::error(
                message: 'الدفعة غير موجودة أو تم إغلاقها',
                statusCode: 404
            );
        }

        $batch->update([
            'status' => 'closed',
            'end_date' => now(),
        ]);

        return ApiResponse::success(
            data: $batch,
            message: 'تم إغلاق الدفعة بنجاح'
        );
    }

    public function open(Request $request, int $id)
    {
        $user = $request->user();

        $batch = $user->batches()
            ->where('status', 'closed')
            ->find($id);

        if (! $batch) {
            return ApiResponse::error(
                message: 'الدفعة غير موجودة أو ليست مغلقة',
                statusCode: 404
            );
        }

        // Check if there's another active batch in the same barn
        $exists = $user->batches()
            ->where('barn_id', $batch->barn_id)
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return ApiResponse::error(
                message: 'لا يمكن إعادة فتح الدفعة. يوجد دفعة نشطة بالفعل في هذا العنبر.',
                statusCode: 422
            );
        }

        $batch->update([
            'status' => 'active',
            'end_date' => null,
        ]);

        return ApiResponse::success(
            data: $batch,
            message: 'تم إعادة فتح الدفعة بنجاح'
        );
    }

    public function costs(Request $request, int $id)
    {
        $user = $request->user();
        $batch = $user->batches()->find($id);

        if (! $batch) {
            return ApiResponse::error(
                message: 'الدفعة غير موجودة',
                statusCode: 404
            );
        }

        $purchases = $batch->purchases()->get();
        $sales = $batch->sales()->get();

        $purchaseByType = $purchases->groupBy('type')->map(function ($items, $type) {
            return [
                'total' => (float) $items->sum('total_price'),
                'count' => $items->count(),
                'quantity' => $items->sum('quantity'),
            ];
        });

        $costs = [
            'batch_id' => $batch->id,
            'summary' => [
                'total_purchases' => (float) $purchases->sum('total_price'),
                'total_sales' => (float) $sales->sum('total_price'),
                'net' => (float) $sales->sum('total_price') - $purchases->sum('total_price'),
            ],
            'purchases' => [
                'chicks' => $purchaseByType['chicks'] ?? ['total' => 0, 'count' => 0, 'quantity' => 0],
                'feed' => $purchaseByType['feed'] ?? ['total' => 0, 'count' => 0, 'quantity' => 0],
                'medicine' => $purchaseByType['medicine'] ?? ['total' => 0, 'count' => 0, 'quantity' => 0],
                'other' => $purchaseByType['other'] ?? ['total' => 0, 'count' => 0, 'quantity' => 0],
            ],
            'revenue' => [
                'total' => (float) $sales->sum('total_price'),
                'count' => $sales->count(),
            ],
        ];

        return ApiResponse::success(
            data: $costs,
            message: 'تم جلب تكاليف الدفعة بنجاح'
        );
    }
}
