<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardStats
{
    public static function totalSales(): float
    {
        return (float) Invoice::query()
            ->selectRaw('COALESCE(SUM(total), 0) as sales')
            ->value('sales');
    }

    public static function monthlySales(int $months = 12): Collection
    {
        $results = collect();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);

            $sales = (float) Invoice::query()
                ->whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->selectRaw('COALESCE(SUM(total), 0) as sales')
                ->value('sales');

            $results->push([
                'label' => $date->format('M Y'),
                'year'  => $date->year,
                'month' => $date->month,
                'sales' => $sales,
            ]);
        }

        return $results;
    }

    public static function topProducts(int $limit = 10): Collection
    {
        return InvoiceItem::query()
            ->leftJoin('products', 'invoice_items.product_id', '=', 'products.id')
            ->selectRaw('COALESCE(products.name, invoice_items.description) as product_name')
            ->selectRaw('SUM(invoice_items.quantity) as total_quantity')
            ->selectRaw('SUM(invoice_items.total) as total_revenue')
            ->groupBy(DB::raw('COALESCE(products.name, invoice_items.description)'))
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    public static function invoicesThisMonth(): int
    {
        return Invoice::query()
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->count();
    }
}
