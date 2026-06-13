<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Santri extends Model
{
    protected $guarded = [];

    public function kamar()
    {
        return $this->belongsTo(Kamar::class);
    }
}
