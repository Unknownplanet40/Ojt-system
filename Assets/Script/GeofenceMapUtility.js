class GeofenceMapUtility {
  static initializeMap(containerId, options = {}) {
    const {
      centerLat = 14.5794,
      centerLon = 121.0047,
      zoom = 15,
      height = '400px',
      theme = 'light'
    } = options;

    const container = document.getElementById(containerId);
    if (!container) {
      console.error(`Map container "${containerId}" not found`);
      return null;
    }

    container.style.height = height;
    const map = L.map(containerId).setView([centerLat, centerLon], zoom);

    if (theme === 'dark') {
      L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        maxZoom: 19
      }).addTo(map);
    } else {
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
        opacity: 0.8
      }).addTo(map);
    }

    if (!document.getElementById('leaflet-custom-marker-styles')) {
      const style = document.createElement('style');
      style.id = 'leaflet-custom-marker-styles';
      style.innerHTML = `
        .geofence-popup .leaflet-popup-content-wrapper {
          background: rgba(var(--bs-body-bg-rgb), 0.85);
          backdrop-filter: blur(12px);
          -webkit-backdrop-filter: blur(12px);
          border: 1px solid rgba(255, 255, 255, 0.15);
          border-radius: 12px;
          color: var(--bs-body-color);
          box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        .geofence-popup .leaflet-popup-tip {
          background: rgba(var(--bs-body-bg-rgb), 0.85);
          backdrop-filter: blur(12px);
          -webkit-backdrop-filter: blur(12px);
          border-left: 1px solid rgba(255, 255, 255, 0.15);
          border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .leaflet-div-icon {
          background: transparent !important;
          border: none !important;
        }
        
        .custom-pin-container {
          display: flex;
          align-items: center;
          justify-content: center;
          width: 40px;
          height: 40px;
        }
        
        .custom-pin-base {
          position: relative;
          width: 32px;
          height: 32px;
          border-radius: 50% 50% 50% 0;
          transform: rotate(-45deg);
          display: flex;
          align-items: center;
          justify-content: center;
          border: 2px solid #ffffff;
          box-shadow: 0 4px 12px rgba(0,0,0,0.3);
          transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .custom-pin-container:hover .custom-pin-base {
          transform: rotate(-45deg) scale(1.15);
          box-shadow: 0 6px 16px rgba(0,0,0,0.4);
          z-index: 1000 !important;
        }
        
        .custom-pin-icon {
          transform: rotate(45deg);
          display: flex;
          align-items: center;
          justify-content: center;
          color: #ffffff;
          font-size: 13px;
        }
        
        .pin-company {
          background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
          box-shadow: 0 4px 10px rgba(99, 102, 241, 0.4), 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        
        .pin-within-bounds {
          background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
          box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4), 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .pin-out-bounds {
          background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
          box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4), 0 0 0 3px rgba(239, 68, 68, 0.2);
        }
        
        .pin-pulse-ring {
          position: absolute;
          top: 0;
          left: 0;
          width: 32px;
          height: 32px;
          border-radius: 50% 50% 50% 0;
          background: inherit;
          opacity: 0.4;
          z-index: -1;
          animation: pinPulse 2s infinite ease-out;
        }
        
        @keyframes pinPulse {
          0% { transform: scale(1); opacity: 0.4; }
          100% { transform: scale(1.6); opacity: 0; }
        }
      `;
      document.head.appendChild(style);
    }

    return map;
  }

  static addGeofenceCircle(map, centerLat, centerLon, radiusMeters, options = {}) {
    const defaultOptions = {
      color: '#10b981',
      weight: 2,
      opacity: 0.8,
      fillColor: '#10b981',
      fillOpacity: 0.1,
      dashArray: '5, 5'
    };

    const circleOptions = { ...defaultOptions, ...options };
    const circle = L.circle([centerLat, centerLon], radiusMeters, circleOptions).addTo(map);

    return circle;
  }

  static addCompanyMarker(map, lat, lon, companyName = 'Company') {
    const marker = L.marker([lat, lon], {
      icon: L.divIcon({
        html: `
          <div class="custom-pin-container">
            <div class="custom-pin-base pin-company">
              <div class="pin-pulse-ring"></div>
              <div class="custom-pin-icon">
                <i class="bi bi-building-fill"></i>
              </div>
            </div>
          </div>
        `,
        iconSize: [40, 40],
        iconAnchor: [20, 36],
        popupAnchor: [0, -36]
      })
    }).addTo(map);

    marker.bindPopup(`<strong>${companyName}</strong><br>HQ Location`, {
      className: 'geofence-popup'
    });

    return marker;
  }

  static addStudentMarker(map, lat, lon, data = {}, markerType = 'in') {
    const {
      distance = null,
      timestamp = null,
      photoPath = null,
      withinBounds = true,
      studentName = 'Student'
    } = data;

    const isOutOfBounds = distance && !withinBounds;
    const pinClass = isOutOfBounds ? 'pin-out-bounds' : 'pin-within-bounds';
    const iconClass = markerType === 'in' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-out-right';
    const label = markerType === 'in' ? 'Clock-In' : 'Clock-Out';

    const marker = L.marker([lat, lon], {
      icon: L.divIcon({
        html: `
          <div class="custom-pin-container">
            <div class="custom-pin-base ${pinClass}">
              <div class="custom-pin-icon">
                <i class="bi ${isOutOfBounds ? 'bi-exclamation-triangle-fill' : iconClass}"></i>
              </div>
            </div>
          </div>
        `,
        iconSize: [40, 40],
        iconAnchor: [20, 36],
        popupAnchor: [0, -36]
      })
    }).addTo(map);

    let popupHTML = `
      <div style="min-width: 180px;">
        <strong>${label}</strong><br>
        <small class="text-muted">${studentName}</small><br>
    `;

    if (distance !== null) {
      const distanceLabel = withinBounds ? '✓ Within' : '✗ Outside';
      const distanceColor = withinBounds ? '#10b981' : '#ef4444';
      popupHTML += `
        <hr style="margin: 0.5rem 0;">
        <small>
          <span style="color: ${distanceColor}; font-weight: bold;">${distanceLabel} Geofence</span><br>
          Distance: <code>${distance.toFixed(2)}m</code>
        </small>
      `;
    }

    if (timestamp) {
      popupHTML += `<br><small class="text-muted">${timestamp}</small>`;
    }

    if (photoPath) {
      popupHTML += `
        <hr style="margin: 0.5rem 0;">
        <img src="${photoPath}" style="
          width: 100%;
          height: 120px;
          border-radius: 4px;
          object-fit: cover;
          cursor: zoom-in;
        " alt="Verification photo" onclick="window.open('${photoPath}', '_blank')">
      `;
    }

    popupHTML += '</div>';
    marker.bindPopup(popupHTML, {
      className: 'geofence-popup',
      maxWidth: 250
    });

    return marker;
  }

  static fitMapBounds(map, markers = [], circle = null) {
    if (markers.length === 0 && !circle) return;

    const group = new L.featureGroup(markers);
    if (circle) {
      group.addLayer(circle);
    }

    try {
      map.fitBounds(group.getBounds().pad(0.1), { maxZoom: 17 });
    } catch (e) {
      console.warn('Could not fit bounds:', e);
    }
  }

  static addRoute(map, points, options = {}) {
    const defaultOptions = {
      color: '#667eea',
      weight: 2,
      opacity: 0.7,
      dashArray: '5, 3'
    };

    const polylineOptions = { ...defaultOptions, ...options };
    const polyline = L.polyline(points, polylineOptions).addTo(map);

    return polyline;
  }

  static clearMap(map) {
    map.eachLayer((layer) => {
      if (layer instanceof L.Marker || layer instanceof L.Circle || layer instanceof L.Polyline) {
        map.removeLayer(layer);
      }
    });
  }

  static createGeofencePopup(data) {
    const {
      title = 'Location',
      coordinates = null,
      distance = null,
      withinBounds = null,
      radius = null
    } = data;

    let html = `<div><strong>${title}</strong>`;

    if (coordinates) {
      html += `<br><small><code>${coordinates[0].toFixed(6)}, ${coordinates[1].toFixed(6)}</code></small>`;
    }

    if (distance !== null && radius !== null) {
      const status = withinBounds ? '✓ Within' : '✗ Outside';
      const color = withinBounds ? '#10b981' : '#ef4444';
      html += `<br><small style="color: ${color};"><strong>${status}</strong> (${distance.toFixed(2)}m / ${radius}m)</small>`;
    }

    html += '</div>';
    return html;
  }
}

export default GeofenceMapUtility;
