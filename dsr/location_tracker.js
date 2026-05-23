// dsr/location_tracker.js
(function() {
  const TRACK_API = '../api/dsr_mobile.php?action=track_location';
  const MIN_DISTANCE_METERS = 10; // Only upload if they moved at least 10m
  const MIN_INTERVAL_MS = 60000;   // Or if 60 seconds have passed since last upload
  
  let watchId = null;
  let lastUploadedLat = null;
  let lastUploadedLng = null;
  let lastUploadTime = 0;
  
  // Calculate distance between two coordinates in meters (Haversine formula)
  function getDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // Earth radius in meters
    const phi1 = lat1 * Math.PI/180;
    const phi2 = lat2 * Math.PI/180;
    const deltaPhi = (lat2-lat1) * Math.PI/180;
    const deltaLambda = (lon2-lon1) * Math.PI/180;

    const a = Math.sin(deltaPhi/2) * Math.sin(deltaPhi/2) +
              Math.cos(phi1) * Math.cos(phi2) *
              Math.sin(deltaLambda/2) * Math.sin(deltaLambda/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c; // in meters
  }
  
  function showPermissionBlock() {
    if (document.getElementById('location-block-overlay')) return;
    
    const overlay = document.createElement('div');
    overlay.id = 'location-block-overlay';
    overlay.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.98); z-index:99999; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:24px; text-align:center; color:#f8fafc; font-family:"Plus Jakarta Sans", sans-serif;';
    
    overlay.innerHTML = `
      <div style="max-width:360px; background:#1e293b; border:1px solid rgba(255,255,255,0.1); padding:32px 24px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.5);">
        <div style="width:64px; height:64px; background:rgba(239, 68, 68, 0.15); color:#ef4444; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px auto; font-size:28px;">
          <i class="fa-solid fa-location-crosshairs"></i>
        </div>
        <h2 style="font-size:18px; font-weight:800; margin-bottom:12px; color:#ffffff;">অবস্থান অনুমতি প্রয়োজন</h2>
        <p style="font-size:13px; color:#94a3b8; line-height:1.6; margin-bottom:24px;">
          অ্যাপ্লিকেশনের কাজ চালু রাখার জন্য আপনার রিয়েল-টাইম অবস্থান (Location) শেয়ার করা বাধ্যতামূলক। অনুগ্রহ করে ব্রাউজার সেটিংসে গিয়ে লোকেশন পারমিশন অনুমতি দিন।
        </p>
        <button onclick="window.location.reload()" style="background:#2563eb; color:#ffffff; font-weight:700; border:none; padding:12px 24px; font-size:13px; cursor:pointer; width:100%; transition:background 0.2s;">
          আবার চেষ্টা করুন
        </button>
      </div>
    `;
    
    document.body.appendChild(overlay);
  }
  
  function removePermissionBlock() {
    const overlay = document.getElementById('location-block-overlay');
    if (overlay) overlay.remove();
  }
  
  async function uploadLocation(latitude, longitude, accuracy) {
    const now = Date.now();
    try {
      const response = await fetch(TRACK_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ latitude, longitude, accuracy })
      });
      const data = await response.json();
      if (data.success) {
        lastUploadedLat = latitude;
        lastUploadedLng = longitude;
        lastUploadTime = now;
        console.log('Location tracked successfully:', { latitude, longitude, accuracy });
      }
    } catch (e) {
      console.warn('Failed to upload location:', e);
    }
  }
  
  function handleLocationUpdate(position) {
    removePermissionBlock();
    
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    const accuracy = position.coords.accuracy;
    const now = Date.now();
    
    if (lastUploadedLat === null || lastUploadedLng === null) {
      uploadLocation(lat, lng, accuracy);
      return;
    }
    
    const distance = getDistance(lastUploadedLat, lastUploadedLng, lat, lng);
    const elapsed = now - lastUploadTime;
    
    if (distance >= MIN_DISTANCE_METERS || elapsed >= MIN_INTERVAL_MS) {
      uploadLocation(lat, lng, accuracy);
    }
  }
  
  function handleLocationError(error) {
    console.error('Location error:', error);
    if (error.code === error.PERMISSION_DENIED) {
      showPermissionBlock();
    }
  }
  
  function startTracking() {
    if (!navigator.geolocation) {
      console.warn('Geolocation not supported by this browser.');
      return;
    }
    
    navigator.geolocation.getCurrentPosition(
      (position) => {
        handleLocationUpdate(position);
        
        watchId = navigator.geolocation.watchPosition(
          handleLocationUpdate,
          handleLocationError,
          {
            enableHighAccuracy: true,
            maximumAge: 10000,
            timeout: 10000
          }
        );
      },
      handleLocationError,
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startTracking);
  } else {
    startTracking();
  }
})();
