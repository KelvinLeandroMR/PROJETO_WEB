<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/lib/security.php';
require_login();
$title = 'Mapa dos Países';
require __DIR__ . '/../app/views/header.php';
?>
<div class="page-head">
    <div>
        <h1>Mapa dos países</h1>
        <p class="muted">Países, capitais e informações geográficas carregados pela API REST Countries.</p>
    </div>
</div>
<section class="card countries-map-card">
    <div class="countries-map-toolbar">
        <div class="countries-map-intro">
            <strong>Explorar o mundo</strong>
            <p class="muted">Selecione um marcador no mapa ou pesquise um país para localizar e consultar seus dados.</p>
        </div>
        <div class="countries-map-search">
            <label for="country-search">Pesquisar país</label>
            <input id="country-search" type="search" placeholder="Ex.: Brasil, Japão, Canadá..." autocomplete="off">
            <div id="country-suggestions" class="country-suggestions" hidden></div>
        </div>
    </div>
    <div id="countries-map" class="countries-map" role="region" aria-label="Mapa interativo dos países do mundo"></div>
    <div class="countries-map-footer">
        <span id="countries-status" class="muted" role="status">Carregando países da API REST Countries...</span>
        <span class="muted">Fonte dos dados: <a href="https://restcountries.com/" target="_blank" rel="noopener noreferrer">REST Countries</a> · Mapa: <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a></span>
    </div>
    <article id="country-details" class="country-details" aria-live="polite">
        <p class="muted">Clique em um marcador para ver os detalhes do país.</p>
    </article>
</section>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(() => {
    const apiUrl = 'https://restcountries.com/v3.1/all?fields=name,cca3,capital,region,subregion,population,latlng,flags';
    const mapElement = document.getElementById('countries-map');
    const statusElement = document.getElementById('countries-status');
    const searchInput = document.getElementById('country-search');
    const suggestions = document.getElementById('country-suggestions');
    const details = document.getElementById('country-details');

    const map = L.map(mapElement).setView([15, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    const markers = L.featureGroup().addTo(map);
    let countries = [];

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[char]);
    const formatNumber = value => new Intl.NumberFormat('pt-BR').format(value || 0);
    const countryName = country => country.name?.common || 'País sem nome';

    function showCountry(country) {
        const capital = Array.isArray(country.capital) ? country.capital.join(', ') : 'Não informada';
        const flag = country.flags?.svg || country.flags?.png || '';
        details.innerHTML = `
            <div class="country-details-inner">
                ${flag ? `<img class="country-flag" src="${escapeHtml(flag)}" alt="Bandeira de ${escapeHtml(countryName(country))}">` : ''}
                <div>
                    <h2>${escapeHtml(countryName(country))}</h2>
                    <p><strong>Capital:</strong> ${escapeHtml(capital)}</p>
                    <p><strong>Continente:</strong> ${escapeHtml(country.region || 'Não informado')}</p>
                    <p><strong>Sub-região:</strong> ${escapeHtml(country.subregion || 'Não informada')}</p>
                    <p><strong>População:</strong> ${formatNumber(country.population)}</p>
                    <p><strong>Código do país:</strong> ${escapeHtml(country.cca3 || 'N/A')}</p>
                </div>
            </div>`;
    }

    function focusCountry(country) {
        if (!country || !Array.isArray(country.latlng) || country.latlng.length < 2) return;
        const [lat, lng] = country.latlng;
        map.setView([lat, lng], 5);
        const marker = markers.getLayers().find(layer => layer.options.countryCode === country.cca3);
        if (marker) marker.openPopup();
        showCountry(country);
        suggestions.hidden = true;
    }

    function renderSuggestions(query) {
        const normalized = query.trim().toLocaleLowerCase('pt-BR');
        if (!normalized) {
            suggestions.hidden = true;
            suggestions.innerHTML = '';
            return;
        }
        const matches = countries.filter(country => countryName(country).toLocaleLowerCase('pt-BR').includes(normalized)).slice(0, 8);
        suggestions.innerHTML = matches.map(country =>
            `<button type="button" class="country-suggestion" data-code="${escapeHtml(country.cca3)}">${escapeHtml(countryName(country))}</button>`
        ).join('');
        suggestions.hidden = matches.length === 0;
    }

    searchInput.addEventListener('input', () => renderSuggestions(searchInput.value));
    suggestions.addEventListener('click', event => {
        const button = event.target.closest('[data-code]');
        if (!button) return;
        const country = countries.find(item => item.cca3 === button.dataset.code);
        if (country) {
            searchInput.value = countryName(country);
            focusCountry(country);
        }
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('.countries-map-search')) suggestions.hidden = true;
    });

    fetch(apiUrl)
        .then(response => {
            if (!response.ok) throw new Error(`A API respondeu com status ${response.status}.`);
            return response.json();
        })
        .then(data => {
            countries = data.filter(country => Array.isArray(country.latlng) && country.latlng.length >= 2)
                .sort((a, b) => countryName(a).localeCompare(countryName(b), 'pt-BR'));

            countries.forEach(country => {
                const [lat, lng] = country.latlng;
                const marker = L.marker([lat, lng], { title: countryName(country) });
                marker.options.countryCode = country.cca3;
                marker.bindPopup(`<strong>${escapeHtml(countryName(country))}</strong><br>${escapeHtml(Array.isArray(country.capital) ? country.capital.join(', ') : 'Capital não informada')}`);
                marker.on('click', () => showCountry(country));
                marker.addTo(markers);
            });
            statusElement.textContent = `${countries.length} países carregados com coordenadas pela API REST Countries.`;
        })
        .catch(error => {
            console.error(error);
            statusElement.textContent = 'Não foi possível carregar os países. Verifique sua conexão e tente atualizar a página.';
            details.innerHTML = '<div class="alert alert-warning">Falha ao consultar a API REST Countries. Confira a conexão com a internet e tente novamente.</div>';
        });
})();
</script>
<?php require __DIR__ . '/../app/views/footer.php'; ?>
