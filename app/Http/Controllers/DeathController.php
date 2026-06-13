<?php

namespace App\Http\Controllers;

use App\Http\Requests\Death\StoreDeathRequest;
use App\Http\Requests\Death\UpdateDeathRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Death;
use Illuminate\Http\Request;

class DeathController extends Controller
{
    public function store(StoreDeathRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $batch = $user->batches()->find($data['batch_id']);

        if (!$batch) {
            return ApiResponse::error(message: 'الدفعة غير موجودة', statusCode: 404);
        }

        if ($batch->status === 'closed') {
            return ApiResponse::error(message: 'لا يمكن تسجيل وفيات في دفعة مغلقة', statusCode: 422);
        }

        if ($batch->current_quantity < $data['quantity']) {
            return ApiResponse::error(message: 'الكمية المطلوبة تتجاوز الكمية المتاحة في الدفعة', statusCode: 422);
        }

        $data['user_id'] = $user->id;
        $death = $user->deaths()->create($data);
        $batch->decrement('current_quantity', $data['quantity']);

        return ApiResponse::success(
            data: $death,
            message: 'تم تسجيل عملية الوفاة بنجاح',
            statusCode: 201
        );
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $deaths = $request->user()->deaths()
            ->with('batch')
            ->latest()
            ->paginate($perPage);
        return ApiResponse::success(
            data: $deaths,
            message: 'تم جلب الوفيات بنجاح'
        );
    }

    public function show(Request $request, int $id)
    {
        $death = $request->user()->deaths()
            ->with('batch')
            ->find($id);

        if (!$death) {
            return ApiResponse::error(message: 'الوفاة غير موجودة', statusCode: 404);
        }

        return ApiResponse::success(
            data: $death,
            message: 'تم جلب الوفاة بنجاح'
        );
    }

    public function update(UpdateDeathRequest $request, int $id)
    {
        $user = $request->user();
        $death = $user->deaths()->find($id);

        if (!$death) {
            return ApiResponse::error(message: 'الوفاة غير موجودة', statusCode: 404);
        }

        $data = $request->validated();

        if (array_key_exists('batch_id', $data) && $data['batch_id'] !== $death->batch_id) {
            return ApiResponse::error(message: 'لا يمكن تغيير الدفعة بعد تسجيل الوفاة', statusCode: 422);
        }

        $oldQuantity = $death->quantity;
        $diff = 0;

        if (array_key_exists('quantity', $data)) {
            $diff = $data['quantity'] - $oldQuantity;

            if ($diff > 0) {
                $batch = $user->batches()->find($death->batch_id);

                if (!$batch || $batch->current_quantity < $diff) {
                    return ApiResponse::error(message: 'الكمية المطلوبة تتجاوز الكمية المتاحة في الدفعة', statusCode: 422);
                }
            }
        }

        $death->update($data);

        if ($diff !== 0) {
            $death->batch()->decrement('current_quantity', $diff);
        }

        return ApiResponse::success(
            data: $death->fresh()->load('batch'),
            message: 'تم تحديث الوفاة بنجاح'
        );
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $death = $user->deaths()->find($id);

        if (!$death) {
            return ApiResponse::error(message: 'الوفاة غير موجودة', statusCode: 404);
        }

        $batch = $user->batches()->find($death->batch_id);

        if ($batch && $batch->status === 'closed') {
            return ApiResponse::error(message: 'لا يمكن حذف وفيات من دفعة مغلقة', statusCode: 422);
        }

        if ($batch) {
            $batch->increment('current_quantity', $death->quantity);
        }

        $death->delete();

        return ApiResponse::success(
            message: 'تم حذف الوفاة بنجاح'
        );
    }
}
