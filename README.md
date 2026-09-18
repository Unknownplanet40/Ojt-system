# Universal Vibe Coding Template 🚀

> **The ultimate starter template for AI-assisted development (vibe coding).**  
> Clean, stack-agnostic, pre-configured with 17 design & UI agent skills, automated semantic releases, and living PRD / Architecture blueprints.

---

## ✨ Features

- 🧠 **AI Agent Native:** Pre-configured with [`AGENTS.md`](./AGENTS.md) and [`GEMINI.md`](./GEMINI.md) for full compatibility with Antigravity, Cursor, Windsurf, Claude Code, and Copilot.
- 🎨 **17 Pre-installed UI/UX Skills:** Instant access to industry-grade design skills:
  - High-end aesthetics (`ui-ux-pro-max`, `high-end-visual-design`, `make-interfaces-feel-better`, `design-taste-frontend`)
  - Design systems & components (`design-system`, `ui-styling`, `better-icons`, `brand`)
  - Marketing, slides & assets (`banner-design`, `slides`, `imagegen-frontend-web`, `imagegen-frontend-mobile`)
  - Deployment & Optimization (`deploy-to-vercel`, `vercel-optimize`, `redesign-existing-projects`, `web-design-guidelines`)
- 📦 **Automated Semantic Releases:** Continuous deployment pipeline via `.github/workflows/release.yml` with conventional commit parsing, automated version bumping (`VERSION`), `CHANGELOG.md` generation, and GitHub release archives with SHA-256 checksums.
- 📐 **Living Specs & Architecture:** Battle-tested templates for Product Requirements (`docs/template.prd.md`) and System Architecture (`docs/template.architecture.md`).
- 🛠️ **Cross-Platform Bootstrapping:** One-line initialization scripts for Windows PowerShell (`scripts/init-project.ps1`) and Unix Bash (`scripts/init-project.sh`).
- 🛡️ **Clean & Secure:** Comprehensive `.gitignore` protecting secrets (`.env`), build caches, and package dependencies across JavaScript/TypeScript, Python, and native platforms.

---

## 🚀 Quick Start

### 1. Bootstrap a New Project
Run the initialization script from the root directory to name your project and set up your environment:

**On Windows (PowerShell):**
```powershell
.\scripts\init-project.ps1
```

**On Linux / macOS (Bash):**
```bash
chmod +x ./scripts/init-project.sh
./scripts/init-project.sh
```

The script will:
1. Prompt for your project name and a brief description.
2. Initialize `docs/prd.md` and `docs/architecture.md` from the templates.
3. Initialize a Git repository (`main` branch) with an initial commit.

---

### 2. Scaffold Your Tech Stack
Because this template is stack-agnostic, you can initialize your desired framework directly into the root:

**Vite + React / Vue / Vanilla (TypeScript):**
```bash
npm create vite@latest . -- --template react-ts
```

**Next.js:**
```bash
npx create-next-app@latest .
```

**Python (FastAPI / Flask):**
```bash
python -m venv .venv
source .venv/bin/activate  # Or on Windows: .\.venv\Scripts\Activate.ps1
pip install fastapi uvicorn
```

---

## 📂 Project Structure

```
├── .agents/
│   ├── rules/                 # AI behavioral rules, commit conventions, token usage
│   │   ├── Project-default-rule.md
│   │   ├── ponytail.md        # Lazy senior dev mode (YAGNI, minimal diff, root-cause fixes)
│   │   └── token-usage.md
│   └── skills/                # 17 specialized design & agent skills
├── .github/
│   ├── ISSUE_TEMPLATE/        # Bug report and feature request issue templates
│   ├── workflows/
│   │   └── release.yml        # Automated release & changelog generator
│   └── pull_request_template.md
├── docs/
│   ├── template.prd.md         # Template for product specs
│   ├── template.architecture.md# Template for architecture blueprints
│   └── token-usage.md         # Running session token cost tracker
├── scripts/
│   ├── init-project.ps1       # Windows bootstrap script
│   └── init-project.sh        # Unix bootstrap script
├── AGENTS.md                  # Universal AI agent instructions
├── GEMINI.md                  # Antigravity/Gemini agent instructions
├── .gitattributes             # Line ending normalization
├── .gitignore                 # Universal ignores
└── README.md
```

---

## 🔄 Commit & Release Workflow

This project enforces **Conventional Commits**:
- `feat: ...` / `feat(scope): ...` → Triggers a **minor** release (`v1.1.0`)
- `fix: ...` / `fix(scope): ...` → Triggers a **patch** release (`v1.0.1`)
- `feat!: ...` or `BREAKING CHANGE:` → Triggers a **major** release (`v2.0.0`)

When code is merged/pushed to `main`, `.github/workflows/release.yml` automatically:
1. Calculates the new semantic version.
2. Updates `VERSION` and prepends the new release section in `CHANGELOG.md`.
3. Creates a Git tag and pushes it.
4. Generates a release zip archive with `sha256sums.txt`.
5. Publishes a GitHub Release with notes.
6. Updates `version.json` with the new stable release URL.
