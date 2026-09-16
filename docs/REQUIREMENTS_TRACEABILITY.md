# Requirements Traceability

This matrix records implementation evidence against `Hazard_Perception_360_PRD.docx`. “Implemented” means code and automated evidence exist; it does not replace client acceptance or qualified safety-content approval.

| PRD requirement | Status | Implementation/evidence |
|---|---|---|
| FR-001–002 Authentication and role access | Implemented | Session login/logout, password verification, inactive-account checks, server-side trainee/admin guards, CSRF; negative HTTP checks in the test report. |
| FR-003–006 Dashboard, instructions, tutorial, scene flow | Implemented | Trainee dashboard, tutorial-first attempt state machine, ordered six-scene progression and persistent scene progress. |
| FR-007–013 360 viewer, hotspots, questions, feedback | Implemented | Marzipano equirectangular viewer; 36 assessed hotspots; keyboard buttons; single-answer dialogs; immediate correctness and explanations; server validation. |
| FR-014–019 Progress, score, completion, results, history | Implemented | HUD, server scoring, configurable thresholds, result detail, missed-hazard review, persisted history and snapshot integrity. Perfect run is deterministically 7,150. |
| FR-020 Leaderboard | Implemented | Best eligible attempt per trainee with deterministic score/time/completion ordering and privacy-safe names. |
| FR-021 Achievements | Implemented | Perfect accuracy, all hazards found, and perfect run awards. |
| FR-022 User management | Implemented | Trainer account list and active/inactive control; self-deactivation prevented. |
| FR-023–026 Content management | Implemented baseline | Module/pass/publish settings, scene panorama/view metadata, numeric hotspot coordinates, hazard/question/four-option editing, and draft hazard creation. Content version increments preserve old snapshots. Drag placement/media upload are deliberately outside baseline. |
| FR-027–029 Reporting | Implemented | Aggregate dashboard, recent attempts, completion/accuracy/score metrics, difficult-hazard reporting and Chart.js visualisation. |
| FR-030 Security/access control | Implemented | RBAC, prepared statements, output escaping, CSRF, session rotation, CSP and security headers. See `SECURITY.md`. |
| FR-031–032 Validation and historical integrity | Implemented | Coordinate/status/threshold/answer validation, exactly-one-correct seed checks, deactivation model, immutable per-attempt content JSON. |
| FR-033 Hints | Deferred/optional | Seeded hint data exists; penalised hint interaction is not enabled because the PRD marks it optional. |
| FR-034 Resume incomplete attempt | Deferred | A new attempt is created from the dashboard; resumable sessions remain a documented enhancement. |
| FR-035 Multiplayer | Out of scope | Explicitly excluded by the PRD baseline. |
| NFR-001–004 Performance/usability/responsiveness | Implemented baseline | Local assets, next-scene flow, loading/error states, responsive CSS and touch/pointer interaction. Device/browser acceptance remains manual. |
| NFR-005–008 Security/privacy | Implemented baseline | Secure coding controls, generic auth failures, minimum stored identity, role separation and protected routes. Production HTTPS/operations are deployment responsibilities. |
| NFR-009–012 Reliability/maintainability | Implemented | Transactions, unique duplicate guard, centralized errors/logging, repeatable seed, data-driven content and layered code. |
| NFR-013–015 Accessibility | Implemented baseline | Semantic controls/labels, keyboard hotspots, dialog focus handling, non-colour labels, visible focus, reduced-motion support. Formal WCAG audit remains an acceptance activity. |
| NFR-016–018 Compatibility/backup/support | Partially operational | Chrome/Edge-oriented standards implementation, MySQL schema and backup-ready data/assets. Cross-device matrix and production backup restore require the target deployment environment. |
| AC-01–05 Login through hazard interaction | Implemented | HTTP journey plus automated CSRF, ordering and response tests. |
| AC-06–09 Feedback, scoring, results, history | Implemented | Automated perfect-run, duplicate, pass, persistence and result calculations. |
| AC-10–12 Leaderboard, analytics, admin content | Implemented | Reporting/leaderboard tests and trainer authoring interface. |
| AC-13 Security boundary | Implemented | Trainee-to-admin request returns 403; invalid CSRF returns 419. |
| AC-14–16 Responsive, accessible, complete demo | Code complete; manual sign-off required | Responsive/accessibility implementation and six-scene assets are present. Run the supplied demo and target-device checklist for final evaluator sign-off. |

## Release interpretation

All Must-level functional paths are implemented. Remaining items are optional/deferred features or environment-dependent acceptance checks. Prototype safety content and generated imagery must not be represented as certified workplace instruction.
