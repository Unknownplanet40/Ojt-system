# AGENTS.md

## 1. Project Identity

**Project:** CvSU Imus Central Student Government Management System  
**Scope:** Cavite State University – Imus Campus  
**Status:** Version 1 development

This repository contains a production-oriented student government management system for CSG and recognized student organizations.

---

## 2. Source of Truth

Read these files before making architectural or product decisions:

1. `AGENTS.md` — agent behavior and repository rules
2. `PRD.md` — product requirements and feature scope
3. `architecture.md` — technical architecture and implementation constraints
4. `.github/` — issue forms, pull request templates, workflows, and repository automation

When documents disagree:

1. Explicit user instruction
2. `AGENTS.md`
3. `PRD.md`
4. `architecture.md`
5. Existing working code
6. Personal assumptions

Never silently invent requirements to resolve ambiguity.

If a conflict materially affects implementation, stop and report the conflict before making a large architectural change.

---

## 3. Agent Operating Rules

### 3.1 Inspect Before Editing

Before changing code:

- Inspect the repository structure.
- Read relevant existing files.
- Search for existing implementations before creating new ones.
- Check existing types, schemas, utilities, components, and Server Actions.
- Check existing tests.
- Check existing migrations before modifying the database.
- Check `.github/pull_request_template.md` before preparing a PR.
- Check relevant GitHub Issue Forms before creating an issue.

Do not recreate functionality that already exists.

### 3.2 Make the Smallest Correct Change

Prefer:

- Small focused changes
- Existing patterns
- Existing dependencies
- Existing components
- Existing abstractions

Avoid:

- Unrelated refactors
- Large rewrites
- New dependencies without a clear reason
- Duplicate utilities/components
- Mock implementations
- Temporary hacks left in production code
- Changing architecture without justification

A feature should not become an excuse to refactor unrelated code.

---

## 4. Product Scope

Version 1 includes:

- Authentication
- Account activation
- Password reset
- Account lockout
- Role-based authorization
- Organization directory
- Announcements
- Document management
- Document approval workflow
- Live chat
- Calendar/events
- Dashboards
- Analytics
- Reports
- User management
- Course management
- Section management
- Audit logging
- Responsive mobile UI
- PWA support

Version 1 does NOT include:

- Multi-campus support
- Native iOS/Android applications
- SIS integration
- Payment processing
- Financial accounting
- Digital signatures
- Public student portal
- Chat file/media sharing
- Chat message editing/deletion
- Arbitrary theme customization

Do not implement out-of-scope functionality unless explicitly requested.

---

## 5. Architecture Rules

### Frontend

Use the framework, libraries, and conventions already approved by `architecture.md`.

Prefer:

- Next.js App Router
- React Server Components by default
- Client Components only when browser state/interactivity requires them
- TypeScript
- Tailwind CSS
- shadcn/ui
- Lucide React

Prefer semantic HTML and accessible shadcn/Radix primitives.

Do not replace accessible components with unnecessary custom interactive `div` elements.

### Data Access

Use the database/backend services defined in `architecture.md`.

Server-side data access must use the appropriate server-side client.

Never expose:

- service-role credentials
- private API keys
- secrets
- privileged database credentials

to browser/client code.

### Mutations

Prefer the mutation mechanism defined by `architecture.md`.

Every mutation must:

1. Authenticate the user.
2. Authorize the requested operation.
3. Validate input with Zod or the repository's approved validation layer.
4. Perform the mutation.
5. Write required audit information.
6. Return a typed result.
7. Handle expected errors without leaking internal details.

Never rely only on client-side authorization.

---

## 6. Security Rules

Treat all client input as untrusted.

Validate on the server.

Authorization must be enforced server-side and at the database/RLS layer where applicable.

Never trust:

- client-provided role values
- client-provided organization IDs
- hidden form fields
- URL parameters
- client-side permission checks
- localStorage authorization state

Users must not be able to access another organization's private records by modifying IDs in requests.

Use least privilege.

Avoid returning sensitive database fields when they are not required.

Never log:

- passwords
- authentication tokens
- OTP codes
- service-role keys
- session secrets

IP addresses and audit data must only be handled where required by the product requirements and applicable privacy rules.

---

## 7. Database Rules

All production schema changes must use the repository's migration workflow.

Never manually modify production schema as part of normal development.

Every new table should have, where applicable:

- Primary key
- Appropriate foreign keys
- Appropriate indexes
- `created_at`
- `updated_at` where applicable
- RLS enabled
- Explicit RLS policies

Do not assume that hiding UI controls provides authorization.

For every authorization-sensitive table, test both:

- allowed access
- denied access

RLS must be treated as a security boundary when enabled by the architecture.

Prefer database constraints for invariants that must always hold.

---

## 8. Roles

The application currently defines:

- `super_admin`
- `csg_officer`
- `sub_org_officer`
- `member`

Do not create additional roles without an explicit requirement.

Role checks must be centralized where practical.

Do not scatter hard-coded role strings throughout components when an existing type/helper can be used.

---

## 9. Organization Isolation

A `sub_org_officer` may only manage records belonging to their authorized organization.

Never authorize organization access using an organization ID supplied directly by the client.

Resolve organization membership from the authenticated user's server-side identity and database records.

Test cross-organization access explicitly.

Example security test:

> Organization A officer must not be able to read, modify, approve, or delete Organization B's protected records.

---

## 10. Document Workflow

The expected document lifecycle is:

`draft → submitted → under_review → approved`

or:

`draft → submitted → under_review → returned`

Do not introduce undocumented status transitions.

Every status transition must verify:

- authenticated user
- role
- organization permissions
- current document status
- valid transition

Status changes must be auditable.

---

## 11. Audit Logging

Audit logs are security/accountability records.

Audited operations include, at minimum:

- Login success
- Login failure
- Password reset
- Role changes
- Document status changes
- Other security-sensitive administrative operations

Audit records should include the relevant actor, timestamp, action, target, and metadata required by the PRD.

Audit logs are append-only from normal application behavior.

Do not provide normal users with update/delete access to audit logs.

---

## 12. Error Handling

Expected application errors should return safe, structured errors.

Do not expose:

- SQL errors
- stack traces
- secrets
- internal infrastructure details
- raw backend errors to end users

Log technical details server-side where appropriate.

Use consistent error/result patterns throughout the application.

---

## 13. UI Rules

The UI must be:

- Responsive
- Keyboard accessible
- Screen-reader friendly
- Touch-friendly
- Consistent with existing design patterns

Prefer existing shadcn/ui components when the repository uses them.

Do not introduce a second UI component system.

Do not use arbitrary colors when existing design tokens are available.

Loading, empty, error, and success states should be implemented for user-facing async operations.

Forms should provide:

- Validation
- Clear field errors
- Loading state
- Success/error feedback
- Disabled submission while processing

---

## 14. Performance Rules

Prefer:

- Server Components
- Server-side data fetching
- Narrow database queries
- Pagination for potentially large datasets
- Proper database indexes
- Selective realtime subscriptions
- Lazy loading for expensive client functionality

Do not fetch an entire table when the UI only needs a subset.

Do not subscribe to global realtime events when a narrower subscription is possible.

Avoid unnecessary client-side JavaScript.

---

## 15. Testing Requirements

Before considering a feature complete:

### Unit Tests

Test:

- Validation schemas
- Business rules
- Utility functions
- Server Action or mutation logic

### Integration Tests

Test:

- Database constraints
- RLS policies where applicable
- Authorization
- Organization isolation

### E2E Tests

Core flows should cover:

- Login
- Account activation
- Password reset
- Document submission
- Document review/approval
- Announcements
- Live chat
- Important administrative operations

### Accessibility

Run the repository's configured accessibility checks against required pages.

### Required Security Tests

For permission-sensitive features, include negative tests.

Example:

```text
Allowed:
Sub-org officer → own organization's document

Denied:
Sub-org officer → another organization's document
```

A test suite that only verifies successful operations is incomplete.

---

## 16. Definition of Done

A feature is not complete when the UI works once.

A feature is complete when:

- Requirements are implemented.
- Server-side validation exists.
- Authorization exists.
- RLS is correct where applicable.
- Loading/empty/error states exist.
- Tests cover important logic.
- Accessibility has been considered.
- Audit logging exists when required.
- No unrelated files were changed.
- TypeScript passes.
- Lint passes.
- Tests pass.
- Build passes when applicable.
- Documentation is updated if behavior or architecture changed.

Never claim a check passed unless it was actually run.

If a check could not be run, explicitly report that limitation.

---

## 17. Development Workflow

Before starting:

1. Inspect repository.
2. Read relevant documentation.
3. Identify existing implementation.
4. Identify affected files.
5. Identify security/authorization implications.
6. Plan the smallest change.

During implementation:

1. Implement.
2. Validate inputs.
3. Implement authorization.
4. Add/update tests.
5. Run targeted checks.
6. Run broader checks when appropriate.

Before finishing:

1. Review git diff.
2. Remove unrelated changes.
3. Check for secrets/debug code.
4. Run tests.
5. Run typecheck.
6. Run lint.
7. Run build when appropriate.
8. Update documentation.
9. Report exactly what changed and what was tested.

---

## 18. Git Rules

Before committing:

```powershell
git fetch --all --prune
git status
git diff
git diff --cached
```

Use conventional commit messages.

Examples:

```text
feat(auth): add account activation flow
fix(documents): prevent unauthorized status changes
test(rls): add organization isolation coverage
docs(architecture): document audit logging
```

Do not create meaningless commits.

Do not rewrite unrelated history.

Do not push automatically.

Pushing requires explicit user confirmation.

---

## 19. Issue Workflow

When asked to create an issue:

1. Inspect existing Issue Forms.
2. Select the appropriate issue type.
3. Include only verified information.
4. Do not invent reproduction steps.
5. Use existing repository labels.
6. Assign the issue to `@me` when requested by repository workflow.
7. Preserve issue linkage when implementing it.

When implementing an issue, use an appropriate GitHub closing keyword such as:

```text
Closes #123
```

or:

```text
Fixes #123
```

or:

```text
Refs #123
```

---

## 20. Documentation Rules

Update documentation when changing:

- Architecture
- Database schema
- Authentication
- Authorization
- Environment variables
- Deployment
- Major user workflows
- API contracts
- Development commands

Do not document behavior that does not exist.

---

## 21. Dependency Rules

Before adding a dependency:

1. Check whether the repository already provides the required functionality.
2. Check whether an existing dependency can solve the problem.
3. Prefer a small, maintained dependency.
4. Avoid duplicate libraries serving the same purpose.
5. Document the reason when adding a significant dependency.

Do not add packages merely for convenience.

---

## 22. Environment Variables

Never commit secrets.

Maintain an example environment file such as:

```text
.env.example
```

Document:

- Variable name
- Purpose
- Required/optional status
- Which environment uses it

Never put real credentials in `.env.example`.

---

## 23. No Mock Production Data

Do not add fake production data, placeholder records, fake users, fake organizations, or simulated API responses to make a feature appear functional.

Development fixtures are allowed only when:

1. They are clearly separated from production data.
2. They are documented.
3. They cannot execute in production.
4. They are required for automated tests or local development.

---

## 24. Existing Implementation Rule

Before introducing a new pattern, search the repository for an existing implementation.

Prefer consistency with working code over introducing a theoretically cleaner pattern.

Do not create duplicate:

- Authentication helpers
- Database clients
- Validation utilities
- Date/format utilities
- UI primitives
- API abstractions

unless there is a documented reason.

---

## 25. Do Not Assume

Agents must not assume:

- A user belongs to an organization because the client supplied an organization ID.
- A user is an officer because the UI displays an officer role.
- A document can be approved because the button is visible.
- A user can access another organization because the route is reachable.
- A database record is safe because the page is protected.
- An operation succeeded because the client received a successful response.
- A feature exists because it is mentioned in documentation but not implemented.
- A feature is required merely because it would be useful.

When a requirement is ambiguous, inspect existing requirements and implementation first.

If ambiguity materially changes behavior or security, ask before implementing.

---

## 26. Agent Communication

When reporting work, use this structure:

### Changed

- List concrete changes.

### Security

- List authorization/RLS/security changes.
- Say `None` when none were required.

### Tests

- List commands actually executed and their results.

### Not Tested

- List anything that could not be verified.

### Notes

- Mention assumptions, limitations, or follow-up work.

Do not claim something is production-ready unless the relevant checks were actually performed.
