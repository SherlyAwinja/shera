<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    public static function colors()
    {
        return Color::where('status', 1)->get();
    }
}
