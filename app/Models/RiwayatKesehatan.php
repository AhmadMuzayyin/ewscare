<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatKesehatan extends Model
{
    protected $guarded = [];

    public function santri()
    {
        return $this->belongsTo(Santri::class);
    }

    public function penyakit()
    {
        return $this->belongsTo(Penyakit::class);
    }

    public function kamar()
    {
        return $this->belongsTo(Kamar::class);
    }

    public function gejalas()
    {
        return $this->belongsToMany(Gejala::class, 'gejala_riwayat_kesehatan', 'riwayat_kesehatan_id', 'gejala_id');
    }
}
