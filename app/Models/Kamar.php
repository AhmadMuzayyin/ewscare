<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kamar extends Model
{
    protected $guarded = [];

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function santris()
    {
        return $this->hasMany(Santri::class);
    }
}
