<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    use HasFactory;
    // Specify the primary key column name
    protected $primaryKey = 'credential_id';
    public $timestamps = false;
}
