<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Mapa de Geofences<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Leaflet.markercluster CSS (optional, for clustering markers) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

<style>
    #map {
        height: calc(100vh - 250px);
        min-height: 600px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }

    .leaflet-popup-content {
        min-width: 250px;
    }

    .leaflet-popup-content h6 {
        margin-bottom: 0.5rem;
        color: #667eea;
        font-weight: 600;
    }

    .map-legend {
        background: white;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .legend-item {
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        margin-right: 0.5rem;
    }

    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .filter-pills .btn {
        border-radius: 20px;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-map-marked-alt me-2"></i>Mapa de Geofences
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?= base_url('geofences') ?>">Geofences</a></li>
                            <li class="breadcrumb-item active">Mapa</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-primary" id="centerMapBtn">
                        <i class="fas fa-crosshairs me-2"></i>Centralizar
                    </button>
                    <button type="button" class="btn btn-outline-info" id="myLocationBtn">
                        <i class="fas fa-location-arrow me-2"></i>Minha Localização
                    </button>
                    <a href="<?= base_url('geofences') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-list me-2"></i>Ver Lista
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Map Section -->
        <div class="col-lg-9">
            <!-- Filter Pills -->
            <div class="card mb-3">
                <div class="card-body py-2">
                    <div class="filter-pills">
                        <button type="button" class="btn btn-sm btn-primary active" data-filter="all">
                            <i class="fas fa-globe me-1"></i>Todas (<span id="countAll">0</span>)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" data-filter="active">
                            <i class="fas fa-check-circle me-1"></i>Ativas (<span id="countActive">0</span>)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-filter="inactive">
                            <i class="fas fa-times-circle me-1"></i>Inativas (<span id="countInactive">0</span>)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Map Card -->
            <div class="card">
                <div class="card-body p-3">
                    <div id="map"></div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-3">
            <!-- Stats -->
            <div class="stats-card">
                <h5 class="mb-3">
                    <i class="fas fa-chart-pie me-2"></i>Estatísticas
                </h5>
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span>Total de Geofences:</span>
                        <strong id="totalGeofences">0</strong>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span>Ativas:</span>
                        <strong id="activeGeofences">0</strong>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span>Inativas:</span>
                        <strong id="inactiveGeofences">0</strong>
                    </div>
                </div>
                <hr class="my-2 opacity-25">
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span>Raio Médio:</span>
                        <strong id="avgRadius">0</strong> m
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span>Área Total:</span>
                        <strong id="totalArea">0</strong> km²
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Legenda
                    </h6>
                </div>
                <div class="card-body">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #4caf50;"></div>
                        <span>Geofence Ativa</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #9e9e9e;"></div>
                        <span>Geofence Inativa</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #2196f3;"></div>
                        <span>Sua Localização</span>
                    </div>
                    <hr class="my-2">
                    <small class="text-muted">
                        <i class="fas fa-lightbulb me-1"></i>
                        Clique nos círculos para ver detalhes
                    </small>
                </div>
            </div>

            <!-- Geofences List -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-layer-group me-2"></i>Geofences
                    </h6>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <div class="list-group list-group-flush" id="geofencesList">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Leaflet.markercluster JS (optional) -->
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<!-- Geolocator -->
<script src="<?= base_url('assets/js/geolocator.js') ?>"></script>

<script>
    let map, geofencesData = [], circles = [], currentFilter = 'all', userLocationMarker = null;

    // Initialize map
    function initMap() {
        // Default center (São Paulo, Brazil)
        map = L.map('map').setView([-23.550520, -46.633308], 12);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Add scale control
        L.control.scale({ imperial: false, metric: true }).addTo(map);

        // Load geofences
        loadGeofences();
    }

    // Load geofences from API
    function loadGeofences() {
        showLoading();

        fetch('<?= base_url('geofences/json') ?>')
            .then(response => response.json())
            .then(data => {
                hideLoading();

                if (data.success && data.data) {
                    geofencesData = data.data;
                    renderGeofences();
                    updateStats();
                    updateGeofencesList();

                    // Center map to show all geofences
                    if (geofencesData.length > 0) {
                        centerMapToGeofences();
                    }
                } else {
                    alert('Erro ao carregar geofences');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error loading geofences:', error);
                alert('Erro ao carregar geofences');
            });
    }

    // Render geofences on map
    function renderGeofences() {
        // Clear existing circles
        circles.forEach(circle => map.removeLayer(circle));
        circles = [];

        geofencesData.forEach(geofence => {
            // Determine color based on active status
            const color = geofence.active ? '#4caf50' : '#9e9e9e';

            // Create circle
            const circle = L.circle([geofence.latitude, geofence.longitude], {
                color: color,
                fillColor: color,
                fillOpacity: 0.2,
                radius: geofence.radius,
                geofenceId: geofence.id,
                geofenceActive: geofence.active
            });

            // Create popup
            const popupContent = `
                <h6>${geofence.name}</h6>
                <p class="mb-2 small text-muted">${geofence.description || 'Sem descrição'}</p>
                <hr class="my-2">
                <div class="small">
                    <div class="mb-1">
                        <strong>Coordenadas:</strong><br>
                        <span class="font-monospace">${geofence.latitude.toFixed(6)}, ${geofence.longitude.toFixed(6)}</span>
                    </div>
                    <div class="mb-1">
                        <strong>Raio:</strong> ${geofence.radius} metros
                    </div>
                    <div class="mb-2">
                        <strong>Status:</strong>
                        <span class="badge ${geofence.active ? 'bg-success' : 'bg-secondary'}">
                            ${geofence.active ? 'Ativa' : 'Inativa'}
                        </span>
                    </div>
                    <div class="d-grid gap-1">
                        <a href="<?= base_url('geofences') ?>/${geofence.id}/edit" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit me-1"></i>Editar
                        </a>
                        <a href="https://www.google.com/maps?q=${geofence.latitude},${geofence.longitude}"
                           target="_blank"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-external-link-alt me-1"></i>Google Maps
                        </a>
                    </div>
                </div>
            `;

            circle.bindPopup(popupContent);

            // Add to map if passes filter
            if (shouldShowGeofence(geofence)) {
                circle.addTo(map);
            }

            circles.push(circle);
        });
    }

    // Filter logic
    function shouldShowGeofence(geofence) {
        if (currentFilter === 'all') return true;
        if (currentFilter === 'active') return geofence.active;
        if (currentFilter === 'inactive') return !geofence.active;
        return true;
    }

    // Update stats
    function updateStats() {
        const total = geofencesData.length;
        const active = geofencesData.filter(g => g.active).length;
        const inactive = total - active;

        document.getElementById('totalGeofences').textContent = total;
        document.getElementById('activeGeofences').textContent = active;
        document.getElementById('inactiveGeofences').textContent = inactive;

        document.getElementById('countAll').textContent = total;
        document.getElementById('countActive').textContent = active;
        document.getElementById('countInactive').textContent = inactive;

        // Average radius
        const avgRadius = total > 0
            ? geofencesData.reduce((sum, g) => sum + g.radius, 0) / total
            : 0;
        document.getElementById('avgRadius').textContent = Math.round(avgRadius);

        // Total area (sum of all circle areas in km²)
        const totalArea = geofencesData.reduce((sum, g) => {
            const areaM2 = Math.PI * g.radius * g.radius;
            return sum + (areaM2 / 1000000); // Convert to km²
        }, 0);
        document.getElementById('totalArea').textContent = totalArea.toFixed(2);
    }

    // Update geofences list in sidebar
    function updateGeofencesList() {
        const listEl = document.getElementById('geofencesList');
        listEl.innerHTML = '';

        if (geofencesData.length === 0) {
            listEl.innerHTML = '<div class="p-3 text-center text-muted">Nenhuma geofence cadastrada</div>';
            return;
        }

        geofencesData
            .filter(shouldShowGeofence)
            .forEach(geofence => {
                const item = document.createElement('a');
                item.href = '#';
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">${geofence.name}</h6>
                            <small class="text-muted">${geofence.radius}m</small>
                        </div>
                        <span class="badge ${geofence.active ? 'bg-success' : 'bg-secondary'}">
                            ${geofence.active ? 'Ativa' : 'Inativa'}
                        </span>
                    </div>
                `;

                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    map.setView([geofence.latitude, geofence.longitude], 16);

                    // Find and open popup
                    circles.forEach(circle => {
                        if (circle.options.geofenceId === geofence.id) {
                            circle.openPopup();
                        }
                    });
                });

                listEl.appendChild(item);
            });
    }

    // Center map to show all geofences
    function centerMapToGeofences() {
        if (geofencesData.length === 0) return;

        const bounds = L.latLngBounds(
            geofencesData.map(g => [g.latitude, g.longitude])
        );

        map.fitBounds(bounds, { padding: [50, 50] });
    }

    // Show user location
    function showUserLocation() {
        Geolocator.requestLocation(
            function(position) {
                // Remove existing marker
                if (userLocationMarker) {
                    map.removeLayer(userLocationMarker);
                }

                // Add blue marker for user location
                userLocationMarker = L.marker([position.lat, position.lng], {
                    icon: L.divIcon({
                        className: 'user-location-marker',
                        html: '<div style="background-color: #2196f3; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
                        iconSize: [20, 20]
                    })
                }).addTo(map);

                userLocationMarker.bindPopup(`
                    <h6><i class="fas fa-location-arrow me-2"></i>Você está aqui</h6>
                    <p class="small mb-0">
                        Coordenadas: ${position.lat.toFixed(6)}, ${position.lng.toFixed(6)}<br>
                        Precisão: ±${position.accuracy}m
                    </p>
                `).openPopup();

                map.setView([position.lat, position.lng], 15);
            },
            function(error) {
                alert('Erro ao obter localização: ' + error.message);
            }
        );
    }

    // Filter buttons
    document.querySelectorAll('[data-filter]').forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active button
            document.querySelectorAll('[data-filter]').forEach(b => {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-' + (b.dataset.filter === 'active' ? 'success' : b.dataset.filter === 'inactive' ? 'secondary' : 'primary'));
            });

            this.classList.remove('btn-outline-primary', 'btn-outline-success', 'btn-outline-secondary');
            this.classList.add('btn-primary', 'active');

            // Update filter
            currentFilter = this.dataset.filter;

            // Re-render
            renderGeofences();
            updateGeofencesList();
        });
    });

    // Center map button
    document.getElementById('centerMapBtn').addEventListener('click', centerMapToGeofences);

    // My location button
    document.getElementById('myLocationBtn').addEventListener('click', showUserLocation);

    // Initialize on load
    document.addEventListener('DOMContentLoaded', initMap);
</script>
<?= $this->endSection() ?>
