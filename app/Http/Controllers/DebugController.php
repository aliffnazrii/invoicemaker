<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;

class DebugController extends Controller
{
    private $route;

    public function index(Request $request)
    {

        $this->route = $request->route;

        $this->{$this->route}();
    }

    public function test()
    {
        dd(config('settings.company_name'));
    }

    public function update()
    {
        $invoices = Invoice::all();

        foreach ($invoices as $invoice) {

            $invoice->total = $invoice->subtotal - $invoice->discount;
            $invoice->save();
        }
    }
}
