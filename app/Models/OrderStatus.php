<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    protected $fillable = ['name', 'status', 'sort'];

    public function logs()
    {
        return $this->hasMany(OrderLog::class, 'order_status_id');
    }
}
