# Architecture Decision Records

This file records decisions that agents should not casually reverse.

## ADR-001: Single-Campus Scope

**Status:** Accepted

### Decision

Version 1 targets Cavite State University – Imus Campus.

### Consequence

Do not introduce multi-campus behavior unless explicitly required.

---

## ADR-002: Server-First Rendering

**Status:** Accepted

### Decision

Use React Server Components by default where supported by the project's Next.js architecture.

Client Components are used when browser-side state, effects, or interaction require them.

### Consequence

Do not move server data fetching into Client Components without a concrete reason.

---

## ADR-003: Server-Side Authorization

**Status:** Accepted

### Decision

Client-side permission checks are presentation only. Authorization must be enforced server-side and at the database/RLS layer where applicable.

### Consequence

A hidden button is never considered a security control.

---

## ADR-004: Organization Isolation

**Status:** Accepted

### Decision

Organization-scoped users can only access data belonging to organizations they are authorized to manage.

### Consequence

Organization IDs supplied by clients cannot be trusted as proof of membership.

---

## ADR-005: No Unrequested Architecture Changes

**Status:** Accepted

### Decision

Agents must not replace core infrastructure, libraries, authentication, database access patterns, or UI systems without an explicit requirement or documented architecture decision.

### Consequence

Existing approved architecture wins over agent preference.

---

## ADR-006: Document Return and Resubmission Lifecycle

**Status:** Accepted

### Decision

When a document is marked `returned` by a CSG reviewer with feedback, the author may edit and resubmit the same document record directly (`returned → submitted`). The system maintains an internal version history and audit log for each revision.

### Consequence

Returning does not fork or orphan records; document versions remain linked to the primary document identifier with an append-only audit trail of reviewer notes and revisions.

---

## ADR-007: Document Header Composition

**Status:** Accepted

### Decision

Official document generation and export (PDF) must merge a centralized, standardized campus/CSG header banner (configured by CSG leadership/Admin) with the submitting sub-organization's custom organization header and logo.

### Consequence

Sub-organizations retain identity branding on official submissions while ensuring strict university and CSG compliance in the generated letterheads.

---

## ADR-008: Hybrid Account Lockout Mechanism

**Status:** Accepted

### Decision

After the configured threshold of consecutive failed login attempts (default 5), accounts enter a temporary 15-minute lockout (`locked_until`). Super Admins have the ability to manually unlock accounts immediately from the Admin Dashboard prior to time expiration.

### Consequence

Protects against credential stuffing and brute force without permanently locking users out in the event of forgotten passwords, while retaining administrative override authority.

---

## ADR-009: Hierarchical Live Chat Boundaries

**Status:** Accepted

### Decision

Live Chat enforces an organizational hierarchy: regular members can only chat within their assigned sub-organization's group thread; cross-organization direct messaging is reserved for Sub-Org Officers, CSG Officers, and Super Admins.

### Consequence

Prevents unsolicited messaging spam across student populations while preserving necessary coordination channels between organization leadership and CSG.

---

## ADR-010: Tiered Account Provisioning Delegation

**Status:** Accepted

### Decision

User provisioning follows a tiered hierarchy:
- Super Admins can provision any account and assign any role.
- CSG Officers can provision Sub-Org President accounts.
- Sub-Org Presidents/Officers can provision officers and members for their respective organization.

### Consequence

Decentralizes administrative burden while preserving role hierarchy; sub-org officers cannot create accounts for other organizations or grant CSG/Super Admin privileges.

---

## ADR-011: Term-Based Officer Handoff & Historical Archiving

**Status:** Accepted

### Decision

Officer roles are tied to academic year term dates (`term_start`, `term_end`, and `is_current`). When an officer's term ends, the system automatically transitions them to regular member status with read-only access to their past tenure records. Incoming officers inherit active organization management and access to the organization's historical transition folder.

### Consequence

Prevents lockouts and lost institutional knowledge during annual leadership handoffs, maintaining a continuous archive across academic years.

---

## ADR-012: State-Diff Mutation Audit Logging

**Status:** Accepted

### Decision

Audit logging captures all authentication events (success, failure, lockout, reset) and state mutations (document transitions, role changes, organization profile updates). The `metadata` JSONB field stores structured before/after diffs (`previous_state`, `new_state`, and contextual notes). Routine read/view events are omitted to prevent database bloat.

### Consequence

Provides forensic traceability for sensitive operations without degrading transactional performance or exceeding Supabase storage capacities.

---

## ADR-013: Soft Conflict Detection for Campus Events

**Status:** Accepted

### Decision

The event scheduling subsystem checks for overlapping campus venues and time slots across all organizations. Conflicts generate an advisory warning to the submitting officer and are flagged explicitly to CSG officers during the associated activity proposal review, rather than strictly blocking submission.

### Consequence

Prevents duplicate venue bookings while preserving organizational flexibility in cases of co-hosted events or multi-purpose venue subdivisions.

---

## ADR-014: Term-Scoped & Role-Gated Report Generation

**Status:** Accepted

### Decision

Analytics and reporting are scoped by Academic Year and Semester. Super Admins and CSG Officers can export both campus-wide and individual organization reports in PDF (formal executive summaries) and CSV (raw datasets). Sub-Org Officers are strictly restricted to exporting their own organization's summary report. Regular members have no export permissions.

### Consequence

Protects inter-organization confidentiality and institutional data while providing leadership with the compliance data needed for university administration.

---

## ADR-015: Strict Activation Gating via Middleware

**Status:** Accepted

### Decision

Users created with `is_active: false` are strictly intercepted by Next.js edge middleware on all authenticated routes and redirected to `/activate`. Users cannot access any application dashboards or execute mutations until they complete the profile activation form (providing student number, course, section, contact number, and initial password change).

### Consequence

Guarantees 100% data integrity for student identity and academic classification, preventing partially onboarded accounts from interacting with campus organizations.

---

## ADR-016: Operational Suspension for Inactive/Pending Organizations

**Status:** Accepted

### Decision

Organizations with `status` set to `inactive` or `pending` enter strict operational suspension: their members and officers retain read-only historical access to past records, but are blocked from submitting documents, creating calendar events, or posting group chat messages until CSG leadership explicitly transitions the organization to `active`.

### Consequence

Enforces official university recognition compliance at the database/RLS and Server Action layers, preventing unauthorized or delinquent organizations from conducting official campus business.

---

## ADR-017: Hybrid Realtime & Transactional Notification Delivery

**Status:** Accepted

### Decision

Notifications utilize a two-tier delivery pattern:
1. Realtime in-app notification bell and unread badge counters (via Supabase Realtime) for all system events, new announcements, and chat messages.
2. Transactional email alerts (via Resend) reserved strictly for high-impact events: document status changes (`approved` / `returned`), account security actions (lockouts, manual unlocks), and password reset OTP verification codes.

### Consequence

Ensures immediate awareness without spamming students' email inboxes with routine chatter, preserving email delivery quotas and user engagement.

---

## ADR-018: Document File Upload Limits and MIME Whitelisting

**Status:** Accepted

### Decision

Document attachments and file uploads are restricted to a maximum size of 10MB per file. Permitted MIME types are strictly whitelisted to PDF (`.pdf`), Microsoft Word (`.docx`), Microsoft PowerPoint (`.pptx`), and images (`.png`, `.jpg`, `.jpeg`). Executables, scripts, and generic archive formats (`.zip`, `.rar`) are rejected both on the client and via server-side file inspection.

### Consequence

Prevents malicious file uploads, keeps Supabase storage consumption within sustainable limits, and guarantees document compatibility across campus administrative devices.

---

## ADR-019: Dual-Level Announcement Scoping

**Status:** Accepted

### Decision

Announcements support dual-level publishing authority:
- CSG Officers and Super Admins can publish announcements targeted campus-wide or targeted to specific student organizations.
- Sub-Org Officers can publish internal announcements strictly scoped and visible only to members of their own organization.

### Consequence

Allows individual organizations to maintain an official bulletin board for their general membership without cluttering the campus-wide newsfeed or requiring ad-hoc group chats.

---

## ADR-020: PWA Offline Strategy (App Shell & Cached Read-Only)

**Status:** Accepted

### Decision

The Progressive Web App implementation focuses on mobile installability ("Add to Home Screen"), caching the Next.js application shell, stylesheets, and icons, alongside recently viewed announcements and calendar events for read-only offline viewing. Mutations (document submissions, chat messaging, status reviews) require an active network connection and display a graceful offline banner when connectivity is lost.

### Consequence

Provides immediate, app-like responsiveness on campus mobile networks without the complexity and synchronization conflicts of offline mutation queuing.

---

## ADR-021: Primary Affiliation with Multi-Org Junction Membership

**Status:** Accepted

### Decision

Student profiles store a primary organization reference in `profiles.organization_id` for immediate role and primary permission evaluation. Secondary memberships in other student clubs/organizations are supported via a dedicated `organization_members` junction table.

### Consequence

Keeps RLS policies and server-side authorization fast and predictable for primary duties while supporting multi-club campus involvement.

---

## ADR-022: Session Security and Idle Inactivity Logout

**Status:** Accepted

### Decision

Sessions use short-lived JWT access tokens (1 hour) with automatic background token refresh via the Supabase client while actively used. To secure unattended sessions in shared campus computer laboratories and council offices, a 30-minute idle inactivity detector triggers an auto-logout modal prompt.

### Consequence

Prevents unauthorized access from abandoned lab workstations while delivering an uninterrupted experience for active users on personal or mobile devices.

---

## ADR-023: Structured Activity Proposal & Dynamic Itemized Budget

**Status:** Accepted

### Decision

Activity Proposals use structured form schemas with typed fields for title, theme, objectives, venue, schedule breakdown, and an interactive itemized budget table with automated category subtotals and grand total calculations. This structured data directly populates both the web view and the generated official co-branded PDF document.

### Consequence

Eliminates calculation errors and unstructured formatting discrepancies across student submissions, ensuring standard fiscal transparency and automated validation during CSG review.

---

## ADR-024: Operational Service Health Diagnostics for Admin Dashboard

**Status:** Accepted

### Decision

The Super Admin Dashboard displays live operational service health indicators: PostgreSQL database connectivity and query round-trip latency, Supabase Realtime WebSocket connection status, Supabase Storage bucket availability and quota usage, and Resend transactional email API delivery status.

### Consequence

Enables the CSG adviser and IT administrators to diagnose connectivity or third-party service outages immediately without granting access to production server consoles or third-party APM dashboards.

---

## ADR-025: Announcement Pinning Precedence & Auto-Archive Lifecycle

**Status:** Accepted

### Decision

Announcement feeds enforce a maximum of 3 concurrently pinned items at the top of the feed, with CSG campus-wide pins taking visual precedence over sub-organization internal pins. Announcements support an optional `expires_at` auto-archive date. High-priority announcements trigger a dismissible urgent banner alert on recipient dashboards.

### Consequence

Prevents dashboard clutter and notification exhaustion while ensuring time-sensitive campus advisories command immediate attention.

---

## ADR-026: Document Revision Snapshots & Visual Field Diffing

**Status:** Accepted

### Decision

Every resubmission of a returned document creates an immutable version snapshot in a `document_versions` table. The reviewer comparison UI presents a visual field-by-field diff (highlighting changes in objectives, schedule, and budgetary line items) and displays a mandatory author 'Summary of Changes' note alongside historical reviewer feedback.

### Consequence

Allows CSG reviewers to immediately identify modifications between iterations without re-reading entire documents, speeding up review turnaround times and preventing unauthorized sneaky edits.

---

## ADR-027: Interactive Calendar Conflict Review Modal

**Status:** Accepted

### Decision

When an activity proposal involves a venue or timeslot conflict, the CSG review interface embeds a dedicated conflict comparison banner directly within the review modal. The reviewer is provided with integrated resolution actions:
1. Approve anyway (for joint/co-hosted events or partitioned venue spaces).
2. Request rescheduling via the standard Return action (pre-populating conflict details into the reviewer feedback notes).
3. Suggest an alternative campus venue from a real-time availability dropdown.

### Consequence

Keeps conflict mediation streamlined within the existing document approval pipeline, preventing fragmentation into separate dispute ticketing queues.

---

## ADR-028: Institutional Administrative Standards for Exported Reports

**Status:** Accepted

### Decision

Exported reports adhere to formal institutional standards:
- **PDF Reports:** Rendered with the official CvSU campus letterhead, report metadata (Academic Year, Semester, Date, Author), high-level KPI cards, styled data tables, and formal signatory approval blocks for the CSG President and CSG Adviser.
- **CSV Exports:** Provide clean, tabular, unstyled datasets with normalized ISO-8601 timestamps, explicit string identifiers, and raw numeric decimals to ensure frictionless analysis in spreadsheet software (Excel, Google Sheets).

### Consequence

Produces audit-ready, administrative-grade documents suitable for physical filing with the Office of Student Affairs and Services (OSAS) while providing clean raw data for ad-hoc spreadsheet analytics.

---

## ADR-029: Realtime Unread Message Aggregation via Indexed Postgres Helper

**Status:** Accepted

### Decision

Unread chat message counts are computed via an indexed Postgres helper function (`get_unread_message_counts(user_id)`) that leverages an index on `(conversation_id, created_at)` against `conversation_participants.last_read_at`. The frontend pairs this with Supabase Realtime channel listeners on `messages` to dynamically increment badges and clear counts upon reading.

### Consequence

Maintains sub-millisecond database response times even as conversation history grows into thousands of messages, avoiding heavy client-side calculations or unindexed table scans.

---

## ADR-030: Production Master Catalog Baseline & Isolated Local Seed Fixtures

**Status:** Accepted

### Decision

The official academic catalog of CvSU Imus Campus (accredited degree programs, year levels, standard section series, and the Central Student Government root organization) is embedded in production migrations as authoritative baseline reference data. All mock student accounts, test passwords, and dummy activities are strictly isolated to `supabase/seed.sql` which runs only in local development and testing environments.

### Consequence

Ensures immediate out-of-the-box readiness for campus deployment with real institutional course codes, while strictly abiding by the repository's no-mock-production-data security rule.








