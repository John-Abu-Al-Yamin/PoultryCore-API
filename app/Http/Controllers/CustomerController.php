<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    //
    public function store(StoreCustomerRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();
        $customer = $user->customers()->create($data);
        return ApiResponse::success(
            data: $customer,
            message: 'تم حفظ العميل بنجاح',
            statusCode: 201
        );
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = (int) $request->input('per_page', 10);
        $customers = $user->customers()->paginate($perPage);

        return ApiResponse::success(
            data: $customers,
            message: 'تم جلب العملاء بنجاح'
        );
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $customer = $user->customers()->find($id);

        if (! $customer) {
            return ApiResponse::error(
                message: 'العميل غير موجود',
                statusCode: 404
            );
        }

        return ApiResponse::success(
            data: $customer,
            message: 'تم جلب العميل بنجاح'
        );
    }

    public function update(UpdateCustomerRequest $request, int $id)
    {
        $user = $request->user();
        $customer = $user->customers()->find($id);

        if (! $customer) {
            return ApiResponse::error(
                message: 'العميل غير موجود',
                statusCode: 404
            );
        }

        $data = $request->validated();
        $customer->update($data);

        return ApiResponse::success(
            data: $customer,
            message: 'تم تحديث العميل بنجاح'
        );
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $customer = $user->customers()->find($id);

        if (! $customer) {
            return ApiResponse::error(
                message: 'العميل غير موجود',
                statusCode: 404
            );
        }

        if ($customer->sales()->count() > 0) {
            return ApiResponse::error(
                message: 'لا يمكن حذف عميل له مبيعات مسجلة',
                statusCode: 422
            );
        }

        $customer->delete();

        return ApiResponse::success(
            message: 'تم حذف العميل بنجاح'
        );
    }

    public function syncDebts(Request $request, int $id)
    {
        $user = $request->user();
        $customer = $user->customers()->find($id);

        if (! $customer) {
            return ApiResponse::error(
                message: 'العميل غير موجود',
                statusCode: 404
            );
        }

        $customer->recalculateTotalDebts();

        return ApiResponse::success(
            data: $customer,
            message: 'تم تحديث المديونية بنجاح'
        );
    }
}
