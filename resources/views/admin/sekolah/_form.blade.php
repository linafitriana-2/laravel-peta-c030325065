@php
$teks = [
    'npsn'      => 'NPSN (8 digit)',
    'nama'      => 'Nama Sekolah',
    'kecamatan' => 'Kecamatan',
    'telepon'   => 'Telepon',
];
$pilihan = [
    'jenjang'   => ['SD', 'SMP', 'SMA', 'SMK'],
    'status'    => ['Negeri', 'Swasta'],
];
$cls = 'w-full border rounded px-3 py-2';
@endphp

<div class="bg-white rounded shadow p-6 grid md:grid-cols-2 gap-6">
    <div class="space-y-4">
        @foreach ($teks as $name => $label)
        <div>
            <label for="{{ $name }}" class="block font-medium">
                {{ $label }}
            </label>
            <input type="text" id="{{ $name }}" name="{{ $name }}"
                value="{{ old($name, $sekolah->$name) }}"
                @readonly($name === 'npsn' && auth()->user()->isSekolah())
                class="{{ $cls }}">
            @error($name)
            <p class="text-red-600 text-sm">{{ $message }}</p>
            @enderror
        </div>
        @endforeach

        @foreach ($pilihan as $name => $opsi)
        <div>
            <label for="{{ $name }}" class="block font-medium">
                {{ ucfirst($name) }}
            </label>
            <select id="{{ $name }}" name="{{ $name }}" class="{{ $cls }}">
                <option value="">-- Pilih --</option>
                @foreach ($opsi as $o)
                <option value="{{ $o }}" @selected(old($name, $sekolah->$name) === $o)>
                    {{ $o }}
                </option>
                @endforeach
            </select>
            @error($name)
            <p class="text-red-600 text-sm">{{ $message }}</p>
            @enderror
        </div>
        @endforeach

        <div>
            <div class="flex items-center justify-between">
                <label for="alamat" class="block font-medium">Alamat</label>
                <button type="button" id="btn-geocoding" class="text-sm text-blue-700 hover:text-blue-900 font-medium flex items-center gap-1">
                    📍 Isi alamat dari peta
                </button>
            </div>
            <textarea id="alamat" name="alamat" rows="3" class="{{ $cls }}">{{ old('alamat', $sekolah->alamat) }}</textarea>
            @error('alamat')
            <p class="text-red-600 text-sm">{{ $message }}</p>
            @enderror
        </div>
        
        <div>
            <label for="logo" class="block font-medium">Logo Sekolah (Opsional)</label>
            <input type="file" id="logo" name="logo" class="{{ $cls }}">
            @error('logo')
            <p class="text-red-600 text-sm">{{ $message }}</p>
            @enderror

            @if(isset($sekolah->logo) && $sekolah->logo)
                <div class="mt-2 flex items-center gap-3">
                    <img src="{{ asset('storage/' . $sekolah->logo) }}" alt="Logo Sekolah" class="w-16 h-16 object-cover rounded border">
                    <span class="text-xs text-gray-500">Logo saat ini</span>
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-2">
            <p class="font-medium">
                Klik peta atau geser marker untuk menentukan lokasi sekolah
            </p>
            
            <button type="button" id="btn-lokasi" class="px-3 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700 shrink-0">
                Gunakan lokasi saya
            </button>
        </div>

        <div id="map-picker" class="h-80 rounded border"></div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="latitude" class="block font-medium">Latitude</label>
                <input type="number" step="any" id="latitude" name="latitude"
                    value="{{ old('latitude', $sekolah->latitude) }}"
                    class="{{ $cls }}">
                @error('latitude')
                <p class="text-red-600 text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="longitude" class="block font-medium">Longitude</label>
                <input type="number" step="any" id="longitude" name="longitude"
                    value="{{ old('longitude', $sekolah->longitude) }}"
                    class="{{ $cls }}">
                @error('longitude')
                <p class="text-red-600 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-blue-700 text-white rounded">
                Simpan
            </button>
            <a href="{{ auth()->user()->isDinas() ? route('dinas.sekolah.index') : route('dashboard') }}" class="px-5 py-2 bg-gray-300 rounded">
                Batal
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
const latInput = document.getElementById('latitude');
const lngInput = document.getElementById('longitude');
const alamatInput = document.getElementById('alamat');
const btnGeocoding = document.getElementById('btn-geocoding');

const sudahAda = latInput.value !== '' && lngInput.value !== '';
const pusat = sudahAda
    ? [parseFloat(latInput.value), parseFloat(lngInput.value)]
    : [-3.3194, 114.5908]; 

const map = L.map('map-picker').setView(pusat, sudahAda ? 16 : 13);

L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

const marker = L.marker(pusat, { draggable: true }).addTo(map);

function isiKoordinat(lat, lng) {
    latInput.value = lat.toFixed(7);
    lngInput.value = lng.toFixed(7);
}

map.on('click', (e) => {
    marker.setLatLng(e.latlng);
    isiKoordinat(e.latlng.lat, e.latlng.lng);
});

marker.on('dragend', () => {
    const p = marker.getLatLng();
    isiKoordinat(p.lat, p.lng);
});

[latInput, lngInput].forEach((el) => {
    el.addEventListener('change', () => {
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);
        if (!isNaN(lat) && !isNaN(lng)) {
            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], 16);
        }
    });
});

document.getElementById('btn-lokasi').addEventListener('click', () => {
    if (!navigator.geolocation) {
        alert('Browser tidak mendukung fitur Geolocation.');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], 16);
            isiKoordinat(lat, lng);
        },
        (err) => {
            alert('Gagal mengambil lokasi: ' + err.message);
        }
    );
});

btnGeocoding.addEventListener('click', async () => {
    const lat = latInput.value.trim();
    const lng = lngInput.value.trim();

    if (!lat || !lng) {
        alert('Silakan tentukan titik lokasi pada peta atau isi Latitude & Longitude terlebih dahulu.');
        return;
    }

    alamatInput.value = 'Sedang mendeteksi alamat dari koordinat...';

    try {
        const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=id`;
        const respon = await fetch(url);
        if (!respon.ok) throw new Error('Gagal menghubungi layanan geocoding');

        const data = await respon.json();

        if (data && data.display_name) {
            alamatInput.value = data.display_name;
        } else {
            alert('Alamat tidak ditemukan untuk koordinat tersebut.');
            alamatInput.value = '';
        }
    } catch (error) {
        console.error(error);
        alert('Terjadi kesalahan saat mengambil data alamat.');
        alamatInput.value = '';
    }
});
</script>
@endpush
