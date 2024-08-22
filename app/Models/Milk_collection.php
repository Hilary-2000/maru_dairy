<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Milk_collection extends Model
{
    use HasFactory;
    protected $primaryKey = 'collection_id';
    public $timestamps = false;
}
