<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SekolahController extends Controller
{
    public function index(Request $request)
    {
       $sekolahs = Sekolah::query()
            ->when($request->q, function ($query, $q) {
                $query->where(function ($w) use ($q) {
                    $w->whereLike('nama', "%{$q}%")
                    ->orWhereLike('npsn', "%{$q}%")
                    ->orWhereLike('kecamatan', "%{$q}%");
                });
            })
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();


        return view('admin.sekolah.index', compact('sekolahs'));
    }

    public function create()
    {
        return view('admin.sekolah.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('sekolah-logos', 'public');
        }

        Sekolah::create($data);
        
        return redirect()->route('dinas.sekolah.index')
            ->with('success', 'Data sekolah berhasil ditambahkan.');
    }

    public function edit(Sekolah $sekolah)
    {
        Gate::authorize('update', $sekolah);

        return view('admin.sekolah.edit', compact('sekolah'));
    }

    public function update(Request $request, Sekolah $sekolah)
    {
        Gate::authorize('update', $sekolah);

        $data = $request->validate($this->rules($sekolah->id));

        $maxDistance = 100; 

        $distance = $this->calculateDistance(
            $sekolah->latitude, $sekolah->longitude, 
            $request->latitude, $request->longitude
        );

        if ($distance > $maxDistance) {
            return back()->withInput()->withErrors([
                'latitude' => "Titik koordinat terlalu jauh dari lokasi awal! Maksimal pergeseran adalah {$maxDistance} meter (jarak saat ini: " . round($distance, 2) . " meter)."
            ]);
        }

        if ($request->user()->isSekolah()) {
            unset($data['npsn']);
        }

        if ($request->hasFile('logo')) {
            if ($sekolah->logo && Storage::disk('public')->exists($sekolah->logo)) {
                Storage::disk('public')->delete($sekolah->logo);
            }
            $data['logo'] = $request->file('logo')->store('sekolah-logos', 'public');
        }

        $sekolah->update($data);

        return $this->kembali($request)
            ->with('success', 'Data sekolah berhasil diperbarui.');
    }

    public function destroy(Sekolah $sekolah)
    {
        if ($sekolah->logo && Storage::disk('public')->exists($sekolah->logo)) {
            Storage::disk('public')->delete($sekolah->logo);
        }

        $sekolah->delete();
    
        return redirect()->route('dinas.sekolah.index')
            ->with('success', 'Data sekolah berhasil dihapus.');
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; 

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a)); // Sudah diperbaiki

        return $earthRadius * $c; 
    }

    private function kembali(Request $request)
    {
        return $request->user()->isDinas()
            ? redirect()->route('dinas.sekolah.index')
            : redirect()->route('dashboard');
    }

    private function rules(?int $id = null): array
    {
        return [
            'npsn' => ['required', 'digits:8', Rule::unique('sekolahs', 'npsn')->ignore($id)],
            'nama' => ['required', 'string', 'max:255'],
            'jenjang' => ['required', Rule::in(['SD', 'SMP', 'SMA', 'SMK'])],
            'status' => ['required', Rule::in(['Negeri', 'Swasta'])],
            'alamat' => ['required', 'string'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}