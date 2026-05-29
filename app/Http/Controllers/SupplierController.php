<?php

namespace App\Http\Controllers;

use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    //

    public function store(StoreSupplierRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();
        $supplier = $user->suppliers()->create($data);
        return ApiResponse::success(
            data: $supplier,
            message: 'تم حفظ المورد بنجاح',
            statusCode: 201
        );
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $suppliers = $user->suppliers()->get();

        return ApiResponse::success(
            data: $suppliers,
            message: 'تم جلب الموردين بنجاح'
        );
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $supplier = $user->suppliers()->find($id);

        if (!$supplier) {
            return ApiResponse::error(
                message: 'المورد غير موجود',
                statusCode: 404
            );
        }

        return ApiResponse::success(
            data: $supplier,
            message: 'تم جلب المورد بنجاح'
        );
    }

    public function update(UpdateSupplierRequest $request, int $id)
    {
        $user = $request->user();
        $supplier = $user->suppliers()->find($id);

        if (!$supplier) {
            return ApiResponse::error(
                message: 'المورد غير موجود',
                statusCode: 404
            );
        }

        $data = $request->validated();
        $supplier->update($data);

        return ApiResponse::success(
            data: $supplier,
            message: 'تم تحديث المورد بنجاح'
        );
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $supplier = $user->suppliers()->find($id);

        if (!$supplier) {
            return ApiResponse::error(
                message: 'المورد غير موجود',
                statusCode: 404
            );
        }

        $supplier->delete();

        return ApiResponse::success(
            message: 'تم حذف المورد بنجاح'
        );
    }
}
