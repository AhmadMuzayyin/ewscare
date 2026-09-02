<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    protected $guarded = [];

    public function kamars()
    {
        return $this->hasMany(Kamar::class);
    }
}
