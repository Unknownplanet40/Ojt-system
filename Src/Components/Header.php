<nav class="navbar navbar-expand-lg bg-semi-transparent mx-3 mb-3 border rounded-2" aria-label="Main navigation" id="adminTopNavbar" style="--blur-lvl: 0.2">
  <div class="container-fluid">
    <a class="navbar-brand" href="../Login"><?= $ShortTitle ?></a>
    <button class="navbar-toggler p-0 border-0 shadow-none" type="button" id="navbarSideCollapse" aria-label="Toggle navigation">
      <i class="bi bi-list fs-1"></i>
    </button>
    <div class="navbar-collapse offcanvas-collapse z-3" id="navbarsExampleDefault" style="--blur-lvl: 0.2">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 bg-semi-transparent">
        <li class="nav-item">
          <a class="nav-link <?= $CurrentPage === 'AdminDashboard' ? 'active' : '' ?>" aria-current="page" href="../Admin/AdminDashboard">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $CurrentPage === 'Reports' ? 'active' : '' ?>" aria-current="page" href="../Admin/Reports">Reports</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="javascript:void(0)" data-bs-toggle="dropdown" aria-expanded="false"> Accounts </a>
          <ul class="dropdown-menu bg-blur-5 bg-semi-transparent shadow">
            <li><span class="dropdown-item-text text-nowrap text-success text-center text-capitalize">User Management</span></li>
            <li>
              <a class="dropdown-item" href="javascript:void(0)">
                <div class="hstack">
                  <i class="bi bi-person-badge me-2"></i>
                  <div class="vstack">
                    <span>Coordinators</span>
                    <small class="text-muted" style="font-size: 0.7em">Add, edit, and coordinator accounts.</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0)">
                <div class="hstack">
                  <i class="bi bi-person me-2"></i>
                  <div class="vstack">
                    <span>Students</span>
                    <small class="text-muted" style="font-size: 0.7em">Add, edit, student accounts</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0)">
                <div class="hstack">
                  <i class="bi bi-person-circle me-2"></i>
                  <div class="vstack">
                    <span>Supervisors</span>
                    <small class="text-muted" style="font-size: 0.7em">View all supervisor accounts.</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0)">
                <div class="hstack">
                  <div class="vstack">
                    <span>All Users</span>
                    <small class="text-muted" style="font-size: 0.7em">View and manage all user accounts</small>
                  </div>
                  <span class="badge bg-danger rounded-pill ms-auto" id="totalUsersBadge">0</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $CurrentPage === 'Companies' ? 'active' : '' ?>" aria-current="page" href="../Admin/Companies">Companies</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-capitalize <?= in_array($CurrentPage, ['Batches', 'Programs']) ? 'active' : '' ?>" href="javascript:void(0)" data-bs-toggle="dropdown" aria-expanded="false"> Academic</a>
          <ul class="dropdown-menu bg-blur-5 bg-semi-transparent shadow">
            <li>
              <a class="dropdown-item <?= $CurrentPage === 'Batches' ? 'nav-active' : '' ?>" href="../Admin/Batches">
                <div class="hstack">
                  <i class="bi bi-calendar-event me-2"></i>
                  <div class="vstack">
                    <span>Batches</span>
                    <small class="text-muted" style="font-size: 0.7em">School year / semester setup</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item <?= $CurrentPage === 'Programs' ? 'nav-active' : '' ?>" href="../Admin/Programs">
                <div class="hstack">
                  <i class="bi bi-building me-2"></i>
                  <div class="vstack">
                    <span>Programs</span>
                    <small class="text-muted" style="font-size: 0.7em">BSIT, BSCS, required hours.</small>
                  </div>
                </div>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="https://placehold.co/30x30?text=No+Photo" alt="Profile" id="navProfilePhoto" class="rounded-circle" style="width: 30px; height: 30px; object-fit: cover" />
          </a>
          <ul class="dropdown-menu bg-blur-5 bg-semi-transparent shadow">
            <li>
              <div class="hstack">
                <img src="https://placehold.co/30x30?text=No+Photo" alt="Profile" id="dropdownProfilePhoto" class="rounded-circle mx-3" style="width: 30px; height: 30px; object-fit: cover" />
                <div>
                  <span class="dropdown-item-text text-nowrap ps-0 pb-0"><strong id="userName"></strong> </span>
                  <small class="text-muted dropdown-item-text ps-0 pt-0"><?= $_SESSION['user_role'] === 'admin' ? 'Administrator' : $_SESSION['user_role'] ?></small>
                </div>
              </div>
            </li>
            <li>
              <hr class="dropdown-divider" />
            </li>
            <li>
              <span class="dropdown-item-text text-nowrap text-success text-center text-capitalize">System Management</span>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0)">
                <div class="hstack">
                  <i class="bi bi-person-circle me-2"></i>
                  <div class="vstack">
                    <span>Profile</span>
                    <small class="text-muted" style="font-size: 0.7em">View and edit your profile information.</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0)">
                <div class="hstack">
                  <i class="bi bi-file-earmark me-2"></i>
                  <div class="vstack">
                    <span>Audit Logs</span>
                    <small class="text-muted" style="font-size: 0.7em">View your login history and account activity.</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0)">
                <div class="hstack">
                  <i class="bi bi-gear me-2"></i>
                  <div class="vstack">
                    <span>Settings</span>
                    <small class="text-muted" style="font-size: 0.7em">Manage your account settings and preferences.</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
                <a class="dropdown-item" href="javascript:void(0)" id="signOutBtn">
                <div class="hstack">
                  <i class="bi bi-box-arrow-right me-2"></i>
                  <div class="vstack">
                    <span>Sign Out</span>
                    <small class="text-muted" style="font-size: 0.7em">Sign out of your account securely.</small>
                  </div>
                </div>
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
