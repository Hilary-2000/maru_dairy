<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class chat extends Model
{
    use HasFactory;
    protected $table = "chat";
    protected $primaryKey = "chat_id";
    public $timestamps = false;
}
