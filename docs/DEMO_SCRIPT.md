# Assessment Demonstration Script

1. Reset the disposable demo database with `php scripts/setup.php --fresh` and start the app.
2. Sign in as `trainee@safe360.test` and show the module objectives, configurable pass rules, history, and leaderboard.
3. Start training. Complete the tutorial hotspot and explain that tutorial activity is unscored.
4. Enter Loading Bay. Drag/zoom the 360° view, select a keyboard-accessible hotspot, answer once incorrectly, and show immediate explanation and server-calculated score.
5. Replay the same request if desired: duplicate protection must award no extra points.
6. Complete the scene and show found/missed review, then demonstrate ordered progression through the remaining warehouse areas.
7. Finish the module and show score, hazard completion, question accuracy, pass status, achievements, and persistent result detail.
8. Sign out/in to show the attempt remains in history and leaderboard.
9. Sign in as `admin@safe360.test`. Show analytics, difficult hazards, recent attempts, user activation, module thresholds, scene panorama settings, existing question/answer editing, and draft hazard creation.
10. State the integrity rule: new content increments a version, while old attempts retain their JSON snapshot.
11. State the safety boundary: imagery and wording are academic prototype content pending qualified review.

For a perfect-run proof, the authoritative expected result is 7,150 points, 36/36 hazards, 36/36 correct, passed, and three achievements.
