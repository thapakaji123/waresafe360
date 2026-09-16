# Test Report

Tested on 27 August 2026 with PHP 8.0.30, isolated SQLite, and the local XAMPP MariaDB-compatible runtime.

## Automated results

- `php tests/run.php`: **18 passed, 0 failed**.
- Validated six scenes, 36 assessed hazards, one question per hazard, and exactly one correct answer per question.
- Validated CSRF accept/reject paths, idempotent duplicate responses, all-scene ordering, early scene completion, result persistence, reporting and leaderboard calculations.
- Perfect journey reconciled to **7,150 points**, 100% hazards, 100% accuracy, pass, and all three achievements.
- PHP syntax checks passed for application PHP files.
- JavaScript syntax checks passed for `app.js`, `training.js`, and `admin.js`.
- `npm audit --omit=dev`: **0 known vulnerabilities** at test time.

## HTTP integration evidence

Using the seeded MySQL/MariaDB application and PHP front controller:

- Login → dashboard → attempt start → tutorial response → duplicate replay → scene advance succeeded.
- A complete six-scene journey persisted a completed 7,150-point result.
- Direct trainer-page access by a trainee returned **403**.
- Invalid CSRF submission returned **419**.
- CSP and `X-Content-Type-Options: nosniff` headers were present.
- Local CSS, Marzipano, and panorama assets returned **200** without CDN access.

## Remaining manual acceptance

The current execution environment did not expose an interactive browser instance, so pixel-level/browser-device visual QA could not be automated here. Before assessment, run the demo in current Chrome and Edge at desktop and tablet widths and verify panorama drag/zoom, every hotspot’s visual alignment, keyboard focus/return, dialog readability, touch targets, reduced motion, and panorama failure messaging.

Safety correctness is a separate approval gate: a qualified reviewer must validate all hazard depictions, questions, correct answers, explanations, PPE statements, and emergency advice.
