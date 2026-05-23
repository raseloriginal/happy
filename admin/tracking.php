<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$pageTitle = 'DSR Real-time Tracking';
include __DIR__ . '/../includes/header.php';
?>
<!-- Leaflet Map CSS/JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
  .dsr-card {
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
  }
  .dsr-card.online {
    border-left-color: #10b981;
  }
  .dsr-card.offline {
    border-left-color: #64748b;
  }
  .dsr-card.absent {
    border-left-color: #ef4444;
  }
  #map {
    height: 600px;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
    z-index: 10;
  }
  /* Custom marker pulse effect */
  .pulse-online {
    position: relative;
  }
  .pulse-online::after {
    content: '';
    position: absolute;
    width: 12px;
    height: 12px;
    background: #10b981;
    border: 2px solid white;
    border-radius: 50%;
    top: -2px;
    right: -2px;
    box-shadow: 0 0 8px #10b981;
    animation: marker-pulse 1.5s infinite;
  }
  @keyframes marker-pulse {
    0% { transform: scale(0.9); opacity: 1; }
    50% { transform: scale(1.3); opacity: 0.5; }
    100% { transform: scale(0.9); opacity: 1; }
  }
</style>

<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">
      
      <!-- Page Header -->
      <div class="flex flex-wrap items-center justify-between mb-6 gap-3">
        <div>
          <h2 class="text-xl font-bold text-gray-800"><i class="fa-solid fa-map-location-dot text-indigo-500 mr-2"></i>DSR Real-time Tracking (All Warehouses)</h2>
          <p class="text-sm text-gray-500">Live locations of all active Delivery Sales Representatives in the system</p>
        </div>
        <div class="flex items-center gap-2 bg-white px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-medium text-gray-500">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
          </span>
          Auto-refreshing in <span id="countdown" class="font-bold font-mono">20</span>s
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Side: DSR List -->
        <div class="lg:col-span-1 flex flex-col gap-4">
          <div class="bg-white p-4 border border-gray-100 rounded-xl shadow-sm">
            <div class="relative">
              <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
              <input type="text" id="dsrSearch" placeholder="Search DSR by name or warehouse..." class="w-full bg-gray-50 border border-gray-200 py-2 pl-9 pr-4 rounded-lg text-sm focus:outline-none focus:border-indigo-500 focus:bg-white transition-all" oninput="filterDsrList()" />
            </div>
          </div>

          <div id="dsrListContainer" class="flex flex-col gap-3 max-h-[480px] overflow-y-auto pr-1">
            <div class="text-center py-8 text-gray-400">Loading reps…</div>
          </div>
        </div>

        <!-- Right Side: Leaflet Map -->
        <div class="lg:col-span-2">
          <div class="bg-white p-2 border border-gray-100 rounded-xl shadow-sm">
            <div id="map"></div>
          </div>
        </div>

      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
const API = '<?= rootPath() ?>/api/tracking.php';
let map;
let markerGroup;
let dsrData = [];
let countdownInterval;
let countdownSec = 20;

// Initialize Map
window.addEventListener('DOMContentLoaded', () => {
  map = L.map('map').setView([23.6850, 90.3563], 7);
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
  }).addTo(map);

  markerGroup = L.featureGroup().addTo(map);

  loadTrackingData();
  startCountdown();
});

// Load Tracking Data
async function loadTrackingData() {
  try {
    const res = await fetch(API);
    const result = await res.json();
    if (result.success) {
      dsrData = result.data || [];
      renderDsrList();
      updateMapMarkers();
    }
  } catch (err) {
    console.error('Failed to load tracking data:', err);
  }
}

// Start Countdown Timer for Polling
function startCountdown() {
  clearInterval(countdownInterval);
  countdownSec = 20;
  document.getElementById('countdown').textContent = countdownSec;
  
  countdownInterval = setInterval(() => {
    countdownSec--;
    document.getElementById('countdown').textContent = countdownSec;
    if (countdownSec <= 0) {
      countdownSec = 20;
      loadTrackingData();
    }
  }, 1000);
}

// Render DSR Cards on Sidebar
function renderDsrList() {
  const container = document.getElementById('dsrListContainer');
  const searchVal = document.getElementById('dsrSearch').value.toLowerCase().trim();
  
  const filtered = dsrData.filter(d => 
    d.name.toLowerCase().includes(searchVal) || 
    d.warehouse_name.toLowerCase().includes(searchVal)
  );

  if (filtered.length === 0) {
    container.innerHTML = `
      <div class="bg-white p-8 text-center text-gray-400 border border-gray-100 rounded-xl shadow-sm">
        <i class="fa-solid fa-user-slash text-3xl mb-2 opacity-40"></i>
        <p class="text-sm">No DSRs found</p>
      </div>`;
    return;
  }

  container.innerHTML = filtered.map(d => {
    let statusClass = 'offline';
    let statusBadge = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 uppercase">Offline</span>';
    
    if (d.attendance_status === 'absent' || !d.checkin_time) {
      statusClass = 'absent';
      statusBadge = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-700 uppercase">Not Checked In</span>';
    } else if (d.is_online) {
      statusClass = 'online';
      statusBadge = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 uppercase font-mono">Active Now</span>';
    } else {
      statusClass = 'offline';
      statusBadge = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 uppercase">Idle</span>';
    }

    const hasLoc = d.latitude !== null && d.longitude !== null;
    const locationBtn = hasLoc 
      ? `<button onclick="locateDsr(${d.dsr_id})" class="btn btn-ghost btn-xs text-indigo-600 hover:bg-indigo-50 font-bold border border-indigo-200 mt-2 flex items-center gap-1 justify-center py-1.5 w-full"><i class="fa-solid fa-crosshairs"></i> Locate DSR</button>`
      : `<span class="text-[10px] text-gray-400 mt-2 block italic text-center border border-dashed border-gray-150 py-1.5"><i class="fa-solid fa-location-slash mr-1"></i> No Location Logs</span>`;

    const lastSeenText = hasLoc 
      ? `<span class="text-xs font-mono font-bold text-gray-600">${d.last_seen}</span>`
      : `<span class="text-xs text-gray-400">Never</span>`;

    return `
      <div class="bg-white p-4 border border-gray-150 rounded-xl shadow-sm dsr-card ${statusClass} flex flex-col gap-2 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
          <div>
            <h4 class="font-bold text-gray-800 text-sm">${esc(d.name)}</h4>
            <p class="text-[10px] text-indigo-600 font-bold mt-0.5 flex items-center gap-1"><i class="fa-solid fa-warehouse text-[8px]"></i> ${esc(d.warehouse_name)}</p>
            <p class="text-[11px] text-gray-500 font-mono mt-0.5"><i class="fa-solid fa-phone mr-1"></i>${esc(d.phone)}</p>
          </div>
          ${statusBadge}
        </div>
        
        <div class="border-t border-gray-100 pt-2 grid grid-cols-2 gap-2 text-[10px] text-gray-500 uppercase tracking-wide">
          <div>
            <span class="block text-gray-400">Check-in</span>
            <span class="font-bold text-gray-700 font-mono">${d.checkin_time || '—'}</span>
          </div>
          <div class="text-right">
            <span class="block text-gray-400">Last Seen</span>
            ${lastSeenText}
          </div>
        </div>
        
        ${locationBtn}
      </div>`;
  }).join('');
}

// Filter DSR Sidebar list
function filterDsrList() {
  renderDsrList();
}

// Update Markers on Leaflet Map
function updateMapMarkers() {
  markerGroup.clearLayers();
  let hasBounds = false;

  dsrData.forEach(d => {
    if (d.latitude !== null && d.longitude !== null) {
      let iconColor = '#64748b'; // default offline
      if (d.is_online) iconColor = '#10b981'; // active
      else if (d.attendance_status === 'absent' || !d.checkin_time) iconColor = '#ef4444'; // absent/not checked-in

      const customIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `<div style="background-color:${iconColor}; width:16px; height:16px; border:2px solid white; border-radius:50%; box-shadow:0 0 6px rgba(0,0,0,0.3);" class="${d.is_online ? 'pulse-online' : ''}"></div>`,
        iconSize: [16, 16],
        iconAnchor: [8, 8]
      });

      const popupContent = `
        <div style="font-family:'Inter', sans-serif; padding:2px; min-width:150px;">
          <h4 style="margin:0 0 2px 0; font-weight:700; color:#1f2937; font-size:13px;">${esc(d.name)}</h4>
          <p style="margin:0 0 4px 0; font-size:9px; color:#4f46e5; font-weight:bold;"><i class="fa-solid fa-warehouse"></i> ${esc(d.warehouse_name)}</p>
          <p style="margin:0 0 6px 0; font-size:10px; color:#6b7280; font-family:monospace;">${esc(d.phone)}</p>
          <div style="border-top:1px solid #f3f4f6; padding-top:6px; font-size:10px; color:#4b5563; display:grid; grid-template-columns:1fr 1fr; gap:6px;">
            <div>
              <span style="display:block; color:#9ca3af; text-transform:uppercase; font-size:8px;">Check-in</span>
              <strong>${d.checkin_time || '—'}</strong>
            </div>
            <div>
              <span style="display:block; color:#9ca3af; text-transform:uppercase; font-size:8px;">Last Seen</span>
              <strong>${d.last_seen}</strong>
            </div>
          </div>
          <a href="https://www.google.com/maps?q=${d.latitude},${d.longitude}" target="_blank" style="display:block; text-align:center; background:#4f46e5; color:white; font-size:10px; font-weight:bold; border-radius:4px; padding:6px; margin-top:8px; text-decoration:none;"><i class="fa-solid fa-location-arrow"></i> Google Maps</a>
        </div>
      `;

      const marker = L.marker([d.latitude, d.longitude], { icon: customIcon })
        .bindPopup(popupContent);
      
      marker.dsr_id = d.dsr_id;
      markerGroup.addLayer(marker);
      hasBounds = true;
    }
  });

  if (hasBounds && markerGroup.getLayers().length > 0) {
    map.fitBounds(markerGroup.getBounds(), { padding: [40, 40] });
  }
}

// Pan and Zoom map smoothly to selected DSR marker
function locateDsr(dsrId) {
  const marker = markerGroup.getLayers().find(m => m.dsr_id === dsrId);
  if (marker) {
    map.flyTo(marker.getLatLng(), 16, { animate: true, duration: 1.5 });
    marker.openPopup();
  }
}

// Helpers
function esc(str) {
  return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
