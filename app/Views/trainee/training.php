<div class="training-app" id="trainingApp" data-attempt-id="<?= (int) $attemptId ?>">
    <div class="training-loading" id="trainingLoading" role="status">
        <div class="spinner-border" aria-hidden="true"></div>
        <span>Preparing your 360° training scene…</span>
    </div>

    <section class="viewer-shell" aria-label="Interactive 360-degree warehouse scene">
        <div id="panorama" class="panorama-viewer" tabindex="0" aria-describedby="viewerInstructions"></div>
        <div class="scene-shade" aria-hidden="true"></div>
        <div class="training-topbar">
            <div>
                <span class="scene-kicker" id="sceneStep">Scene</span>
                <h1 id="sceneTitle">Loading scene…</h1>
            </div>
            <a class="training-exit" href="/dashboard">Save & exit</a>
        </div>

        <div class="hud" aria-label="Attempt progress">
            <div class="hud-card"><span>Score</span><strong id="scoreValue">0</strong></div>
            <div class="hud-card"><span>Hazards</span><strong id="hazardValue">0 / 0</strong></div>
            <div class="hud-card" id="timerCard"><span>Time</span><strong id="timerValue">—</strong></div>
        </div>

        <div class="viewer-help" id="viewerInstructions">
            <span aria-hidden="true">↔</span>
            <span>Drag to explore. Select a pulsing marker when you identify a hazard.</span>
        </div>

        <button class="btn btn-light scene-complete" id="completeSceneButton" type="button" hidden>Complete scene <span aria-hidden="true">→</span></button>
    </section>

    <div class="modal fade" id="questionModal" tabindex="-1" aria-labelledby="questionTitle" aria-describedby="questionContext" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content question-card">
                <div class="modal-header border-0">
                    <div>
                        <span class="hazard-category" id="hazardCategory">Hazard</span>
                        <h2 class="modal-title" id="questionTitle">Safety challenge</h2>
                    </div>
                    <span class="severity-chip" id="hazardSeverity">High</span>
                </div>
                <div class="modal-body">
                    <p class="question-context" id="questionContext"></p>
                    <form id="questionForm">
                        <fieldset>
                            <legend id="questionText">Choose the safest answer.</legend>
                            <div class="answer-list" id="answerList"></div>
                        </fieldset>
                        <div class="form-error" id="questionError" role="alert" hidden></div>
                        <button class="btn btn-primary btn-lg w-100" type="submit" id="submitAnswer">Submit answer</button>
                    </form>
                    <div class="feedback-panel" id="feedbackPanel" tabindex="-1" hidden>
                        <span class="feedback-label" id="feedbackLabel">Correct</span>
                        <h3 id="feedbackHeading">Good observation</h3>
                        <p id="feedbackExplanation"></p>
                        <div class="feedback-points"><span>Points this interaction</span><strong id="feedbackPoints">+150</strong></div>
                        <button class="btn btn-primary w-100" id="continueTraining" type="button">Continue scanning</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sr-live" id="trainingLive" aria-live="polite"></div>
</div>

