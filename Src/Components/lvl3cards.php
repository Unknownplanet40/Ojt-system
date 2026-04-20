<div class="row row-cols-1 row-cols-md-3 g-4 mt-1">
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card h-100 border-0 shadow-sm bg-blur-5 bg-semi-transparent" style="--blur-lvl: <?= $opacitylvl ?>">
      <div class="card-header bg-transparent border-0 pb-0">
        <div class="d-flex align-items-center justify-content-between">
          <h5 class="card-title mb-0">Users by Role</h5>
          <span class="badge text-bg-light">Live</span>
        </div>
        <small class="text-muted">Distribution of active users</small>
      </div>

      <div class="card-body pt-3">
        <ul class="list-group list-group-flush">
          <li class="list-group-item bg-transparent px-0 py-3">
            <div class="d-flex align-items-start gap-3">
              <i class="bi bi-mortarboard-fill text-primary fs-5"></i>
              <div class="w-100">
                <div class="d-flex justify-content-between mb-2">
                  <span class="fw-medium">Students</span>
                  <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis fw-bold px-3" id="studentsCount">0</span>
                </div>
                <div class="progress" style="height: 6px;">
                  <div class="progress-bar bg-primary rounded-pill" id="studentProgressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </li>

          <li class="list-group-item bg-transparent px-0 py-3">
            <div class="d-flex align-items-start gap-3">
              <i class="bi bi-person-workspace text-success fs-5"></i>
              <div class="w-100">
                <div class="d-flex justify-content-between mb-2">
                  <span class="fw-medium">Supervisors</span>
                  <span class="badge rounded-pill bg-success-subtle text-success-emphasis fw-bold px-3" id="supervisorCount">0</span>
                </div>
                <div class="progress" style="height: 6px;">
                  <div class="progress-bar bg-success rounded-pill" id="supervisorProgressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </li>

          <li class="list-group-item bg-transparent px-0 py-3">
            <div class="d-flex align-items-start gap-3">
              <i class="bi bi-people-fill text-warning fs-5"></i>
              <div class="w-100">
                <div class="d-flex justify-content-between mb-2">
                  <span class="fw-medium">Coordinators</span>
                  <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis fw-bold px-3" id="coordinatorCount">0</span>
                </div>
                <div class="progress" style="height: 6px;">
                  <div class="progress-bar bg-warning rounded-pill" id="coordinatorProgressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </li>

          <li class="list-group-item bg-transparent px-0 py-3">
            <div class="d-flex align-items-start gap-3">
              <i class="bi bi-shield-lock-fill text-danger fs-5"></i>
              <div class="w-100">
                <div class="d-flex justify-content-between mb-2">
                  <span class="fw-medium">Admin</span>
                  <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis fw-bold px-3" id="adminCount">0</span>
                </div>
                <div class="progress" style="height: 6px;">
                  <div class="progress-bar bg-danger rounded-pill" id="adminProgressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100 bg-blur-5 bg-semi-transparent shadow h-100" style="--blur-lvl: <?= $opacitylvl ?>">
      <div class="card-header border-bottom-0 bg-transparent">
        <div class="hstack">
          <h5 class="card-title">Recent Accounts</h5>
          <a href="../Admin/Students" class="ms-auto text-decoration-none text-success fw-medium">View all</a>
        </div>
      </div>  
    <div class="card-body">
        <ul class="list-group list-group-flush" id="recentAccountsList" style="max-height: 400px; overflow-y: auto;">
        </ul>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100 bg-blur-5 bg-semi-transparent shadow h-100" style="--blur-lvl: <?= $opacitylvl ?>">
      <div class="card-header border-bottom-0 bg-transparent">
        <div class="hstack">
          <h5 class="card-title">Recent Activity - (<small class="text-muted" id="activityCount">0</small>)</h5>
          <a href="../Admin/AuditLogs" class="ms-auto text-decoration-none text-success fw-medium">View all</a>
        </div>
      </div>
    <div class="card-body">
        <ul class="list-group list-group-flush" id="recentActivityList" style="max-height: 400px; overflow-y: auto;">
        </ul>
      </div>
    </div>
  </div>
</div>
