# Technical Architecture Document

**Product:** CvSU Imus Central Student Government Management System
**Version:** 1.1
**Date:** September 17, 2026
**Status:** Approved for Development
**Focus:** Single campus (CvSU Imus) – Production-grade, long-term maintainable

> **Changelog v1.0 → v1.1:** Added Realtime (live chat), audit logging, account
> lockout, courses/sections as first-class tables, an explicit testing strategy, and
> CI/CD gates. These close gaps against the PRD v1.1 (§3.5, §3.9, §3.10, §4).

---

## 1. Architecture Overview

The system follows a modern **JAMstack + Backend-as-a-Service** approach:

- **Frontend**: Next.js 15 (App Router) running on Vercel
- **Backend / Database / Auth / Storage / Realtime**: Supabase
- **Primary pattern**: Server Components + Client Components where needed, with Supabase client for data access and Row Level Security (RLS) for authorization

This architecture prioritizes:
- Security (production-grade auth + RLS + audit trail)
- Developer experience and long-term maintainability
- Performance and mobile responsiveness
- Automatic backups and operational simplicity
- Clear separation of concerns

```
┌─────────────────────────────────────────────────────────────┐
│                        Users (Browser / PWA)                │
└─────────────────────────────┬───────────────────────────────┘
                              │ HTTPS + WSS (Realtime)
┌─────────────────────────────▼───────────────────────────────┐
│                     Vercel (Next.js 15)                     │
│  • App Router                                               │
│  • Server Components + Client Components                    │
│  • API Routes / Server Actions                              │
│  • Tailwind CSS + shadcn/ui                                 │
└─────────────────────────────┬───────────────────────────────┘
                              │ Supabase Client (SSR + Client)
┌─────────────────────────────▼───────────────────────────────┐
│                         Supabase                            │
│  • PostgreSQL Database                                      │
│  • Auth (Email + Password, OTP)                             │
│  • Row Level Security (RLS)                                 │
│  • Storage (Documents & Files)                               │
│  • Realtime (live chat, presence, notification badges)      │
│  • Automatic daily backups                                  │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Technology Stack

| Layer                  | Technology                          | Purpose |
|------------------------|-------------------------------------|-------|
| Framework              | Next.js 15 (App Router) + TypeScript | Full-stack React framework |
| UI                     | Tailwind CSS + shadcn/ui            | Consistent, accessible, modern UI |
| Icons                  | Lucide React                        | Clean icon set |
| Auth                   | Supabase Auth                       | Production-grade authentication |
| Database               | Supabase PostgreSQL                 | Relational data + RLS |
| Realtime               | Supabase Realtime (Postgres Changes + Presence) | Live chat, presence, unread badges |
| File Storage           | Supabase Storage                    | Secure document storage |
| Hosting                | Vercel                              | Frontend + serverless functions |
| Email                  | Resend                              | OTP codes, transactional emails |
| Charts                 | Recharts or Tremor                  | Analytics visualizations |
| PDF Generation         | @react-pdf/renderer or similar      | Exportable PDF reports |
| CSV Export             | Built-in / papaparse                | Data export |
| PWA                    | next-pwa or native Next.js support  | Mobile installability |
| Form Validation        | Zod + React Hook Form               | Type-safe forms |
| Date Handling          | date-fns                            | Lightweight date utilities |
| Testing                | Vitest, Playwright, axe-core        | Unit, E2E, and accessibility tests |

---

## 3. Authentication & Authorization

### 3.1 Authentication Flow
- Email + Password (primary)
- **Account activation:** admin/president-issued temporary account → user completes profile on first login → account flips to active
- **Password reset (OTP)**: user submits student number + email → Supabase Auth OTP (or custom token stored server-side with a 5-minute TTL, whichever Supabase's current OTP flow supports natively — see §3.4) → new password set
- **Session management**: Supabase Auth JWT (1-hour expiry) with client-side automatic background token refresh; paired with a 30-minute idle inactivity auto-logout prompt to protect shared campus workstations
- Protected routes handled via Next.js middleware + server-side checks

### 3.2 Roles
Roles are stored in a `profiles` table and enforced via Row Level Security policies.

| Role              | Description |
|-------------------|-------------|
| `super_admin`     | Full system control |
| `csg_officer`     | CSG leadership access |
| `sub_org_officer` | Organization officer access |
| `member`          | Basic member access |

### 3.3 Authorization Strategy
- **Database level**: Row Level Security (RLS) policies on every table
- **Application level**: Role checks in Server Actions and Server Components
- Principle of least privilege applied throughout

### 3.4 Account Lockout & Rate Limiting
- `profiles` tracks `failed_login_attempts` and `locked_until`
- A Postgres function/trigger (or Supabase Auth hook) increments the counter on failed sign-in and sets `locked_until` after the configurable threshold (default 5 attempts / 15-minute temporary lock).
- Super Admins can manually reset failed attempts and clear `locked_until` at any time via the Admin Dashboard.
- Login and password-reset Server Actions are rate-limited per IP and per account (e.g. Upstash Redis or a simple Postgres-backed token bucket) to blunt brute-force and OTP-spam attempts
- Every attempt — success or failure — is written to `audit_logs` (§4)

---

## 4. Database Design (High-Level)

### Core Tables

```sql
profiles
├── id (uuid, references auth.users)
├── student_number
├── full_name
├── email
├── role (enum)
├── organization_id (nullable)
├── course_id (fk → courses)
├── section_id (fk → sections)
├── is_active
├── failed_login_attempts
├── locked_until
└── created_at / updated_at

courses
├── id
├── code
├── name
├── is_active
└── timestamps

sections
├── id
├── course_id
├── year_level
├── code
├── is_active
└── timestamps

organizations
├── id
├── name
├── abbreviation
├── description
├── type (academic / non-academic / etc.)
├── status (active / inactive / pending)
├── logo_url
└── timestamps

organization_officers
├── id
├── organization_id
├── profile_id
├── position
├── term_start
├── term_end
└── is_current

organization_members
├── id
├── organization_id
├── profile_id
├── joined_at
└── is_active

announcements
├── id
├── title
├── content
├── priority (high / normal / low)
├── author_id
├── is_pinned
├── visibility
└── timestamps

documents
├── id
├── organization_id
├── type (activity_proposal | project_proposal | minutes | excuse_letter | memorandum | other)
├── title
├── status (draft | submitted | under_review | approved | returned)
├── content (jsonb structured fields)
├── submitted_by
├── reviewed_by
├── review_notes
└── timestamps

document_versions
├── id
├── document_id
├── version_number
├── title
├── content (jsonb structured snapshot)
├── change_summary
├── submitted_by
└── created_at

document_files
├── id
├── document_id
├── file_path
├── file_name
├── mime_type
└── uploaded_by

events
├── id
├── organization_id
├── title
├── description
├── start_at
├── end_at
├── location
└── timestamps

conversations
├── id
├── type (direct | organization)
├── organization_id (nullable, set when type = organization)
└── timestamps

conversation_participants
├── conversation_id
├── profile_id
├── last_read_at
└── joined_at

messages
├── id
├── conversation_id
├── sender_id
├── content
├── created_at
└── edited_at (nullable, reserved for post-v1)

audit_logs
├── id
├── actor_id (nullable — null for failed logins on unknown accounts)
├── action (login_success | login_failure | password_reset | role_change | document_status_change | ...)
├── target_table / target_id (nullable)
├── ip_address
├── metadata (jsonb)
└── created_at
```

All tables will have appropriate indexes, foreign keys, and comprehensive RLS policies. `audit_logs` is insert-only from the application (no update/delete policies) and readable only by `super_admin`.

---

## 5. Key Architectural Decisions

### 5.1 Server Components First
Prefer React Server Components for data fetching and rendering. Use Client Components only for interactivity (forms, modals, charts, chat, that need browser APIs).

### 5.2 Server Actions
Mutations (create/update/delete) will primarily use Next.js Server Actions for better security and simpler code compared to traditional API routes. Every mutation that touches an audited action (§4 `audit_logs`) writes its log entry in the same transaction, not as a best-effort side call.

### 5.3 Row Level Security (RLS)
Every table will have RLS enabled. Policies will be written to enforce:
- Users can only see data they are allowed to see based on role and organization membership
- CSG officers have broader read access across organizations
- Sub-org officers can only modify their own organization's data
- `conversation_participants` and hierarchical role checks gate access to `messages`: regular members are restricted to their sub-organization group thread; Direct Messages require Sub-Org Officer, CSG Officer, or Super Admin privileges
- `audit_logs` is `super_admin`-read-only and append-only

### 5.4 Realtime (Live Chat)
- Supabase Realtime subscribes to `messages` inserts scoped to the user's conversations (enforced by RLS on the underlying table, not just client-side filtering)
- Presence channel per conversation for online/offline indicators
- Unread badge count derived from `conversation_participants.last_read_at` vs. latest `messages.created_at`
- Fallback: if a client's WebSocket connection fails or the project's concurrent-connection limit is hit, the chat UI degrades to short-interval polling rather than failing silently

### 5.5 File Handling & Document Generation
- Documents and attachments stored in Supabase Storage
- Signed URLs for secure temporary access
- Organized by organization and document type for easy management
- Official PDF export dynamically combines the CSG/campus master letterhead banner with the sub-organization's logo and custom header block

### 5.6 Backups & Reliability
- Supabase provides automatic daily backups
- Point-in-time recovery available on paid plans
- Vercel provides high availability for the frontend

### 5.7 Mobile Strategy & PWA
- Fully responsive design using Tailwind CSS
- Progressive Web App (PWA) capabilities for "Add to Home Screen"
- Service worker caches application shell, stylesheets, and recently viewed announcements and calendar events for read-only offline viewing
- Mutations (document submissions, chat messages) require active connectivity with a graceful offline indicator banner
- Touch-optimized components from shadcn/ui with WCAG 2.1 AA compliant tap targets

### 5.8 Accessibility (WCAG 2.1 AA)
- shadcn/ui primitives (built on Radix) provide correct semantics and keyboard nav out of the box — don't override that with raw `div`s for interactive elements
- Color contrast checked against Tailwind theme tokens, not just default palette
- `axe-core` run against key pages in CI (see §12) as a regression gate, not a one-time audit

### 5.9 Route Protection & Middleware Activation Gate
- Next.js edge middleware intercepts all authenticated requests.
- If a user's session profile has `is_active: false`, all protected routes redirect directly to `/activate`.
- Users cannot access dashboard metrics, navigation, or data mutation actions until they complete the profile activation form (validating unique student number, course, section, phone, and first-time password update).

### 5.10 Event Scheduling & Conflict Detection
- Event creation runs a venue and timeslot overlap query against existing scheduled events.
- Overlaps trigger an advisory soft warning to the user before submitting, and surface an active conflict indicator to CSG reviewers during associated activity proposal evaluations.


---

## 6. Folder Structure (Next.js App Router)

```
src/
├── app/
│   ├── (auth)/
│   │   ├── login/
│   │   ├── activate/            # temporary → active account flow
│   │   ├── reset-password/
│   │   └── layout.tsx
│   ├── (dashboard)/
│   │   ├── layout.tsx          # Protected layout
│   │   ├── page.tsx            # Main dashboard
│   │   ├── organizations/
│   │   ├── announcements/
│   │   ├── documents/
│   │   ├── chat/
│   │   ├── calendar/
│   │   ├── analytics/
│   │   ├── reports/
│   │   ├── profile/
│   │   └── admin/
│   │       ├── users/
│   │       ├── courses-sections/
│   │       └── audit-logs/
│   ├── api/                    # Only if needed
│   ├── layout.tsx
│   └── globals.css
├── components/
│   ├── ui/                     # shadcn components
│   ├── forms/
│   ├── charts/
│   ├── documents/
│   ├── chat/
│   └── layout/
├── lib/
│   ├── supabase/
│   │   ├── client.ts
│   │   ├── server.ts
│   │   └── middleware.ts
│   ├── actions/                # Server Actions
│   ├── validations/            # Zod schemas
│   ├── audit.ts                # shared helper to write audit_logs entries
│   └── utils.ts
├── types/
└── hooks/
```

---

## 7. Security Considerations

- All secrets stored in environment variables (never committed)
- Supabase service role key used only on the server
- RLS as the primary authorization layer
- HTTPS enforced
- Input validation with Zod on both client and server
- Rate limiting on auth endpoints (login, password reset/OTP request) — see §3.4
- Account lockout after repeated failed logins, with the lockout event itself audited
- Regular dependency updates

---

## 8. Performance & Scalability

- Server Components reduce client-side JavaScript
- Supabase connection pooling
- Image and file optimization via Next.js and Supabase
- Efficient queries with proper indexes (esp. `messages.conversation_id`, `audit_logs.created_at`, `documents.organization_id`)
- Realtime subscriptions scoped narrowly (per-conversation, not global) to avoid unnecessary fan-out
- Designed to handle a single campus comfortably (hundreds of active users)

---

## 9. Observability & Maintenance

- Supabase dashboard for database and auth monitoring
- Vercel analytics and logs
- `audit_logs` as the application-level activity trail, separate from infra logs
- Structured error handling
- Clear TypeScript types throughout the codebase
- Comprehensive README and internal documentation for long-term ownership

---

## 10. Deployment Pipeline

1. Development on local machine with Supabase local (or remote dev project)
2. Push to GitHub
3. CI runs lint, typecheck, unit tests, and `axe-core` accessibility checks (§12) before merge
4. Vercel automatically builds and deploys preview environments
5. Production deployment on main branch
6. Database migrations managed via Supabase migrations, applied as part of the deploy step rather than manually against production

---

## 11. Future Extensibility

The architecture is intentionally designed so that multi-campus support can be added later by:
- Introducing a `campuses` table
- Adding `campus_id` foreign keys
- Updating RLS policies to be campus-aware

No major rewrite will be required.

---

## 12. Testing Strategy

- **Unit tests (Vitest)**: Zod schemas, Server Action business logic, utility functions
- **Integration tests**: RLS policies verified against a Supabase local instance for each role (a `super_admin` fixture must not be the only account tested — verify `sub_org_officer` genuinely cannot read another org's documents, etc.)
- **E2E (Playwright)**: core flows — login, account activation, document submission and approval, live chat send/receive, announcement posting
- **Accessibility**: `axe-core` against key pages, run in CI as a blocking check
- **Pre-launch UAT**: scored against the same functionality/usability/reliability/efficiency rubric used in the original study (PRD §9)

---

**Document Owner:** Technical Lead
**Last Updated:** September 17, 2026
