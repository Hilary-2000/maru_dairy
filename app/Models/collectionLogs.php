<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class collectionLogs extends Model
{
    use HasFactory;
    protected $primaryKey = 'log_id';
    public $timestamps = false;
    protected $table = 'collection_change_logs';

}
