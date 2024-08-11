<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $table = "milk_collections";
    protected $primary_key = "collection_id";
    use HasFactory;
}
