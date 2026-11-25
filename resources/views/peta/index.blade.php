
@extends('layouts.public')
@section('title', 'Peta Sekolah')

@section('content')
<div class="toolbar">
    <label>Jenjang
        <select id="filter-jenjang">
            <option value="">Semua jenjang</option>
            <option value="SD">SD</option>
            <option value="SMP">SMP</option>
            <option value="SMA">SMA</option>
            <option value="SMK">SMK</option>
        </select>
    </label>

    <label>Status
        <select id="filter-status">
            <option value="">Semua Status</option>
            <option value="Negeri">Negeri</option>
            <option value="Swasta">Swasta</option>
        </select>
    </label>

    <span id="jumlah"></span>

    <button type="button" id="btn-radius">
        Cari Sekolah Radius 1 KM
    </button>

    <button type="button" id="btn-download-geojson">
        ⬇ Unduh GeoJSON
    </button>
</div>

<div id="peta" class="peta"></div>
@endsection

@push('scripts')
<script>
    const map = Peta.buat('peta');
    const lapisan = L.layerGroup().addTo(map);

    let lingkaranRadius = null;

    function urlDataSekolah(pakaiBatasPeta = false) {
        const jenjang = document.getElementById('filter-jenjang').value;
        const status = document.getElementById('filter-status').value;

        const params = new URLSearchParams();

        if (jenjang) params.append('jenjang', jenjang);
        if (status) params.append('status', status);

        if (pakaiBatasPeta) {
            const bounds = map.getBounds();

            params.append('west', bounds.getWest());
            params.append('south', bounds.getSouth());
            params.append('east', bounds.getEast());
            params.append('north', bounds.getNorth());
        }

        return window.PETA_URL.data + '?' + params.toString();
    }

    function hapusLingkaranRadius() {
        if (lingkaranRadius) {
            map.removeLayer(lingkaranRadius);
            lingkaranRadius = null;
        }
    }

    async function muat() {
        try {
            hapusLingkaranRadius();

            const respon = await fetch(urlDataSekolah(true));

            if (!respon.ok) {
                throw new Error('Gagal memuat data sekolah');
            }

            const data = await respon.json();
            const fitur = data.features;

            lapisan.clearLayers();

            fitur.forEach((f) => {
                L.marker(Peta.latlng(f))
                    .bindPopup(Peta.popup(f))
                    .addTo(lapisan);
            });

            document.getElementById('jumlah').textContent =
                fitur.length + ' sekolah ditemukan';

        } catch (error) {
            document.getElementById('jumlah').textContent =
                'Gagal memuat data sekolah';

            console.error(error);
        }
    }

    // Pencarian sekolah dalam radius 1 kilometer menggunakan PostGIS
    async function cariRadius() {
        const tombol = document.getElementById('btn-radius');
        const jumlah = document.getElementById('jumlah');

        const pusat = map.getCenter();

        const jenjang = document.getElementById('filter-jenjang').value;
        const status = document.getElementById('filter-status').value;

        const params = new URLSearchParams();

        params.append('latitude', pusat.lat);
        params.append('longitude', pusat.lng);

        if (jenjang) params.append('jenjang', jenjang);
        if (status) params.append('status', status);

        tombol.disabled = true;
        tombol.textContent = 'Mencari...';

        try {
            const respon = await fetch(
                "{{ route('peta.radius') }}" + '?' + params.toString()
            );

            if (!respon.ok) {
                throw new Error('Gagal melakukan pencarian radius');
            }

            const data = await respon.json();

            lapisan.clearLayers();
            hapusLingkaranRadius();

            lingkaranRadius = L.circle(
                [pusat.lat, pusat.lng],
                {
                    radius: 1000
                }
            ).addTo(map);

            data.forEach((sekolah) => {
                const latitude = Number(sekolah.latitude);
                const longitude = Number(sekolah.longitude);

                const marker = L.marker([latitude, longitude]);

                const popup = `
                    <strong>${sekolah.nama}</strong><br>
                    Jenjang: ${sekolah.jenjang ?? '-'}<br>
                    Status: ${sekolah.status ?? '-'}<br>
                    Jarak: ${Number(sekolah.jarak_meter).toFixed(2)} meter
                `;

                marker.bindPopup(popup);
                marker.addTo(lapisan);
            });

            jumlah.textContent =
                data.length + ' sekolah ditemukan dalam radius 1 km';

        } catch (error) {
            jumlah.textContent = 'Gagal melakukan pencarian radius';

            console.error(error);
            alert('Pencarian radius gagal. Periksa route dan PetaController.');
        } finally {
            tombol.disabled = false;
            tombol.textContent = 'Cari Sekolah Radius 1 KM';
        }
    }

    // Unduh data GeoJSON
    document.getElementById('btn-download-geojson')
        .addEventListener('click', async function () {

        const tombol = this;
        tombol.disabled = true;
        tombol.textContent = 'Mengunduh...';

        try {
            const respon = await fetch(urlDataSekolah());

            if (!respon.ok) {
                throw new Error('Gagal mengunduh data GeoJSON');
            }

            const data = await respon.json();

            const blob = new Blob(
                [JSON.stringify(data, null, 2)],
                { type: 'application/geo+json' }
            );

            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = url;
            link.download = 'data-sekolah.geojson';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            URL.revokeObjectURL(url);

            tombol.textContent = '⬇ Unduh GeoJSON';

        } catch (error) {
            alert('Gagal mengunduh data GeoJSON.');
            tombol.textContent = '⬇ Unduh GeoJSON';

            console.error(error);
        } finally {
            tombol.disabled = false;
        }
    });

    document.getElementById('btn-radius')
        .addEventListener('click', cariRadius);

    document.getElementById('filter-jenjang')
        .addEventListener('change', muat);

    document.getElementById('filter-status')
        .addEventListener('change', muat);

    map.on('moveend', muat);

    muat();
</script>
@endpush
