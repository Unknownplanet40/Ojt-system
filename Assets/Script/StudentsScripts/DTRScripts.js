import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
BGcircleTheme(true);
let swalTheme = SwalTheme();

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const modalEl = document.getElementById('dtrEntryModal');
const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
const ENDPOINTS = {
  get: '../../../process/dtr/get_dtr',
  submit: '../../../process/dtr/submit_dtr',
  edit: '../../../process/dtr/edit_dtr',
  delete: '../../../process/dtr/delete_dtr',
};

const state = {
  entries: [],
  summary: null,
  filters: {
    status: '',
    month: '',
    search: '',
  },
  mode: 'create',
  activeUuid: '',
  geofence: null,
};

let cameraStream = null;

function toast(icon, title) {
  if (window.Swal) {
    ToastVersion(swalTheme, title, icon, 3000, 'top-end', '8');
  }
}

function setFieldError(id, message = '') {
  const el = document.getElementById(id);
  if (el) el.textContent = message || '';
}

function clearFieldErrors() {
  ['entryDateError', 'timeInError', 'timeOutError', 'lunchBreakMinutesError', 'activitiesError', 'backdateReasonError'].forEach((id) => setFieldError(id, ''));
}

function pad2(n) { return String(n).padStart(2, '0'); }
function getTodayYYYYMMDD() {
  const d = new Date();
  return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
}
function getCurrentTimeHHMM() {
  const d = new Date();
  return `${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
}

// Haversine Distance & GPS Verification functions
function deg2rad(deg) {
  return deg * (Math.PI / 180);
}

function calculateDistance(lat1, lon1, lat2, lon2) {
  const R = 6371000; // Radius of the earth in meters
  const dLat = deg2rad(lat2 - lat1);
  const dLon = deg2rad(lon2 - lon1);
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
    Math.sin(dLon / 2) * Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c;
}

function acquireGeolocation(callback) {
  const gpsSpinner = document.getElementById('gpsSpinner');
  const gpsStatusText = document.getElementById('gpsStatusText');
  const gpsCoordsDisplay = document.getElementById('gpsCoordsDisplay');
  const latDisplay = document.getElementById('latDisplay');
  const lngDisplay = document.getElementById('lngDisplay');
  
  if (gpsSpinner) gpsSpinner.classList.remove('d-none');
  if (gpsStatusText) gpsStatusText.textContent = "Acquiring GPS coordinates...";
  if (gpsCoordsDisplay) gpsCoordsDisplay.classList.add('d-none');
  
  if (!navigator.geolocation) {
    if (gpsSpinner) gpsSpinner.classList.add('d-none');
    if (gpsStatusText) gpsStatusText.textContent = "GPS Error: Geolocation is not supported by your browser.";
    updateGpsBadge(false, "Unsupported");
    return;
  }
  
  navigator.geolocation.getCurrentPosition(
    (position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;
      
      document.getElementById('clockInLatitude').value = lat;
      document.getElementById('clockInLongitude').value = lng;
      document.getElementById('clockOutLatitude').value = lat;
      document.getElementById('clockOutLongitude').value = lng;
      
      if (gpsSpinner) gpsSpinner.classList.add('d-none');
      if (latDisplay) latDisplay.textContent = lat.toFixed(6);
      if (lngDisplay) lngDisplay.textContent = lng.toFixed(6);
      if (gpsCoordsDisplay) gpsCoordsDisplay.classList.remove('d-none');
      
      if (state.geofence && state.geofence.latitude && state.geofence.longitude) {
        const distance = calculateDistance(lat, lng, state.geofence.latitude, state.geofence.longitude);
        const radius = state.geofence.radius || 100;
        if (distance <= radius) {
          if (gpsStatusText) gpsStatusText.textContent = `Within Geofence (${Math.round(distance)}m from premises)`;
          updateGpsBadge(true, "Verified Location");
        } else {
          if (gpsStatusText) gpsStatusText.textContent = `Outside Geofence (${Math.round(distance)}m from premises)`;
          updateGpsBadge(false, "Outside Premises");
        }
      } else {
        if (gpsStatusText) gpsStatusText.textContent = "Location acquired (no geofence configured).";
        updateGpsBadge(true, "Location Acquired");
      }
      
      if (callback) callback(lat, lng);
    },
    (error) => {
      if (gpsSpinner) gpsSpinner.classList.add('d-none');
      let msg = "GPS Error: Timed out or permission denied.";
      if (error.code === error.PERMISSION_DENIED) {
        msg = "GPS Error: Permission denied. Please enable location access.";
      } else if (error.code === error.POSITION_UNAVAILABLE) {
        msg = "GPS Error: Location info unavailable.";
      } else if (error.code === error.TIMEOUT) {
        msg = "GPS Error: Request timed out.";
      }
      if (gpsStatusText) gpsStatusText.textContent = msg;
      updateGpsBadge(false, "GPS Failure");
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
  );
}

function updateGpsBadge(success, text) {
  const badgeEl = document.getElementById('overallVerificationBadge');
  if (!badgeEl) return;
  
  if (success) {
    badgeEl.innerHTML = `<span class="badge bg-success bg-opacity-10 text-success rounded-pill small"><i class="bi bi-shield-fill-check me-1"></i>${text}</span>`;
  } else {
    badgeEl.innerHTML = `<span class="badge bg-danger bg-opacity-10 text-danger rounded-pill small"><i class="bi bi-shield-fill-x me-1"></i>${text}</span>`;
  }
}

// Camera Live Streaming & Selfie Capture functions
async function startWebcam() {
  const video = document.getElementById('webcamVideo');
  const previewImg = document.getElementById('selfiePreviewImg');
  const placeholder = document.getElementById('cameraPlaceholder');
  const startBtn = document.getElementById('startCameraBtn');
  const captureBtn = document.getElementById('captureSelfieBtn');
  const retakeBtn = document.getElementById('retakeSelfieBtn');
  
  try {
    cameraStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }
    });
    video.srcObject = cameraStream;
    video.classList.remove('d-none');
    previewImg.classList.add('d-none');
    placeholder.classList.add('d-none');
    
    startBtn.classList.add('d-none');
    captureBtn.classList.remove('d-none');
    retakeBtn.classList.add('d-none');
  } catch (err) {
    console.error("Camera access error:", err);
    toast('error', "Unable to access camera: " + (err.message || "Unknown error"));
  }
}

function captureSelfie() {
  const video = document.getElementById('webcamVideo');
  const canvas = document.getElementById('selfieCanvas');
  const previewImg = document.getElementById('selfiePreviewImg');
  const captureBtn = document.getElementById('captureSelfieBtn');
  const retakeBtn = document.getElementById('retakeSelfieBtn');
  const photoInput = document.getElementById('selfiePhotoData');
  
  if (!video || !canvas) return;
  
  const context = canvas.getContext('2d');
  canvas.width = video.videoWidth || 640;
  canvas.height = video.videoHeight || 480;
  context.drawImage(video, 0, 0, canvas.width, canvas.height);
  
  const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
  photoInput.value = dataUrl;
  
  previewImg.src = dataUrl;
  previewImg.classList.remove('d-none');
  video.classList.add('d-none');
  
  captureBtn.classList.add('d-none');
  retakeBtn.classList.remove('d-none');
  
  stopWebcam();
  
  // Update verification badge to captured status
  const lat = document.getElementById('clockInLatitude').value;
  if (lat) {
    // If coordinates are already acquired, show fully verified badge!
    if (state.geofence && state.geofence.latitude && state.geofence.longitude) {
      const distance = calculateDistance(Number(lat), Number(document.getElementById('clockInLongitude').value), state.geofence.latitude, state.geofence.longitude);
      const radius = state.geofence.radius || 100;
      updateGpsBadge(distance <= radius, distance <= radius ? "Fully Verified" : "Outside Premises");
    } else {
      updateGpsBadge(true, "Captured & Verified");
    }
  } else {
    updateGpsBadge(true, "Photo Captured");
  }
}

function stopWebcam() {
  if (cameraStream) {
    cameraStream.getTracks().forEach(track => track.stop());
    cameraStream = null;
  }
}

function formValues() {
  return {
    dtr_uuid: document.getElementById('dtrEntryUuid')?.value || '',
    entry_date: document.getElementById('entryDate')?.value || '',
    time_in: document.getElementById('timeIn')?.value || '',
    time_out: document.getElementById('timeOut')?.value || '',
    lunch_break_minutes: document.getElementById('lunchBreakMinutes')?.value || '60',
    activities: document.getElementById('activities')?.value ?? '',
    activities_performed: document.getElementById('activities')?.value ?? '',
    backdate_reason: document.getElementById('backdateReason')?.value || '',
    clock_in_latitude: document.getElementById('clockInLatitude')?.value || '',
    clock_in_longitude: document.getElementById('clockInLongitude')?.value || '',
    clock_out_latitude: document.getElementById('clockOutLatitude')?.value || '',
    clock_out_longitude: document.getElementById('clockOutLongitude')?.value || '',
    selfie_photo_data: document.getElementById('selfiePhotoData')?.value || '',
  };
}

function setModalMode(mode, entry = null) {
  state.mode = mode;
  state.activeUuid = entry?.uuid || '';
  const isResubmission = mode === 'edit' && entry?.status === 'rejected';
  document.getElementById('dtrEntryModalTitle').textContent = isResubmission
    ? 'Resubmit DTR entry'
    : mode === 'edit'
      ? 'Edit DTR entry'
      : 'Log DTR entry';
  document.getElementById('saveDtrEntryBtn').textContent = isResubmission
    ? 'Resubmit entry'
    : mode === 'edit'
      ? 'Update entry'
      : 'Save entry';
  document.getElementById('dtrEntryUuid').value = entry?.uuid || '';
  
  if (mode === 'create' && !entry) {
    document.getElementById('entryDate').value = getTodayYYYYMMDD();
    document.getElementById('timeIn').value = getCurrentTimeHHMM();
    document.getElementById('timeOut').value = '';
    
    document.getElementById('clockInLatitude').value = '';
    document.getElementById('clockInLongitude').value = '';
    document.getElementById('clockOutLatitude').value = '';
    document.getElementById('clockOutLongitude').value = '';
    document.getElementById('selfiePhotoData').value = '';
    
    const webcamVideo = document.getElementById('webcamVideo');
    const selfiePreviewImg = document.getElementById('selfiePreviewImg');
    const cameraPlaceholder = document.getElementById('cameraPlaceholder');
    const startCameraBtn = document.getElementById('startCameraBtn');
    const captureSelfieBtn = document.getElementById('captureSelfieBtn');
    const retakeSelfieBtn = document.getElementById('retakeSelfieBtn');
    
    if (webcamVideo) webcamVideo.classList.add('d-none');
    if (selfiePreviewImg) {
      selfiePreviewImg.src = '';
      selfiePreviewImg.classList.add('d-none');
    }
    if (cameraPlaceholder) cameraPlaceholder.classList.remove('d-none');
    if (startCameraBtn) {
      startCameraBtn.classList.remove('d-none');
      startCameraBtn.textContent = 'Start Camera';
    }
    if (captureSelfieBtn) captureSelfieBtn.classList.add('d-none');
    if (retakeSelfieBtn) retakeSelfieBtn.classList.add('d-none');
    
    updateGpsBadge(false, "Pending Capture");
    
    // Auto-acquire geolocation for new entry
    acquireGeolocation();
  } else {
    document.getElementById('entryDate').value = entry?.entry_date || '';
    document.getElementById('timeIn').value = entry?.time_in || '';
    document.getElementById('timeOut').value = entry?.time_out || '';
    
    const lat = entry?.clock_in_latitude;
    const lng = entry?.clock_in_longitude;
    
    document.getElementById('clockInLatitude').value = lat || '';
    document.getElementById('clockInLongitude').value = lng || '';
    document.getElementById('clockOutLatitude').value = entry?.clock_out_latitude || '';
    document.getElementById('clockOutLongitude').value = entry?.clock_out_longitude || '';
    document.getElementById('selfiePhotoData').value = '';
    
    const gpsStatusText = document.getElementById('gpsStatusText');
    const gpsSpinner = document.getElementById('gpsSpinner');
    const gpsCoordsDisplay = document.getElementById('gpsCoordsDisplay');
    const latDisplay = document.getElementById('latDisplay');
    const lngDisplay = document.getElementById('lngDisplay');
    
    if (lat && lng) {
      if (gpsSpinner) gpsSpinner.classList.add('d-none');
      if (gpsStatusText) gpsStatusText.textContent = "Location verified from previous submission.";
      if (latDisplay) latDisplay.textContent = Number(lat).toFixed(6);
      if (lngDisplay) lngDisplay.textContent = Number(lng).toFixed(6);
      if (gpsCoordsDisplay) gpsCoordsDisplay.classList.remove('d-none');
      updateGpsBadge(true, "Location Loaded");
    } else {
      acquireGeolocation();
    }
    
    const clockInPhoto = entry?.clock_in_photo || entry?.clock_out_photo;
    const webcamVideo = document.getElementById('webcamVideo');
    const selfiePreviewImg = document.getElementById('selfiePreviewImg');
    const cameraPlaceholder = document.getElementById('cameraPlaceholder');
    const startCameraBtn = document.getElementById('startCameraBtn');
    const captureSelfieBtn = document.getElementById('captureSelfieBtn');
    const retakeSelfieBtn = document.getElementById('retakeSelfieBtn');
    
    if (clockInPhoto) {
      if (webcamVideo) webcamVideo.classList.add('d-none');
      if (selfiePreviewImg) {
        selfiePreviewImg.src = "../../../" + clockInPhoto;
        selfiePreviewImg.classList.remove('d-none');
      }
      if (cameraPlaceholder) cameraPlaceholder.classList.add('d-none');
      if (startCameraBtn) {
        startCameraBtn.classList.remove('d-none');
        startCameraBtn.textContent = 'Recapture Photo';
      }
      if (captureSelfieBtn) captureSelfieBtn.classList.add('d-none');
      if (retakeSelfieBtn) retakeSelfieBtn.classList.add('d-none');
      updateGpsBadge(true, "Previous Selfie Loaded");
    } else {
      if (webcamVideo) webcamVideo.classList.add('d-none');
      if (selfiePreviewImg) {
        selfiePreviewImg.src = '';
        selfiePreviewImg.classList.add('d-none');
      }
      if (cameraPlaceholder) cameraPlaceholder.classList.remove('d-none');
      if (startCameraBtn) {
        startCameraBtn.classList.remove('d-none');
        startCameraBtn.textContent = 'Start Camera';
      }
      if (captureSelfieBtn) captureSelfieBtn.classList.add('d-none');
      if (retakeSelfieBtn) retakeSelfieBtn.classList.add('d-none');
      updateGpsBadge(false, "Pending Capture");
    }
  }
  
  clearFieldErrors();
}

function statusBadge(entry) {
  const cls = entry.status === 'approved'
    ? 'bg-success-subtle text-success-emphasis'
    : entry.status === 'rejected'
      ? 'bg-danger-subtle text-danger-emphasis'
      : 'bg-warning-subtle text-warning-emphasis';
  return `<span class="badge rounded-pill ${cls}">${entry.status_label || entry.status}</span>`;
}

function matchesSearch(entry, term) {
  if (!term) return true;
  const haystack = [entry.entry_date_label, entry.time_in_label, entry.time_out_label, entry.activities ?? '', entry.status_label, entry.backdate_reason]
    .filter(Boolean)
    .join(' ')
    .toLowerCase();
  return haystack.includes(term.toLowerCase());
}

function renderSummary(summary) {
  if (!summary) return;
  $('#completionPercent').text(`${summary.percentage ?? 0}%`);
  $('#completionProgressBar').css('width', `${summary.percentage ?? 0}%`);
  $('#approvedHoursLabel').text(Number(summary.total_approved || 0).toFixed(2));
  $('#remainingHoursLabel').text(Number(summary.remaining_hours || 0).toFixed(2));
  $('#pendingCountLabel').text(summary.pending_count ?? 0);
  $('#backdatedCountLabel').text(summary.backdated_pending_count ?? 0);
}

function renderEntries() {
  const list = $('#studentDtrList');
  const empty = $('#studentDtrEmptyState');
  const term = $('#dtrSearchInput').val() || '';
  const status = $('#dtrStatusFilter').val() || '';
  const month = $('#dtrMonthFilter').val() || '';

  const filtered = state.entries.filter((entry) => {
    if (status && entry.status !== status) return false;
    if (month && !String(entry.entry_date || '').startsWith(month)) return false;
    if (!matchesSearch(entry, term)) return false;
    return true;
  });

  list.empty();
  if (!filtered.length) {
    empty.removeClass('d-none');
    return;
  }
  empty.addClass('d-none');

  filtered.forEach((entry) => {
    const accent = entry.status === 'approved' ? 'success' : entry.status === 'rejected' ? 'danger' : entry.is_backdated ? 'warning' : 'info';
    const statusIcon = entry.status === 'approved' ? 'bi-check2-circle' : entry.status === 'rejected' ? 'bi-x-circle' : entry.is_backdated ? 'bi-clock-history' : 'bi-journal-text';
    const isBackdated = entry.is_backdated ? '<span class="badge rounded-pill bg-info-subtle text-info-emphasis">Backdated</span>' : '';
    const requiresResubmission = entry.status === 'rejected' ? '<span class="dtr-chip badge rounded-pill bg-danger-subtle text-danger-emphasis">Requires resubmission</span>' : '';
    const activity = entry.activities ?? '' ? entry.activities : '<span class="text-muted">No activities recorded</span>';

    let verificationHtml = '';
    if (entry.clock_in_latitude || entry.clock_in_photo) {
      verificationHtml = `
        <div class="mt-3 p-3 rounded-3 bg-dark bg-opacity-20 border border-light border-opacity-10">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-medium small text-white-50 text-uppercase letter-spacing-1" style="font-size: 0.75rem;"><i class="bi bi-shield-fill-check me-1"></i>Location & Identity Verification</span>
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
                  <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2" style="font-size: 0.7rem;"><i class="bi bi-geo-alt-fill me-1"></i>GPS Captured</span>
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
            <div class="d-flex gap-3 align-items-start flex-grow-1">
              <div class="dtr-entry-icon bg-${accent}-subtle text-${accent}-emphasis">
                <i class="bi ${statusIcon} fs-5"></i>
              </div>
              <div class="dtr-entry-title flex-grow-1">
                <div class="dtr-chip-row mb-2">
                  <span class="dtr-chip"><i class="bi bi-calendar3"></i>${entry.entry_date_label}</span>
                  <span class="dtr-chip"><i class="bi bi-clock"></i>${entry.time_ago || 'Recently submitted'}</span>
                  ${entry.is_backdated ? '<span class="dtr-chip text-info-emphasis"><i class="bi bi-exclamation-triangle"></i>Backdated</span>' : ''}
                  ${requiresResubmission}
                </div>
                <h5 class="mb-1 fw-semibold">${entry.status_label || entry.status}</h5>
                <p class="mb-0 text-muted dtr-entry-subtitle">${entry.time_in_label} - ${entry.time_out_label} · ${entry.hours_label}</p>
              </div>
            </div>
            <div class="text-lg-end">
              ${statusBadge(entry)}
            </div>
          </div>

          <div class="dtr-entry-meta mt-3">
            <div class="meta-box" data-importance="high"><span class="meta-label">Time in / out</span><span class="meta-value">${entry.time_in_label} - ${entry.time_out_label}</span></div>
            <div class="meta-box"><span class="meta-label">Hours rendered</span><span class="meta-value">${entry.hours_label}</span></div>
            <div class="meta-box"><span class="meta-label">Lunch break</span><span class="meta-value">${entry.lunch_break_minutes} min</span></div>
            <div class="meta-box"><span class="meta-label">Status</span><span class="meta-value">${entry.status_label || entry.status}</span></div>
          </div>

          <div class="dtr-activity-preview mt-3">
            <span class="meta-label mb-2">Activities performed</span>
            <div class="activity-text">${activity}</div>
            ${entry.backdate_reason ? `<small class="text-muted d-block mt-2">Reason: ${entry.backdate_reason}</small>` : ''}
            ${entry.status === 'rejected' && entry.rejection_reason ? `<div class="alert alert-danger alert-sm mt-3 mb-0 p-2" role="alert"><small><strong>Rejection reason:</strong> ${entry.rejection_reason}</small></div>` : ''}
          </div>
          
          ${verificationHtml}

          <div class="d-flex justify-content-end flex-wrap gap-2 dtr-entry-actions mt-3">
            ${entry.can_edit ? `<button class="btn btn-sm ${entry.status === 'rejected' ? 'btn-outline-warning' : 'btn-outline-success'} rounded-pill px-3" data-action="edit" data-uuid="${entry.uuid}">${entry.status === 'rejected' ? 'Resubmit' : 'Edit'}</button>` : ''}
            ${entry.can_delete ? `<button class="btn btn-sm btn-outline-danger rounded-pill px-3" data-action="delete" data-uuid="${entry.uuid}">Delete</button>` : ''}
          </div>
        </div>
      </div>
    `);
  });
}

function loadDtr() {
  $('#pageLoader').removeClass('d-none');
  $.ajax({
    url: ENDPOINTS.get,
    method: 'POST',
    dataType: 'json',
    data: {
      csrf_token: csrfToken,
      status: $('#dtrStatusFilter').val() || '',
      month: $('#dtrMonthFilter').val() || '',
    },
    success: (response) => {
      if (response.status !== 'success') {
        toast('error', response.message || 'Failed to load DTR entries.');
        return;
      }
      state.entries = response.entries || [];
      state.summary = response.summary || null;
      state.geofence = response.geofence || null;
      renderSummary(state.summary);
      renderEntries();
    },
    error: (xhr, status, error) => Errors(xhr, status, error),
    complete: () => {
      $('#pageLoader').fadeOut(200);
    },
  });
}

function submitForm() {
  clearFieldErrors();
  const payload = formValues();
  
  if (state.mode === 'create' && !payload.selfie_photo_data) {
    toast('error', 'An identity verification selfie photo is required to submit DTR.');
    return;
  }
  
  const endpoint = state.mode === 'edit' ? ENDPOINTS.edit : ENDPOINTS.submit;

  $.ajax({
    url: endpoint,
    method: 'POST',
    dataType: 'json',
    data: { csrf_token: csrfToken, ...payload },
    success: (response) => {
      if (response.status !== 'success') {
        const errors = response.errors || {};
        if (errors.entry_date) setFieldError('entryDateError', errors.entry_date);
        if (errors.time_in) setFieldError('timeInError', errors.time_in);
        if (errors.time_out) setFieldError('timeOutError', errors.time_out);
        if (errors.lunch_break_minutes) setFieldError('lunchBreakMinutesError', errors.lunch_break_minutes);
        if (errors.activities) setFieldError('activitiesError', errors.activities);
        if (errors.backdate_reason) setFieldError('backdateReasonError', errors.backdate_reason);
        toast('error', response.message || 'Please review the form.');
        return;
      }
      toast('success', response.message || 'DTR entry saved.');
      modal?.hide();
      loadDtr();
    },
    error: (xhr, status, error) => Errors(xhr, status, error),
  });
}

function deleteEntry(uuid) {
  Swal.fire({
    title: 'Delete entry? ',
    text: 'This will permanently remove the pending DTR entry.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
  }).then((result) => {
    if (!result.isConfirmed) return;
    $.ajax({
      url: ENDPOINTS.delete,
      method: 'POST',
      dataType: 'json',
      data: { csrf_token: csrfToken, dtr_uuid: uuid },
      success: (response) => {
        if (response.status !== 'success') {
          toast('error', response.message || 'Unable to delete entry.');
          return;
        }
        toast('success', response.message || 'Entry deleted.');
        loadDtr();
      },
      error: (xhr, status, error) => Errors(xhr, status, error),
    });
  });
}

$(document).ready(() => {
  $('#pageLoader').fadeOut(300);
  loadDtr();

  $('#dashboardRefreshBtn').on('click', loadDtr);
  $('#newDtrEntryBtn, #emptyStateNewEntryBtn').on('click', () => {
    setModalMode('create');
    modal?.show();
  });

  $('#saveDtrEntryBtn').on('click', submitForm);
  $('#clearDtrFiltersBtn').on('click', () => {
    $('#dtrStatusFilter').val('');
    $('#dtrMonthFilter').val('');
    $('#dtrSearchInput').val('');
    renderEntries();
  });

  $('#dtrStatusFilter, #dtrMonthFilter, #dtrSearchInput').on('input change', renderEntries);

  $('#studentDtrList').on('click', 'button[data-action="edit"]', function () {
    const entry = state.entries.find((item) => item.uuid === $(this).data('uuid'));
    if (!entry) return;
    setModalMode('edit', entry);
    modal?.show();
  });

  $('#studentDtrList').on('click', 'button[data-action="delete"]', function () {
    deleteEntry($(this).data('uuid'));
  });
  
  // Hook up Geolocation & Camera triggers
  $('#retryGpsBtn').on('click', () => acquireGeolocation());
  $('#startCameraBtn').on('click', startWebcam);
  $('#captureSelfieBtn').on('click', captureSelfie);
  $('#retakeSelfieBtn').on('click', startWebcam);
  
  $('#dtrEntryModal').on('hide.bs.modal', function () {
    stopWebcam();
  });
});
