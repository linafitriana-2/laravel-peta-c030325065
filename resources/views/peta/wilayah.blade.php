@extends('layouts.public')
@section('title', 'Wilayah Batas Resmi')
 
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
    <span id="jumlah"></span>
</div>
 
<div id="peta" class="peta"></div>
 
<div class="panel" style="right: 12px; bottom: 24px; font-size: 13px;">
    <strong>Jumlah Sekolah per Kecamatan</strong>
    <div><span style="color: #dc2626;">&#9632;</span> Terbanyak</div>
    <div><span style="color: #ea580c;">&#9632;</span> Banyak</div>
    <div><span style="color: #f59e0b;">&#9632;</span> Sedang</div>
    <div><span style="color: #3b82f6;">&#9632;</span> Sedikit</div>
    <small style="color: #6b7280;">Batas Wilayah Resmi + Tematik</small>
</div>
@endsection
 
@push('scripts')
<script>
    const map = Peta.buat('peta');
    let lapisanBatas = null;
 
    // Fungsi untuk menentukan warna poligon berdasarkan rasio jumlah sekolah
    function warna(rasio) {
        if (rasio >= 0.8) return '#dc2626';
        if (rasio >= 0.6) return '#ea580c';
        if (rasio >= 0.4) return '#f59e0b';
        return '#3b82f6';
    }
 
    async function muat() {
        const jenjang = document.getElementById('filter-jenjang').value;
 
        try {
            // 1. Ambil data sekolah dari endpoint dengan filter jenjang (jika dipilih)
            let urlSekolah = window.PETA_URL.data;
            if (jenjang) urlSekolah += `?jenjang=${encodeURIComponent(jenjang)}`;
            
            const resSekolah = await fetch(urlSekolah);
            const dataSekolah = await resSekolah.json();
            const semuaSekolah = dataSekolah.features;
 
            // 2. Hitung jumlah sekolah & siswa per kecamatan secara dinamis dari data aktif
            const statistikKecamatan = {};
            semuaSekolah.forEach(f => {
                const kec = f.properties.kecamatan;
                if (!kec) return;
                if (!statistikKecamatan[kec]) {
                    statistikKecamatan[kec] = { jumlah_sekolah: 0, jumlah_siswa: 0 };
                }
                statistikKecamatan[kec].jumlah_sekolah += 1;
                statistikKecamatan[kec].jumlah_siswa += (f.properties.jumlah_siswa || 0);
            });
 
            // 3. Ambil berkas GeoJSON batas wilayah resmi dari folder public
            const resBatas = await fetch('/batas-kecamatan.json');
            if (!resBatas.ok) throw new Error('Gagal memuat file GeoJSON batas kecamatan');
            const dataBatas = await resBatas.json();
 
            // Hapus lapisan lama jika peta dimuat ulang
            if (lapisanBatas) {
                map.removeLayer(lapisanBatas);
            }
 
            // Cari jumlah sekolah terbanyak untuk pembagian skala warna
            const jumlahArray = Object.values(statistikKecamatan).map(s => s.jumlah_sekolah);
            const maksSekolah = Math.max(1, ...jumlahArray);
 
            // 4. Render poligon batas wilayah ke peta menggunakan L.geoJSON()
            lapisanBatas = L.geoJSON(dataBatas, {
                style: (feature) => {
                    const namaKec = feature.properties.kecamatan || feature.properties.NAMOBJ;
                    const stats = statistikKecamatan[namaKec] || { jumlah_sekolah: 0 };
                    const rasio = stats.jumlah_sekolah / maksSekolah;
                    const w = warna(rasio);
 
                    return {
                        color: '#1e3a8a',
                        weight: 2,
                        fillColor: w,
                        fillOpacity: 0.4
                    };
                },
                onEachFeature: (feature, layer) => {
                    const namaKec = feature.properties.kecamatan || feature.properties.NAMOBJ;
                    const stats = statistikKecamatan[namaKec] || { jumlah_sekolah: 0, jumlah_siswa: 0 };
 
                    layer.bindTooltip(Peta.esc(namaKec), { sticky: true });
                    layer.bindPopup(`
                        <strong>Kecamatan ${Peta.esc(namaKec)}</strong><br>
                        Jumlah sekolah: ${stats.jumlah_sekolah}<br>
                        Jumlah siswa: ${stats.jumlah_siswa}
                    `);
                }
            }).addTo(map);
 
            document.getElementById('jumlah').textContent = dataBatas.features.length + ' wilayah resmi';
 
            if (dataBatas.features.length > 0) {
                map.fitBounds(lapisanBatas.getBounds().pad(0.1));
            }
        } catch (e) {
            console.error(e);
            document.getElementById('jumlah').textContent = 'Gagal memuat batas wilayah';
        }
    }
 
    // Jalankan ulang fungsi muat saat filter jenjang diubah
    document.getElementById('filter-jenjang').addEventListener('change', muat);
    
    muat();
</script>
@endpush
