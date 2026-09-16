# Safe360 Deployment Runbook

Owner: Bibek Bikram Chaudhary
Scope: Backend, database, security, DevOps, and deployment readiness.

## Pre-Deployment Checklist

- Confirm `.env` uses production values and `APP_DEBUG=false`.
- Confirm `APP_URL` starts with `https://` so session cookies are marked secure.
- Configure the web server document root to `public/` only.
- Replace all demo account passwords before sharing the deployment.
- Use a least-privilege database account with access only to the Safe360 database.
- Run the schema seed only against a disposable demo database when using `--fresh`.
- Keep `storage/` writable by PHP and blocked from public web access.

## Verification Commands

Run these commands before tagging or deploying:

```powershell
C:\xampp\php\php.exe scripts\verify.php
C:\xampp\php\php.exe tests\run.php
node --check public\assets\js\app.js
node --check public\assets\js\admin.js
node --check public\assets\js\training.js
npm audit --omit=dev
```

## Runtime Health Check

Safe360 exposes a minimal deployment health endpoint:

```text
GET /api/health
```

Expected healthy response:

```json
{
  "status": "ok",
  "app": {
    "environment": "production",
    "timezone": "Asia/Katmandu"
  },
  "database": {
    "driver": "mysql",
    "connected": true,
    "missing_tables": []
  }
}
```

The endpoint does not expose database credentials, usernames, content answers, or file paths. A `503` response means the database connection failed or one or more required tables are missing.

## Rollback Notes

- Keep a database backup before deployment.
- Keep the previous release archive or commit SHA available.
- If the health check reports `degraded`, restore the previous application version first, then investigate database schema drift from a private admin machine.
