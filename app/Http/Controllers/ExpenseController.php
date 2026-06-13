<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function store(StoreExpenseRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $expense = Expense::create($data);

        return ApiResponse::success(
            data: $expense,
            message: 'تم تسجيل المصروف بنجاح',
            statusCode: 201
        );
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $expenses = $request->user()->expenses()->with('batch')->paginate($perPage);

        return ApiResponse::success(
            data: $expenses,
            message: 'تم جلب المصروفات بنجاح'
        );
    }

    public function show(Request $request, int $id)
    {
        $expense = $request->user()->expenses()->with('batch')->find($id);

        if (! $expense) {
            return ApiResponse::error(
                message: 'المصروف غير موجود',
                statusCode: 404
            );
        }

        return ApiResponse::success(
            data: $expense,
            message: 'تم جلب المصروف بنجاح'
        );
    }

    public function update(UpdateExpenseRequest $request, int $id)
    {
        $expense = $request->user()->expenses()->find($id);

        if (! $expense) {
            return ApiResponse::error(
                message: 'المصروف غير موجود',
                statusCode: 404
            );
        }

        $expense->update($request->validated());

        return ApiResponse::success(
            data: $expense,
            message: 'تم تحديث المصروف بنجاح'
        );
    }

    public function destroy(Request $request, int $id)
    {
        $expense = $request->user()->expenses()->find($id);

        if (! $expense) {
            return ApiResponse::error(
                message: 'المصروف غير موجود',
                statusCode: 404
            );
        }

        $expense->delete();

        return ApiResponse::success(
            message: 'تم حذف المصروف بنجاح'
        );
    }
}
