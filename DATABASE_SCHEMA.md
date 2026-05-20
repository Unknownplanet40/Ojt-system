# OJT System Database Schema

This is the canonical schema reference for the project, based on the current `ojt_system` SQL dump.

## Database

- **Name:** `ojt_system`
- **Charset:** `utf8mb4`
- **Collation:** `utf8mb4_unicode_ci`

## Core tables

### `users`

Shared account table for all roles.

- `uuid` (PK, unique)
- `email` (unique)
- `password_hash`
- `role` enum: `admin`, `coordinator`, `student`, `supervisor`
- `is_active` (TINYINT, default: 1)
- `must_change_password` (TINYINT, default: 1)
- `welcome_email_sent` (TINYINT, default: 0)
- `theme_preference` (VARCHAR, default: 'dark')
- `last_login_at` (DATETIME, default: NULL)
- `login_attempts` (INT, default: 0)
- `lockout_until` (DATETIME, default: NULL)
- `manual_lockout` (TINYINT, default: 0)
- `created_by` → `users.uuid`
- `created_at` (DATETIME)


### `user_settings`

Stores system-wide and user-specific configuration (themes, security thresholds, etc.).

- `id` (PK, auto-increment)
- `user_uuid` (nullable) → `users.uuid` (Global settings if NULL)
- `setting_key` (VARCHAR, unique with user_uuid)
- `setting_value` (LONGTEXT)
- `updated_by` → `users.uuid`
- `updated_at` (DATETIME)

#### Global Configuration Keys (`user_uuid` IS NULL)

##### Maintenance & Lockout Settings
- `disable_{feature}_submission`: `'1'` (disabled) or `'0'` (enabled). (Features: `dtr`, `journal`, `evaluation`).
- `dtr_maintenance_start` / `dtr_maintenance_end`: Scheduled maintenance ISO-8601 date range for DTR submission lockout.
- `dtr_disable_reason`: Alert message shown to students when DTR submission is disabled.
- `journal_maintenance_start` / `journal_maintenance_end`: Scheduled maintenance ISO-8601 date range for weekly journal submission lockout.
- `journal_disable_reason`: Alert message shown to students when journal submission is disabled.
- `evaluation_maintenance_start` / `evaluation_maintenance_end`: Scheduled maintenance ISO-8601 date range for supervisor evaluation lockout.
- `evaluation_disable_reason`: Alert message shown to supervisors when evaluation submission is disabled.

##### Security Settings
- `lockout_threshold`: Maximum failed login attempts allowed before IP/user lockout.
- `lockout_duration`: Duration of lockout in minutes.
- `lockout_notify_admin`: `'1'` (notify) or `'0'` (do not notify) admin when lockout occurs.

### `admin_profiles`

- `uuid` (PK, unique)
- `user_uuid` → `users.uuid`
- `employee_id`
- `last_name`
- `first_name`
- `middle_name`
- `contact_number`
- `profile_path`
- `profile_name`
- `isProfileDone`

### `coordinator_profiles`

- `uuid` (PK, unique)
- `user_uuid` → `users.uuid`
- `employee_id`
- `last_name`
- `first_name`
- `middle_name`
- `department`
- `profile_path`
- `profile_name`
- `mobile`
- `isProfileDone`

### `supervisor_profiles`

- `uuid` (PK, unique)
- `user_uuid` → `users.uuid`
- `company_uuid` → `companies.uuid`
- `last_name`
- `first_name`
- `position`
- `profile_path`
- `profile_name`
- `department`
- `mobile`
- `is_active`
- `isProfileDone`
- `is_hr_admin` (TINYINT, default: 0)

### `student_profiles`

- `uuid` (PK, unique)
- `user_uuid` → `users.uuid`
- `student_number` (unique)
- `last_name`
- `first_name`
- `middle_name`
- `profile_path`
- `profile_name`
- `program`
- `program_uuid` → `programs.uuid`
- `year_level`
- `section`
- `mobile`
- `home_address`
- `emergency_contact`
- `emergency_phone`
- `coordinator_uuid` → `coordinator_profiles.uuid`
- `supervisor_uuid` (nullable) → `supervisor_profiles.uuid`
- `batch_uuid` → `batches.uuid`
- `company_uuid` → `companies.uuid`
- `isProfileDone`

### `companies`

- `uuid` (PK, unique)
- `name`
- `industry`
- `address`
- `city`
- `email`
- `phone`
- `website`
- `work_setup` enum: `on-site`, `remote`, `hybrid`
- `accreditation_status` enum: `pending`, `active`, `expired`, `blacklisted`
- `blacklist_reason`
- `created_by` → `users.uuid`
- `latitude` (DECIMAL 10,8) - GPS latitude for geofencing (Phase 4)
- `longitude` (DECIMAL 11,8) - GPS longitude for geofencing (Phase 4)
- `geofence_radius` (INT, default: 100) - Geofencing radius limit in meters (Phase 4)

### `company_contacts`

- `uuid` (PK, unique)
- `company_uuid` → `companies.uuid`
- `name`
- `position`
- `email`
- `phone`
- `is_primary`

### `company_documents`

- `uuid` (PK, unique)
- `company_uuid` → `companies.uuid`
- `doc_type` enum: `moa`, `nda`, `insurance`, `bir_cert`, `sec_dti`, `other`
- `file_name`
- `file_path`
- `valid_from`
- `valid_until`
- `uploaded_by` → `users.uuid`

### `company_slots`

- `uuid` (PK, unique)
- `company_uuid` → `companies.uuid`
- `batch_uuid` → `batches.uuid`
- `total_slots`
- unique composite: `(company_uuid, batch_uuid)`

### `batches`

- `uuid` (PK, unique)
- `school_year`
- `semester` enum: `1st`, `2nd`, `summer`
- `start_date`
- `end_date`
- `required_hours`
- `status` enum: `upcoming`, `active`, `closed`
- `created_by` → `users.uuid`
- `activated_by` → `users.uuid`
- `closed_by` → `users.uuid`
- `activated_at`
- `closed_at`

### `programs`

- `uuid` (PK, unique)
- `code` (unique)
- `name`
- `department`
- `required_hours`
- `is_active`
- `created_by` → `users.uuid`

### `company_accepted_programs`

Bridge table between companies and programs.

- `company_uuid` → `companies.uuid`
- `program_uuid` → `programs.uuid`
- unique composite: `(company_uuid, program_uuid)`

### `ojt_applications`

- `id` (PK, auto-increment)
- `uuid` (unique)
- `student_uuid` → `student_profiles.uuid`
- `batch_uuid` → `batches.uuid`
- `company_uuid` → `companies.uuid`
- `cover_letter`
- `status` enum: `pending`, `approved`, `endorsed`, `active`, `needs_revision`, `rejected`, `withdrawn` (default: `pending`)
- `revision_reason`
- `rejection_reason`
- `created_at`
- `updated_at`
- unique composite: `(student_uuid, batch_uuid)` (`uq_student_batch_active`)

### `student_requirements`

- `uuid` (PK, unique)
- `student_uuid` → `student_profiles.uuid`
- `batch_uuid` → `batches.uuid`
- `req_type` enum: `medical_certificate`, `parental_consent`, `insurance`, `nbi_clearance`, `resume`, `guardian_form`
- `status` enum: `not_submitted`, `submitted`, `under_review`, `approved`, `returned`
- `file_name`
- `file_path`
- `student_note`
- `coordinator_note`
- `return_reason`
- `reviewed_by` → `users.uuid`
- `submitted_at`
- `reviewed_at`

## Logging and auth tables

### `activity_log`

- `actor_uuid` → `users.uuid`
- `target_uuid`
- `event_type`
- `description`
- `module`
- `meta`
- `created_at`

### `login_audit_log`

- `user_uuid` → `users.uuid`
- `ip_address`
- `user_agent`
- `success`
- `fail_reason`
- `attempted_at`

### `password_reset_tokens`

- `user_uuid` → `users.uuid`
- `token_hash`
- `expires_at`
- `used`
- `created_at`

### `application_status_logs`

- `application_uuid` → `ojt_applications.uuid`
- `from_status`
- `to_status`
- `changed_by` → `users.uuid`
- `note`
- `created_at`
- `id` (PK, auto-increment)
- `uuid` (unique)
- `application_uuid` → `ojt_applications.uuid`
- `from_status`
- `to_status`
- `reason`
- `actor_uuid` → `users.uuid`
- `created_at` (default: `NOW()`)

### `endorsement_letters`

- `id` (PK, auto-increment)
- `uuid` (unique)
- `application_uuid` (unique) → `ojt_applications.uuid`
- `student_uuid` → `student_profiles.uuid`
- `file_path`
- `file_name`
- `generated_by` (nullable)
- `generated_at` (default: `NOW()`)

### `ojt_start_confirmations`

- `id` (PK, auto-increment)
- `uuid` (unique)
- `application_uuid` (unique) → `ojt_applications.uuid`
- `student_uuid` → `student_profiles.uuid`
- `supervisor_uuid` → `supervisor_profiles.uuid`
- `start_date`
- `expected_end_date` (nullable)
- `working_hours_per_day` (default: `8`)
- `confirmed_by` (nullable)
- `confirmed_at` (default: `NOW()`)

### `dtr_entries`

- `id` (PK, auto-increment)
- `uuid` (unique)
- `student_uuid` → `student_profiles.uuid`
- `application_uuid` → `ojt_applications.uuid`
- `batch_uuid` → `batches.uuid`
- `entry_date`
- `time_in`
- `time_out`
- `lunch_break_minutes` (default: `60`)
- `hours_rendered` (default: `0.00`)
- `activities` (nullable)
- `is_backdated` (default: `0`)
- `backdate_reason` (nullable)
- `status` enum: `pending`, `approved`, `rejected` (default: `pending`)
- `rejection_reason` (nullable)
- `approved_by` (nullable) → `supervisor_profiles.uuid`
- `approved_at` (nullable, default: `NOW()`)
- `approved_by_role` (nullable) - `supervisor` or `coordinator`
- `submitted_at` (default: `NOW()`)
- `updated_at` (default: `NOW()` on update)
- `clock_in_latitude` (DECIMAL 10,8, nullable) - GPS latitude at clock-in (Phase 4)
- `clock_in_longitude` (DECIMAL 11,8, nullable) - GPS longitude at clock-in (Phase 4)
- `clock_out_latitude` (DECIMAL 10,8, nullable) - GPS latitude at clock-out (Phase 4)
- `clock_out_longitude` (DECIMAL 11,8, nullable) - GPS longitude at clock-out (Phase 4)
- `clock_in_photo` (VARCHAR 255, nullable) - Path to clock-in selfie photo (Phase 4)
- `clock_out_photo` (VARCHAR 255, nullable) - Path to clock-out selfie photo (Phase 4)
- UNIQUE: `(student_uuid, entry_date)`

### `dtr_audit_log`

- `id` (PK, auto-increment)
- `uuid` (unique)
- `dtr_uuid` → `dtr_entries.uuid`
- `action` - `submitted`, `approved`, `rejected`, `backdated`, `edited`
- `actor_uuid` → `users.uuid`
- `actor_role`
- `details` (nullable) - JSON formatted
- `created_at` (default: `NOW()`)

### `weekly_journals`

- `id` (PK, auto-increment)
- `uuid` (unique)
- `student_uuid` (FK) → `student_profiles.uuid`
- `application_uuid` (FK) → `ojt_applications.uuid`
- `batch_uuid` (FK) → `batches.uuid`
- `week_number` (TINYINT)
- `week_start` (DATE)
- `week_end` (DATE)
- `accomplishments` (TEXT)
- `skills_learned` (nullable, TEXT)
- `challenges` (nullable, TEXT)
- `plans_next_week` (nullable, TEXT)
- `issues_concerns` (nullable, TEXT)
- `status` enum: `submitted`, `approved`, `returned` (default: `submitted`)
- `return_reason` (nullable, TEXT)
- `coordinator_remarks` (nullable, TEXT)
- `reviewed_by` (nullable, FK) → `users.uuid`
- `reviewed_at` (nullable, DATETIME)
- `submitted_at` (default: `NOW()`)
- `created_at` (default: `NOW()`)
- `updated_at` (default: `NOW()` on update)
- UNIQUE: `(student_uuid, batch_uuid, week_start)`


### `evaluations`

- `id` (PK, auto-increment)
- `uuid` (unique)
- `student_uuid` (FK) → `student_profiles.uuid`
- `application_uuid` (FK) → `ojt_applications.uuid`
- `batch_uuid` (FK) → `batches.uuid`
- `submitted_by` (CHAR(36)) — supervisor or student profile uuid
- `submitted_by_role` enum: `supervisor`, `student`
- `eval_type` enum: `midterm`, `final`, `self`
- scores per criterion (1-5):
  - `technical_skills` (TINYINT, nullable)
  - `work_attitude` (TINYINT, nullable)
  - `communication` (TINYINT, nullable)
  - `teamwork` (TINYINT, nullable)
  - `problem_solving` (TINYINT, nullable)
- self-evaluation fields:
  - `overall_experience` (TINYINT, nullable)
  - `would_recommend` (TINYINT(1), nullable) — 1=yes, 0=no
- overall:
  - `total_score` (DECIMAL(4,2), nullable) — average of criteria
  - `comments` (TEXT, nullable)
- `submitted_at` (DATETIME, default: `NOW()`)
- `updated_at` (DATETIME, default: `NOW()` on update)
- UNIQUE: `(student_uuid, batch_uuid, eval_type)`

### `ojt_grades`

- `id` (PK, auto-increment)
- `uuid` (unique, CHAR(36))
- `student_uuid` (FK) → `student_profiles.uuid`
- `application_uuid` (FK) → `ojt_applications.uuid`
- `batch_uuid` (FK) → `batches.uuid`
- `finalized_by` (FK) → `coordinator_profiles.uuid`
- Component scores (percentages 0-100):
  - `hours_score` (DECIMAL(5,2), default: 0)
  - `midterm_score` (DECIMAL(5,2), default: 0)
  - `final_score` (DECIMAL(5,2), default: 0)
  - `journal_score` (DECIMAL(5,2), default: 0)
  - `self_score` (DECIMAL(5,2), default: 0)
- Weights used (must sum to 100):
  - `hours_weight` (DECIMAL(5,2), default: 20)
  - `midterm_weight` (DECIMAL(5,2), default: 20)
  - `final_weight` (DECIMAL(5,2), default: 40)
  - `journal_weight` (DECIMAL(5,2), default: 10)
  - `self_weight` (DECIMAL(5,2), default: 10)
- Final computed grade:
  - `weighted_score` (DECIMAL(5,2))
  - `grade_equivalent` (VARCHAR(10))
  - `remarks` (VARCHAR(50)) — `Passed`, `Failed`, `Incomplete`
- `coordinator_notes` (nullable, TEXT)
- `is_finalized` (TINYINT(1), default: 0)
- `finalized_at` (nullable, DATETIME)
- `created_at` (DATETIME, default: `NOW()`)
- `updated_at` (DATETIME, default: `NOW()` on update)
- UNIQUE: `(student_uuid, batch_uuid)`

### `coordinator_visits`

- `id` (PK, auto-increment)
- `uuid` (unique, CHAR(36))
- `coordinator_uuid` (FK) → `coordinator_profiles.uuid`
- `company_uuid` (FK) → `companies.uuid`
- `batch_uuid` (FK) → `batches.uuid`
- `visit_date` (DATE)
- `visit_type` (ENUM: `scheduled`, `unscheduled`, default: `scheduled`)
- `purpose` (TEXT)
- `status` (ENUM: `scheduled`, `completed`, `cancelled`, default: `scheduled`)
- Completion fields:
  - `findings` (TEXT, nullable)
  - `recommendations` (TEXT, nullable)
  - `students_observed` (TEXT, nullable) — JSON array of student UUIDs
- Cancellation:
  - `cancel_reason` (TEXT, nullable)
- `created_at` (DATETIME, default: `NOW()`)
- `updated_at` (DATETIME, default: `NOW()` on update)

### `system_config`

Stores institutional branding, app identity, and system-wide configuration initialized during the Setup Wizard.

- `id` (PK, auto-increment)
- `long_title` (VARCHAR) — System Long Title (App Name)
- `short_title` (VARCHAR) — Short Title
- `system_description` (TEXT)
- `author` (VARCHAR)
- `school_name` (VARCHAR)
- `school_motto` (VARCHAR)
- `school_address` (VARCHAR)
- `school_website` (VARCHAR)
- `school_email` (VARCHAR)
- `school_phone` (VARCHAR)
- `logo_1` (VARCHAR) — Path to primary logo
- `logo_2` (VARCHAR) — Path to secondary logo
- `footer_note` (VARCHAR)
- `verification_note` (TEXT)
- `page_link` (VARCHAR)
- `is_setup_locked` (TINYINT) — Indicates if the initial setup wizard is complete
- `updated_at` (DATETIME, default: `NOW()` on update)

### `email_config`

Stores SMTP configuration and sender details for system emails.

- `id` (PK, auto-increment)
- `smtp_host` (VARCHAR) — SMTP server host (e.g., `smtp.gmail.com`)
- `smtp_port` (INT) — SMTP port (e.g., `587`)
- `smtp_user` (VARCHAR) — SMTP username
- `smtp_pass` (VARCHAR) — SMTP password / App Password
- `smtp_crypto` (VARCHAR) — Encryption method (`tls`, `ssl`)
- `from_email` (VARCHAR) — Sender email address
- `from_name` (VARCHAR) — Sender display name
- `updated_at` (DATETIME, default: `NOW()` on update)

### `system_alerts`

Stores system-wide announcement alerts targetable by user roles.

- `id` (PK, auto-increment)
- `title` (VARCHAR) — Alert title/heading
- `message` (TEXT) — Alert message content
- `alert_type` enum: `info`, `warning`, `danger`, `success`
- `display_type` enum: `banner`, `modal`, `toast`
- `target_roles` (VARCHAR, default: `'all'`) — CSV list of role names or `'all'`
- `is_active` (TINYINT, default: 1)
- `dismissible` (TINYINT, default: 1)
- `expires_at` (DATETIME, nullable)
- `created_by` (FK) → `users.uuid`
- `created_at` (DATETIME)
- `updated_at` (DATETIME)

### `system_alert_dismissals`

Tracks which alerts have been dismissed by individual users to prevent showing them again.

- `id` (PK, auto-increment)
- `alert_id` (FK) → `system_alerts.id`
- `user_uuid` (FK) → `users.uuid`
- `dismissed_at` (DATETIME)
- UNIQUE COMPOSITE: `(alert_id, user_uuid)`

## Key relationships


- One `users` row can have one profile row in the matching profile table.
- `companies` can have many supervisors, contacts, documents, slots, and students.
- `coordinator_profiles` can be linked to many students.
- `student_profiles` can be linked to one company, one coordinator, one batch, and one program.
- `ojt_applications` ties together students, companies, and batches.
- `student_requirements`, `dtr_entries`, `weekly_journals`, and `evaluations` are all linked to specific student applications and batches.

### `certificates` (Proposal 5 - Phase 1)

Stores generated OJT completion certificates with verification and revocation tracking.

- `id` (PK, auto-increment)
- `uuid` (unique, CHAR(36)) — System reference identifier
- `student_uuid` (FK) → `student_profiles.uuid` ON DELETE CASCADE
- `ojt_grades_uuid` (FK) → `ojt_grades.uuid` ON DELETE CASCADE
- `batch_uuid` (FK) → `batches.uuid` ON DELETE CASCADE
- `company_uuid` (FK) → `companies.uuid` ON DELETE CASCADE
- `certificate_number` (VARCHAR(50), unique) — Human-readable format: `OJT-{YEAR}-{COMPANY_CODE}-{SERIAL}`
- `verification_token` (VARCHAR(255), unique) — 32-byte random token for public QR verification
- `file_path` (VARCHAR(500)) — Path to generated PDF: `/uploads/certificates/{uuid}.pdf`
- `hours_completed` (INT) — Total hours rendered by student
- `completion_date` (DATE) — Date of OJT completion
- `generated_by` (FK) → `users.uuid` ON DELETE RESTRICT — Coordinator who generated certificate
- `generated_at` (DATETIME, default: NOW()) — When certificate was generated
- `expires_at` (DATETIME, nullable) — Certificate expiration date (if applicable)
- `is_revoked` (TINYINT, default: 0) — Flag: 1 = revoked, 0 = valid
- `revocation_reason` (TEXT, nullable) — Reason for revocation if applicable
- `revoked_by` (FK, nullable) → `users.uuid` ON DELETE SET NULL — User who revoked certificate
- `revoked_at` (DATETIME, nullable) — When certificate was revoked
- `created_at` (DATETIME, default: NOW())
- `updated_at` (DATETIME, default: NOW() ON UPDATE)
- **Indexes:** `idx_cert_student`, `idx_cert_batch`, `idx_cert_company`, `idx_cert_grades`, `idx_cert_generated`, `idx_cert_revoked`

### `certificate_verifications` (Proposal 5 - Phase 1)

Audit log for certificate verification access via public endpoint. Tracks every verification attempt for security and analytics.

- `id` (PK, auto-increment)
- `certificate_uuid` (FK) → `certificates.uuid` ON DELETE CASCADE
- `ip_address` (VARCHAR(45), nullable) — IP address of verifier (IPv4 or IPv6)
- `user_agent` (VARCHAR(500), nullable) — Browser user agent of verifier
- `verification_result` enum: `valid`, `invalid`, `revoked`, `expired` — Result of verification check
- `accessed_at` (DATETIME, default: NOW()) — When certificate was accessed/verified
- **Indexes:** `idx_cv_certificate`, `idx_cv_result`, `idx_cv_accessed`

## Useful notes for the app

- Supervisor counts can be derived from `student_profiles.company_uuid`.
- Supervisor accounts use `users` + `supervisor_profiles`.
- Company-scoped supervisor/student views are valid because both tables share `company_uuid`.
- Application status changes should be logged in `application_status_logs` for auditability.
- DTR entries must be unique per student per day, enforced by a unique constraint.
- Weekly journals are unique per student per batch per week, enforced by a unique constraint.
- **Certificate Verification Strategy (Proposal 5):** Verification tokens are 256-bit random values (not guessable) used in QR codes. Public verification endpoint logs all attempts for forensics. Certificate numbers are human-readable for display purposes but not used for verification.
- **Certificate Lifecycle:** Once generated, certificates are immutable until revoked. Revocation requires coordinator action with audit trail. Verification token is permanent and cannot be reused.
