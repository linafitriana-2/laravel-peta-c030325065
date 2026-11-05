<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Peta Sekolah</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
<style>
html, body { height: 100%; margin: 0; font-family: system-ui, sans-serif; }
header { height: 56px; box-sizing: border-box; padding: 0 16px;
display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
background: #1e3a8a; color: #fff; }
header h1 { font-size: 18px; margin: 0; }
header select, header input, header button { padding: 4px 8px; border-radius: 4px; border: none; font-size: 14px; }
header button { background: #3b82f6; color: #fff; cursor: pointer; }
header button:hover { background: #2563eb; }
header a { color: #bfdbfe; margin-left: auto; text-decoration: none; }
#map { height: 90vh; width: 100%; }
</style>
</head>
<body>
<header>
    <h1>Peta Sekolah</h1>
    
 
    <select id="filter-jenjang">
        <option value="">Semua jenjang</option>
        <option value="SD">SD</option>
        <option value="SMP">SMP</option>
        <option value="SMA">SMA</option>
        <option value="SMK">SMK</option>
    </select>


    <select id="filter-status">
        <option value="">Semua status</option>
        <option value="Negeri">Negeri</option>
        <option value="Swasta">Swasta</option>
    </select>


    <input type="text" id="cari-nama" placeholder="Cari nama sekolah...">
    <button type="button" id="btn-cari">Cari</button>

    <span id="jumlah"></span>
    <a href="{{ route('admin.sekolah.index') }}">Kelola Data</a>
</header>

<div id="map"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>

const osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
});

const satelit = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19,
    attribution: 'Tiles &copy; Esri'
});

const jalanEsri = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19,
    attribution: 'Tiles &copy; Esri'
});

const map = L.map('map', {
    center: [-3.3194, 114.5908],
    zoom: 12,
    layers: [osm]
});

const petaDasar = {
    'OpenStreetMap': osm,
    'Esri Street Map': jalanEsri,
    'Esri Satelit': satelit
};

const cluster = L.markerClusterGroup();
map.addLayer(cluster);

L.control.layers(petaDasar, { 'Lokasi Sekolah': cluster }).addTo(map);

function esc(teks) {
    const d = document.createElement('div');
    d.textContent = teks ?? '';
    return d.innerHTML;
}


function ikonJenjang(jenjang) {
    const warnaMap = {
        'SD': 'red',      
        'SMP': 'blue',   
        'SMA': 'green',   
        'SMK': 'orange'
    };
    const warna = warnaMap[jenjang] || 'violet';

    return L.icon({
        iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${warna}.png`,
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
}

let daftarMarker = [];

const lapisan = L.geoJSON(null, {
    pointToLayer: (fitur, latlng) => {
        return L.marker(latlng, {
            icon: ikonJenjang(fitur.properties.jenjang)
        });
    },
    onEachFeature: (fitur, layer) => {
        const p = fitur.properties;
        const [lng, lat] = fitur.geometry.coordinates;
       layer.bindPopup(`
            <strong>${esc(p.nama)}</strong><br>
            NPSN: ${esc(p.npsn)}<br>
            ${esc(p.jenjang)} - ${esc(p.status)}<br>
            ${esc(p.alamat)}, ${esc(p.kecamatan)}<br>
            Telp: ${esc(p.telepon) || '-'}<br>
            <small>Koordinat: ${lat}, ${lng}</small>
        `);
        daftarMarker.push({ nama: (p.nama || '').toLowerCase(), layer: layer });
    }
});

async function muatData() {
    const jenjang = document.getElementById('filter-jenjang').value;
    const status = document.getElementById('filter-status').value;

    const url = `{{ route('peta.data') }}?jenjang=${encodeURIComponent(jenjang)}&status=${encodeURIComponent(status)}`;
    const respon = await fetch(url);
    const data = await respon.json();

    daftarMarker = [];
    lapisan.clearLayers();
    cluster.clearLayers();

    lapisan.addData(data);
    cluster.addLayer(lapisan);

    document.getElementById('jumlah').textContent = data.features.length + ' sekolah';

    if (data.features.length > 0) {
        map.fitBounds(cluster.getBounds().pad(0.1));
    }
}

document.getElementById('filter-jenjang').addEventListener('change', muatData);
document.getElementById('filter-status').addEventListener('change', muatData);

function cariSekolah() {
    const kataKunci = document.getElementById('cari-nama').value.trim().toLowerCase();
    if (!kataKunci) return;

    const hasil = daftarMarker.find(item => item.nama.includes(kataKunci));
    if (hasil) {
        cluster.zoomToShowLayer(hasil.layer, () => {
            map.setView(hasil.layer.getLatLng(), 17);
            hasil.layer.openPopup();
        });
    } else {
        alert('Sekolah tidak ditemukan!');
    }
}

document.getElementById('btn-cari').addEventListener('click', cariSekolah);
document.getElementById('cari-nama').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') cariSekolah();
});

muatData();
</script>
</body>
</html>