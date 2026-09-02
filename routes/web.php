<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('wilayah', 'pages::wilayah.index')->name('wilayah.index');
    Route::livewire('kamar', 'pages::kamar.index')->name('kamar.index');
    Route::livewire('santri', 'pages::santri.index')->name('santri.index');
    Route::livewire('gejala', 'pages::gejala.index')->name('gejala.index');
    Route::livewire('penyakit', 'pages::penyakit.index')->name('penyakit.index');
    Route::livewire('dataset', 'pages::dataset.index')->name('dataset.index');
    Route::livewire('prediksi', 'pages::evaluasi.index')->name('prediksi.index');
    Route::livewire('laporan', 'pages::laporan.index')->name('laporan.index');
});

require __DIR__.'/settings.php';
