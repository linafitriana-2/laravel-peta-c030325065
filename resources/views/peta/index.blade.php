
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

    async function muat() {
        try {
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

        } catch (e) {
            document.getElementById('jumlah').textContent =
                'Gagal memuat data';
        }
    }
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

        } finally {
            tombol.disabled = false;
        }
    });

    document.getElementById('filter-jenjang')
        .addEventListener('change', muat);

    document.getElementById('filter-status')
        .addEventListener('change', muat);
    map.on('moveend', muat);
    muat();
</script>
@endpush
