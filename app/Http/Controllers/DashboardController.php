<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $counts = [
            'barns' => $user->barns()->count(),
            'active_batches' => $user->batches()->where('status', 'active')->count(),
            'batches' => $user->batches()->count(),
            'purchases' => $user->purchases()->count(),
            'payments' => $user->payments()->count(),
            'suppliers' => $user->suppliers()->count(),
            'customers' => $user->customers()->count(),
            'sales' => $user->sales()->count(),
            'deaths' => $user->deaths()->count(),
            'expenses' => $user->expenses()->count(),
        ];

        $recentPurchases = $user->purchases()
            ->with('supplier:id,name')
            ->latest()
            ->take(5)
            ->get()
            ->toArray();

        $recentPayments = $user->payments()
            ->with(['supplier:id,name', 'customer:id,name'])
            ->latest()
            ->take(5)
            ->get()
            ->toArray();

        $recentBatches = $user->batches()
            ->with('barn:id,name')
            ->latest()
            ->take(5)
            ->get()
            ->toArray();

        $recentSales = $user->sales()
            ->with('customer:id,name')
            ->latest()
            ->take(5)
            ->get()
            ->toArray();

        $totalPurchases = (float) $user->purchases()->sum('total_price');
        $totalSales = (float) $user->sales()->sum('total_price');
        $totalExpenses = (float) $user->expenses()->sum('amount');

        $financialSummary = [
            'total_purchases_cost' => $totalPurchases,
            'total_sales_revenue' => $totalSales,
            'total_expenses' => $totalExpenses,
            'total_paid_to_suppliers' => (float) $user->payments()->where('type', 'to_supplier')->sum('amount'),
            'total_received_from_customers' => (float) $user->payments()->where('type', 'from_customer')->sum('amount'),
            'outstanding_supplier_dues' => (float) $user->suppliers()->sum('total_dues'),
            'outstanding_customer_debts' => (float) $user->customers()->sum('total_debts'),
            'net_revenue' => $totalSales - $totalPurchases - $totalExpenses,
        ];

        $productionInsights = [
            'total_current_poultry' => (int) $user->batches()->sum('current_quantity'),
            'active_poultry' => (int) $user->batches()->where('status', 'active')->sum('current_quantity'),
            'total_deaths' => (int) $user->deaths()->sum('quantity'),
            'active_batches' => $user->batches()
                ->with('barn:id,name')
                ->where('status', 'active')
                ->latest()
                ->take(10)
                ->get()
                ->toArray(),
        ];

        $today = Carbon::today();
        $sevenDaysAgo = Carbon::today()->subDays(7);
        $sevenDaysFromNow = Carbon::today()->addDays(7);

        $alerts = [
            'low_stock_batches' => $user->batches()
                ->with('barn:id,name')
                ->where('status', 'active')
                ->where('current_quantity', '<=', 100)
                ->latest()
                ->get()
                ->toArray(),
            'suppliers_with_dues' => $user->suppliers()
                ->where('total_dues', '>', 0)
                ->latest('total_dues')
                ->take(10)
                ->get()
                ->toArray(),
            'customers_with_debts' => $user->customers()
                ->where('total_debts', '>', 0)
                ->latest('total_debts')
                ->take(10)
                ->get()
                ->toArray(),
            'recent_deaths_7_days' => (int) $user->deaths()
                ->where('date', '>=', $sevenDaysAgo)
                ->sum('quantity'),
            'batches_ending_soon' => $user->batches()
                ->with('barn:id,name')
                ->where('status', 'active')
                ->whereNotNull('end_date')
                ->whereBetween('end_date', [$today, $sevenDaysFromNow])
                ->latest()
                ->get()
                ->toArray(),
            'unpaid_purchases' => $user->purchases()
                ->where('status', '!=', 'paid')
                ->count(),
            'unpaid_sales' => $user->sales()
                ->where('status', '!=', 'paid')
                ->count(),
        ];

        return ApiResponse::success(data: [
            'counts' => $counts,
            'financial_summary' => $financialSummary,
            'production_insights' => $productionInsights,
            'alerts' => $alerts,
            'recent' => [
                'purchases' => $recentPurchases,
                'payments' => $recentPayments,
                'batches' => $recentBatches,
                'sales' => $recentSales,
            ],
        ], message: 'Dashboard data retrieved successfully');
    }
}
