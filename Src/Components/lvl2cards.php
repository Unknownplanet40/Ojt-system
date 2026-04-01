<div class="row row-cols-1 row-cols-md-2 g-4 mt-1">
  <div class="col-md-8">
    <div class="card h-100 bg-blur-5 bg-semi-transparent shadow" style="max-height: 400px; overflow-y: auto; --blur-lvl: <?= $opacitylvl ?>;">
      <div class="card-body">
        <div class="hstack mb-3">
          <h5 class="card-title">Needs attention</h5>
          <a href="javascript:void(0)" class="ms-auto text-decoration-none text-success fw-medium">View all</a>
        </div>
        <ul class="list-group list-group-flush" id="needsAttentionList">
        </ul>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100 bg-blur-5 bg-semi-transparent shadow" style="--blur-lvl: <?= $opacitylvl ?>;">
      <div class="card-body">
        <div class="hstack">
          <h5 class="card-title">Quick Actions</h5>
        </div>
        <div class="d-grid gap-2 p-4">
          <span class="bg-dark bg-opacity-75 text-white rounded-2 p-2 border border-1 border-secondary quickactions" style="cursor: pointer" id="quickAddCoordinator">
            <div class="hstack">
              <i class="bi bi-person-plus mx-3 text-primary fs-5"></i>
              <div class="vstack">
                <span>Add coordinator account</span>
                <small class="text-muted">Create login for a new faculty coordinator</small>
              </div>
            </div>
          </span>
          <span class="bg-dark bg-opacity-75 text-white rounded-2 p-2 border border-1 border-secondary quickactions" style="cursor: pointer" id="quickCreateBatch">
            <div class="hstack">
              <i class="bi bi-calendar-event mx-3 text-success fs-5"></i>
              <div class="vstack">
                <span>Create new batch</span>
                <small class="text-muted">Set up next school year / semester</small>
              </div>
            </div>
          </span>
          <span class="bg-dark bg-opacity-75 text-white rounded-2 p-2 border border-1 border-secondary quickactions" style="cursor: pointer" id="quickExportSemReports">
            <div class="hstack">
              <i class="bi bi-file-earmark-arrow-down mx-3 text-warning fs-5"></i>
              <div class="vstack">
                <span>Export semester reports</span>
                <small class="text-muted">Download full batch summary as PDF</small>
              </div>
            </div>
          </span>
          <span class="bg-dark bg-opacity-75 text-white rounded-2 p-2 border border-1 border-secondary quickactions" style="cursor: pointer" id="quickViewAuditLogs">
            <div class="hstack">
              <i class="bi bi-file-earmark-text mx-3 text-secondary fs-5"></i>
              <div class="vstack">
                <span>View audit logs</span>
                <small class="text-muted">See recent login and system activity</small>
              </div>
            </div>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>
