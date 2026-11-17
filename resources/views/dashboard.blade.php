@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<h1 class="text-2xl font-bold">Selamat datang, {{ $user->name }}</h1>
<p class="text-gray-600 mb-6">Panel Kontrol Utama</p>

@if ($user->email === 'admin@sig.test' || $user->email === 'dinas@sig.test' || (method_exists($user, 'isAdmin') && $user->isAdmin()))
    <div class="bg-white rounded shadow p-6">
        <h2 class="text-lg font-bold text-blue-800 mb-2">Status: Admin Sistem Aktif</h2>
        <p class="text-gray-600">Semua menu pengelolaan backend, peta, dan form input sekolah sudah dapat diakses dengan normal.</p>
    </div>
@elseif (isset($sekolah) && $sekolah)
    <div class="bg-white rounded shadow p-5 max-w-lg">
        <p class="text-xl font-bold">{{ $sekolah->nama }}</p>
        <p class="text-gray-600">NPSN {{ $sekolah->npsn }}</p>
    </div>
@else
    <div class="bg-white rounded shadow p-5">
        <p class="text-lg font-semibold text-blue-700">Sistem Berjalan Normal</p>
        <p class="text-gray-600 text-sm mt-1">Fitur tombol "Isi alamat dari peta" pada form input backend sudah siap digunakan.</p>
    </div>
@endif
@endsection