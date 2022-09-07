<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Airplane extends Model
{
    public function airline()
    {
        return $this->belongsTo(Airline::class);
    }
}
