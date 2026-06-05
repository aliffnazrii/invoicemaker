<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}
