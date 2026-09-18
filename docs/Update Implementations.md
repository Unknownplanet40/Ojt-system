# Step-by-Step Guide: Implementing GitHub-Based Auto-Updates in Any Application

This guide is an end-to-end, step-by-step tutorial for implementing an automated self-update system using **GitHub Releases**. 

If you are an **AI Agent** or **developer** tasked with adding auto-update capabilities to a project, follow these numbered steps sequentially.

---

## Table of Contents
1. [How GitHub-Based Updates Work](#1-how-github-based-updates-work)
2. [Step 1: Repository & Version Setup](#step-1-repository--version-setup)
3. [Step 2: Automated Release Workflow (GitHub Actions)](#step-2-automated-release-workflow-github-actions)
4. [Step 3: Checking GitHub for Updates (Backend)](#step-3-checking-github-for-updates-backend)
5. [Step 4: Downloading & Staging the Update Securely](#step-4-downloading--staging-the-update-securely)
6. [Step 5: Applying Files & Handling Process Restarts](#step-5-applying-files--handling-process-restarts)
7. [Step 6: Exposing API Endpoints (Backend Routes)](#step-6-exposing-api-endpoints-backend-routes)
8. [Step 7: Building the Frontend UI & Notifications](#step-7-building-the-frontend-ui--notifications)
9. [Step 8: Verification & Testing Workflow](#step-8-verification--testing-workflow)

---

## 1. How GitHub-Based Updates Work

```
  [Developer / Agent]
         │
         │ (1) git push origin main
         ▼
  [GitHub Actions] 
         │ (2) Reads commit message & tags new version (e.g. v1.2.0)
         │ (3) Bundles source code into `app-update-1.2.0.zip`
         │ (4) Publishes GitHub Release with changelog
         ▼
  [GitHub Releases CDN]
         ▲
         │ (5) Queries latest release via GitHub API / raw JSON
         │ (6) Downloads `app-update-1.2.0.zip` into `_update_tmp/`
         │ (7) Verifies files, replaces code, backs up old files
         │ (8) Restarts application (or reloads browser UI)
         │
  [User's Running Application]
```

---

## Step 1: Repository & Version Setup

First, create a single source of truth for the local application version.

### 1.1 Create a `VERSION` file
In your repository root, create a file named `VERSION` containing the starting version:
```text
1.0.0
```

### 1.2 Create `version.json`
Create a companion file in the root for static fallbacks:
```json
{
  "version": "1.0.0",
  "title": "Initial Release",
  "published_at": "2026-01-01T00:00:00Z"
}
```

---

## Step 2: Automated Release Workflow (GitHub Actions)

Create `.github/workflows/release.yml` in your repository. This workflow automatically calculates version bumps from commit messages, packages the clean application files into a `.zip` archive, and publishes a new GitHub Release.

### Create `.github/workflows/release.yml`:

```yaml
name: Automated Release

on:
  push:
    branches:
      - main
    paths-ignore:
      - 'README.md'
      - 'docs/**'
      - '.gitignore'

permissions:
  contents: write

jobs:
  build-and-release:
    runs-on: ubuntu-latest
    if: "!contains(github.event.head_commit.message, '[skip ci]')"
    steps:
      - name: Checkout Code
        uses: actions/checkout@v4
        with:
          fetch-depth: 0
          token: ${{ secrets.GITHUB_TOKEN }}

      - name: Calculate Version Bump
        id: bump
        run: |
          LATEST_TAG=$(git describe --tags --abbrev=0 2>/dev/null || echo "v1.0.0")
          echo "Current version tag: $LATEST_TAG"
          
          # Inspect commit messages since last release
          LOGS=$(git log ${LATEST_TAG}..HEAD --oneline)
          
          BUMP="patch"
          if echo "$LOGS" | grep -Eq "^[a-f0-9]+ (feat|feature)(\(.*\))?!?:"; then
            BUMP="minor"
          fi
          if echo "$LOGS" | grep -Eq "BREAKING CHANGE|!:"; then
            BUMP="major"
          fi
          
          CLEAN="${LATEST_TAG#v}"
          IFS='.' read -r -a PARTS <<< "$CLEAN"
          MAJOR=${PARTS[0]:-1}
          MINOR=${PARTS[1]:-0}
          PATCH=${PARTS[2]:-0}

          if [ "$BUMP" = "major" ]; then
            MAJOR=$((MAJOR + 1)); MINOR=0; PATCH=0
          elif [ "$BUMP" = "minor" ]; then
            MINOR=$((MINOR + 1)); PATCH=0
          else
            PATCH=$((PATCH + 1))
          fi
          
          NEW_TAG="v${MAJOR}.${MINOR}.${PATCH}"
          NEW_VER="${MAJOR}.${MINOR}.${PATCH}"
          echo "new_tag=${NEW_TAG}" >> $GITHUB_OUTPUT
          echo "new_version=${NEW_VER}" >> $GITHUB_OUTPUT

      - name: Update Version Files
        run: |
          NEW_VER="${{ steps.bump.outputs.new_version }}"
          echo "$NEW_VER" > VERSION
          cat <<EOF > version.json
          {
            "version": "$NEW_VER",
            "title": "Release v$NEW_VER",
            "published_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
          }
          EOF

      - name: Build Update Zip Archive
        run: |
          NEW_VER="${{ steps.bump.outputs.new_version }}"
          ZIP_NAME="app-update-${NEW_VER}.zip"
          
          # IMPORTANT: Adjust included files. Exclude user config, databases, secrets, and caches:
          zip -r "$ZIP_NAME" . \
            -x "*.git*" ".github/*" "docs/*" "data/*" "*.db" "*.sqlite*" \
               ".env*" "config.json" "config.yaml" "logs/*" "*.pyc" "*__pycache__*" \
               "node_modules/*"

      - name: Commit Updated Version Files
        run: |
          NEW_TAG="${{ steps.bump.outputs.new_tag }}"
          git config user.name "github-actions[bot]"
          git config user.email "github-actions[bot]@users.noreply.github.com"
          git add VERSION version.json
          git commit -m "chore(release): bump version to ${NEW_TAG} [skip ci]"
          git tag "$NEW_TAG"
          git push origin main --tags

      - name: Publish GitHub Release
        uses: softprops/action-gh-release@v2
        with:
          tag_name: "${{ steps.bump.outputs.new_tag }}"
          name: "Release ${{ steps.bump.outputs.new_tag }}"
          generate_release_notes: true
          files: "app-update-${{ steps.bump.outputs.new_version }}.zip"
```

---

## Step 3: Checking GitHub for Updates (Backend)

The updater queries GitHub to compare the local version with the latest release.

### Rate-Limiting Defense (Dual-Tier Check)
Unauthenticated calls to GitHub's REST API are capped at 60 requests/hour per IP.
- **Tier 1 (API)**: Query `GET https://api.github.com/repos/<owner>/<repo>/releases/latest`.
- **Tier 2 (Static Fallback)**: If the API returns HTTP 403 or fails, fetch `https://raw.githubusercontent.com/<owner>/<repo>/main/version.json` directly (raw GitHub content is NOT rate-limited).

### Create `updater.py` (or equivalent in your backend language):

```python
import os
import json
import re
import urllib.request
import urllib.error

GITHUB_REPO = "YourUsername/YourRepository"  # e.g. "octocat/hello-world"
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
VERSION_FILE = os.path.join(BASE_DIR, "VERSION")

PRIMARY_API = f"https://api.github.com/repos/{GITHUB_REPO}/releases/latest"
FALLBACK_URL = f"https://raw.githubusercontent.com/{GITHUB_REPO}/main/version.json"


def get_current_version():
    """Reads the current version from the local VERSION file."""
    try:
        with open(VERSION_FILE, "r", encoding="utf-8") as f:
            return f.read().strip()
    except Exception:
        return "0.0.0"


def check_for_update():
    """
    Checks GitHub for a newer version.
    Returns a dictionary with status, latest version, changelog, and download URL.
    """
    current = get_current_version()
    latest = None
    download_url = None
    changelog = ""
    release_title = ""

    # Tier 1: GitHub Releases API
    try:
        req = urllib.request.Request(PRIMARY_API, headers={"User-Agent": "AutoUpdater/1.0"})
        with urllib.request.urlopen(req, timeout=10) as res:
            data = json.loads(res.read().decode("utf-8"))
            latest = (data.get("tag_name") or "").lstrip("vV")
            changelog = data.get("body") or ""
            release_title = data.get("name") or f"Release v{latest}"
            for asset in data.get("assets", []):
                if asset.get("name", "").endswith(".zip"):
                    download_url = asset.get("browser_download_url")
                    break
    except Exception:
        # Tier 2: Fallback to raw version.json
        try:
            req = urllib.request.Request(FALLBACK_URL, headers={"User-Agent": "AutoUpdater/1.0"})
            with urllib.request.urlopen(req, timeout=10) as res:
                vj = json.loads(res.read().decode("utf-8"))
                latest = (vj.get("version") or "").strip()
                changelog = vj.get("changelog", "")
                release_title = vj.get("title", f"Release v{latest}")
        except Exception:
            pass

    # Deterministic asset download URL fallback
    if latest and not download_url:
        download_url = f"https://github.com/{GITHUB_REPO}/releases/download/v{latest}/app-update-{latest}.zip"

    # Compare versions
    is_available = False
    if latest:
        def parse_ver(v):
            return tuple(int(x) for x in re.findall(r"\d+", v))
        is_available = parse_ver(latest) > parse_ver(current)

    return {
        "status": "available" if is_available else "up_to_date",
        "current": current,
        "latest": latest,
        "download_url": download_url,
        "changelog": changelog,
        "title": release_title
    }
```

---

## Step 4: Downloading & Staging the Update Securely

Never unzip directly over live application code. Download into a temporary folder, sanitize the zip entries, and validate syntax.

### Safety Rules:
1. **Zip-Slip Protection**: Disallow paths that start with `/`, contain `..`, or contain Windows drive specifiers (`:`).
2. **Strict Allowlist**: Only permit application code (e.g. `src/`, `static/`, `app.py`).
3. **Strict Denylist**: Prevent overwriting `.env`, `config.json`, `data/`, or databases.
4. **Pre-flight Syntax Gate**: Verify that all Python files compile (`compile()`) or JS files parse before touching installed code.

```python
import shutil
import zipfile

TMP_DIR = os.path.join(BASE_DIR, "_update_tmp")
PRE_UPDATE_DIR = os.path.join(BASE_DIR, "data", "pre_update")

ALLOWED_FILES = {"app.py", "main.py", "requirements.txt", "package.json", "VERSION"}
ALLOWED_DIRS = {"src", "backend", "static", "templates"}
DENY_LIST = {".env", "config.json", "config.yaml", "data", "db", ".git", "logs"}


def is_safe_entry(name):
    clean = name.replace("\\", "/")
    # Prevent directory traversal
    if clean.startswith("/") or ".." in clean or ":" in clean:
        return False
    top = clean.split("/", 1)[0]
    if top in DENY_LIST:
        return False
    if clean in ALLOWED_FILES:
        return True
    if top in ALLOWED_DIRS:
        return True
    return False


def download_package(download_url, progress_callback=None):
    """Streams the release zip to a temporary file."""
    os.makedirs(TMP_DIR, exist_ok=True)
    zip_path = os.path.join(TMP_DIR, "update.zip")

    req = urllib.request.Request(download_url, headers={"User-Agent": "AutoUpdater/1.0"})
    with urllib.request.urlopen(req, timeout=120) as res, open(zip_path, "wb") as f:
        total = int(res.headers.get("Content-Length", 0))
        done = 0
        while True:
            chunk = res.read(65536)
            if not chunk:
                break
            done += len(chunk)
            f.write(chunk)
            if progress_callback:
                progress_callback(done, total)

    return zip_path


def extract_and_validate(zip_path):
    """Extracts to staging and validates syntax."""
    staging_dir = os.path.join(TMP_DIR, "staged")
    if os.path.exists(staging_dir):
        shutil.rmtree(staging_dir)
    os.makedirs(staging_dir)

    with zipfile.ZipFile(zip_path, "r") as z:
        for member in z.infolist():
            if is_safe_entry(member.filename):
                z.extract(member, staging_dir)

    # Pre-flight syntax validation gate
    for root, _, files in os.walk(staging_dir):
        for f in files:
            if f.endswith(".py"):
                path = os.path.join(root, f)
                with open(path, "rb") as fh:
                    compile(fh.read(), path, "exec")  # Raises SyntaxError if invalid

    return staging_dir
```

---

## Step 5: Applying Files & Handling Process Restarts

### The Process-Lock Challenge
- **Linux/macOS**: Open files can be unlinked and replaced immediately. The running process keeps the old inode in RAM until exit.
- **Windows**: The operating system locks running scripts (`app.py`), binaries, and open DLLs. Calling `os.replace()` directly on an open file raises `PermissionError`.

### The Solution:
1. Try replacing files directly with a retry loop (handles transient antivirus locks).
2. If `PermissionError` occurs, stage the locked file in `data/pending_update/`.
3. Spawn an independent, detached restart helper script (`_update_helper.py`) that waits for the old application process to exit, swaps the pending files into place, and restarts the application.

```python
import time
import subprocess
import sys

PENDING_DIR = os.path.join(BASE_DIR, "data", "pending_update")


def apply_staged_files(staging_dir):
    """Moves staged files into place, queuing locked files into pending_update."""
    pending_files = []
    
    for root, _, files in os.walk(staging_dir):
        for f in files:
            src = os.path.join(root, f)
            rel = os.path.relpath(src, staging_dir)
            dst = os.path.join(BASE_DIR, rel)
            os.makedirs(os.path.dirname(dst), exist_ok=True)

            try:
                os.replace(src, dst)
            except PermissionError:
                # File is locked by the running process (Windows)
                pend_dst = os.path.join(PENDING_DIR, rel)
                os.makedirs(os.path.dirname(pend_dst), exist_ok=True)
                shutil.copy2(src, pend_dst)
                pending_files.append(rel)

    if pending_files:
        with open(os.path.join(PENDING_DIR, "manifest.json"), "w") as mf:
            json.dump(pending_files, mf)

    # Clean up staging
    shutil.rmtree(TMP_DIR, ignore_errors=True)
    return len(pending_files) > 0


def spawn_restart_helper():
    """Spawns an independent background helper to swap locked files and restart."""
    helper_code = '''
import sys, os, time, shutil, json, subprocess

old_pid = int(sys.argv[1])
base_dir = sys.argv[2]

# Wait for old server to terminate
if os.name == "nt":
    import ctypes
    SYNCHRONIZE = 0x00100000
    WAIT_TIMEOUT = 0x00000102
    k32 = ctypes.windll.kernel32
    handle = k32.OpenProcess(SYNCHRONIZE, False, old_pid)
    if handle:
        while k32.WaitForSingleObject(handle, 500) == WAIT_TIMEOUT:
            pass
        k32.CloseHandle(handle)
else:
    while True:
        try:
            os.kill(old_pid, 0)
            time.sleep(0.5)
        except OSError:
            break

# Apply pending swaps
pending_dir = os.path.join(base_dir, "data", "pending_update")
manifest = os.path.join(pending_dir, "manifest.json")
if os.path.isfile(manifest):
    with open(manifest, "r") as f:
        files = json.load(f)
    for rel in files:
        src = os.path.join(pending_dir, rel)
        dst = os.path.join(base_dir, rel)
        if os.path.isfile(src):
            os.replace(src, dst)
    shutil.rmtree(pending_dir, ignore_errors=True)

# Relaunch application
subprocess.Popen([sys.executable, os.path.join(base_dir, "app.py")], cwd=base_dir)
os.remove(__file__)
'''
    helper_path = os.path.join(BASE_DIR, "_finish_update_helper.py")
    with open(helper_path, "w", encoding="utf-8") as f:
        f.write(helper_code.strip())

    flags = 0
    if hasattr(subprocess, "CREATE_NO_WINDOW"):
        flags = subprocess.CREATE_NO_WINDOW | subprocess.CREATE_NEW_PROCESS_GROUP

    subprocess.Popen(
        [sys.executable, helper_path, str(os.getpid()), BASE_DIR],
        cwd=BASE_DIR,
        creationflags=flags,
        close_fds=True
    )
```

---

## Step 6: Exposing API Endpoints (Backend Routes)

Expose four REST endpoints to let your frontend dashboard or client manage updates:

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/system/check-update` | Returns current vs latest version & changelog. |
| `POST` | `/api/system/apply-update` | Starts download and installation in a worker thread. |
| `GET` | `/api/system/update-progress` | Returns download progress (`{ stage, done, total }`). |
| `POST` | `/api/system/restart` | Calls `spawn_restart_helper()` and exits the server. |

### Example Flask / Express Implementation:
```python
from flask import Blueprint, jsonify
import threading

update_bp = Blueprint("update", __name__)
_progress = {"stage": "idle", "done": 0, "total": 0}

@update_bp.route("/api/system/check-update", methods=["GET"])
def route_check_update():
    return jsonify(check_for_update())

@update_bp.route("/api/system/apply-update", methods=["POST"])
def route_apply_update():
    info = check_for_update()
    if info["status"] != "available":
        return jsonify({"success": False, "message": "Already up to date."})

    def run_update():
        global _progress
        _progress = {"stage": "downloading", "done": 0, "total": 0}
        
        def on_prog(done, total):
            _progress = {"stage": "downloading", "done": done, "total": total}

        zip_file = download_package(info["download_url"], on_prog)
        _progress = {"stage": "installing", "done": 0, "total": 0}
        staging = extract_and_validate(zip_file)
        apply_staged_files(staging)
        _progress = {"stage": "done", "done": 100, "total": 100}

    threading.Thread(target=run_update, daemon=True).start()
    return jsonify({"success": True, "message": "Update started."})

@update_bp.route("/api/system/update-progress", methods=["GET"])
def route_progress():
    return jsonify(_progress)

@update_bp.route("/api/system/restart", methods=["POST"])
def route_restart():
    spawn_restart_helper()
    # Clean exit in 1 second
    threading.Timer(1.0, lambda: os._exit(0)).start()
    return jsonify({"restarting": True})
```

---

## Step 7: Building the Frontend UI & Notifications

In your web frontend or UI:

1. **Background Check**: Call `/api/system/check-update` when the user opens the application.
2. **Update Banner**: If `status === "available"`, show a banner:
   > 🚀 **Update Available**: v1.2.0 is now available. [View Changelog] [Update Now]
3. **Progress Bar**: When the user clicks **Update Now**, poll `/api/system/update-progress` every 500ms to render a percentage progress bar.
4. **Restart Prompt**: When progress reaches `done`, show:
   > ✅ **Update Installed!** Click **Restart Now** to complete installation.
5. **Post-Update Notification**:
   Store the last known version in `localStorage.setItem('last_version', current)`. On startup, if the version increased, display a "What's New" modal with the release changelog.

---

## Step 8: Verification & Testing Workflow

When testing your update implementation:

1. **Test Update Check**:
   Push a test release tag (e.g. `v1.0.1`) to your GitHub repository. Verify that calling `/api/system/check-update` returns `status: "available"` with the new version and changelog.
2. **Test Safety Protections**:
   Ensure existing files in `data/`, your `.env`, and your user configurations remain untouched after applying the update.
3. **Test Windows Locked Files**:
   Run the application on Windows, trigger the update, and confirm that `_finish_update_helper.py` successfully replaces `app.py` after the server terminates.
4. **Test UI Hot-Reload**:
   Modify only a CSS or HTML file in a release; confirm that the frontend refreshes automatically without terminating the backend.
