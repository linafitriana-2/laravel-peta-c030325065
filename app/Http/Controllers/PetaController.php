<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use App\Support\Geometri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PetaController extends Controller
{
    public function index()
    {
        return view('peta.index');
    }

    public function cari()
    {
        return view('peta.cari');
    }

    public function klaster()
    {
        return view('peta.klaster');
    }

    public function panas()
    {
        return view('peta.panas');
    }

    public function wilayah()
    {
        return view('peta.wilayah');
    }

    public function rute()
    {
        return view('peta.rute');
    }

    public function alamat()
    {
        return view('peta.alamat');
    }

    public function gabungan()
    {
        return view('peta.gabungan');
    }

    public function radius(Request $request)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'jenjang' => ['nullable', 'in:SD,SMP,SMA,SMK'],
            'status' => ['nullable', 'in:Negeri,Swasta'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];

        $query = DB::table('sekolahs')
            ->select(
                'id',
                'nama',
                'jenjang',
                'status',
                'latitude',
                'longitude'
            )
            ->selectRaw(
                'extensions.ST_Distance(
                    location,
                    extensions.ST_SetSRID(
                        extensions.ST_MakePoint(?, ?),
                        4326
                    )::extensions.geography
                ) AS jarak_meter',
                [$longitude, $latitude]
            )
            ->whereNotNull('location')
            ->whereRaw(
                'extensions.ST_DWithin(
                    location,
                    extensions.ST_SetSRID(
                        extensions.ST_MakePoint(?, ?),
                        4326
                    )::extensions.geography,
                    1000
                )',
                [$longitude, $latitude]
            );

        if (!empty($validated['jenjang'])) {
            $query->where('jenjang', $validated['jenjang']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $sekolahs = $query
            ->orderBy('jarak_meter')
            ->get();

        return response()->json($sekolahs);
    }

    
    public function geojson(Request $request)
    {
        $request->validate([
            'west'  => 'nullable|numeric',
            'south' => 'nullable|numeric',
            'east'  => 'nullable|numeric',
            'north' => 'nullable|numeric',
        ]);

        $params = $request->only(['jenjang', 'status', 'west', 'south', 'east', 'north']);
        ksort($params);
        $cacheKey = 'data_sekolah:' . md5(json_encode($params));

        $geojson = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request) {
            $query = Sekolah::with('profil')
                ->when($request->jenjang, fn ($q, $j) => $q->where('jenjang', $j))
                ->when($request->status, fn ($q, $s) => $q->where('status', $s));

            if (
                $request->has(['west', 'south', 'east', 'north'])
            ) {
                $query
                    ->whereBetween('longitude', [
                        $request->west,
                        $request->east,
                    ])
                    ->whereBetween('latitude', [
                        $request->south,
                        $request->north,
                    ]);
            }

            $features = $query->get()->map(fn ($s) => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$s->longitude, $s->latitude],
                ],
                'properties' => [
                    'id'             => $s->id,
                    'npsn'           => $s->npsn,
                    'nama'           => $s->nama,
                    'jenjang'        => $s->jenjang,
                    'status'         => $s->status,
                    'alamat'         => $s->alamat,
                    'kecamatan'      => $s->kecamatan,
                    'telepon'        => $s->telepon,
                    'logo'           => $s->logo,
                    'kepala_sekolah' => $s->profil?->kepala_sekolah,
                    'akreditasi'     => $s->profil?->akreditasi,
                    'jumlah_siswa'   => $s->profil?->jumlah_siswa,
                    'jumlah_guru'    => $s->profil?->jumlah_guru,
                ],
            ]);

            return [
                'type'     => 'FeatureCollection',
                'features' => $features->values()->all(),
            ];
        });

        return response()->json($geojson);
    }

    
    public function geojsonWilayah(Request $request)
    {
        $features = Sekolah::with('profil')
            ->when($request->jenjang, fn ($q, $j) => $q->where('jenjang', $j))
            ->get()
            ->groupBy('kecamatan')
            ->map(function ($kelompok, $kecamatan) {
                $titik = $kelompok
                    ->map(fn ($s) => [$s->longitude, $s->latitude])
                    ->all();
 
                $hull = Geometri::convexHull($titik);
 

                if (count($hull) < 3) {
                    return null;
                }
 
                $hull[] = $hull[0]; 
 
                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [$hull],
                    ],
                    'properties' => [
                        'kecamatan'      => $kecamatan,
                        'jumlah_sekolah' => $kelompok->count(),
                        'jumlah_siswa'   => (int) $kelompok->sum(
                            fn ($s) => $s->profil?->jumlah_siswa ?? 0
                        ),
                    ],
                ];
            })
            ->filter()
            ->values();
 
        return response()->json([
            'type'     => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}