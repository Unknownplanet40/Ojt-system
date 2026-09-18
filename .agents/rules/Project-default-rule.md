# Global Rules

## 1. Response Prefix
Start every reply with the exact word `Capsss` followed by a single space. Never omit it, never alter it, never put anything before it.

This is a best-effort check — the model cannot detect its own omissions with certainty. If you (the user) notice the prefix missing, treat it as a signal to start a new session.

## 2. Commit & Push Workflow

Whenever the user asks to commit, release, or save changes in this project, follow this exact workflow, in order.

> `CHANGELOG.md` is generated entirely by `release.yml` from commit history on push to `main` — this workflow never edits `CHANGELOG.md` directly. Step 4's changelog-style commit body exists only for readable git history; it's not written to any file by hand.

### Step 1 — Check Remote Status
- Run `git fetch` and check whether `origin/main` has moved ahead (the other dev PC or CI may have pushed).
- Just note the result for now — the actual sync (rebase) happens in Step 5, after the local commit exists.

### Step 2 — Scan & Inspect Changes
- Run `git status` and `git diff` to thoroughly inspect all modified, staged, and untracked files.
- Ensure temporary/scratch test files are removed or appropriately handled before committing.

### Step 3 — Check for Automated Versioning (Project-Dependent)
- Look for an existing CI release workflow in the project (e.g. `.github/workflows/*release*.yml` or similar).
- **If one exists:** do NOT manually edit version files (`VERSION`, `version.json`, `package.json`'s version field, etc.). Assume the workflow reads conventional-commit subjects since the last tag and bumps the version automatically:
  - `feat:` / `feat(...)` → minor bump
  - `fix:` / `fix(...)` → patch bump
  - breaking changes → major bump
  - If local version files ever drift from origin, take origin's copy (the Step 5 rebase resolves this).
- **If no such workflow exists:** ask the user how they want versioning handled before proceeding — don't assume a scheme or bump a version file unprompted.

### Step 4 — Generate Commit Message (Full Changelog Style)
- Formulate a conventional commit subject line (`feat(...)`, `fix(...)`, `refactor(...)`, `chore(...)`, etc.).
- Check whether this commit relates to an open GitHub issue (run `gh issue list` if unsure). If it does, add a reference line directly under the subject:
  - `Closes #N` / `Fixes #N` — if this commit fully resolves the issue (GitHub auto-closes it on push to the default branch).
  - `Refs #N` — if this commit is related but doesn't fully resolve it.
- After the subject line (and issue reference, if any), write a **complete changelog-style body**:

  ```
  <type>(<scope>): <short summary>

  Closes #N  <!-- or "Refs #N" — omit this line entirely if no related issue -->

  ## Summary
  <1–3 sentence overview of what this commit does and why>

  ## Added
  - ...

  ## Changed
  - ...

  ## Fixed
  - ...

  ## Removed
  - ...

  ## Performance
  - ...

  ## Security
  - ...

  ## Breaking Changes
  - ...
  ```

- Only include sections that have actual content (omit empty ones).
- Write clear, concise bullet points. Focus on user-facing and developer-relevant changes.
- Summarize related changes instead of listing every single file.
- Mention breaking changes explicitly.

### Step 5 — Commit the Changes
- Stage the relevant files.
- Commit using the full conventional commit message generated in Step 4.
- **PowerShell escaping**: Do NOT pass complex commit messages via `git commit -m "..."` in PowerShell. Backticks, dollar signs, and other metacharacters will cause parse failures. Instead:
  1. Write the message to a temporary `.commit_msg.txt` file.
  2. Run `git commit -F .commit_msg.txt`.
  3. Delete `.commit_msg.txt` after the commit succeeds.
- If `origin/main` had moved ahead (per Step 1), now run `git rebase origin/main` and resolve any conflicts.

### Step 6 — Ask Before Push (Mandatory)
- **ALWAYS** ask the user for confirmation before executing `git push`.
- Only run `git push` once the user explicitly approves.
- After pushing, report:
  - the commit hash
  - push status
  - what the release CI will do next, if this project has one (e.g. "next auto-release: v2.5.0")

## 3. GitHub Issue Creation & Implementation

Trigger: the user's message contains the phrase **"create issue"** (case-insensitive), anywhere in the message. Uses the `gh` CLI against the current repository.

1. **Determine title and description** from the surrounding context of the user's message — a short, specific title, and a fuller description in the body covering what's wrong / what's wanted and any relevant detail already given.
2. **Determine the label:**
   - Run `gh label list` to see what labels actually exist in this repo — never assume a label exists without checking.
   - Match the request to the closest existing label (`bug`, `enhancement`, `question`, etc.) based on context.
   - If nothing fits confidently, ask the user which label to use rather than guessing.
3. **Create the issue, assigned to the user:**
   ```
   gh issue create --title "<title>" --body "<description>" --label "<label>" --assignee "@me"
   ```
4. **Report back** the issue number and URL from the command output.
5. **Immediately execute & implement the requested task:**
   - Once the issue is created, immediately proceed to implement the requested feature, addition, or bug fix without waiting for a second prompt (unless essential clarification is required).
6. **Remember the issue number** for the rest of the session — when committing the work that addresses it, apply Step 4 of the Commit & Push Workflow (Section 2) to reference it (`Closes #N` / `Refs #N`), so the issue and the commit that resolves it stay linked and easy to trace.