<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use CrudTrait;

    protected $fillable = [
        'first_name', 'last_name', 'company_name', 'email', 
        'phone', 'address_line_1', 'address_line_2', 'city', 'state', 'postal_code'
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Mutator to display full name in Backpack dropdowns
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name} ({$this->company_name})";
    }
}
