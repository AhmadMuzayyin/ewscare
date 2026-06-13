<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatasetTraining extends Model
{
    protected $guarded = [];

    public function penyakit()
    {
        return $this->belongsTo(Penyakit::class);
    }

    public function gejalas()
    {
        return $this->belongsToMany(Gejala::class, 'dataset_training_gejala');
    }
}
