<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class chat_thread extends Model
{
    use HasFactory;
    protected $table = "chat_thread";
    protected $primaryKey = "chat_thread_id";
    public $timestamps = false;
}
