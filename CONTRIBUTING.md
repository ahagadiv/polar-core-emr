Contributing to 🧊 POLARCORE™

A project by POLAR™ Healthcare Professional Corporation

Thank you for contributing. POLARCORE™—and home-based healthcare—gets better because of people like you.

We aim to merge your pull request smoothly and quickly. Before you begin, please ensure your work aligns with our standards in Development Policies
 (coding style, security, HIPAA-aligned data handling, commit conventions, and review flow).

Code Contributions (local development)

You’ll need a local POLARCORE™ environment. The fastest path is Docker.

Quick Start (Docker)

Fork & clone

Fork: https://github.com/polarhealthcare/polarcore

Clone your fork locally:

git clone https://github.com/<your-username>/polarcore.git
cd polarcore


(Recommended) Add upstream:

git remote add upstream https://github.com/polarhealthcare/polarcore.git


Prereqs

Docker & Docker Compose installed

Node.js 22.x

PHP 8.3+ (for local tooling when needed)

Git

Start dev stack

docker compose up --build


When ready, you’ll see app/services start messages.

Access the app

App: https://localhost:9300/ (or http://localhost:8300/)

Default admin: admin / pass (dev only)

Database access (dev)

phpMyAdmin: http://localhost:8310/

Direct MySQL: localhost:8320

Username/Password: openemr / openemr (dev only)

Edit code & hot-reload
Edit files on your machine; most changes appear on refresh.
For theme asset rebuilds, run:

docker compose exec app /root/devtools build-themes


Clean up

docker compose down -v      # full clean
# or
docker compose down         # keep volumes/cache for faster next boot


Stay current

docker compose pull
git fetch upstream
git rebase upstream/main


Open a PR
Push to your fork and open a PR into polarhealthcare/polarcore:main.

POLARCORE™ Dev Tasks & Utilities

All commands run inside the app container unless stated.

Debugging (Xdebug)

Ensure port 9003 open on host.

VS Code: use a PHP Xdebug launch config (template provided in ./.vscode/launch.json.example).

Helpers:

docker compose exec app /root/devtools xdebug-log
docker compose exec app /root/devtools list-xdebug-profiles

API (REST / FHIR) – Swagger

Swagger UI: https://localhost:9300/swagger

Rebuild API docs:

docker compose exec app /root/devtools build-api-docs


Register a local OAuth2 client for testing:

docker compose exec app /root/devtools register-oauth2-client

PHP & Code Quality
docker compose exec app /root/devtools php-log
docker compose exec app /root/devtools psr12-report
docker compose exec app /root/devtools psr12-fix
docker compose exec app /root/devtools php-parserror
docker compose exec app /root/devtools unit-test
docker compose exec app /root/devtools api-test
docker compose exec app /root/devtools e2e-test


Full sweep:

docker compose exec app /root/devtools clean-sweep


Tests only:

docker compose exec app /root/devtools clean-sweep-tests

Reset / Demo Data (safe for dev only)
docker compose exec app /root/devtools dev-reset
docker compose exec app /root/devtools dev-reset-install
docker compose exec app /root/devtools dev-reset-install-demodata

Snapshots (backup/restore dev data)
docker compose exec app /root/devtools backup example
docker compose exec app /root/devtools restore example
docker compose exec app /root/devtools list-snapshots


Export/import “capsules”:

docker compose exec app /root/devtools list-capsules
docker compose cp app:/snapshots/example.tgz .
docker compose cp example.tgz app:/snapshots/

Optional Integrations (for local testing)

CouchDB (patient docs) GUI: http://localhost:5984/_utils/ (admin/password: admin/password)

LDAP (auth testing) toggles:

docker compose exec app /root/devtools enable-ldap
docker compose exec app /root/devtools disable-ldap

Non-Docker Install

If you prefer native installs, see INSTALL.md
 for Ubuntu 24.10 + PHP 8.3 + Nginx/Apache + MariaDB/MySQL setup, along with local SSL and dev CA guidance.

Security, Privacy & Compliance

Follow least privilege principles in code and infra.

Never commit secrets or PHI. Use .env.local and secret managers.

Report vulnerabilities privately to security@polarhealthcare.net
.

See SECURITY.md
 and CODE_OF_CONDUCT.md
.

Financial Support & Partnerships

POLARCORE™ is developed by POLAR™ Healthcare for the home-care ecosystem (mobile vascular access, home health, home infusion, dialysis).

Sponsorships / pilots / partnerships: partners@polarhealthcare.net

Investor relations: invest@polarhealthcare.net

Credits

Founder & Principal Architect

Alejandro M. Hagad IV, RN, VA-BC, CCRN — Precision point-of-care systems, mobile vascular access workflows

Clinical & Ops

Jean Patrick “JP” Sato, RN — Alternate RN Administrator, clinical systems

Lizbeth Espiritu — Operations & compliance

Contributors
We’re grateful to every developer, clinician, and designer helping move decentralized care forward. Add yourself via PR to docs/CONTRIBUTORS.md.

Pull Request Checklist

 Follows Development Policies & PSR-12

 Tests added/updated (unit/API/e2e as applicable)

 Security review complete (no secrets/PHI)

 Docs updated (README/CHANGELOG/API docs)

 Clean commit history (conventional commits preferred)

We look forward to your contribution.
Precision. Compassion. Innovation — delivered at the point of care.
