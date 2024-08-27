<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MilkPrice extends Model
{
    use HasFactory;
    protected $table = "milk_prices";
    protected $primaryKey = 'price_id';
    public $timestamps = false;
}
