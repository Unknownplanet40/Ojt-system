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
- `is_active`
- `must_change_password`
- `last_login_at`
- `created_by` → `users.uuid`

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
- `status` enum: `submitted`, `approved`, `returned` (default: `submitted`)
- `return_reason` (nullable, TEXT)
- `coordinator_remarks` (nullable, TEXT)
- `reviewed_by` (nullable, FK) → `users.uuid`
- `reviewed_at` (nullable, DATETIME)
- `submitted_at` (default: `NOW()`)
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

## Key relationships

- One `users` row can have one profile row in the matching profile table.
- `companies` can have many supervisors, contacts, documents, slots, and students.
- `coordinator_profiles` can be linked to many students.
- `student_profiles` can be linked to one company, one coordinator, one batch, and one program.
- `ojt_applications` ties together students, companies, and batches.
- `student_requirements`, `dtr_entries`, `weekly_journals`, and `evaluations` are all linked to specific student applications and batches.

## Useful notes for the app

- Supervisor counts can be derived from `student_profiles.company_uuid`.
- Supervisor accounts use `users` + `supervisor_profiles`.
- Company-scoped supervisor/student views are valid because both tables share `company_uuid`.
- Application status changes should be logged in `application_status_logs` for auditability.
- DTR entries must be unique per student per day, enforced by a unique constraint.
- Weekly journals are unique per student per batch per week, enforced by a unique constraint.
