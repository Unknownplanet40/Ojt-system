<div class="row row-cols-1 row-cols-md-3 g-4 mt-1">
  <div class="col-md-4">
    <div class="card h-100 bg-blur-5 bg-semi-transparent shadow" style="max-height: 400px; overflow-y: auto; --blur-lvl: <?= $opacitylvl ?>">
      <div class="card-body">
        <div class="hstack">
          <h5 class="card-title">User by role</h5>
        </div>
        <ul class="list-group list-group-flush mt-3">
          <li class="list-group-item bg-transparent">
            <div class="hstack">
              <i class="bi bi-person-circle me-3 text-primary fs-4"></i>
              <div class="vstack w-100">
                <div class="hstack mb-1">
                  <span>Students</span>
                  <span class="text-muted ms-auto" id="studentsCount">0</span>
                </div>
                <div class="progress w-100" style="height: 5px">
                  <div class="progress-bar" role="progressbar" style="width: 56%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" id="studentProgressBar"></div>
                </div>
              </div>
            </div>
          </li>
          <li class="list-group-item bg-transparent">
            <div class="hstack">
              <i class="bi bi-person-circle me-3 text-primary fs-4"></i>
              <div class="vstack w-100">
                <div class="hstack mb-1">
                  <span>Supervisors</span>
                  <span class="text-muted ms-auto" id="supervisorCount">0</span>
                </div>
                <div class="progress w-100" style="height: 5px">
                  <div class="progress-bar" role="progressbar" style="width: 12%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" id="supervisorProgressBar"></div>
                </div>
              </div>
            </div>
          </li>
          <li class="list-group-item bg-transparent">
            <div class="hstack">
              <i class="bi bi-person-circle me-3 text-primary fs-4"></i>
              <div class="vstack w-100">
                <div class="hstack mb-1">
                  <span>Coordinators</span>
                  <span class="text-muted ms-auto" id="coordinatorCount">0</span>
                </div>
                <div class="progress w-100" style="height: 5px">
                  <div class="progress-bar" role="progressbar" style="width: 4%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" id="coordinatorProgressBar"></div>
                </div>
              </div>
            </div>
          </li>
          <li class="list-group-item bg-transparent">
            <div class="hstack">
              <i class="bi bi-person-circle me-3 text-primary fs-4"></i>
              <div class="vstack w-100">
                <div class="hstack mb-1">
                  <span>Admin</span>
                  <span class="text-muted ms-auto" id="adminCount">0</span>
                </div>
                <div class="progress w-100" style="height: 5px">
                  <div class="progress-bar" role="progressbar" style="width: 1%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" id="adminProgressBar"></div>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100 bg-blur-5 bg-semi-transparent shadow" style="max-height: 400px; overflow-y: auto; --blur-lvl: <?= $opacitylvl ?>">
      <div class="card-body">
        <div class="hstack">
          <h5 class="card-title">Recent Accounts</h5>
          <a href="javascript:void(0)" class="ms-auto text-decoration-none text-success fw-medium">View all</a>
        </div>
        <ul class="list-group list-group-flush mt-3" id="recentAccountsList">
        </ul>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100 bg-blur-5 bg-semi-transparent shadow" style="max-height: 400px; overflow-y: auto; --blur-lvl: <?= $opacitylvl ?>">
      <div class="card-body">
        <div class="hstack">
          <h5 class="card-title">Recent Activity - (<small class="text-muted" id="activityCount">0</small>)</h5>
          <a href="javascript:void(0)" class="ms-auto text-decoration-none text-success fw-medium">View all</a>
        </div>
        <ul class="list-group list-group-flush mt-3" id="recentActivityList">
        </ul>
      </div>
    </div>
  </div>
</div>
