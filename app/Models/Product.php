<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use CrudTrait;

    protected $fillable = ['sku', 'name', 'description', 'price'];

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
