# Safe360 Warehouse Hazard Perception

Safe360 is a PRD-aligned PHP 8 training application. A trainee explores one tutorial and five assessed 360° warehouse scenes, identifies 36 hazards, answers contextual questions, receives immediate feedback, and earns a server-authoritative result. Trainers can review analytics, manage users, and version structured training content.

## Quick start (Windows/XAMPP)

Requirements: PHP 8 with PDO MySQL and PDO SQLite, MySQL 8 (MariaDB 10.4 is supported for local demonstration), and Node/npm.

1. Copy `.env.example` to `.env` and set the database credentials.
2. Create the configured database (the default name is `safe360`).
3. Run `npm install` to install and copy pinned browser libraries locally.
4. Run `C:\xampp\php\php.exe scripts\setup.php --fresh` to create and seed the schema.
5. Run `C:\xampp\php\php.exe -S 127.0.0.1:8080 -t public public\index.php`.
6. Open `http://127.0.0.1:8080`.

Demo accounts:

- Trainer: `admin@safe360.test` / `Safe360!Admin`
- Trainee: `trainee@safe360.test` / `Safe360!Trainee`
- Second trainee: `alex@safe360.test` / `Safe360!Trainee`

These credentials are demonstration-only. Replace them before any shared deployment.

## Quality commands

```powershell
C:\xampp\php\php.exe tests\run.php
C:\xampp\php\php.exe scripts\verify.php
npm audit --omit=dev
node --check public\assets\js\training.js
```

The automated domain/integration suite uses an isolated SQLite database and does not alter the configured application database. `scripts/setup.php --fresh` is destructive and must only be used against a disposable demo database.

## Architecture

- PHP front controller and explicit routes
- Server-rendered responsive pages plus JSON interaction endpoints
- PDO prepared statements and transactional scoring
- MySQL/MariaDB application storage; SQLite test isolation
- Marzipano 360° viewer, Bootstrap, and Chart.js served locally
- Content snapshots on attempts protect historical results from later author edits

Only `public/` should be configured as the web root. Production must use HTTPS, secure cookies, a least-privilege database account, protected backups, and non-demo credentials.

## Delivery evidence

- [Implementation plan](IMPLEMENTATION_PLAN.md)
- [Requirements traceability](docs/REQUIREMENTS_TRACEABILITY.md)
- [Test report](docs/TEST_REPORT.md)
- [Demonstration script](docs/DEMO_SCRIPT.md)
- [Deployment runbook](docs/DEPLOYMENT_RUNBOOK.md)
- [Panorama provenance](docs/content-validation/PANORAMA_PROVENANCE.md)
- [Security and release checklist](docs/SECURITY.md)

The warehouse imagery and safety wording are prototype content. A qualified safety reviewer must approve hazards, answers, explanations, and site-specific emergency procedures before operational use.
