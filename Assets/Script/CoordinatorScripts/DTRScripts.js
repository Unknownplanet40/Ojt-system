import { ToastVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
BGcircleTheme(true);
let swalTheme = SwalTheme();

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const decisionModalEl = document.getElementById('coordinatorDecisionModal');
const decisionModal = decisionModalEl ? new bootstrap.Modal(decisionModalEl) : null;
const ENDPOINTS = {
  get: '../../../process/dtr/get_dtr',
  approve: '../../../process/dtr/approve_dtr',
};

const state = { entries: [], selected: new Set(), active: null };
let mapInstance = null;

// Get current theme (dark or light)
function getCurrentTheme() {
  return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
}

// Get appropriate tile layer based on theme
function getTileLayer() {
  const theme = getCurrentTheme();
  
  if (theme === 'dark') {
    // Dark theme tile layers
    return L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
      maxZoom: 19
    });
  } else {
    // Light theme tile layer
    return L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
      maxZoom: 19
    });
  }
}

function toast(icon, title) {
  if (window.Swal) ToastVersion(swalTheme, title, icon, 3000, 'top-end', '8');
}

function renderSummary() {
  $('#coordinatorEntriesCount').text(state.entries.length);
  $('#coordinatorStudentsCount').text(new Set(state.entries.map((e) => e.student_uuid || e.student_number)).size);
  $('#coordinatorPendingCount').text(state.entries.filter((e) => e.status === 'pending').length);
  $('#coordinatorBackdatedCount').text(state.entries.filter((e) => e.is_backdated).length);
}

function filteredEntries() {
  const term = ($('#coordinatorSearchInput').val() || '').toLowerCase();
  const status = $('#coordinatorStatusFilter').val() || '';
  const month = $('#coordinatorMonthFilter').val() || '';
  const backdated = $('#coordinatorBackdatedFilter').val();

  return state.entries.filter((entry) => {
    if (status && entry.status !== status) return false;
    if (month && !String(entry.entry_date || '').startsWith(month)) return false;
    if (backdated !== '' && Number(entry.is_backdated ? 1 : 0) !== Number(backdated)) return false;
    const text = [entry.full_name, entry.student_number, entry.program_code, entry.activities, entry.entry_date_label].filter(Boolean).join(' ').toLowerCase();
    return text.includes(term);
  });
}

function renderEntries() {
  const list = $('#coordinatorDtrList');
  const empty = $('#coordinatorDtrEmptyState');
  const filtered = filteredEntries();

  list.empty();
  state.selected = new Set([...state.selected].filter((uuid) => filtered.some((entry) => entry.uuid === uuid)));

  if (!filtered.length) {
    empty.removeClass('d-none');
    $('#selectAllCoordinatorEntries').prop('checked', false);
    return;
  }
  empty.addClass('d-none');

  filtered.forEach((entry) => {
    const checked = state.selected.has(entry.uuid) ? 'checked' : '';
    const activity = entry.activities || '<span class="text-muted">No activities recorded</span>';
    const isPending = entry.status === 'pending';
    const accent = entry.status === 'approved' ? 'success' : entry.status === 'rejected' ? 'danger' : entry.is_backdated ? 'warning' : 'info';
    const statusIcon = entry.status === 'approved' ? 'bi-check-circle' : entry.status === 'rejected' ? 'bi-x-circle' : entry.is_backdated ? 'bi-clock-history' : 'bi-journal-text';

    let verificationHtml = '';
    if (entry.clock_in_latitude || entry.clock_in_photo) {
      verificationHtml = `
        <div class="mt-3 p-3 rounded-3 bg-dark bg-opacity-20 border border-light border-opacity-10">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-medium small text-white-50 text-uppercase letter-spacing-1" style="font-size: 0.75rem;"><i class="bi bi-shield-fill-check me-1"></i>Location & Identity Logs</span>
          </div>
          <div class="row g-2 align-items-center">
            ${entry.clock_in_photo ? `
              <div class="col-auto">
                <img src="../../../file_serve.php?type=dtr_selfie&dtr_uuid=${entry.uuid}" class="rounded border border-light border-opacity-10 object-fit-cover shadow-sm" style="width: 55px; height: 55px; cursor: zoom-in;" alt="Verification Photo" onclick="window.open('../../../file_serve.php?type=dtr_selfie&dtr_uuid=${entry.uuid}', '_blank')" title="View Original Selfie">
              </div>
            ` : ''}
            <div class="col">
              ${entry.clock_in_latitude ? `
                <div class="small d-flex flex-wrap gap-1 align-items-center">
                  <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2" style="font-size: 0.7rem;"><i class="bi bi-geo-alt-fill me-1"></i>GPS Verified</span>
                  <code class="text-white-50 small">${Number(entry.clock_in_latitude).toFixed(6)}, ${Number(entry.clock_in_longitude).toFixed(6)}</code>
                </div>
              ` : '<div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>No coordinates logged</div>'}
            </div>
          </div>
        </div>
      `;
    }

    list.append(`
      <div class="card bg-blur-5 bg-semi-transparent shadow-sm" data-accent="${accent}">
        <div class="card-body">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 dtr-entry-header">
            <div class="d-flex gap-3 flex-grow-1 align-items-start">
              <div class="pt-1">
                ${isPending ? `<input class="form-check-input coordinator-entry-check" type="checkbox" data-uuid="${entry.uuid}" ${checked}>` : `<span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary-subtle text-secondary-emphasis" style="width: 1.2rem; height: 1.2rem;"><i class="bi bi-dot"></i></span>`}
              </div>
              <div class="dtr-entry-icon bg-${accent}-subtle text-${accent}-emphasis">
                <i class="bi ${statusIcon} fs-5"></i>
              </div>
              <div class="dtr-entry-title flex-grow-1">
                <div class="dtr-chip-row mb-2">
                  <span class="dtr-chip"><i class="bi bi-person"></i>${entry.full_name}</span>
                  <span class="dtr-chip"><i class="bi bi-activity"></i>${entry.status_label || entry.status}</span>
                  ${entry.is_backdated ? '<span class="dtr-chip text-info-emphasis"><i class="bi bi-exclamation-triangle"></i>Backdated</span>' : ''}
                </div>
                <h5 class="mb-1 fw-semibold">${entry.full_name}</h5>
                <p class="mb-0 text-muted dtr-entry-subtitle">${entry.student_number || '—'} · ${entry.program_code || '—'}</p>
              </div>
            </div>
            <div class="text-lg-end">
              <div class="fw-semibold">${entry.entry_date_label}</div>
              <small class="text-muted">Submitted ${entry.time_ago || ''}</small>
            </div>
          </div>

          <div class="dtr-entry-meta mt-3">
            <div class="meta-box" data-importance="high"><span class="meta-label">Time</span><span class="meta-value">${entry.time_in_label} - ${entry.time_out_label}</span></div>
            <div class="meta-box"><span class="meta-label">Hours</span><span class="meta-value">${entry.hours_label}</span></div>
            <div class="meta-box"><span class="meta-label">Activities</span><span class="meta-value">${entry.activities ? 'Recorded' : 'No activity'}</span></div>
            <div class="meta-box"><span class="meta-label">Backdated</span><span class="meta-value">${entry.is_backdated ? 'Yes' : 'No'}</span></div>
          </div>

          <div class="dtr-activity-preview mt-3">
            <span class="meta-label mb-2">Activity details</span>
            <div class="activity-text">${activity}</div>
          </div>
          
          ${verificationHtml}

          <div class="d-flex justify-content-end flex-wrap gap-2 dtr-entry-actions mt-3">
            ${isPending ? `<button class="btn btn-sm btn-outline-success rounded-pill px-3" data-action="review" data-uuid="${entry.uuid}">Review</button><button class="btn btn-sm btn-outline-danger rounded-pill px-3" data-action="reject" data-uuid="${entry.uuid}">Reject</button>` : `<button class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-action="view" data-uuid="${entry.uuid}">View</button>`}
          </div>
        </div>
      </div>
    `);
  });

  $('#selectAllCoordinatorEntries').prop('checked', filtered.filter((entry) => entry.status === 'pending').length > 0 && filtered.filter((entry) => entry.status === 'pending').every((entry) => state.selected.has(entry.uuid)));
}

function loadDtr() {
  $.ajax({
    url: ENDPOINTS.get,
    method: 'POST',
    dataType: 'json',
    data: {
      csrf_token: csrfToken,
      status: $('#coordinatorStatusFilter').val() || '',
      month: $('#coordinatorMonthFilter').val() || '',
      is_backdated: $('#coordinatorBackdatedFilter').val(),
    },
    success: (response) => {
      if (response.status !== 'success') {
        toast('error', response.message || 'Unable to load DTR entries.');
        return;
      }
      state.entries = response.entries || [];
      state.selected = new Set();
      renderSummary();
      renderEntries();
    },
    error: (xhr, status, error) => Errors(xhr, status, error),
    complete: () => $('#pageLoader').fadeOut(200),
  });
}

function openDecision(entry) {
  state.active = entry;
  $('#coordDecisionStudentName').text(entry.full_name);
  $('#coordDecisionStudentLabel').text(entry.full_name);
  $('#coordDecisionStudentNumber').text(entry.student_number || '—');
  $('#coordDecisionProgram').text(entry.program_code || '—');
  $('#coordDecisionEntryDate').text(entry.entry_date_label);
  $('#coordDecisionTimeRange').text(`${entry.time_in_label} - ${entry.time_out_label}`);
  $('#coordDecisionHours').text(entry.hours_label);
  $('#coordDecisionStatus').text(entry.status_label || 'Pending');
  $('#coordDecisionBackdated').text(entry.is_backdated ? 'Yes' : 'No');
  $('#coordDecisionActivities').text(entry.activities || '—');
  $('#coordDecisionSubmittedAt').text(entry.submitted_at || '—');
  $('#coordDecisionReason').val(entry.backdate_reason || '');

  const verificationContainer = $('#coordDecisionVerificationContainer');
  const selfieContainer = $('#coordDecisionSelfieContainer');
  const selfieImg = $('#coordDecisionSelfie');
  const gpsContainer = $('#coordDecisionGpsContainer');
  const gpsCoords = $('#coordDecisionGpsCoords');

  const mapContainer = $('#coordDecisionMapContainer');

  if (entry.clock_in_photo || entry.clock_in_latitude) {
    verificationContainer.removeClass('d-none');
    
    if (entry.clock_in_photo) {
      selfieImg.attr('src', `../../../file_serve.php?type=dtr_selfie&dtr_uuid=${entry.uuid}`);
      selfieContainer.removeClass('d-none');
    } else {
      selfieContainer.addClass('d-none');
    }
    
    if (entry.clock_in_latitude) {
      gpsCoords.text(`${Number(entry.clock_in_latitude).toFixed(6)}, ${Number(entry.clock_in_longitude).toFixed(6)}`);
      gpsContainer.removeClass('d-none');

      if (entry.geofence && entry.geofence.latitude) {
        mapContainer.removeClass('d-none');
        // Initialize map with distance metadata
        fetchDistanceMetadata(entry, () => {
          renderGeofenceMap(entry);
        });
      } else {
        mapContainer.addClass('d-none');
      }
    } else {
      gpsContainer.addClass('d-none');
      mapContainer.addClass('d-none');
    }
  } else {
    verificationContainer.addClass('d-none');
    mapContainer.addClass('d-none');
  }

  decisionModal?.show();
}

function fetchDistanceMetadata(entry, callback) {
  $.ajax({
    url: '../../../process/dtr/calculate_distance',
    method: 'POST',
    dataType: 'json',
    data: { dtr_uuid: entry.uuid, csrf_token: csrfToken },
    success: (response) => {
      if (response.status === 'success' && response.data) {
        state.active.distanceData = response.data;
        if (callback) callback();
      }
    },
    error: (xhr, status, error) => {
      console.warn('Could not fetch distance metadata:', error);
      if (callback) callback();
    }
  });
}

function renderGeofenceMap(entry) {
  setTimeout(() => {
    const mapElement = document.getElementById('coordDecisionMapContainer');
    if (!mapElement) {
      console.warn('Map element not found');
      return;
    }

    // Check if element is visible
    if (mapElement.offsetParent === null) {
      console.warn('Map element is not visible');
      return;
    }

    if (mapInstance) {
      mapInstance.remove();
      mapInstance = null;
    }

    const geofence = entry.geofence;
    if (!geofence || !geofence.latitude) {
      console.warn('Geofence data not available');
      return;
    }

    const distData = state.active.distanceData || {};

    try {
      mapInstance = L.map('coordDecisionMapContainer').setView([geofence.latitude, geofence.longitude], 17);
    } catch (e) {
      console.error('Error initializing map:', e);
      return;
    }
    
    getTileLayer().addTo(mapInstance);

    // Inject custom professional map marker styles if not already injected
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

    // Company Geofence Circle
    const geofenceCircle = L.circle([geofence.latitude, geofence.longitude], {
      color: '#10b981',
      weight: 2,
      opacity: 0.8,
      fillColor: '#10b981',
      fillOpacity: 0.1,
      dashArray: '5, 5',
      radius: geofence.radius || 100
    }).addTo(mapInstance);

    // Company Marker
    const companyMarker = L.marker([geofence.latitude, geofence.longitude], {
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
    }).addTo(mapInstance);

    companyMarker.bindPopup(`<strong>${geofence.company_name || 'Company'}</strong><br><small>HQ Location</small>`, {
      className: 'geofence-popup'
    });

    const markers = [companyMarker];

    // Clock-In Student Marker
    if (entry.clock_in_latitude && entry.clock_in_longitude) {
      const isWithinBounds = distData.clock_in_within_bounds !== false;
      const pinClass = isWithinBounds ? 'pin-within-bounds' : 'pin-out-bounds';
      const iconClass = isWithinBounds ? 'bi-box-arrow-in-right' : 'bi-exclamation-triangle-fill';
      
      const clockInMarker = L.marker([entry.clock_in_latitude, entry.clock_in_longitude], {
        icon: L.divIcon({
          html: `
            <div class="custom-pin-container">
              <div class="custom-pin-base ${pinClass}">
                <div class="custom-pin-icon">
                  <i class="bi ${iconClass}"></i>
                </div>
              </div>
            </div>
          `,
          iconSize: [40, 40],
          iconAnchor: [20, 36],
          popupAnchor: [0, -36]
        })
      }).addTo(mapInstance);

      let popupHTML = `
        <div>
          <strong>Clock-In</strong><br>
          <small>${entry.full_name}</small><br>
      `;

      if (distData.clock_in_distance !== null) {
        const distanceLabel = isWithinBounds ? '✓ Within' : '✗ Outside';
        const distanceColor = isWithinBounds ? '#10b981' : '#ef4444';
        popupHTML += `
          <hr style="margin: 0.5rem 0;">
          <small>
            <span style="color: ${distanceColor}; font-weight: bold;">${distanceLabel} Geofence</span><br>
            Distance: <code>${distData.clock_in_distance.toFixed(2)}m</code><br>
            Limit: <code>${distData.allowed_radius}m</code>
          </small>
        `;
      }

      if (entry.clock_in_photo) {
        popupHTML += `
          <hr style="margin: 0.5rem 0;">
          <img src="../../../file_serve.php?type=dtr_selfie&dtr_uuid=${entry.uuid}" style="width: 100%; height: 120px; border-radius: 8px; object-fit: cover; cursor: zoom-in;" onclick="window.open('../../../file_serve.php?type=dtr_selfie&dtr_uuid=${entry.uuid}', '_blank')" alt="Verification photo">
        `;
      }

      popupHTML += '</div>';
      clockInMarker.bindPopup(popupHTML, { className: 'geofence-popup', maxWidth: 250 });
      markers.push(clockInMarker);
    }

    // Clock-Out Student Marker (if available)
    if (entry.clock_out_latitude && entry.clock_out_longitude) {
      const isWithinBounds = distData.clock_out_within_bounds !== false;
      const pinClass = isWithinBounds ? 'pin-within-bounds' : 'pin-out-bounds';
      const iconClass = isWithinBounds ? 'bi-box-arrow-out-right' : 'bi-exclamation-triangle-fill';
      
      const clockOutMarker = L.marker([entry.clock_out_latitude, entry.clock_out_longitude], {
        icon: L.divIcon({
          html: `
            <div class="custom-pin-container">
              <div class="custom-pin-base ${pinClass}">
                <div class="custom-pin-icon">
                  <i class="bi ${iconClass}"></i>
                </div>
              </div>
            </div>
          `,
          iconSize: [40, 40],
          iconAnchor: [20, 36],
          popupAnchor: [0, -36]
        })
      }).addTo(mapInstance);

      let popupHTML = `
        <div>
          <strong>Clock-Out</strong><br>
          <small>${entry.full_name}</small><br>
      `;

      if (distData.clock_out_distance !== null) {
        const distanceLabel = isWithinBounds ? '✓ Within' : '✗ Outside';
        const distanceColor = isWithinBounds ? '#10b981' : '#ef4444';
        popupHTML += `
          <hr style="margin: 0.5rem 0;">
          <small>
            <span style="color: ${distanceColor}; font-weight: bold;">${distanceLabel} Geofence</span><br>
            Distance: <code>${distData.clock_out_distance.toFixed(2)}m</code><br>
            Limit: <code>${distData.allowed_radius}m</code>
          </small>
        `;
      }

      popupHTML += '</div>';
      clockOutMarker.bindPopup(popupHTML, { className: 'geofence-popup', maxWidth: 250 });
      markers.push(clockOutMarker);
    }

    // Fit map bounds to show all markers
    try {
      const group = new L.featureGroup(markers);
      mapInstance.fitBounds(group.getBounds().pad(0.1), { maxZoom: 17 });
    } catch (e) {
      console.warn('Could not fit map bounds:', e);
    }
  }, 300);
}

function approveEntry(entry) {
  $.ajax({
    url: ENDPOINTS.approve,
    method: 'POST',
    dataType: 'json',
    data: { csrf_token: csrfToken, action: 'approve', dtr_uuid: entry.uuid },
    success: (response) => {
      if (response.status !== 'success') {
        toast('error', response.message || 'Unable to approve entry.');
        return;
      }
      toast('success', response.message || 'Entry approved.');
      decisionModal?.hide();
      loadDtr();
    },
    error: (xhr, status, error) => Errors(xhr, status, error),
  });
}

function rejectEntry(entry) {
  const reason = $('#coordDecisionReason').val().trim();
  if (!reason) {
    toast('error', 'Please provide a reason.');
    return;
  }
  $.ajax({
    url: ENDPOINTS.approve,
    method: 'POST',
    dataType: 'json',
    data: { csrf_token: csrfToken, action: 'reject', dtr_uuid: entry.uuid, reason },
    success: (response) => {
      if (response.status !== 'success') {
        toast('error', response.message || 'Unable to reject entry.');
        return;
      }
      toast('success', response.message || 'Entry rejected.');
      decisionModal?.hide();
      loadDtr();
    },
    error: (xhr, status, error) => Errors(xhr, status, error),
  });
}

function approveSelected() {
  const uuids = [...state.selected];
  if (!uuids.length) {
    toast('error', 'Select at least one entry first.');
    return;
  }

  const grouped = new Map();
  state.entries.filter((entry) => uuids.includes(entry.uuid)).forEach((entry) => {
    if (!grouped.has(entry.student_uuid)) grouped.set(entry.student_uuid, []);
    grouped.get(entry.student_uuid).push(entry.uuid);
  });

  const requests = [...grouped.entries()].map(([studentUuid, dtrUuids]) => $.ajax({
    url: ENDPOINTS.approve,
    method: 'POST',
    dataType: 'json',
    data: {
      csrf_token: csrfToken,
      action: 'bulk_approve',
      student_uuid: studentUuid,
      dtr_uuids: JSON.stringify(dtrUuids),
    },
  }));

  Promise.all(requests)
    .then(() => {
      toast('success', 'Selected entries approved.');
      loadDtr();
    })
    .catch((error) => {
      const message = error?.responseJSON?.message || 'Unable to approve selected entries.';
      toast('error', message);
    });
}

$(document).ready(() => {
  loadDtr();

  $('#dashboardRefreshBtn').on('click', loadDtr);
  $('#approveSelectedBtn').on('click', approveSelected);
  $('#clearCoordinatorFiltersBtn').on('click', () => {
    $('#coordinatorSearchInput').val('');
    $('#coordinatorStatusFilter').val('');
    $('#coordinatorMonthFilter').val('');
    $('#coordinatorBackdatedFilter').val('');
    renderEntries();
  });

  $('#coordinatorSearchInput, #coordinatorStatusFilter, #coordinatorMonthFilter, #coordinatorBackdatedFilter').on('input change', renderEntries);

  $('#selectAllCoordinatorEntries').on('change', function () {
    const checked = $(this).is(':checked');
    $('#coordinatorDtrList .coordinator-entry-check').toArray().forEach((el) => {
      const uuid = $(el).data('uuid');
      if (checked) state.selected.add(uuid); else state.selected.delete(uuid);
    });
    renderEntries();
  });

  $('#coordinatorDtrList').on('change', '.coordinator-entry-check', function () {
    const uuid = $(this).data('uuid');
    if ($(this).is(':checked')) state.selected.add(uuid); else state.selected.delete(uuid);
    renderEntries();
  });

  $('#coordinatorDtrList').on('click', 'button[data-action="review"], button[data-action="view"]', function () {
    const entry = state.entries.find((item) => item.uuid === $(this).data('uuid'));
    if (entry) openDecision(entry);
  });

  $('#coordinatorDtrList').on('click', 'button[data-action="reject"]', function () {
    const entry = state.entries.find((item) => item.uuid === $(this).data('uuid'));
    if (entry) openDecision(entry);
    $('#coordDecisionReason').focus();
  });

  $('#coordDecisionApproveBtn').on('click', () => {
    if (state.active) approveEntry(state.active);
  });

  $('#coordDecisionRejectBtn').on('click', () => {
    if (state.active) rejectEntry(state.active);
  });
});
