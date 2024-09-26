<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionType extends Model
{
    use HasFactory;
    protected $table = "deduction_type";
    protected $primaryKey = 'deduction_id';
    public $timestamps = false;
}
