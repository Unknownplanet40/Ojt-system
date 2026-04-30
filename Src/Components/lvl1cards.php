<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-4">
  <!-- Total Users -->
  <div class="col totalusercard">
    <div class="card h-100 border-0 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent position-relative overflow-hidden"
         style="--blur-lvl: <?= $opacitylvl ?>;">
      <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: linear-gradient(90deg, #0d6efd, #6ea8fe);"></div>
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <h6 class="text-secondary fw-semibold mb-0">Total Users</h6>
          <span class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
            <i class="bi bi-people-fill"></i>
          </span>
        </div>
        <p class="display-6 fw-bold mb-1 text-body" id="totalUsersCounts">0</p>
        <p class="small text-muted mb-0" id="totalUsersStatus">Loading...</p>
      </div>
    </div>
  </div>

  <!-- Students -->
  <div class="col studentcard">
    <div class="card h-100 border-0 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent position-relative overflow-hidden"
         style="--blur-lvl: <?= $opacitylvl ?>;">
      <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: linear-gradient(90deg, #ffc107, #ffda6a);"></div>
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <h6 class="text-secondary fw-semibold mb-0">Students</h6>
          <span class="bg-warning-subtle text-warning rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
            <i class="bi bi-mortarboard-fill"></i>
          </span>
        </div>
        <p class="display-6 fw-bold mb-1 text-body" id="studentsCounts">0</p>
        <p class="small text-warning mb-0" id="studentStatus">Loading...</p>
      </div>
    </div>
  </div>

  <!-- Coordinators -->
  <div class="col coordinatorcard">
    <div class="card h-100 border-0 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent position-relative overflow-hidden"
         style="--blur-lvl: <?= $opacitylvl ?>;">
      <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: linear-gradient(90deg, #198754, #75b798);"></div>
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <h6 class="text-secondary fw-semibold mb-0">Coordinators</h6>
          <span class="bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
            <i class="bi bi-person-badge-fill"></i>
          </span>
        </div>
        <p class="display-6 fw-bold mb-1 text-body" id="coordinatorCounts">0</p>
        <p class="small text-muted mb-0" id="coordinatorStatus">Loading...</p>
      </div>
    </div>
  </div>

  <!-- Companies -->
  <div class="col companiescard">
    <div class="card h-100 border-0 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent position-relative overflow-hidden"
         style="--blur-lvl: <?= $opacitylvl ?>;">
      <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: linear-gradient(90deg, #fd7e14, #ffb37a);"></div>
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <h6 class="text-secondary fw-semibold mb-0">Companies</h6>
          <span class="bg-warning-subtle text-warning rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
            <i class="bi bi-building-fill"></i>
          </span>
        </div>
        <p class="display-6 fw-bold mb-1 text-body" id="companiesCounts">0</p>
        <p class="small text-warning mb-0" id="companiesStatus">Loading...</p>
      </div>
    </div>
  </div>
</div>
