<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColumnPreference extends Model
{
    protected $table = 'column_prefences';

    protected $fillable = ['admin_id', 'table_name', 'column_order', 'hidden_columns'];
}
