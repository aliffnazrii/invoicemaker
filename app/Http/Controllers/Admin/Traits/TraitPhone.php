<?php

namespace App\Http\Controllers\Admin\Traits;

use Illuminate\Http\Request;;

trait TraitPhone
{
    public function handlePhone($field)
    {
        $phone = request()->$field;
        // $phone = $data['company_phone'] ?? '';

        $phone = ltrim(trim($phone), '+');

        if (!str_starts_with($phone, '60')) {
            return '60' . $phone;
        }

        return $phone;
    }

    public function handlePhone2($data)
    {
        if (substr($data, 0, 2) != '60') {
            $data = '60' . $data;
        }

        return $data;
    }
}
