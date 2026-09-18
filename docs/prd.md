# Product Requirements Document (PRD)

**Product Name:** CvSU Imus Central Student Government Management System
**Version:** 1.1
**Date:** September 17, 2026
**Status:** Draft for Development
**Owner:** Product Team
**Campus Focus:** Cavite State University – Imus Campus only

> **Changelog v1.0 → v1.1:** Reconciled against the original capstone requirements
> (client-agreed feature table, non-functional requirements table, and evaluation
> criteria). Added Live Chat, account security/lockout, audit logging, an explicit
> non-functional requirements table, course & section administration, and a
> testing/evaluation plan. See §11 for the full diff rationale.

---

## 1. Overview

### 1.1 Problem Statement
The Central Student Government (CSG) of Cavite State University – Imus Campus currently relies heavily on manual, paper-based processes and fragmented tools (Facebook, Google Forms, group chats, spreadsheets). This leads to:

- Lost or incomplete documents during officer transitions
- Difficulty tracking organization recognition status and current officers
- Inefficient communication between CSG and recognized student organizations
- Lack of centralized records for activity proposals, minutes, memoranda, and reports
- Limited visibility into organizational activities and performance
- No audit trail of who accessed or changed what, which the CSG adviser needs for accountability

### 1.2 Solution
A modern, web-based management system designed specifically for CvSU Imus CSG and its recognized student organizations. The system will provide secure role-based access, centralized document management, announcements, real-time communication, analytics, and officer handoff capabilities while remaining simple enough for student leaders to adopt quickly.

### 1.3 Goals
- Replace paper-based and fragmented digital processes with a single source of truth
- Enable smooth leadership transitions every academic year
- Improve transparency and coordination between CSG and student organizations
- Provide actionable insights through analytics and exportable reports
- Give the CSG adviser/admin a verifiable audit trail of account and document activity
- Deliver a production-grade, maintainable system that can be owned long-term

### 1.4 Non-Goals (Version 1)
- Multi-campus support
- Monetization / billing features
- Native mobile applications (PWA only)
- Complex financial accounting or payment processing
- Integration with university SIS or other campus systems
- Fully custom theming/personalization beyond light/dark mode (stretch, see §7.10)

---

## 2. Users & Roles

| Role                    | Description                                      | Key Permissions |
|-------------------------|--------------------------------------------------|-----------------|
| Super Admin             | System administrator (usually CSG Adviser or IT) | Full access, manage all users, courses/sections, org registry, and settings; view audit logs |
| CSG Officer             | Elected CSG officers (President, VPs, Senators, Committee Heads) | Manage announcements, view all orgs, approve/review documents, access analytics, customize document headers |
| Sub-Org Officer         | Officers of recognized student organizations      | Manage own organization profile, submit documents, view CSG announcements, use live chat |
| Member                  | Regular members of organizations                 | View announcements, calendar, limited documents, use live chat |

Role enforcement will be handled via Supabase Auth + Row Level Security (RLS).

---

## 3. Core Features (Version 1)

### 3.1 Authentication & Access Control
- Secure login using email + password (Supabase Auth)
- **Account activation & tiered provisioning:** Super Admins can provision any account; CSG Officers can provision Sub-Org Presidents; Sub-Org Presidents/Officers can provision officers and members for their organization. On first login, the new user must complete their profile (student number, course, section, contact info) before the account transitions to active.
- **Password reset (OTP):** user submits student number + registered email → system sends a one-time code valid for **5 minutes** → user sets a new password after verification. Expired/reused codes are rejected.
- Role-based access control enforced at both the RLS and Server Action layer
- Session management and secure logout
- **Account lockout:** after a configurable threshold of consecutive failed login attempts (default 5), the account enters a temporary 15-minute lockout (`locked_until`) and the attempt is logged (see §3.10 Audit Logging). Super Admins have the capability to manually unlock accounts immediately via the Admin Dashboard.

### 3.2 Organization Directory
- List of all recognized student organizations
- Organization profile (name, abbreviation, description, type, status, custom header/logo)
- Current officers list with positions and contact information
- Ability for CSG to update recognition status (Active / Inactive / Pending)
- **Operational Suspension for Inactive/Pending:** Organizations with `Inactive` or `Pending` status are restricted to read-only access (cannot submit documents, create events, or send group chat messages) until explicitly marked `Active` by CSG leadership.
- Search and filter by organization type or status
- **Term-Based Leadership Transition:** Officer tenures are tracked via term dates (`term_start`, `term_end`, and `is_current`). Expired officers automatically revert to regular members with historical read-only access, while incoming officers inherit organization management and transition archives.

### 3.3 Announcements & News Feed
- **Dual-Level Broadcasting:** CSG officers can publish announcements campus-wide or targeted to specific organizations. Sub-Org Officers can publish internal announcements strictly scoped to their own organization's members.
- Priority levels: High (banner/pinned) / Normal / Low
- Visibility: Campus-wide, specific organization(s), or internal org-only
- Pin important announcements (maximum 3 concurrent pins; CSG pins take visual precedence over sub-org pins; optional `expires_at` auto-archive date; High Priority displays as a dismissible alert banner)
- Rich text support and basic attachments (following §3.4 file constraints)

### 3.4 Document Management
Supported document types:
- Activity Proposal
- Project Proposal
- Excuse Letter
- Minutes of the Meeting
- Office Memorandum
- Other official documents

Features:
- Template-based creation with **combined header letterhead composition**: merges a standardized campus/CSG header banner (configured by CSG leadership/Admin) with the submitting sub-organization's custom organization header and logo.
- **Structured Activity Proposals:** Typed fields for title, theme, objectives, venue, schedule breakdown, and an interactive itemized budget table calculating automatic category subtotals and grand totals.
- Upload supporting files (max 10MB per file; strictly `.pdf`, `.docx`, `.pptx`, `.png`, `.jpg`, `.jpeg`)
- Status tracking (`Draft → Submitted → Under Review → Approved / Returned`)
- **Return & Resubmission Lifecycle:** When marked `returned` by a reviewer with feedback, the author can edit and resubmit the same document (`returned → submitted`), with all revisions, reviewer notes, and audit logs appended to the document's history.
- **Version History & Visual Diffs:** Immutable version snapshots (`document_versions`), visual field diffing highlighting modified objectives and budget line items, and mandatory author 'Summary of Changes' note on resubmission.
- Download as PDF with official co-branded letterhead
- Officer transition folder (knowledge handoff across academic years)

### 3.5 Live Chat (Real-Time Messaging)
Client-agreed core feature carried over from the original requirements — not a future/optional add-on.
- **Hierarchical communication boundaries:** Regular members can only chat within their assigned sub-organization's group thread; cross-organization direct messaging is reserved for Sub-Org Officers, CSG Officers, and Super Admins.
- Read receipts and online/offline presence indicator
- New-message notification badge in the dashboard
- Message history persisted and searchable within a conversation
- Out of scope for v1: file/media sharing in chat, message editing/deletion, group chat beyond org-level threads (candidates for v1.1+)

### 3.6 Activity & Event Calendar
- Campus-wide calendar of CSG and organization activities
- Ability to create events linked to organizations
- Filter by organization or event type
- **Soft Conflict Detection & Reviewer Resolution:** Automated check for overlapping campus venues and time slots during event creation/submission. Displays an advisory warning to the submitting officer. During proposal review, CSG reviewers are presented with an interactive conflict comparison modal featuring integrated actions: approve anyway (co-hosted/partitioned), return with pre-populated reschedule feedback, or suggest an open alternate venue.

### 3.7 Dashboards
**CSG Officer Dashboard**
- Number of active organizations
- Pending document reviews
- Recent announcements
- Unread chat messages
- Quick links to key modules

**Sub-Org Officer Dashboard**
- Own organization status
- Pending documents
- Upcoming events
- Recent CSG announcements
- Unread chat messages

**Admin Dashboard**
- Total active users, CSG officers, daily logins, currently locked accounts
- User access log: account name, login timestamp, IP address, success/failure
- Monthly login trend
- System health indicators (live database latency, Realtime WebSocket connectivity, Supabase Storage quota usage, Resend email delivery status)

### 3.8 Analytics & Reports
Two related but distinct capabilities, matching the client's original request for both:
- **Reports:** operational, record-level views — document status per organization, activity log exports, submission timelines — for tracking progress and keeping records transparent. Scoped by Academic Year and Semester. Super Admins and CSG Officers can export campus-wide and individual organization reports; Sub-Org Officers can only export their own organization's summary.
- **Analytics:** aggregated visualizations — organization activity overview (documents submitted, events held), document status distribution, participation trends over time.
- **Institutional Administrative Standards:** PDF reports rendered with official CvSU letterhead, report metadata, executive KPI cards, styled data tables, and formal signatory approval blocks for CSG President and Adviser. CSV exports provide clean, unstyled tabular data with ISO-8601 timestamps and raw numeric fields.
- Charts built with Recharts/Tremor per the architecture doc.

### 3.9 User, Course, Section & Profile Management
- View and edit personal profile
- CSG/Admin can manage user accounts and assign roles
- **Course & Section management:** Admin maintains the list of courses and sections (by year level), and can enable/disable entries — this is what populates the dropdowns on user profiles and organization membership, rather than being free-text.
- **Strict Middleware Gating:** Users created with `is_active: false` are intercepted by Next.js middleware and redirected to `/activate` until profile completion (student number, course, section, contact info, initial password change).
- Soft-delete / deactivate accounts

### 3.10 Audit Logging
New in v1.1 — required to satisfy the Admin Dashboard's access-log requirement and general accountability:
- **State-diff mutation logging:** Every login attempt (success/failure), password reset, account lockout, role change, and document status transition is recorded with actor, timestamp, IP address, and JSONB metadata containing structured before/after diffs (`previous_state`, `new_state`, notes).
- Admin-only read access via RLS (append-only from application).
- Exportable for the CSG adviser's records.

### 3.11 Mobile Experience & PWA
- Fully responsive design using Tailwind CSS and mobile-first navigation layouts
- Progressive Web App (PWA) support for mobile installability ("Add to Home Screen")
- **Offline Strategy:** Service worker caches app shell, styles, and recently viewed announcements and calendar events for read-only offline viewing. Form submissions, chat messaging, and status updates gracefully require an active network connection and present an offline status banner.
- Touch-friendly interface with WCAG 2.1 AA accessible target sizes

---

## 4. Non-Functional Requirements

Carried over from the original requirements table and made testable:

| Quality Attribute | Requirement |
|---|---|
| **Time behavior / Performance** | First Contentful Paint < 2.5s and dashboard data load < 1.5s on a typical campus 4G/Wi-Fi connection |
| **Usability** | New officers can complete the account activation flow and submit a first document without external help |
| **Accessibility** | WCAG 2.1 AA compliance (shadcn/ui + semantic HTML gets most of this for free; verify with axe-core in CI) |
| **Security** | Strong auth (Supabase Auth + RLS on every table), account lockout after repeated failed logins, all access logged |
| **Scalability** | Supports hundreds of concurrent users and multi-year data growth without a redesign |
| **Reliability** | ≥ 99.5% uptime target; automatic daily backups |

---

## 5. Success Metrics (Version 1)

| Metric                              | Target |
|-------------------------------------|--------|
| System adoption by CSG officers     | ≥ 80% of current officers actively using within 1 month |
| Document submission via system      | ≥ 70% of official documents submitted digitally |
| User satisfaction (post-launch survey) | Average ≥ 4.2 / 5.0 (functionality, usability, reliability, efficiency — matching the evaluation rubric used in the original study) |
| Officer transition success          | New officers can access previous documents without manual transfer |
| System uptime                       | ≥ 99.5% |

---

## 6. Constraints & Assumptions

### Constraints
- Must work reliably on average student devices and campus internet
- Must comply with basic data privacy expectations (student data)
- Limited initial development resources (solo or small team)
- Live chat must degrade gracefully (polling fallback) if Supabase Realtime connection limits are hit on the free/lower tiers

### Assumptions
- CSG leadership is willing to adopt and champion the system
- Organizations are willing to submit documents digitally
- Internet access is generally available for officers
- One primary Super Admin will be assigned

---

## 7. Out of Scope for Version 1
- Multi-campus architecture
- Native iOS / Android apps
- Advanced workflow automation / digital signatures
- Integration with university student information systems
- Payment or budget tracking modules
- Public-facing student portal (beyond logged-in users)
- File/media sharing inside live chat
- **7.10 Full theming/preference customization** (background/theme picker from the original thesis) — nice-to-have, deferred unless time allows after core modules ship; light/dark mode via Tailwind is in scope, arbitrary theme picking is not.

---

## 8. Future Considerations (Post Version 1)
- Multi-campus support
- Advanced analytics and AI-assisted insights
- Native mobile applications
- Digital approval workflows with e-signatures
- Public transparency dashboard for the student body
- Integration with other campus systems
- Rich media in live chat

---

## 9. Testing & Evaluation Plan
The original study validated the system with 50 respondents (CSG officers, sub-org officers, instructors) across four criteria: functionality, usability, reliability, efficiency. Carry this forward as the v1 acceptance test:
- Pre-launch UAT with a representative sample of current officers, scored on the same four criteria (target: ≥ 4.2/5.0 weighted mean each)
- Automated test coverage: unit tests for Server Actions and Zod validation, integration tests against a Supabase local instance for RLS policies, and a Playwright smoke suite for the core flows (login, document submission, chat)
- Accessibility check (axe-core) run in CI against key pages

---

## 10. Timeline (High-Level)

| Phase                  | Duration     | Focus |
|------------------------|--------------|-------|
| Foundation             | 2–3 weeks    | Auth, roles, RLS, audit logging, database, basic layout |
| Core Modules           | 5–7 weeks    | Organizations, Announcements, Documents, Live Chat |
| Analytics & Polish     | 2–3 weeks    | Dashboards, reports, mobile refinements, accessibility pass |
| Testing & Launch Prep  | 2 weeks      | User testing with CSG officers, bug fixes |
| Launch & Support       | Ongoing      | Soft launch, training, iteration |

*(Live Chat adds real-time infra work, hence the +1–2 weeks in Core Modules versus v1.0.)*

---

## 11. What changed from v1.0 and why
Cross-checking against the original capstone requirements (client-agreed feature table and non-functional requirements table) surfaced three gaps that would have caused a scope mismatch with what CSG actually asked for:
1. **Live Chat** was a client-agreed main feature, not an optional extra — added as §3.5.
2. **Account security & audit logging** — the original admin dashboard spec calls for daily logins, locked accounts, and IP-logged access history, which implies lockout policy and an audit log table that v1.0 didn't have.
3. **Explicit, testable non-functional requirements** (esp. WCAG 2.1 accessibility, which was a stated compliance requirement) were referenced in the architecture doc but never stated as acceptance criteria in the PRD — now §4.

Also tightened: Reports vs. Analytics were treated as one feature in v1.0; the client actually asked for both as distinct capabilities. Course & Section management was implied by the `profiles.course`/`profiles.section` fields but had no owning admin module — added as §3.9.

---

## 12. Open Questions
- Who will serve as the initial Super Admin?
- Exact list of recognized organizations for initial data seeding?
- Preferred document header templates from current CSG?
- Any mandatory fields required by OSAS for document submissions?
- Failed-login lockout threshold and lockout duration — 5 attempts / 15 minutes assumed above, confirm with CSG adviser
- Retention period for audit logs and chat history

---

**Document Owner:** Product Team
**Last Updated:** September 17, 2026
