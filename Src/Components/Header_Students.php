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
          <a class="nav-link <?= $CurrentPage === 'CoordinatorDashboard' ? 'active' : '' ?>" aria-current="page" href="../Coordinator/CoordinatorDashboard">Dashboard</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= in_array($CurrentPage, ['MyStudents', 'Requirements']) ? 'active' : '' ?>" href="javascript:void(0)" data-bs-toggle="dropdown" aria-expanded="false"> Students</a>
          <ul class="dropdown-menu bg-blur-5 bg-semi-transparent shadow">
              <a class="dropdown-item" href="javascript:void(0)">
                <div class="hstack">
                  <i class="bi bi-person-badge me-2"></i>
                  <div class="vstack">
                    <span>My Students</span>
                    <small class="text-muted" style="font-size: 0.7em">View and manage students under your supervision.</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item <?= $CurrentPage === 'Requirements' ? 'nav-active' : '' ?>" href="../Coordinator/Requirements">
                <div class="hstack">
                  <i class="bi bi-person me-2"></i>
                  <div class="vstack">
                    <span>Requirements</span>
                    <small class="text-muted" style="font-size: 0.7em">View and manage student requirements and submissions.</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-capitalize <?= in_array($CurrentPage, ['Applications', 'DTR_Approvals', 'journals', 'evaluations']) ? 'active' : '' ?>" href="javascript:void(0)" data-bs-toggle="dropdown" aria-expanded="false"> OJT Process</a>
          <ul class="dropdown-menu bg-blur-5 bg-semi-transparent shadow">
            <li>
              <a class="dropdown-item <?= $CurrentPage === 'Applications' ? 'nav-active' : '' ?>" href="javascript:void(0)">
                <div class="hstack">
                  <i class="bi bi-calendar-event me-2"></i>
                  <div class="vstack">
                    <span>Applications</span>
                    <small class="text-muted" style="font-size: 0.7em">View and manage student OJT applications and placements.</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item <?= $CurrentPage === 'DTR_Approvals' ? 'nav-active' : '' ?>" href="javascript:void(0)">
                <div class="hstack">
                  <i class="bi bi-building me-2"></i>
                  <div class="vstack">
                    <span>DTR Approvals</span>
                    <small class="text-muted" style="font-size: 0.7em">View and manage daily time records and approvals.</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item <?= $CurrentPage === 'journals' ? 'nav-active' : '' ?>" href="javascript:void(0)">
                 <div class="hstack">
                  <i class="bi bi-journal-text me-2"></i>
                  <div class="vstack">
                    <span>Journals</span>
                    <small class="text-muted" style="font-size: 0.7em">View and manage student daily journals and reflections.</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item <?= $CurrentPage === 'evaluations' ? 'nav-active' : '' ?>" href="javascript:void(0)">
                 <div class="hstack">
                  <i class="bi bi-clipboard-check me-2"></i>
                  <div class="vstack">
                    <span>Evaluations</span>
                    <small class="text-muted" style="font-size: 0.7em">View and manage student performance evaluations and feedback.</small>
                  </div>
                </div>
              </a>
            </li>
          </ul>
        </li>
                <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-capitalize <?= in_array($CurrentPage, ['Companies', 'visits']) ? 'active' : '' ?>" href="javascript:void(0)" data-bs-toggle="dropdown" aria-expanded="false"> Companies</a>
          <ul class="dropdown-menu bg-blur-5 bg-semi-transparent shadow">
            <li>
              <a class="dropdown-item <?= $CurrentPage === 'Companies' ? 'nav-active' : '' ?>" href="javascript:void(0)">
                 <div class="hstack">
                  <i class="bi bi-building me-2"></i>
                  <div class="vstack">
                    <span>Companies</span>
                    <small class="text-muted" style="font-size: 0.7em">View and manage company profiles and partnerships.</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item <?= $CurrentPage === 'visits' ? 'nav-active' : '' ?>" href="javascript:void(0)">
                 <div class="hstack">
                  <i class="bi bi-briefcase me-2"></i>
                  <div class="vstack">
                    <span>Visits</span>
                    <small class="text-muted" style="font-size: 0.7em">Schedule and manage company visits and meetings.</small>
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
                  <small class="text-muted dropdown-item-text ps-0 pt-0" id="userRole"></small>
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
              <a class="dropdown-item <?= $CurrentPage === 'viewProfile' ? 'nav-active' : '' ?>" href="../Coordinator/viewProfile">
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
              <a class="dropdown-item <?= $CurrentPage === 'settings' ? 'nav-active' : '' ?>" href="javascript:void(0)">
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
