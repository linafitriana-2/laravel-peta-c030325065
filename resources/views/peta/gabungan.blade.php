@extends('layouts.public')
@section('title', 'Kontrol Lapisan Peta')
 
@push('styles')

<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css">
@endpush
 
@section('content')
<div class="toolbar">
    <span>Gunakan kotak di pojok kanan atas peta untuk menghidupkan atau mematikan lapisan.</span>
    <span id="jumlah"></span>
</div>
 
<div id="peta" class="peta"></div>
@endsection
 
@push('scripts')
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script>
    const map = Peta.buat('peta');
 
    const layerMarker = L.layerGroup();
    const layerKlaster = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 60,
        spiderfyOnMaxZoom: true,
        disableClusteringAtZoom: 17,
        chunkedLoading: true,
    });
    let layerHeat = L.heatLayer([], { radius: 25, blur: 15 });
 
    Peta.ambilSekolah().then((fitur) => {
        fitur.forEach((f) => {
            const marker = L.marker(Peta.latlng(f)).bindPopup(Peta.popup(f));
            layerMarker.addLayer(marker);
 
            const markerKlaster = L.marker(Peta.latlng(f)).bindPopup(Peta.popup(f));
            layerKlaster.addLayer(markerKlaster);
        });

        const titikHeat = fitur.map((f) => [...Peta.latlng(f), 0.5]);
        layerHeat.setLatLngs(titikHeat);
 
        document.getElementById('jumlah').textContent = fitur.length + ' total sekolah';
 
        if (fitur.length > 0) {
            map.fitBounds(L.latLngBounds(fitur.map(Peta.latlng)).pad(0.1));
        }
    });
    layerMarker.addTo(map);
 
    const overlayMaps = {
        "Marker Sekolah": layerMarker,
        "Klaster Marker": layerKlaster,
        "Peta Panas (Heatmap)": layerHeat
    };

    L.control.layers(null, overlayMaps, { collapsed: false }).addTo(map);
</script>
@endpush