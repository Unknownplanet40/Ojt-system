<div class="row row-cols-1 row-cols-md-2 g-4 mt-1">
  <div class="col-md-8 needsattention">
    <div class="card h-100 bg-blur-5 bg-semi-transparent shadow h-100" style="max-height: 645px; overflow-y: auto; --blur-lvl: <?= $opacitylvl ?>;">
      <div class="card-body">
        <div class="d-flex align-items-start gap-2 mb-2">
          <div>
            <h5 class="card-title mb-1">Needs attention</h5>
            <p class="text-muted small mb-0" id="needsAttentionStatus">Monitoring key risks and blockers</p>
          </div>
          <div class="ms-auto d-flex align-items-center gap-2">
            <small class="text-muted text-nowrap" id="needsAttentionLastUpdated">Last updated: --</small>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshNeedsAttentionBtn" title="Refresh dashboard insights">
              <i class="bi bi-arrow-clockwise me-1"></i>
              Refresh
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="needsAttentionViewAll">View all</button>
          </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-3" id="needsAttentionSummary"></div>
        <ul class="list-group list-group-flush needs-attention-list" id="needsAttentionList">
        </ul>
      </div>
    </div>
  </div>
  <div class="col-md-4 quickactions">
    <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-sm border-0 h-100" style="--blur-lvl: <?= $opacitylvl ?>;">
      <div class="card-body p-4">
      <div class="d-flex align-items-center mb-3">
        <h5 class="card-title mb-0">Quick Actions</h5>
        <span class="ms-auto badge text-bg-light border">Admin Tools</span>
      </div>

      <div class="list-group list-group-flush gap-2">
        <button type="button" class="list-group-item list-group-item-action rounded-3 border quickactions d-flex align-items-center p-3" id="quickAddCoordinator">
        <span class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px;">
          <i class="bi bi-person-plus fs-5"></i>
        </span>
        <span class="flex-grow-1 text-start">
          <span class="d-block fw-semibold">Add coordinator account</span>
          <small class="text-muted">Create login for a new faculty coordinator</small>
        </span>
        <i class="bi bi-chevron-right text-muted"></i>
        </button>

        <button type="button" class="list-group-item list-group-item-action rounded-3 border quickactions d-flex align-items-center p-3" id="quickCreateBatch">
        <span class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px;">
          <i class="bi bi-calendar-event fs-5"></i>
        </span>
        <span class="flex-grow-1 text-start">
          <span class="d-block fw-semibold">Create new batch</span>
          <small class="text-muted">Set up next school year / semester</small>
        </span>
        <i class="bi bi-chevron-right text-muted"></i>
        </button>

        <button type="button" class="list-group-item list-group-item-action rounded-3 border quickactions d-flex align-items-center p-3" id="quickExportSemReports">
        <span class="rounded-circle bg-warning bg-opacity-10 text-warning d-inline-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px;">
          <i class="bi bi-file-earmark-arrow-down fs-5"></i>
        </span>
        <span class="flex-grow-1 text-start">
          <span class="d-block fw-semibold">Export semester reports</span>
          <small class="text-muted">Download full batch summary as PDF</small>
        </span>
        <i class="bi bi-chevron-right text-muted"></i>
        </button>

        <button type="button" class="list-group-item list-group-item-action rounded-3 border quickactions d-flex align-items-center p-3" id="quickViewAuditLogs">
        <span class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-inline-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px;">
          <i class="bi bi-file-earmark-text fs-5"></i>
        </span>
        <span class="flex-grow-1 text-start">
          <span class="d-block fw-semibold">View audit logs</span>
          <small class="text-muted">See recent login and system activity</small>
        </span>
        <i class="bi bi-chevron-right text-muted"></i>
        </button>
      </div>
      </div>
    </div>
  </div>
</div>
