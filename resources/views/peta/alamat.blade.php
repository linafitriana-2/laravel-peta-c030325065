@extends('layouts.public')
@section('title', 'Cek Lokasi & Analisis Jangkauan')
 
@section('content')
<div class="toolbar" style="display: flex; justify-content: space-between; align-items: center;">
    <span>Klik di mana saja pada peta atau gunakan tombol GPS untuk mencari alamat, sekolah terdekat, dan radius 1 km.</span>
    <button id="btn-lokasi" type="button" class="btn" style="background: #1e3a8a; color: #fff; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer;">
        📍 Lokasi Saya
    </button>
</div>
 
<div id="peta" class="peta"></div>
 
<div id="hasil" class="panel"
     style="top: 64px; right: 12px; width: 320px; max-height: 70%;
            overflow: auto; display: none;"></div>
@endsection
 
@push('scripts')
<script>
    const map = Peta.buat('peta');
    const lapisan = L.layerGroup().addTo(map);
    const panel = document.getElementById('hasil');
    let semua = [];
    let terakhir = 0;
    let lingkaranJangkauan = null; 
 
    Peta.ambilSekolah().then((fitur) => { semua = fitur; });
 
    
    function terdekat(titik, n = 3) {
        return semua
            .map((f) => ({ fitur: f, jarak: Peta.jarakKm(titik, Peta.latlng(f)) }))
            .sort((a, b) => a.jarak - b.jarak)
            .slice(0, n);
    }
 
    async function ambilAlamat(lat, lng) {
        const url = 'https://nominatim.openstreetmap.org/reverse'
            + '?format=jsonv2&addressdetails=1&accept-language=id'
            + `&lat=${lat}&lon=${lng}`;
 
        const respon = await fetch(url);
        if (!respon.ok) throw new Error('HTTP ' + respon.status);
 
        return respon.json();
    }

    async function prosesTitik(lat, lng) {
        const titik = [lat, lng];
 
        lapisan.clearLayers();

        if (lingkaranJangkauan) {
            map.removeLayer(lingkaranJangkauan);
        }

        lingkaranJangkauan = L.circle(titik, {
            radius: 1000,          
            color: '#2563eb',      
            weight: 2,
            fillColor: '#3b82f6',  
            fillOpacity: 0.15      
        }).addTo(lapisan);

        L.circleMarker(titik, {
            radius: 7, color: '#fff', weight: 2,
            fillColor: '#ef4444', fillOpacity: 1,
        }).addTo(lapisan);

        const sekolahDalamRadius = semua.filter(f => Peta.jarakKm(titik, Peta.latlng(f)) <= 1.0);
 
        const dekat = terdekat(titik);
        dekat.forEach((d) => {
            L.polyline([titik, Peta.latlng(d.fitur)], {
                color: '#2563eb', weight: 2, dashArray: '6 6',
            }).addTo(lapisan);
        });
 
        panel.style.display = 'block';
        panel.textContent = 'Mencari alamat...';
 
        let bagianAlamat = '<em>Alamat tidak dapat dimuat.</em>';
        try {
            const data = await ambilAlamat(lat, lng);
            const a = data.address || {};
 
            const baris = [
                ['Jalan', a.road],
                ['Kelurahan/Desa', a.suburb || a.neighbourhood || a.village],
                ['Kota/Kabupaten', a.city || a.town || a.municipality || a.county],
                ['Provinsi', a.state],
                ['Kode pos', a.postcode],
            ].map(([k, v]) => `
                <tr>
                    <td style="padding: 2px 8px 2px 0; color: #6b7280;">${k}</td>
                    <td>${Peta.esc(v) || '-'}</td>
                </tr>`).join('');
 
            bagianAlamat = `
                <p style="margin: 6px 0;">${Peta.esc(data.display_name || '-')}</p>
                <table style="font-size: 13px;">${baris}</table>`;
        } catch (err) {
            console.error(err);
        }
 
        const daftar = dekat.map((d) => `
            <li data-npsn="${Peta.esc(d.fitur.properties.npsn)}"
                style="padding: 6px 0; border-top: 1px solid #e5e7eb; cursor: pointer;">
                <strong>${Peta.esc(d.fitur.properties.nama)}</strong><br>
                <small>${d.jarak.toFixed(2)} km (garis lurus)</small>
            </li>`).join('');
 
        panel.innerHTML = `
            <strong>Koordinat</strong>
            <div style="font-family: monospace;">${lat.toFixed(6)}, ${lng.toFixed(6)}</div>
            <strong style="display: block; margin-top: 8px;">Analisis Jangkauan</strong>
            <div style="background: #eff6ff; padding: 6px 8px; border-radius: 4px; margin-top: 4px; color: #1e40af; font-weight: 500;">
                🏫 Jumlah sekolah dalam radius 1 km: <strong>${sekolahDalamRadius.length} sekolah</strong>
            </div>
            <strong style="display: block; margin-top: 8px;">Alamat</strong>
            ${bagianAlamat}
            <strong style="display: block; margin-top: 8px;">3 Sekolah terdekat</strong>
            <ul style="list-style: none; margin: 4px 0 0; padding: 0;">${daftar}</ul>`;
    }

    map.on('click', async (e) => {
        const sekarang = Date.now();
        if (sekarang - terakhir < 1200) return;
        terakhir = sekarang;
 
        const { lat, lng } = e.latlng;
        await prosesTitik(lat, lng);
    });
 
    document.getElementById('btn-lokasi').addEventListener('click', () => {
        if (!navigator.geolocation) {
            alert('Geolocation tidak didukung oleh browser Anda.');
            return;
        }
 
        panel.style.display = 'block';
        panel.textContent = 'Mendeteksi lokasi Anda...';
 
        navigator.geolocation.getCurrentPosition(
            async (posisi) => {
                const lat = posisi.coords.latitude;
                const lng = posisi.coords.longitude;

                map.setView([lat, lng], 16);

                await prosesTitik(lat, lng);
            },
            (error) => {
                alert('Gagal mendeteksi posisi Anda. Pastikan izin akses lokasi (GPS) diaktifkan pada browser.');
                panel.style.display = 'none';
                console.error(error);
            },
            { enableHighAccuracy: true }
        );
    });

    panel.addEventListener('click', (e) => {
        const li = e.target.closest('[data-npsn]');
        if (!li) return;
 
        const f = semua.find((x) => x.properties.npsn === li.dataset.npsn);
        if (f) {
            L.popup().setLatLng(Peta.latlng(f)).setContent(Peta.popup(f)).openOn(map);
            map.flyTo(Peta.latlng(f), 17);
        }
    });
</script>
@endpush
