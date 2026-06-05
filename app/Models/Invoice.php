<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use CrudTrait;

    protected $fillable = [
        'invoice_number', 'contact_id', 'date', 'subtotal', 
        'tax_total', 'discount', 'total', 'notes', 'invoice_path'
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
