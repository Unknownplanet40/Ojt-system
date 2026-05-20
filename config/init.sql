-- OJT Management System - Complete Database Schema
-- Version: 0.0.1
-- Description: Complete structure for OJT management, security, and institutional configuration.

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_uuid` char(36) DEFAULT NULL,
  `target_uuid` char(36) DEFAULT NULL,
  `event_type` enum('profile_completed','account_created','account_deactivated','account_activated','password_changed','password_reset','role_changed','login_success','login_failed','logout','application_submitted','application_approved','application_rejected','endorsement_issued','dtr_submitted','dtr_approved','dtr_rejected','journal_submitted','evaluation_submitted','document_uploaded','company_added','company_updated','moa_uploaded','batch_created','batch_closed','other','program_created','program_disabled','program_enabled','program_updated','certificate_generated','certificate_verified','certificate_revoked') NOT NULL,
  `description` varchar(255) NOT NULL,
  `module` varchar(50) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_al_actor` (`actor_uuid`),
  KEY `idx_al_event` (`event_type`),
  KEY `idx_al_created` (`created_at`),
  KEY `idx_al_module` (`module`)
) ENGINE=InnoDB AUTO_INCREMENT=144 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_profiles`
--
CREATE TABLE IF NOT EXISTS `admin_profiles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `user_uuid` char(36) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `profile_path` text DEFAULT NULL,
  `profile_name` text DEFAULT NULL,
  `isProfileDone` int(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `user_uuid` (`user_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_status_logs`
--
CREATE TABLE IF NOT EXISTS `application_status_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `application_uuid` char(36) NOT NULL,
  `from_status` varchar(30) DEFAULT NULL,
  `to_status` varchar(30) NOT NULL,
  `reason` text DEFAULT NULL,
  `actor_uuid` char(36) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `application_uuid` (`application_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--
CREATE TABLE IF NOT EXISTS `batches` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `school_year` varchar(20) NOT NULL,
  `semester` enum('1st','2nd','summer') NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `required_hours` int(10) UNSIGNED NOT NULL DEFAULT 486,
  `status` enum('upcoming','active','closed') NOT NULL DEFAULT 'upcoming',
  `created_by` char(36) DEFAULT NULL,
  `activated_by` char(36) DEFAULT NULL,
  `closed_by` char(36) DEFAULT NULL,
  `activated_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `fk_batch_created_by` (`created_by`),
  KEY `fk_batch_activated_by` (`activated_by`),
  KEY `fk_batch_closed_by` (`closed_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--
CREATE TABLE IF NOT EXISTS `companies` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `name` varchar(200) NOT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `work_setup` enum('on-site','remote','hybrid') NOT NULL DEFAULT 'on-site',
  `accreditation_status` enum('pending','active','expired','blacklisted') NOT NULL DEFAULT 'pending',
  `blacklist_reason` text DEFAULT NULL,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `geofence_radius` int(11) DEFAULT 100 COMMENT 'Geofencing radius limit in meters',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `fk_co_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_accepted_programs`
--
CREATE TABLE IF NOT EXISTS `company_accepted_programs` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_uuid` char(36) NOT NULL,
  `program_uuid` char(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_company_program` (`company_uuid`,`program_uuid`),
  KEY `fk_cap_program` (`program_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_contacts`
--
CREATE TABLE IF NOT EXISTS `company_contacts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `company_uuid` char(36) NOT NULL,
  `name` varchar(200) NOT NULL,
  `position` varchar(150) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `fk_cc_company` (`company_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_documents`
--
CREATE TABLE IF NOT EXISTS `company_documents` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `company_uuid` char(36) NOT NULL,
  `doc_type` enum('moa','nda','insurance','bir_cert','sec_dti','other') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `uploaded_by` char(36) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `fk_cd_company` (`company_uuid`),
  KEY `fk_cd_uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_slots`
--
CREATE TABLE IF NOT EXISTS `company_slots` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `company_uuid` char(36) NOT NULL,
  `batch_uuid` char(36) NOT NULL,
  `total_slots` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `uq_company_batch` (`company_uuid`,`batch_uuid`),
  KEY `fk_cs_batch` (`batch_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coordinator_profiles`
--
CREATE TABLE IF NOT EXISTS `coordinator_profiles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `user_uuid` char(36) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `profile_path` text DEFAULT NULL,
  `profile_name` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `isProfileDone` int(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `user_uuid` (`user_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coordinator_visits`
--
CREATE TABLE IF NOT EXISTS `coordinator_visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `coordinator_uuid` char(36) NOT NULL,
  `company_uuid` char(36) NOT NULL,
  `batch_uuid` char(36) NOT NULL,
  `visit_date` date NOT NULL,
  `visit_type` enum('scheduled','unscheduled') NOT NULL DEFAULT 'scheduled',
  `purpose` text NOT NULL,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `findings` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `students_observed` text DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `coordinator_uuid` (`coordinator_uuid`),
  KEY `company_uuid` (`company_uuid`),
  KEY `batch_uuid` (`batch_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dtr_audit_log`
--
CREATE TABLE IF NOT EXISTS `dtr_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `dtr_uuid` char(36) NOT NULL,
  `action` varchar(50) NOT NULL,
  `actor_uuid` char(36) NOT NULL,
  `actor_role` varchar(20) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `dtr_uuid` (`dtr_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dtr_entries`
--
CREATE TABLE IF NOT EXISTS `dtr_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `student_uuid` char(36) NOT NULL,
  `application_uuid` char(36) NOT NULL,
  `batch_uuid` char(36) NOT NULL,
  `entry_date` date NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `lunch_break_minutes` tinyint(4) NOT NULL DEFAULT 60,
  `hours_rendered` decimal(4,2) NOT NULL DEFAULT 0.00,
  `activities` text DEFAULT NULL,
  `is_backdated` tinyint(1) NOT NULL DEFAULT 0,
  `backdate_reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `approved_by` char(36) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by_role` varchar(20) DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `clock_in_latitude` decimal(10,8) DEFAULT NULL,
  `clock_in_longitude` decimal(11,8) DEFAULT NULL,
  `clock_out_latitude` decimal(10,8) DEFAULT NULL,
  `clock_out_longitude` decimal(11,8) DEFAULT NULL,
  `clock_in_photo` varchar(255) DEFAULT NULL,
  `clock_out_photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `uq_student_date` (`student_uuid`,`entry_date`),
  KEY `application_uuid` (`application_uuid`),
  KEY `batch_uuid` (`batch_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_config`
--
CREATE TABLE IF NOT EXISTS `email_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `smtp_host` varchar(255) NOT NULL,
  `smtp_port` int(11) NOT NULL,
  `smtp_user` varchar(255) NOT NULL,
  `smtp_pass` varchar(255) NOT NULL,
  `smtp_crypto` enum('none','ssl','tls') NOT NULL DEFAULT 'tls',
  `from_email` varchar(255) NOT NULL,
  `from_name` varchar(255) NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `endorsement_letters`
--
CREATE TABLE IF NOT EXISTS `endorsement_letters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `application_uuid` char(36) NOT NULL,
  `student_uuid` char(36) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `generated_by` char(36) DEFAULT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `application_uuid` (`application_uuid`),
  KEY `student_uuid` (`student_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--
CREATE TABLE IF NOT EXISTS `evaluations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `student_uuid` char(36) NOT NULL,
  `application_uuid` char(36) NOT NULL,
  `batch_uuid` char(36) NOT NULL,
  `submitted_by` char(36) NOT NULL,
  `submitted_by_role` enum('supervisor','student') NOT NULL,
  `eval_type` enum('midterm','final','self') NOT NULL,
  `technical_skills` tinyint(4) DEFAULT NULL,
  `work_attitude` tinyint(4) DEFAULT NULL,
  `communication` tinyint(4) DEFAULT NULL,
  `teamwork` tinyint(4) DEFAULT NULL,
  `problem_solving` tinyint(4) DEFAULT NULL,
  `overall_experience` tinyint(4) DEFAULT NULL,
  `would_recommend` tinyint(1) DEFAULT NULL,
  `total_score` decimal(4,2) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `uq_student_eval_type` (`student_uuid`,`batch_uuid`,`eval_type`),
  KEY `application_uuid` (`application_uuid`),
  KEY `batch_uuid` (`batch_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_audit_log`
--
CREATE TABLE IF NOT EXISTS `login_audit_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_uuid` char(36) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `fail_reason` varchar(255) DEFAULT NULL,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_lal_user` (`user_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ojt_applications`
--
CREATE TABLE IF NOT EXISTS `ojt_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `student_uuid` char(36) NOT NULL,
  `batch_uuid` char(36) NOT NULL,
  `company_uuid` char(36) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `preferred_department` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','endorsed','active','needs_revision','rejected','withdrawn') NOT NULL DEFAULT 'pending',
  `revision_reason` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `uq_student_batch_active` (`student_uuid`,`batch_uuid`),
  KEY `batch_uuid` (`batch_uuid`),
  KEY `company_uuid` (`company_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ojt_grades`
--
CREATE TABLE IF NOT EXISTS `ojt_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `student_uuid` char(36) NOT NULL,
  `application_uuid` char(36) NOT NULL,
  `batch_uuid` char(36) NOT NULL,
  `finalized_by` char(36) NOT NULL,
  `hours_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `midterm_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `final_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `journal_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `self_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `hours_weight` decimal(5,2) NOT NULL DEFAULT 20.00,
  `midterm_weight` decimal(5,2) NOT NULL DEFAULT 20.00,
  `final_weight` decimal(5,2) NOT NULL DEFAULT 40.00,
  `journal_weight` decimal(5,2) NOT NULL DEFAULT 10.00,
  `self_weight` decimal(5,2) NOT NULL DEFAULT 10.00,
  `weighted_score` decimal(5,2) NOT NULL,
  `grade_equivalent` varchar(10) NOT NULL,
  `remarks` varchar(50) NOT NULL,
  `coordinator_notes` text DEFAULT NULL,
  `is_finalized` tinyint(1) NOT NULL DEFAULT 0,
  `finalized_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `uq_student_batch` (`student_uuid`,`batch_uuid`),
  KEY `application_uuid` (`application_uuid`),
  KEY `batch_uuid` (`batch_uuid`),
  KEY `finalized_by` (`finalized_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ojt_start_confirmations`
--
CREATE TABLE IF NOT EXISTS `ojt_start_confirmations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `application_uuid` char(36) NOT NULL,
  `student_uuid` char(36) NOT NULL,
  `supervisor_uuid` char(36) DEFAULT NULL,
  `start_date` date NOT NULL,
  `expected_end_date` date DEFAULT NULL,
  `working_hours_per_day` tinyint(4) NOT NULL DEFAULT 8,
  `confirmed_by` char(36) DEFAULT NULL,
  `confirmed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `application_uuid` (`application_uuid`),
  KEY `student_uuid` (`student_uuid`),
  KEY `supervisor_uuid` (`supervisor_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_uuid` char(36) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_prt_user` (`user_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--
CREATE TABLE IF NOT EXISTS `programs` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `code` varchar(20) NOT NULL,
  `name` varchar(200) NOT NULL,
  `department` varchar(200) DEFAULT NULL,
  `required_hours` int(10) UNSIGNED NOT NULL DEFAULT 486,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `code` (`code`),
  KEY `fk_prog_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--
CREATE TABLE IF NOT EXISTS `student_profiles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `user_uuid` char(36) NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `profile_path` text DEFAULT NULL,
  `profile_name` text DEFAULT NULL,
  `program` varchar(100) NOT NULL,
  `program_uuid` char(36) DEFAULT NULL,
  `year_level` tinyint(3) UNSIGNED NOT NULL,
  `section` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `emergency_contact` varchar(200) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `coordinator_uuid` char(36) DEFAULT NULL,
  `supervisor_uuid` char(36) DEFAULT NULL,
  `batch_uuid` char(36) DEFAULT NULL,
  `company_uuid` char(36) DEFAULT NULL,
  `isProfileDone` int(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `user_uuid` (`user_uuid`),
  UNIQUE KEY `student_number` (`student_number`),
  KEY `fk_sp_coordinator` (`coordinator_uuid`),
  KEY `fk_sp_program` (`program_uuid`),
  KEY `fk_sp_company` (`company_uuid`),
  KEY `supervisor_uuid` (`supervisor_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_requirements`
--
CREATE TABLE IF NOT EXISTS `student_requirements` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `student_uuid` char(36) NOT NULL,
  `batch_uuid` char(36) NOT NULL,
  `req_type` enum('medical_certificate','parental_consent','insurance','nbi_clearance','resume','guardian_form') NOT NULL,
  `status` enum('not_submitted','submitted','under_review','approved','returned') NOT NULL DEFAULT 'not_submitted',
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `return_reason` text DEFAULT NULL,
  `student_note` text DEFAULT NULL,
  `coordinator_note` text DEFAULT NULL,
  `reviewed_by` char(36) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `uq_student_batch_req` (`student_uuid`,`batch_uuid`,`req_type`),
  KEY `fk_sr_batch` (`batch_uuid`),
  KEY `fk_sr_reviewer` (`reviewed_by`)
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supervisor_profiles`
--
CREATE TABLE IF NOT EXISTS `supervisor_profiles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `user_uuid` char(36) NOT NULL,
  `company_uuid` char(36) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `position` varchar(150) DEFAULT NULL,
  `profile_path` text DEFAULT NULL,
  `profile_name` text DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `isProfileDone` int(1) NOT NULL DEFAULT 0,
  `is_hr_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `user_uuid` (`user_uuid`),
  KEY `fk_svp_company` (`company_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_alerts`
--
CREATE TABLE IF NOT EXISTS `system_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `alert_type` enum('info','warning','danger','success') NOT NULL DEFAULT 'info',
  `display_type` enum('banner','modal','toast') NOT NULL DEFAULT 'banner',
  `target_roles` varchar(100) NOT NULL DEFAULT 'all',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `dismissible` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` datetime DEFAULT NULL,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_alerts_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_alert_dismissals`
--
CREATE TABLE IF NOT EXISTS `system_alert_dismissals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_id` int(11) NOT NULL,
  `user_uuid` char(36) NOT NULL,
  `dismissed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_alert_user` (`alert_id`,`user_uuid`),
  KEY `fk_dismissals_alert` (`alert_id`),
  KEY `fk_dismissals_user` (`user_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_config`
--
CREATE TABLE IF NOT EXISTS `system_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `long_title` varchar(255) DEFAULT 'On-The-Job Training Management System',
  `short_title` varchar(50) DEFAULT 'OJT-SMS',
  `system_description` text DEFAULT NULL,
  `author` varchar(100) DEFAULT 'Ryan James V. Capadocia',
  `school_name` varchar(255) DEFAULT 'Cavite State University - Imus Campus',
  `school_motto` varchar(255) DEFAULT 'Truth, Excellence, and Service',
  `school_address` text DEFAULT NULL,
  `school_website` varchar(255) DEFAULT 'https://cvsu-imus.edu.ph/',
  `school_email` varchar(100) DEFAULT 'cvsuimus@cvsu.edu.ph',
  `school_phone` varchar(50) DEFAULT '+63 (46) 471-6607',
  `logo_1` varchar(100) DEFAULT 'school_logo.png',
  `logo_2` varchar(100) DEFAULT 'office_logo.png',
  `footer_note` text DEFAULT NULL,
  `verification_note` text DEFAULT NULL,
  `page_link` varchar(255) DEFAULT 'http://localhost/ojt-system',
  `is_setup_locked` tinyint(1) DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','coordinator','student','supervisor') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `welcome_email_sent` tinyint(1) DEFAULT 0,
  `theme_preference` varchar(10) DEFAULT 'dark',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` char(36) DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `lockout_until` datetime DEFAULT NULL,
  `manual_lockout` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_users_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--
-- Stores global system configurations and user-specific preferences.
-- Global configuration keys include:
--   Maintenance & Lockout (user_uuid IS NULL):
--     disable_{feature}_submission       : '1' (disabled) or '0' (enabled)
--     {feature}_maintenance_start/end     : Scheduled lockout date ranges
--     {feature}_disable_reason            : Alert messages for lockout UI
--   Security Settings (user_uuid IS NULL):
--     lockout_threshold, lockout_duration, lockout_notify_admin
CREATE TABLE IF NOT EXISTS `user_settings` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_uuid` char(36) DEFAULT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext NOT NULL,
  `updated_by` varchar(36) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_setting` (`user_uuid`,`setting_key`),
  KEY `fk_admin_settings_updated_by` (`updated_by`),
  KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weekly_journals`
--
CREATE TABLE IF NOT EXISTS `weekly_journals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `student_uuid` varchar(36) NOT NULL,
  `application_uuid` char(36) DEFAULT NULL,
  `batch_uuid` varchar(36) NOT NULL,
  `week_number` tinyint(4) DEFAULT NULL,
  `week_start` date NOT NULL,
  `week_end` date NOT NULL,
  `accomplishments` text NOT NULL,
  `skills_learned` text DEFAULT NULL,
  `challenges` text DEFAULT NULL,
  `plans_next_week` text DEFAULT NULL,
  `issues_concerns` text DEFAULT NULL,
  `status` enum('submitted','approved','returned') DEFAULT 'submitted',
  `coordinator_remarks` text DEFAULT NULL,
  `return_reason` text DEFAULT NULL,
  `reviewed_by` varchar(36) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `uq_student_batch_week` (`student_uuid`,`batch_uuid`,`week_start`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_al_actor` FOREIGN KEY (`actor_uuid`) REFERENCES `users` (`uuid`) ON DELETE SET NULL;

ALTER TABLE `admin_profiles`
  ADD CONSTRAINT `fk_ap_user` FOREIGN KEY (`user_uuid`) REFERENCES `users` (`uuid`) ON DELETE CASCADE;

ALTER TABLE `application_status_logs`
  ADD CONSTRAINT `application_status_logs_ibfk_1` FOREIGN KEY (`application_uuid`) REFERENCES `ojt_applications` (`uuid`);

ALTER TABLE `batches`
  ADD CONSTRAINT `fk_batch_activated_by` FOREIGN KEY (`activated_by`) REFERENCES `users` (`uuid`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_batch_closed_by` FOREIGN KEY (`closed_by`) REFERENCES `users` (`uuid`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_batch_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`uuid`) ON DELETE SET NULL;

ALTER TABLE `companies`
  ADD CONSTRAINT `fk_co_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`uuid`) ON DELETE SET NULL;

ALTER TABLE `company_accepted_programs`
  ADD CONSTRAINT `fk_cap_company` FOREIGN KEY (`company_uuid`) REFERENCES `companies` (`uuid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cap_program` FOREIGN KEY (`program_uuid`) REFERENCES `programs` (`uuid`) ON DELETE CASCADE;

ALTER TABLE `company_contacts`
  ADD CONSTRAINT `fk_cc_company` FOREIGN KEY (`company_uuid`) REFERENCES `companies` (`uuid`) ON DELETE CASCADE;

ALTER TABLE `company_documents`
  ADD CONSTRAINT `fk_cd_company` FOREIGN KEY (`company_uuid`) REFERENCES `companies` (`uuid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cd_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`uuid`) ON DELETE SET NULL;

ALTER TABLE `company_slots`
  ADD CONSTRAINT `fk_cs_batch` FOREIGN KEY (`batch_uuid`) REFERENCES `batches` (`uuid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cs_company` FOREIGN KEY (`company_uuid`) REFERENCES `companies` (`uuid`) ON DELETE CASCADE;

ALTER TABLE `coordinator_profiles`
  ADD CONSTRAINT `fk_cp_user` FOREIGN KEY (`user_uuid`) REFERENCES `users` (`uuid`) ON DELETE CASCADE;

ALTER TABLE `coordinator_visits`
  ADD CONSTRAINT `coordinator_visits_ibfk_1` FOREIGN KEY (`coordinator_uuid`) REFERENCES `coordinator_profiles` (`uuid`),
  ADD CONSTRAINT `coordinator_visits_ibfk_2` FOREIGN KEY (`company_uuid`) REFERENCES `companies` (`uuid`),
  ADD CONSTRAINT `coordinator_visits_ibfk_3` FOREIGN KEY (`batch_uuid`) REFERENCES `batches` (`uuid`);

ALTER TABLE `dtr_audit_log`
  ADD CONSTRAINT `dtr_audit_log_ibfk_1` FOREIGN KEY (`dtr_uuid`) REFERENCES `dtr_entries` (`uuid`);

ALTER TABLE `dtr_entries`
  ADD CONSTRAINT `dtr_entries_ibfk_1` FOREIGN KEY (`student_uuid`) REFERENCES `student_profiles` (`uuid`),
  ADD CONSTRAINT `dtr_entries_ibfk_2` FOREIGN KEY (`application_uuid`) REFERENCES `ojt_applications` (`uuid`),
  ADD CONSTRAINT `dtr_entries_ibfk_3` FOREIGN KEY (`batch_uuid`) REFERENCES `batches` (`uuid`);

ALTER TABLE `endorsement_letters`
  ADD CONSTRAINT `endorsement_letters_ibfk_1` FOREIGN KEY (`application_uuid`) REFERENCES `ojt_applications` (`uuid`),
  ADD CONSTRAINT `endorsement_letters_ibfk_2` FOREIGN KEY (`student_uuid`) REFERENCES `student_profiles` (`uuid`);

ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`student_uuid`) REFERENCES `student_profiles` (`uuid`),
  ADD CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`application_uuid`) REFERENCES `ojt_applications` (`uuid`),
  ADD CONSTRAINT `evaluations_ibfk_3` FOREIGN KEY (`batch_uuid`) REFERENCES `batches` (`uuid`);

ALTER TABLE `login_audit_log`
  ADD CONSTRAINT `fk_lal_user` FOREIGN KEY (`user_uuid`) REFERENCES `users` (`uuid`) ON DELETE SET NULL;

ALTER TABLE `ojt_applications`
  ADD CONSTRAINT `ojt_applications_ibfk_1` FOREIGN KEY (`student_uuid`) REFERENCES `student_profiles` (`uuid`),
  ADD CONSTRAINT `ojt_applications_ibfk_2` FOREIGN KEY (`batch_uuid`) REFERENCES `batches` (`uuid`),
  ADD CONSTRAINT `ojt_applications_ibfk_3` FOREIGN KEY (`company_uuid`) REFERENCES `companies` (`uuid`);

ALTER TABLE `ojt_grades`
  ADD CONSTRAINT `ojt_grades_ibfk_1` FOREIGN KEY (`student_uuid`) REFERENCES `student_profiles` (`uuid`),
  ADD CONSTRAINT `ojt_grades_ibfk_2` FOREIGN KEY (`application_uuid`) REFERENCES `ojt_applications` (`uuid`),
  ADD CONSTRAINT `ojt_grades_ibfk_3` FOREIGN KEY (`batch_uuid`) REFERENCES `batches` (`uuid`),
  ADD CONSTRAINT `ojt_grades_ibfk_4` FOREIGN KEY (`finalized_by`) REFERENCES `coordinator_profiles` (`uuid`);

ALTER TABLE `ojt_start_confirmations`
  ADD CONSTRAINT `ojt_start_confirmations_ibfk_1` FOREIGN KEY (`application_uuid`) REFERENCES `ojt_applications` (`uuid`),
  ADD CONSTRAINT `ojt_start_confirmations_ibfk_2` FOREIGN KEY (`student_uuid`) REFERENCES `student_profiles` (`uuid`),
  ADD CONSTRAINT `ojt_start_confirmations_ibfk_3` FOREIGN KEY (`supervisor_uuid`) REFERENCES `supervisor_profiles` (`uuid`);

ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_prt_user` FOREIGN KEY (`user_uuid`) REFERENCES `users` (`uuid`) ON DELETE CASCADE;

ALTER TABLE `programs`
  ADD CONSTRAINT `fk_prog_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`uuid`) ON DELETE SET NULL;

ALTER TABLE `student_profiles`
  ADD CONSTRAINT `fk_sp_company` FOREIGN KEY (`company_uuid`) REFERENCES `companies` (`uuid`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sp_coordinator` FOREIGN KEY (`coordinator_uuid`) REFERENCES `coordinator_profiles` (`uuid`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sp_program` FOREIGN KEY (`program_uuid`) REFERENCES `programs` (`uuid`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sp_user` FOREIGN KEY (`user_uuid`) REFERENCES `users` (`uuid`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`supervisor_uuid`) REFERENCES `supervisor_profiles` (`uuid`);

ALTER TABLE `student_requirements`
  ADD CONSTRAINT `fk_sr_batch` FOREIGN KEY (`batch_uuid`) REFERENCES `batches` (`uuid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sr_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`uuid`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sr_student` FOREIGN KEY (`student_uuid`) REFERENCES `student_profiles` (`uuid`) ON DELETE CASCADE;

ALTER TABLE `supervisor_profiles`
  ADD CONSTRAINT `fk_svp_company` FOREIGN KEY (`company_uuid`) REFERENCES `companies` (`uuid`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_svp_user` FOREIGN KEY (`user_uuid`) REFERENCES `users` (`uuid`) ON DELETE CASCADE;

ALTER TABLE `system_alerts`
  ADD CONSTRAINT `fk_alerts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`uuid`) ON DELETE SET NULL;

ALTER TABLE `system_alert_dismissals`
  ADD CONSTRAINT `fk_dismissals_alert` FOREIGN KEY (`alert_id`) REFERENCES `system_alerts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dismissals_user` FOREIGN KEY (`user_uuid`) REFERENCES `users` (`uuid`) ON DELETE CASCADE;

ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`uuid`) ON DELETE SET NULL;

ALTER TABLE `user_settings`
  ADD CONSTRAINT `fk_admin_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`uuid`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_settings_user` FOREIGN KEY (`user_uuid`) REFERENCES `users` (`uuid`) ON DELETE CASCADE;

-- --------------------------------------------------------

--
-- Table structure for table `schema_version`
--
CREATE TABLE IF NOT EXISTS `schema_version` (
  `id`         int(10) unsigned NOT NULL AUTO_INCREMENT,
  `version`    varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  `source`     varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
-- Stores OJT Completion Certificates with verification tokens (Proposal 5)
--
CREATE TABLE IF NOT EXISTS `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `student_uuid` char(36) NOT NULL,
  `ojt_grades_uuid` char(36) NOT NULL,
  `batch_uuid` char(36) NOT NULL,
  `company_uuid` char(36) NOT NULL,
  `certificate_number` varchar(50) NOT NULL UNIQUE,
  `verification_token` varchar(255) NOT NULL UNIQUE,
  `file_path` varchar(500) NOT NULL,
  `hours_completed` int(11) NOT NULL,
  `completion_date` date NOT NULL,
  `generated_by` char(36) NOT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `is_revoked` tinyint(1) NOT NULL DEFAULT 0,
  `revocation_reason` text DEFAULT NULL,
  `revoked_by` char(36) DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `certificate_number` (`certificate_number`),
  UNIQUE KEY `verification_token` (`verification_token`),
  KEY `idx_cert_student` (`student_uuid`),
  KEY `idx_cert_batch` (`batch_uuid`),
  KEY `idx_cert_company` (`company_uuid`),
  KEY `idx_cert_grades` (`ojt_grades_uuid`),
  KEY `idx_cert_generated` (`generated_at`),
  KEY `idx_cert_revoked` (`is_revoked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificate_verifications`
-- Audit log for certificate verification access (Proposal 5)
--
CREATE TABLE IF NOT EXISTS `certificate_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `certificate_uuid` char(36) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `verification_result` enum('valid','invalid','revoked','expired') NOT NULL,
  `accessed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cv_certificate` (`certificate_uuid`),
  KEY `idx_cv_result` (`verification_result`),
  KEY `idx_cv_accessed` (`accessed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Add foreign key constraints for certificates tables
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `fk_cert_student` FOREIGN KEY (`student_uuid`) REFERENCES `student_profiles` (`uuid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cert_grades` FOREIGN KEY (`ojt_grades_uuid`) REFERENCES `ojt_grades` (`uuid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cert_batch` FOREIGN KEY (`batch_uuid`) REFERENCES `batches` (`uuid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cert_company` FOREIGN KEY (`company_uuid`) REFERENCES `companies` (`uuid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cert_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`uuid`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_cert_revoked_by` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`uuid`) ON DELETE SET NULL;

ALTER TABLE `certificate_verifications`
  ADD CONSTRAINT `fk_cv_certificate` FOREIGN KEY (`certificate_uuid`) REFERENCES `certificates` (`uuid`) ON DELETE CASCADE;

-- --------------------------------------------------------

--
-- Table structure for table `verification_logs` (Proposal 5 Phase 4)
-- Stores complete audit trail of certificate verification attempts
--
CREATE TABLE IF NOT EXISTS `verification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `certificate_uuid` char(36) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `verification_source` enum('public_link','qr_code','manual_verification','api') NOT NULL DEFAULT 'public_link',
  `verification_result` enum('valid','invalid','revoked','expired') NOT NULL,
  `accessed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `idx_vl_certificate` (`certificate_uuid`),
  KEY `idx_vl_result` (`verification_result`),
  KEY `idx_vl_source` (`verification_source`),
  KEY `idx_vl_accessed` (`accessed_at`),
  KEY `idx_vl_ip` (`ip_address`),
  KEY `idx_vl_result_date` (`verification_result`, `accessed_at`),
  KEY `idx_vl_source_date` (`verification_source`, `accessed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qr_code_cache` (Proposal 5 Phase 4)
-- Cache for generated QR codes (30-day TTL)
--
CREATE TABLE IF NOT EXISTS `qr_code_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `certificate_uuid` char(36) NOT NULL,
  `qr_data` longblob NOT NULL,
  `qr_format` enum('png','svg') NOT NULL DEFAULT 'png',
  `qr_size` int(3) NOT NULL DEFAULT 300,
  `verification_url` varchar(500) NOT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `accessed_at` datetime DEFAULT NULL,
  `access_count` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `idx_qrc_certificate` (`certificate_uuid`),
  KEY `idx_qrc_expires` (`expires_at`),
  KEY `idx_qrc_generated` (`generated_at`),
  KEY `idx_qrc_cleanup` (`expires_at`, `access_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificate_revocation_logs` (Proposal 5 Phase 4)
-- Immutable audit log of certificate revocation events
--
CREATE TABLE IF NOT EXISTS `certificate_revocation_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `certificate_uuid` char(36) NOT NULL,
  `revocation_reason` text NOT NULL,
  `detailed_reason` text DEFAULT NULL,
  `revoked_by` char(36) NOT NULL,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` char(36) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `can_appeal` tinyint(1) NOT NULL DEFAULT 1,
  `appeal_deadline` datetime DEFAULT NULL,
  `revoked_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `idx_crl_certificate` (`certificate_uuid`),
  KEY `idx_crl_revoked_by` (`revoked_by`),
  KEY `idx_crl_status` (`approval_status`),
  KEY `idx_crl_revoked` (`revoked_at`),
  KEY `idx_crl_status_date` (`approval_status`, `revoked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Add foreign key constraints for Phase 4 tables
--
ALTER TABLE `verification_logs`
  ADD CONSTRAINT `fk_vl_certificate` FOREIGN KEY (`certificate_uuid`) REFERENCES `certificates` (`uuid`) ON DELETE CASCADE;

ALTER TABLE `qr_code_cache`
  ADD CONSTRAINT `fk_qrc_certificate` FOREIGN KEY (`certificate_uuid`) REFERENCES `certificates` (`uuid`) ON DELETE CASCADE;

ALTER TABLE `certificate_revocation_logs`
  ADD CONSTRAINT `fk_crl_certificate` FOREIGN KEY (`certificate_uuid`) REFERENCES `certificates` (`uuid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_crl_revoked_by` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`uuid`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_crl_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`uuid`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS=1;
COMMIT;
