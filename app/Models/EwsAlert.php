<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EwsAlert extends Model
{
    protected $guarded = [];

    public function kamar()
    {
        return $this->belongsTo(Kamar::class);
    }

    public function penyakit()
    {
        return $this->belongsTo(Penyakit::class);
    }
}
