<?php

namespace App\Http\Controllers;

use App\Http\Requests\Barn\StoreBarnRequest;
use App\Http\Requests\Barn\UpdateBarnRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

class BarnController extends Controller
{
    //
    public function store(StoreBarnRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $barn = $user->barns()->create($data);
        return ApiResponse::success(
            data: $barn,
            message: 'تم حفظ الحقل بنجاح',
            statusCode: 201
        );
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $barns = $user->barns()->withCount('batches')->get();

        return ApiResponse::success(
            data: $barns,
            message: 'تم جلب الحقول بنجاح'
        );
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $barn = $user->barns()->with('batches')->find($id);

        if (!$barn) {
            return ApiResponse::error(
                message: 'الحقل غير موجود',
                statusCode: 404
            );
        }

        return ApiResponse::success(
            data: $barn,
            message: 'تم جلب الحقل بنجاح'
        );
    }

    public function update(UpdateBarnRequest $request, int $id)
    {
        $user = $request->user();
        $barn = $user->barns()->find($id);

        if (!$barn) {
            return ApiResponse::error(
                message: 'الحقل غير موجود',
                statusCode: 404
            );
        }

        $data = $request->validated();
        $barn->update($data);

        return ApiResponse::success(
            data: $barn,
            message: 'تم تحديث الحقل بنجاح'
        );
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $barn = $user->barns()->find($id);

        if (!$barn) {
            return ApiResponse::error(
                message: 'الحقل غير موجود',
                statusCode: 404
            );
        }

        $barn->delete();

        return ApiResponse::success(
            message: 'تم حذف الحقل بنجاح'
        );
    }
}
