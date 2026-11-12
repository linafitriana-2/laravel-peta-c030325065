<?php

namespace App\Observers;

use App\Models\Sekolah;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class SekolahObserver
{
    public function created(Sekolah $sekolah): void
    {
        if (Auth::check()) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity' => 'Menambahkan data sekolah: ' . $sekolah->nama,
            ]);
        }
    }

    public function updated(Sekolah $sekolah): void
    {
        if (Auth::check()) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity' => 'Memperbarui data sekolah: ' . $sekolah->nama,
            ]);
        }
    }

    public function deleted(Sekolah $sekolah): void
    {
        if (Auth::check()) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity' => 'Menghapus data sekolah: ' . $sekolah->nama,
            ]);
        }
    }
}