<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use CrudTrait;
    protected $fillable = ['key', 'name', 'value', 'type'];

}
