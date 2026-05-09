# OJT Coordinator System

A web-based On-the-Job Training management platform built for academic institutions in the Philippines. The system handles the full OJT lifecycle — from student pre-requirements submission and company accreditation, to coordinator oversight and batch management — under one roof.

This is a rebuilt version of the original OJT system. The goal of the rewrite was simple: keep the workflow, lose the structural debt. The current codebase uses a cleaner folder layout, bundled libraries, and a more maintainable set of PHP modules.

> **Status:** Under active development. Core modules are functional; some features are still being wired up.

---

## Who this is for

This system is designed for Philippine academic institutions that run OJT programs. If your school manages dozens or hundreds of students every semester — coordinating with companies, tracking document submissions, and watching MOA validity dates like a hawk — this is built for that exact workflow.

---

## What it does

The platform gives each role a focused workspace instead of making everyone wade through the same generic dashboard.

- **Admins** manage batches, companies, programs, and account setup.
- **Coordinators** review student requirements and monitor assigned students.
- **Students** set up profiles, submit pre-OJT documents, and track status.
- **Supervisors** have scaffolded pages and are still being fleshed out.

---

## Tech stack

| Layer | Technologies |
|---|---|
| Frontend | HTML5, CSS3, JavaScript, Bootstrap 5, jQuery |
| Backend | PHP 7.4+ (MySQLi) |
| Database | MySQL / MariaDB |
| Animations | Anime.js, AOS (Animate On Scroll) |
| UX extras | SweetAlert2, Driver.js, Quill 2.0 |
| Optional | PHPMailer (email), Ratchet (WebSockets), mPDF (PDF generation), PhpSpreadsheet (Excel/spreadsheets) |

All frontend libraries are bundled locally inside `/libs`, so the project does not depend on external CDNs.

---

## Roles & permissions

| Feature | Admin | Coordinator | Student | Supervisor |
|---|:---:|:---:|:---:|:---:|
| **Authentication & Account** | | | | |
| Login / logout | ✅ | ✅ | ✅ | ✅ |
| Forgot password / reset | ✅ | ✅ | ✅ | ✅ |
| Change password (voluntary) | ✅ | ✅ | ✅ | ✅ |
| Forced password change (first login) | ✅ | ✅ | ✅ | ✅ |
| Setup own profile | ✅ | ✅ | ✅ | ✅ |
| Edit own profile | ✅ | ✅ | ⚠️ | ✅ |
| User preferences & appearance settings | ✅ | ✅ | ✅ | ✅ |
| **Admin Management** | | | | |
| View admin dashboard | ✅ | ❌ | ❌ | ❌ |
| Manage batches (create, activate, close) | ✅ | ❌ | ❌ | ❌ |
| Manage programs (create, edit, toggle) | ✅ | ❌ | ❌ | ❌ |
| Manage companies (create, edit, MOA upload) | ✅ | ✅ | ❌ | ❌ |
| Manage coordinator accounts | ✅ | ❌ | ❌ | ❌ |
| Manage student accounts (all students) | ✅ | ⚠️ | ❌ | ❌ |
| Manage supervisor accounts | ✅ | ✅ | ❌ | ❌ |
| Bulk student import (CSV/XLSX) | ✅ | ✅ | ❌ | ❌ |
| View audit log | ✅ | ⚠️ | ❌ | ❌ |
| **Reports & Analytics** | | | | |
| View system-wide analytics & charts | ✅ | ❌ | ❌ | ❌ |
| Export institutional-grade PDF reports | ✅ | ✅ | ❌ | ❌ |
| Monitor company performance ratings | ✅ | ✅ | ❌ | ❌ |
| View coordinator visitation logs | ✅ | ✅ | ❌ | ❌ |
| Admin system settings | ✅ | ❌ | ❌ | ❌ |
| Reassign student to another coordinator | ✅ | ❌ | ❌ | ❌ |
| View all grades across coordinators | ✅ | ❌ | ❌ | ❌ |
| **Coordinator Workflow** | | | | |
| View coordinator dashboard | ❌ | ✅ | ❌ | ❌ |
| Create & manage own students | ⚠️ | ✅ | ❌ | ❌ |
| Reset student password | ✅ | ✅ | ❌ | ❌ |
| Approve / return student requirements | ⚠️ | ✅ | ❌ | ❌ |
| Review OJT applications (approve/return/reject) | ❌ | ✅ | ❌ | ❌ |
| Generate endorsement letter | ❌ | ✅ | ❌ | ❌ |
| Confirm OJT start & assign supervisor | ❌ | ✅ | ❌ | ❌ |
| Monitor student DTR & hours progress | ⚠️ | ✅ | ❌ | ❌ |
| Override / manually approve rejected DTR | ❌ | ✅ | ❌ | ❌ |
| Review weekly journals & add remarks | ❌ | ✅ | ❌ | ❌ |
| Approve / return weekly journals | ❌ | ✅ | ❌ | ❌ |
| View evaluations submitted by supervisor | ❌ | ✅ | ❌ | ❌ |
| Compute & finalize student grade | ❌ | ✅ | ❌ | ❌ |
| Schedule & log company visits | ❌ | ✅ | ❌ | ❌ |
| View & export reports (own students) | ✅ | ✅ | ❌ | ❌ |
| **Student Workflow** | | | | |
| View student dashboard | ❌ | ❌ | ✅ | ❌ |
| Upload pre-OJT requirement documents | ❌ | ❌ | ✅ | ❌ |
| Browse & apply to available companies | ❌ | ❌ | ✅ | ❌ |
| Withdraw / resubmit OJT application | ❌ | ❌ | ✅ | ❌ |
| Download endorsement letter | ❌ | ✅ | ✅ | ❌ |
| Log daily DTR (time-in, time-out) | ❌ | ❌ | ✅ | ❌ |
| Edit / delete pending DTR entries | ❌ | ❌ | ✅ | ❌ |
| Submit backdated DTR (up to 3 days) | ❌ | ❌ | ✅ | ❌ |
| View hours progress & completion status | ❌ | ✅ | ✅ | ✅ |
| Submit weekly journal | ❌ | ❌ | ✅ | ❌ |
| Edit & resubmit returned journal | ❌ | ❌ | ✅ | ❌ |
| Submit self-evaluation form | ❌ | ❌ | ✅ | ❌ |
| View own finalized grade | ❌ | ❌ | ✅ | ❌ |
| View application status & history | ❌ | ✅ | ✅ | ❌ |
| Edit section in student profile | ❌ | ✅ | ✅ | ❌ |
| **Supervisor Workflow** | | | | |
| View supervisor dashboard | ❌ | ❌ | ❌ | ✅ |
| View assigned students only | ❌ | ❌ | ❌ | ✅ |
| Approve / reject DTR entries | ❌ | ❌ | ❌ | ✅ |
| Bulk approve non-backdated DTR | ❌ | ❌ | ❌ | ✅ |
| Submit midterm evaluation | ❌ | ❌ | ❌ | ✅ |
| Submit final evaluation | ❌ | ❌ | ❌ | ✅ |
| View student weekly journals | ❌ | ❌ | ❌ | ✅ |
| View own company info | ❌ | ❌ | ❌ | ✅ |

**Legend:**
✅ Full access | ⚠️ Partial / scoped access | ❌ No access

---

## Modules

### Admin
- **Batches** — create and manage academic batches, set OJT hours, and activate or close batches with confirmation safeguards
- **Companies** — accredit partner companies, manage slots per batch, track MOA validity, and handle document uploads
- **Programs** — manage academic programs with per-program required hour overrides
- **Students** — create/edit/view students, reset passwords, activate/deactivate accounts, and run bulk import with validation preview + credentials export
- **Coordinator Accounts** — create/edit/view coordinator accounts, reset passwords, activate/deactivate accounts, and monitor assigned student counts
- **Supervisors** — view and manage supervisor accounts linked to accredited companies
- **Requirements Overview** — admin-level view of all student requirement statuses across the active batch
- **Audit Logs** — read-only unified activity trail from `activity_log` and `login_audit_log` with date/user/action/module/source filters, search, CSV export, and detailed log inspector modal
- **Dashboard** — stat cards, recent activity, and needs-attention alerts
- **Reports & Analytics** — interactive data visualization dashboard using Chart.js, providing insights into placement distribution, completion rates, and industry partner performance with professional PDF export functionality.

### Coordinator
- **Dashboard** — summary of assigned students, upcoming visits, company info, and hours progress
- **My Students** — card-based student directory with search/filter, add/edit/reset-password/activate-deactivate, bulk import, and PDF/CSV credential export
- **Student Profile** — consolidated view showing full student info, placement details, OJT progress, requirements, DTR entries, and journals with action buttons (Edit, Reset Password, Activate/Deactivate)
- **Requirements Review** — approve or return student-submitted documents with feedback
- **Applications Review** — review applications through `pending → approved → endorsed → active` with guarded transitions and notes
- **Journals Review** — view and review student weekly journal submissions
- **Grading** — compute, adjust, and finalize OJT grades per student using a weighted multi-component rubric
- **Profile** — profile setup and read-only view page

### Student
- **Requirements** — upload and track pre-OJT document submissions
- **Applications** — apply to available companies, track timeline/status, withdraw when allowed, and download endorsement once endorsed
- **Evaluations** — submit self-evaluation once final OJT requirements are met
- **DTR & Journals** — log daily work hours and weekly reflections (unlocked after application approval)
- **Profile** — profile setup tied to role and program

### Supervisor
- **Dashboard** — view assigned student list and their OJT progress
- **Evaluations** — conduct Midterm (at 50% hours) and Final (at 100% hours) evaluations for assigned students
- **Profile** — manage supervisor profile and company affiliation

### Evaluation Module (New)
- **Multi-role Rubric** — standardized 1-5 scale evaluation across 5 key performance criteria
- **Hour-based Triggers** — evaluations are automatically unlocked based on student's logged DTR hours
- **Self-Evaluation** — allows students to reflect on their own performance at the end of the OJT period
- **Monitoring** — coordinators can view and download completed evaluations for grading purposes

### Security
- Role-based access control across pages and endpoints
- **OJT Process Locking** — DTR, Journals, and Evaluations are strictly locked until a student has an active/accepted application
- **Milestone Enforcement** — Midterm/Final evaluations are locked until specific hour thresholds are met in the DTR
- **Grade Finalization Lock** — finalized grades are immutable; only unfinalised records can be adjusted
- Sensitive documents are served through `file_serve.php` instead of direct static links
- Document access is checked against the logged-in user role before files are streamed
- Password hashing uses PHP's `password_hash()`
- Standardized AJAX routing with clean URLs (no `.php` extensions)

---

## Architecture at a glance

The project is split into a few clear layers so PHP pages stay thin and business logic stays reusable.

- **`Src/Pages/`**: role-specific UI pages (auth, dashboard, profile, requirements, admin tools)
- **`Src/Components/`**: reusable layout parts and cards
- **`functions/`**: core backend logic (auth, batch, profile, grading, evaluations, journals, DTR)
- **`process/`**: request handlers for auth, batch, profile, grades, and all OJT modules
- **`Assets/Script/`**: client-side behavior by page/module
- **`Assets/style/`**: shared style layer
- **`uploads/`**: uploaded files served via guarded access

Typical request flow:

1. A page loads from `Src/Pages/`
2. JavaScript sends a request to `process/`
3. The endpoint uses `functions/` logic
4. JSON is returned to the UI

---

## Current implementation notes

- Login/session bootstrap: `process/auth/login.php`
- Password change: `process/auth/changepass.php`
- Password reset flow: handlers under `process/auth/`
- Batch lifecycle endpoints: `process/batches/` with logic in `functions/batch_functions.php`
- Program lifecycle endpoints: `process/programs/` with logic in `functions/program_functions.php`
- Student lifecycle endpoints: `process/students/` with logic in `functions/student_functions.php` and `functions/bulk_student_functions.php`
- Coordinator account lifecycle endpoints: `process/coordinators/` with logic in `functions/coordinator_functions.php`
- Requirements lifecycle endpoints: `process/requirements/` with logic in `functions/requirement_functions.php`
- Applications lifecycle endpoints: `process/applications/` with logic in `functions/application_functions.php`
- Grading lifecycle endpoints: `process/grades/` with logic in `functions/grade_functions.php`
- Journal lifecycle endpoints: `process/journals/` with logic in `functions/journal_functions.php`
- DTR lifecycle endpoints: `process/dtr/` with logic in `functions/dtr_functions.php`
- Evaluation lifecycle endpoints: `process/evaluation/` with logic in `functions/evaluation_functions.php`
- Profile fetch/save endpoints: `process/profile/` with logic in `functions/profile_functions.php`
- Secure file delivery: `file_serve.php`
- DB connection: `config/db.php` (MySQLi, `utf8mb4`)

---

## Secure document serving

`file_serve.php` sits between the user and the uploaded file. It validates session state, role, and file path before streaming as either inline view or forced download.

Example usage:

```text
file_serve.php?uuid=<document-uuid>&for=companyView&action=inline
file_serve.php?uuid=<document-uuid>&for=companyView&action=download

# requirement files (student/coordinator/admin access-checked)
file_serve.php?type=requirement&req_uuid=<requirement-uuid>
file_serve.php?type=requirement&req_uuid=<requirement-uuid>&action=download
```

---

## Authentication flow

Login routes users based on account state:

1. First login with temporary password → forced password change
2. Profile not yet set up → redirected to role-specific profile page
3. Profile complete → redirected to the appropriate dashboard

Forgot password and voluntary password change flows are also implemented.

---

## First login & system setup

There is no public registration page by design. Accounts are seeded and managed by an admin.

A setup wizard is part of the roadmap and will be finalized near the end of development. Until that is completed, the first admin account must still be created manually in the database. After that, the normal login flow takes over: password change, profile setup, then dashboard access.

---

## Configuration reference

The main database connection settings live in `config/db.php`.

| Setting | Description | Example |
|---|---|---|
| `host` | Database host | `localhost` |
| `dbname` | Database name | `ojt_system` |
| `username` | MySQL username | `root` |
| `password` | MySQL password | `""` |
| `charset` | Connection charset | `utf8mb4` |

---

## Project structure

```text
Ojt-system/
├── Assets/
│   ├── Images/
│   ├── Script/
│   ├── style/
│   └── SystemInfo.php
├── config/
│   └── db.php
├── functions/
│   ├── auth_functions.php
│   ├── application_functions.php
│   ├── batch_functions.php
│   ├── requirement_functions.php
│   ├── bulk_student_functions.php
│   ├── coordinator_functions.php
│   ├── audit_log_functions.php
│   ├── program_functions.php
│   ├── student_functions.php
│   ├── profile_functions.php
│   ├── dtr_functions.php
│   ├── journal_functions.php
│   ├── evaluation_functions.php
│   └── grade_functions.php
├── process/
│   ├── applications/
│   ├── auth/
│   ├── audit_logs/
│   ├── batches/
│   ├── coordinators/
│   ├── requirements/
│   ├── programs/
│   ├── students/
│   ├── dtr/
│   ├── journals/
│   ├── evaluation/
│   ├── grades/
│   └── profile/
├── Src/
│   ├── Components/
│   └── Pages/
├── libs/
├── uploads/
├── file_serve.php
├── index.html
├── .htaccess
└── InstallDependencies.md
```

---

## Known issues & limitations

- **Needs-attention alerts** — some criteria are still placeholder logic
- **Dashboard activity feed** — currently limited and will expand over time
- **Setup wizard** — planned for end-of-development; first admin account is currently seeded manually
- **No live demo** — to be deployed once the system reaches a stable state
- **SQL seed files** — not included yet
- **Grade finalization UI** — backend complete; coordinator grading dashboard frontend still being refined
- **Coordinator visit scheduling** — `coordinator_visits` table exists; scheduling UI not yet started

---

## Changelog

### May 2026 (Part 2) — Reports, Analytics & Visitation Monitoring

- **Reports & Analytics Module (new)**
  - Integrated **Chart.js** for dynamic, interactive data visualizations on the Admin dashboard.
  - Features: Placement Status distribution (Line/Pie), Program Enrollment breakdown, and Monthly Activity trends.
  - **Top Company Partners** UI refactor: migrated from legacy tables to a modern, high-profile card-based layout featuring real performance ratings.
  - Real-time rating engine: average scores are now dynamically computed from the `evaluations` database.

- **Institutional PDF Export Engine**
  - Developed a professional, document-ready export utility using **mPDF**.
  - Features: Official school branding, header metadata, executive summaries, and themed data tables.
  - Applied to both general analytics reports and specific visitation monitoring logs.

- **Visitation Monitoring Enhancement**
  - Implemented the **Export Visitation Report** feature for the Admin Visits module.
  - Added filter-sensitive exports (Status, Coordinator, Company) to generate targeted administrative logs.
  - Refined the visit details modal with a cleaner "Glass-UI" aesthetic and better findings visibility.

- **UI/UX & Design System**
  - Standardized "Glassmorphism" design tokens across all reporting components.
  - Enhanced responsive layouts for analytics cards, ensuring compatibility across different screen sizes and orientations.
  - Optimized chart performance and rendering lifecycle within the dashboard modules.

### May 2026 (Part 1) — Grading System, Coordinator My Students, & Module Expansions

- **Grading System (new)**
  - Added `functions/grade_functions.php` with full weighted grade computation logic
  - Added `process/grades/` handler suite:
    - `compute_grade.php` — auto-calculates scores from hours, evaluations, and journals
    - `save_grade.php` — saves coordinator-adjusted grade components
    - `finalize_grade.php` — locks the grade record and sets `finalized_at`
    - `get_grade.php` — fetches a single student’s grade breakdown
    - `get_all_grades.php` — fetches all grades for a batch (coordinator view)
    - `get_grading_overview.php` — dashboard-level summary of grade statuses
  - Added `ojt_grades` and `coordinator_visits` tables to the database schema
  - Grade components: hours (20%), midterm eval (20%), final eval (40%), journals (10%), self-eval (10%) — weights are stored per record

- **Coordinator My Students module (new)**
  - Added page: `Src/Pages/Coordinator/MyStudents.php`
  - Added script: `Assets/Script/CoordinatorScripts/MyStudentsScripts.js`
  - Features: card-based student grid, search/filter by program/status, view student profile modal, edit student details, reset password with PDF export, single-account creation, and full bulk import (validate → preview → create → export credentials)
  - Coordinators can now manage their own students independently, without going through the Admin panel

- **Admin Requirements module (new)**
  - Added page: `Src/Pages/Admin/Requirements.php`
  - Added script: `Assets/Script/AdminScripts/RequirementsScripts.js`
  - Admins can now view all student requirement statuses across the active batch

- **Admin Supervisors module (new)**
  - Added/updated `Src/Pages/Admin/Supervisors.php`
  - Admin-level view and management of supervisor accounts linked to accredited companies

- **Journal module expansion (multi-role)**
  - Added Journal pages for Coordinator (`Src/Pages/Coordinator/Journal.php`), Student, and Supervisor roles
  - Added Journal scripts for all three roles
  - Coordinators can now review and approve/return student journal submissions
  - Supervisors can view journals of students under their supervision
  - Enhanced journal date handling with validation and sensible defaults

- **DTR improvements**
  - Added `process/dtr/edit_dtr.php` for coordinator/admin corrections to DTR entries
  - Updated DTR scripts for coordinator and student roles

- **Database schema additions** (`DATABASE_SCHEMA.md` updated)
  - `ojt_grades` table — stores finalized weighted grade per student per batch
  - `coordinator_visits` table — tracks scheduled and completed company visits
  - `weekly_journals` and `evaluations` table documentation finalized

- **Coordinator My Students — Student Account Management**
  - Coordinators can now activate/deactivate student accounts for their assigned students
  - Updated `process/students/deactivate_student.php` to support coordinator role with ownership verification
  - Added toggle status buttons with dynamic styling and confirmation dialogs
  - Account status changes reflected immediately in UI with appropriate visual feedback

- **Student Profile Page — UI Consolidation**
  - Unified student profile view: removed redundant modal from MyStudents page
  - Single comprehensive profile page featuring:
    - Full student details, contact information, placement/supervisor assignment
    - OJT progress metrics with hours progress bar
    - Requirements, recent DTR entries, and journals in tabbed interface
    - Quick action buttons: Edit Profile, Reset Password, Activate/Deactivate Account
    - Context-aware back button that returns to originating page (MyStudents or Companies module)
    - Dynamic breadcrumb navigation reflecting entry point

- **Journal Module — Export Restriction**
  - Restricted journal PDF exports to students only
  - Updated `process/journal/export_journal_pdf.php` to enforce student-only authorization
  - Removed export buttons from Coordinator and Supervisor journal views
  - Non-student export attempts receive 403 Unauthorized response

- **Navigation & User Experience**
  - Implemented referrer tracking for intelligent back button navigation
  - Smart breadcrumb updates dynamically based on page entry point
  - View Student buttons now redirect to full profile page instead of opening modal
  - Improved overall navigation flow between My Students, Companies, and Student Profile pages

### Early May 2026 — Evaluation Module & UX Overhaul

- **Evaluation Module Completion**
  - Implemented full logic for Students (self-eval), Supervisors (midterm/final), and Coordinators (monitoring)
  - Added criteria-based rubric scoring system with automatic average calculation
  - Integrated with DTR hours for automatic "Lock/Unlock" triggers at 50% and 100% milestones
  - Added module process guides for each role to explain evaluation triggers

- **UX/UI Modernization**
  - Replaced legacy table-based displays with responsive, high-performance card-based grids in:
    - `Coordinator Evaluations`
    - `Admin Audit Logs`
  - Standardized "Glassmorphism" design language across all new dashboards
  - Improved `Incomplete Requirements` modal with accurate real-time status indicators and danger/success color-coding

- **Security & Access Control Hardening**
  - Implemented **Active Application Lock**: DTR, Journals, and Evaluations now verify application status before allowing access
  - Refactored student header to include a full-screen blurred security overlay for unauthorized page attempts
  - Normalized all AJAX endpoints to use clean URLs (removed `.php` extensions) across the Student, Supervisor, and Coordinator dashboards
  - Fixed database connection inconsistency (migrated all lingering PDO calls to the standard MySQLi `$conn` object)

### Unreleased — Working tree summary *(April 2026)*

This summary is based on the current local git working tree.

- **Applications module refactor and expansion (new process/functions architecture)**
  - Migrated coordinator/student application flows to `functions/application_functions.php` + `process/applications/*`
  - Added transition-safe coordinator actions:
    - `approve_application.php`
    - `return_application.php`
    - `reject_application.php`
    - `endorse_application.php`
    - `confirm_start.php`
  - Added student-side application endpoints:
    - `get_student_application.php`
    - `get_available_companies.php`
    - `submit_application.php`
    - `withdraw_application.php`
    - `download_endorsement.php`
  - Added status timeline logging and student-side status tracking helpers

- **Pre-OJT Requirements module hardening**
  - Added/standardized requirements handlers under `process/requirements/` for student upload and coordinator review
  - Enforced coordinator ownership checks in requirement approval/return flows
  - Fixed upload path resolution to project-root uploads directory
  - Added student note persistence (`student_note`) on requirement upload and review display
  - Preserved `canStudentApply()` gate (all 6 approved)

- **Secure requirement file streaming**
  - Extended `file_serve.php` with a dedicated `type=requirement` route
  - Added role-aware access checks:
    - students: own requirement files only
    - coordinators: only assigned students
    - admins: view allowed

- **Student bulk import flow (enhanced)**
  - Added complete validate-and-preview workflow before account creation
  - Added coordinator-aware parsing/validation/creation/export in bulk helpers
  - Added re-upload and re-validate UX for fixing CSV/XLSX data quickly
  - Added bulk success summary with created vs failed rows and detail toggle
  - Added credential exports for bulk-created accounts:
    - CSV: `process/students/bulk_export_csv.php`
    - PDF: `process/students/bulk_export_pdf.php`
  - Added active-batch header metadata in student listing endpoint and UI binding for:
    - `#activeBatchLabel`
    - `#activeBatchCount`

- **Admin Coordinator Accounts module (new)**
  - Added page: `Src/Pages/Admin/Coordinators.php`
  - Added script: `Assets/Script/AdminScripts/CoordinatorAccounts.js`
  - Added coordinator logic layer: `functions/coordinator_functions.php`
  - Added coordinator process handlers under `process/coordinators/`:
    - `get_coordinators.php`
    - `get_coordinator.php`
    - `create_coordinator.php`
    - `update_coordinator.php`
    - `deactivate_coordinator.php`
    - `reset_coordinator_password.php`
    - `export_coordinator_pdf.php`
  - Updated `Src/Components/Header.php` Accounts dropdown to route to the new Coordinators module

- **Admin Audit Logs module (new)**
  - Added page: `Src/Pages/Admin/AuditLogs.php`
  - Added script: `Assets/Script/AdminScripts/AuditLogs.js`
  - Added style layer: `Assets/style/admin/AuditLogsStyles.css`
  - Added audit logic layer: `functions/audit_log_functions.php`
  - Added audit process handlers under `process/audit_logs/`:
    - `get_audit_logs.php`
    - `export_audit_logs_csv.php`
  - Implemented a unified, read-only feed combining:
    - `activity_log`
    - `login_audit_log`
  - Added filter set for date range, user, action type, module, source, and text search
  - Added pagination with rows-per-page control
  - Added CSV export for the currently filtered result set
  - Added details modal with structured meta rendering (key/value cards), auth context, and source-aware field handling
  - Added row-level badges for quick visibility:
    - `Meta: Yes/No/N/A`
    - `UA: Yes/No/N/A`
  - Updated header dropdown route to `../Admin/AuditLogs`

- **Audit logging data-quality fixes**
  - Updated `functions/auth_functions.php` login audit insertions to persist `user_agent` for both successful and failed login attempts
  - Hardened audit meta decoding in `functions/audit_log_functions.php` to parse both normal JSON and double-encoded JSON strings

- **Student management module (new)**
  - Added `Src/Pages/Admin/Students.php`
  - Added `Assets/Script/AdminScripts/Students.js`
  - Added `functions/student_functions.php`
  - Added student process handlers under `process/students/` (create/get/update/deactivate/export-related flows)

- **Document export enhancements for student credentials**
  - Added mPDF and PhpSpreadsheet dependencies via Composer (`libs/composer/composer.json`, `composer.lock`, `vendor/` updates)
  - Updated student PDF export flow to load Composer autoload from `libs/composer/vendor/autoload.php`
  - Improved client-side blob handling in `Students.js` to correctly detect `application/pdf` vs JSON error payloads
  - Improved filename handling using `Content-Disposition` when available

- **Security and helper hardening**
  - Updated `helpers/helpers.php`
    - `response()` hardened with stricter headers and safer JSON error handling
    - `generateUuid()` switched to cryptographically secure UUID v4 generation
    - Added `isValidUuid()` helper

- **Programs / dashboard / profile / auth frontend updates**
  - Updated scripts in:
    - `Assets/Script/AdminScripts/ProgramsScripts.js`
    - `Assets/Script/AdminScripts/batchesSripts.js`
    - `Assets/Script/DashboardScripts/{AdminDashboard,CoordinatorDashboardScript,StudentDashboard}.js`
    - `Assets/Script/ProfileScripts/{AdminProfileScript,CoordinatorProfileScript,CoordinatorViewProfileScript,StudentProfileScript,SupervisorProfileScript}.js`
    - `Assets/Script/RedirectScript.js`

- **Page/layout/style updates**
  - Updated:
    - `Src/Components/Header.php`
    - `Src/Pages/Admin/{Programs.php,pagehead.php}`
    - `Src/Pages/Coordinator/pagehead.php`
    - `Src/Pages/Students/{Students_Profile.php,pagehead.php}`
    - `Src/Pages/Login.php`
    - `Assets/style/MainStyle.css`
    - `Assets/SystemInfo.php`

- **Config/runtime changes**
  - Updated `config/db.php` and `config/serverStatus.php`
  - Removed `config/serverConfig.php`
  - Updated `functions/auth_functions.php`

- **Assets added in working tree**
  - New profile image asset under `Assets/Images/profiles/...`
  - Additional style assets under `Assets/style/admin/`

---

## What's next

- Coordinator student management beyond requirements review
- Supervisor module completion
- Finalize setup wizard near the end of development
- Expand dashboard activity and alert feeds
- Improve MOA expiry handling
- Live demo deployment

---

## License

[MIT](LICENSE)
