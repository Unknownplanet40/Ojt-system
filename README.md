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
| Manage batches | ✅ | ❌ | ❌ | ❌ |
| Manage companies | ✅ | ❌ | ❌ | ❌ |
| Manage programs | ✅ | ❌ | ❌ | ❌ |
| View admin dashboard | ✅ | ❌ | ❌ | ❌ |
| View coordinator dashboard | ❌ | ✅ | ❌ | ❌ |
| Review student requirements | ❌ | ✅ | ❌ | ❌ |
| View coordinator profile | ❌ | ✅ | ❌ | ❌ |
| Submit pre-OJT requirements | ❌ | ❌ | ✅ | ❌ |
| View student dashboard | ❌ | ❌ | ✅ | ❌ |
| Set up student profile | ❌ | ❌ | ✅ | ❌ |
| Access company documents | ✅ | ✅ | ❌ | ❌ |
| View supervisor dashboard | ❌ | ❌ | ❌ | 🔧 |

> 🔧 = in progress

---

## Modules

### Admin
- **Batches** — create and manage academic batches, set OJT hours, and activate or close batches with confirmation safeguards
- **Companies** — accredit partner companies, manage slots per batch, track MOA validity, and handle document uploads
- **Programs** — manage academic programs with per-program required hour overrides
- **Students** — create/edit/view students, reset passwords, activate/deactivate accounts, and run bulk import with validation preview + credentials export
- **Coordinator Accounts** — create/edit/view coordinator accounts, reset passwords, activate/deactivate accounts, and monitor assigned student counts
- **Audit Logs** — read-only unified activity trail from `activity_log` and `login_audit_log` with date/user/action/module/source filters, search, CSV export, and detailed log inspector modal
- **Dashboard** — stat cards, recent activity, and needs-attention alerts

### Coordinator
- **Dashboard** — summary of assigned students, upcoming visits, company info, and hours progress
- **Requirements Review** — approve or return student-submitted documents with feedback
- **Profile** — profile setup and read-only view page

### Student
- **Requirements** — upload and track pre-OJT document submissions
- **Profile** — profile setup tied to role and program

### Security
- Role-based access control across pages and endpoints
- Sensitive documents are served through `file_serve.php` instead of direct static links
- Document access is checked against the logged-in user role before files are streamed
- Password hashing uses PHP's `password_hash()`

---

## Architecture at a glance

The project is split into a few clear layers so PHP pages stay thin and business logic stays reusable.

- **`Src/Pages/`**: role-specific UI pages (auth, dashboard, profile, requirements, admin tools)
- **`Src/Components/`**: reusable layout parts and cards
- **`functions/`**: core backend logic (auth, batch, profile)
- **`process/`**: request handlers for auth, batch, and profile operations
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
- Audit log listing/export endpoints: `process/audit_logs/` with logic in `functions/audit_log_functions.php`
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
│   ├── batch_functions.php
│   ├── bulk_student_functions.php
│   ├── coordinator_functions.php
│   ├── audit_log_functions.php
│   ├── program_functions.php
│   ├── student_functions.php
│   └── profile_functions.php
├── process/
│   ├── auth/
│   ├── audit_logs/
│   ├── batches/
│   ├── coordinators/
│   ├── programs/
│   ├── students/
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

- **Supervisor module** — pages exist, but dashboard/core workflows are still incomplete
- **Needs-attention alerts** — some criteria are still placeholder logic
- **Dashboard activity feed** — currently limited and will expand over time
- **Setup wizard** — planned for end-of-development; first admin account is currently seeded manually
- **No live demo** — to be deployed once the system reaches a stable state
- **SQL seed files** — not included yet

---

## Changelog

### Unreleased — Working tree summary *(April 2026)*

This summary is based on the current local git working tree.

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
