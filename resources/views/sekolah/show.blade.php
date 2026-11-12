<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil {{ $sekolah->nama }}</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen py-10 px-4">
    <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-100">
        
        <!-- Header Banner & Logo -->
        <div class="bg-indigo-600 px-6 py-8 text-white text-center relative">
            <a href="{{ route('peta.index') }}" class="absolute top-4 left-4 bg-white/20 hover:bg-white/30 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition">
                &larr; Kembali ke Peta
            </a>
            
            <div class="mt-4 flex justify-center">
                @if($sekolah->logo)
                    <img src="{{ asset('storage/' . $sekolah->logo) }}" alt="Logo Sekolah" class="w-24 h-24 object-cover rounded-full border-4 border-white shadow-md bg-white">
                @else
                    <div class="w-24 h-24 rounded-full border-4 border-white shadow-md bg-slate-200 flex items-center justify-center text-slate-500 font-bold text-xl">
                        {{ substr($sekolah->nama, 0, 2) }}
                    </div>
                @endif
            </div>

            <h1 class="text-2xl font-bold mt-4">{{ $sekolah->nama }}</h1>
            <p class="text-indigo-200 text-sm mt-1">NPSN: {{ $sekolah->npsn }} | <span class="uppercase font-semibold">{{ $sekolah->jenjang }}</span> ({{ $sekolah->status }})</p>
        </div>

        <!-- Detail Informasi -->
        <div class="p-8 space-y-6">
            <!-- Informasi Umum -->
            <div>
                <h2 class="text-lg font-bold text-slate-800 border-b pb-2 mb-4">Informasi Lokasi & Kontak</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-slate-600">
                    <div>
                        <span class="font-semibold text-slate-700 block">Alamat:</span>
                        {{ $sekolah->alamat }}, Kec. {{ $sekolah->kecamatan }}
                    </div>
                    <div>
                        <span class="font-semibold text-slate-700 block">Telepon:</span>
                        {{ $sekolah->telepon ?? '-' }}
                    </div>
                    <div>
                        <span class="font-semibold text-slate-700 block">Titik Koordinat:</span>
                        Latitude: {{ $sekolah->latitude }}, Longitude: {{ $sekolah->longitude }}
                    </div>
                </div>
            </div>

            <!-- Informasi Profil Tambahan -->
            <div>
                <h2 class="text-lg font-bold text-slate-800 border-b pb-2 mb-4">Profil & Statistik Sekolah</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-slate-50 p-4 rounded-xl border text-center">
                        <span class="text-xs text-slate-500 block">Kepala Sekolah</span>
                        <span class="font-bold text-slate-800 text-sm mt-1 block">{{ $sekolah->profil?->kepala_sekolah ?? '-' }}</span>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-xl border text-center">
                        <span class="text-xs text-slate-500 block">Akreditasi</span>
                        <span class="font-bold text-indigo-600 text-lg mt-1 block">{{ $sekolah->profil?->akreditasi ?? '-' }}</span>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-xl border text-center">
                        <span class="text-xs text-slate-500 block">Jumlah Siswa</span>
                        <span class="font-bold text-slate-800 text-lg mt-1 block">{{ $sekolah->profil?->jumlah_siswa ?? '-' }}</span>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-xl border text-center">
                        <span class="text-xs text-slate-500 block">Jumlah Guru</span>
                        <span class="font-bold text-slate-800 text-lg mt-1 block">{{ $sekolah->profil?->jumlah_guru ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-slate-100 px-8 py-4 text-center text-xs text-slate-400">
            Web GIS Sistem Informasi Geografis Sekolah &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>