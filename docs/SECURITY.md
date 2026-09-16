# Security and Release Checklist

Implemented application controls include password hashing/verification, session ID rotation at login, HttpOnly/SameSite cookies, CSRF validation, server-side RBAC, prepared PDO statements, output escaping, transaction boundaries, duplicate-response uniqueness, generic authentication errors, CSP, `nosniff`, frame restrictions, referrer policy, non-public configuration, and a minimal `/api/health` deployment check that reports database readiness without exposing secrets or answer data.

Before production:

- Serve only `public/` over HTTPS and enable the Secure cookie flag.
- Replace all demo passwords and use a least-privilege database user.
- Disable detailed error display; protect and rotate logs.
- Back up the database, `.env` through a secrets system, and versioned panorama assets; test a restore.
- Run PHP/npm security updates, the test suite, browser/device acceptance, and a vulnerability scan.
- Configure rate limiting at the web server or gateway for login and mutation endpoints.
- Obtain privacy, retention, accessibility, and qualified safety-content approval.
- Never run `scripts/setup.php --fresh` against production data.
- Treat a `503` response from `/api/health` as a release blocker.

Known boundaries: the app is a training prototype, not a safety control system; generated panoramas and drafted learning content are not certified advice. Hints and incomplete-attempt resume are deferred optional features.
