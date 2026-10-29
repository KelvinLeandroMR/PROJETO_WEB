<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/lib/security.php';
require_login();
require_once __DIR__ . '/../app/config/config.php';
$title = 'Mapa da ETEC';
$mapsKeyConfigured = GOOGLE_MAPS_API_KEY !== 'COLOQUE_SUA_CHAVE_GOOGLE_MAPS_AQUI' && trim(GOOGLE_MAPS_API_KEY) !== '';
require __DIR__ . '/../app/views/header.php';
?>
<div class="page-head">
    <div>
        <h1>Localização da ETEC</h1>
        <p class="muted">Mapa da sede da ETEC Profª Ilza Nascimento Pintus, em São José dos Campos.</p>
    </div>
</div>
<section class="card map-card">
    <div class="map-address">
        <strong>ETEC Profª Ilza Nascimento Pintus</strong>
        <p>Av. Salmão, 570 - Parque Residencial Aquarius, São José dos Campos - SP, CEP 12246-260</p>
        <a class="btn btn-secondary" href="https://www.google.com/maps/search/?api=1&query=ETEC+Prof%C2%AA+Ilza+Nascimento+Pintus%2C+Av.+Salm%C3%A3o%2C+570%2C+S%C3%A3o+Jos%C3%A9+dos+Campos" target="_blank" rel="noopener noreferrer">Abrir no Google Maps</a>
    </div>
    <?php if ($mapsKeyConfigured): ?>
        <div id="etec-map" class="google-map" role="region" aria-label="Mapa da localização da ETEC"></div>
        <p id="map-error" class="alert alert-warning" hidden>Não foi possível localizar o endereço no mapa. Confira a chave e as APIs habilitadas no Google Cloud Console.</p>
        <script>
            function initEtecMap() {
                const address = 'ETEC Profª Ilza Nascimento Pintus, Avenida Salmão, 570, Parque Residencial Aquarius, São José dos Campos, SP, Brasil';
                const map = new google.maps.Map(document.getElementById('etec-map'), {
                    zoom: 16,
                    center: { lat: -23.21, lng: -45.90 },
                    mapTypeControl: true,
                    streetViewControl: true,
                    fullscreenControl: true
                });
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ address }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        map.setCenter(results[0].geometry.location);
                        new google.maps.Marker({
                            map,
                            position: results[0].geometry.location,
                            title: 'ETEC Profª Ilza Nascimento Pintus'
                        });
                        const info = new google.maps.InfoWindow({
                            content: '<strong>ETEC Profª Ilza Nascimento Pintus</strong><br>Av. Salmão, 570 - São José dos Campos - SP'
                        });
                        info.open({ map, anchor: undefined, shouldFocus: false });
                    } else {
                        document.getElementById('map-error').hidden = false;
                    }
                });
            }
        </script>
        <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= h(GOOGLE_MAPS_API_KEY) ?>&callback=initEtecMap&language=pt-BR&region=BR"></script>
    <?php else: ?>
        <div class="map-key-notice">
            <h2>Configure a chave da API do Google Maps</h2>
            <p>Para exibir o mapa interativo nesta página, adicione sua chave em <code>app/config/config.php</code>, na constante <code>GOOGLE_MAPS_API_KEY</code>, e habilite a <strong>Maps JavaScript API</strong> no Google Cloud Console.</p>
            <p>Depois de salvar a chave, atualize esta página. Enquanto isso, use o botão acima para abrir a localização no Google Maps.</p>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../app/views/footer.php'; ?>
