<?php
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Location: ../../Src/Pages/ErrorPage?error=403');
    exit('Direct access not allowed');
}
?>
<nav class="navbar navbar-expand-lg bg-semi-transparent mx-3 mb-3 border rounded-2" aria-label="Main navigation" id="adminTopNavbar" style="--blur-lvl: <?= $opacitylvl ?>">
  <div class="container-fluid">
    <div class="vstack">
      <a class="navbar-brand pb-0" href="../Login"><?= $ShortTitle ?></a>
      <small class="text-muted" style="font-size: 0.7em" id="currentSemester"></small>
    </div>

    <button class="navbar-toggler p-0 border-0 shadow-none" type="button" id="navbarSideCollapse" aria-label="Toggle navigation">
      <i class="bi bi-list fs-1"></i>
    </button>

    <div class="navbar-collapse offcanvas-collapse z-3" id="navbarsExampleDefault" style="--blur-lvl: 0.2">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 bg-semi-transparent">
        <li class="nav-item">
          <a class="nav-link <?= $CurrentPage === 'SupervisorDashboard' ? 'active' : '' ?>" aria-current="page" href="../Supervisor/SupervisorDashboard">Dashboard</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= in_array($CurrentPage, ['DTR', 'SupervisorDTR']) ? 'active' : '' ?>" href="javascript:void(0)" data-bs-toggle="dropdown" aria-expanded="false"> OJT Process</a>
          <ul class="dropdown-menu bg-blur-5 bg-semi-transparent shadow">
            <li>
              <a class="dropdown-item <?= $CurrentPage === 'DTR' ? 'nav-active' : '' ?>" href="../Supervisor/DTR">
                <div class="hstack">
                  <i class="bi bi-clock me-2"></i>
                  <div class="vstack">
                    <span>Daily Time Record</span>
                    <small class="text-muted" style="font-size: 0.7em">Review and approve student time logs.</small>
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
                  <span class="dropdown-item-text text-nowrap ps-0 pb-0"><strong id="userName"></strong></span>
                  <small class="text-muted dropdown-item-text ps-0 pt-0"><?= isset($_SESSION['user_role']) ? ucfirst($_SESSION['user_role']) : 'Supervisor' ?></small>
                </div>
              </div>
            </li>
            <li><hr class="dropdown-divider" /></li>
            <li>
              <a class="dropdown-item <?= $CurrentPage === 'Supervisor_Profile' ? 'nav-active' : '' ?>" href="../Supervisor/Supervisor_Profile">
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
