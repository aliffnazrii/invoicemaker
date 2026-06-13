<?php

namespace App\Http\Controllers\Admin;

use App\Support\DashboardStats;
use Illuminate\Routing\Controller;

class AdminController extends Controller
{
    protected array $data = [];

    public function __construct()
    {
        $this->middleware(backpack_middleware());
    }

    public function dashboard()
    {
        $this->data['title'] = trans('backpack::base.dashboard');
        $this->data['totalSales'] = DashboardStats::totalSales();
        $this->data['monthlySales'] = DashboardStats::monthlySales();
        $this->data['topProducts'] = DashboardStats::topProducts();
        $this->data['invoicesThisMonth'] = DashboardStats::invoicesThisMonth();

        return view(backpack_view('dashboard'), $this->data);
    }

    public function redirect()
    {
        return redirect(backpack_url('dashboard'));
    }
}
