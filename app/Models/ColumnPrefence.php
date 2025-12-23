<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColumnPrefence extends Model
{
    protected $fillable = ['admin_id', 'table_name', 'column_order', 'hidden_columns'];
}
