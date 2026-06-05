<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use CrudTrait;

    protected $fillable = [
        'invoice_id', 'product_id', 'description', 'quantity', 'unit_price', 'subtotal', 'total'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
