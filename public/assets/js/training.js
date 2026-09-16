const root = document.querySelector('#trainingApp');
const attemptId = Number(root?.dataset.attemptId || 0);
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const elements = {
  loading: document.querySelector('#trainingLoading'),
  panorama: document.querySelector('#panorama'),
  sceneStep: document.querySelector('#sceneStep'),
  sceneTitle: document.querySelector('#sceneTitle'),
  score: document.querySelector('#scoreValue'),
  hazards: document.querySelector('#hazardValue'),
  timerCard: document.querySelector('#timerCard'),
  timer: document.querySelector('#timerValue'),
  complete: document.querySelector('#completeSceneButton'),
  modal: document.querySelector('#questionModal'),
  category: document.querySelector('#hazardCategory'),
  severity: document.querySelector('#hazardSeverity'),
  context: document.querySelector('#questionContext'),
  question: document.querySelector('#questionText'),
  answers: document.querySelector('#answerList'),
  form: document.querySelector('#questionForm'),
  error: document.querySelector('#questionError'),
  submit: document.querySelector('#submitAnswer'),
  feedback: document.querySelector('#feedbackPanel'),
  feedbackLabel: document.querySelector('#feedbackLabel'),
  feedbackHeading: document.querySelector('#feedbackHeading'),
  feedbackExplanation: document.querySelector('#feedbackExplanation'),
  feedbackPoints: document.querySelector('#feedbackPoints'),
  continue: document.querySelector('#continueTraining'),
  live: document.querySelector('#trainingLive'),
};

let viewer;
let scene;
let payload;
let activeHazard;
let activeHotspot;
let modal;
let timerInterval;
let remainingSeconds;

function announce(message) {
  elements.live.textContent = '';
  window.setTimeout(() => { elements.live.textContent = message; }, 50);
}

async function api(path, options = {}) {
  const response = await fetch(path, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken,
      ...(options.headers || {}),
    },
  });
  const data = await response.json().catch(() => ({ message: 'The server returned an unreadable response.' }));
  if (!response.ok) throw new Error(data.message || 'The request could not be completed.');
  return data;
}

function formatTime(seconds) {
  const safe = Math.max(0, seconds);
  return `${String(Math.floor(safe / 60)).padStart(2, '0')}:${String(safe % 60).padStart(2, '0')}`;
}

function startTimer(limit) {
  window.clearInterval(timerInterval);
  if (!limit) {
    elements.timerCard.hidden = true;
    return;
  }
  elements.timerCard.hidden = false;
  remainingSeconds = limit;
  elements.timer.textContent = formatTime(remainingSeconds);
  timerInterval = window.setInterval(() => {
    remainingSeconds -= 1;
    elements.timer.textContent = formatTime(remainingSeconds);
    if (remainingSeconds === 60) announce('One minute remains in this scene. Accuracy is more important than speed.');
    if (remainingSeconds <= 0) {
      window.clearInterval(timerInterval);
      elements.complete.hidden = false;
      elements.complete.textContent = 'Time ended · complete scene →';
      announce('Time has ended. Complete the scene to review your progress.');
    }
  }, 1000);
}

function createHotspot(hazard, index) {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = `hazard-hotspot${hazard.completed ? ' completed' : ''}`;
  button.setAttribute('aria-label', hazard.completed ? `Completed hazard ${index + 1}` : `Investigate possible hazard ${index + 1}, ${hazard.category}`);
  button.disabled = Boolean(hazard.completed);
  button.addEventListener('click', () => openQuestion(hazard, button));
  return button;
}

function initializeViewer() {
  if (typeof Marzipano === 'undefined') throw new Error('The 360-degree viewer library did not load.');
  viewer?.destroy();
  viewer = new Marzipano.Viewer(elements.panorama, { controls: { mouseViewMode: 'drag' } });
  const source = Marzipano.ImageUrlSource.fromString(payload.scene.panorama_path);
  const geometry = new Marzipano.EquirectGeometry([{ width: 2048 }]);
  const limiter = Marzipano.RectilinearView.limit.traditional(1024, 100 * Math.PI / 180);
  const view = new Marzipano.RectilinearView({ yaw: payload.scene.initial_yaw, pitch: payload.scene.initial_pitch, fov: payload.scene.initial_fov }, limiter);
  scene = viewer.createScene({ source, geometry, view, pinFirstLevel: true });
  payload.hazards.forEach((hazard, index) => {
    scene.hotspotContainer().createHotspot(createHotspot(hazard, index), { yaw: hazard.yaw, pitch: hazard.pitch });
  });
  scene.switchTo({ transitionDuration: 650 });
}

function renderScene() {
  elements.sceneStep.textContent = payload.scene.is_tutorial ? 'Orientation · practice' : `Scene ${payload.scene.sequence_no} of 5`;
  elements.sceneTitle.textContent = payload.scene.title;
  elements.score.textContent = Number(payload.attempt.score).toLocaleString();
  elements.hazards.textContent = `${payload.progress.found} / ${payload.progress.total}`;
  elements.complete.hidden = false;
  elements.complete.textContent = payload.progress.all_interactions >= payload.progress.required_interactions
    ? 'Complete scene →'
    : 'End scene & review →';
  startTimer(payload.scene.time_limit_seconds);
  initializeViewer();
  elements.loading.hidden = true;
  announce(`${payload.scene.title} loaded. ${payload.hazards.length} interactive markers are available.`);
}

function optionMarkup(option, index) {
  const wrapper = document.createElement('div');
  wrapper.className = 'answer-option';
  const input = document.createElement('input');
  input.type = 'radio';
  input.name = 'answer';
  input.id = `answer-${option.id}`;
  input.value = String(option.id);
  const label = document.createElement('label');
  label.htmlFor = input.id;
  label.textContent = `${String.fromCharCode(65 + index)}. ${option.text}`;
  wrapper.append(input, label);
  return wrapper;
}

function openQuestion(hazard, hotspot) {
  activeHazard = hazard;
  activeHotspot = hotspot;
  elements.category.textContent = hazard.category;
  elements.severity.textContent = hazard.severity.charAt(0).toUpperCase() + hazard.severity.slice(1);
  elements.severity.className = `severity-chip severity-${hazard.severity}`;
  elements.context.textContent = hazard.title;
  elements.question.textContent = hazard.question.text;
  elements.answers.replaceChildren(...hazard.question.options.map(optionMarkup));
  elements.error.hidden = true;
  elements.form.hidden = false;
  elements.feedback.hidden = true;
  elements.feedback.classList.remove('incorrect');
  modal ??= bootstrap.Modal.getOrCreateInstance(elements.modal);
  modal.show();
  elements.modal.addEventListener('shown.bs.modal', () => elements.answers.querySelector('input')?.focus(), { once: true });
}

elements.form?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const selected = elements.form.querySelector('input[name="answer"]:checked');
  if (!selected) {
    elements.error.textContent = 'Select one answer before submitting.';
    elements.error.hidden = false;
    return;
  }
  elements.error.hidden = true;
  elements.submit.disabled = true;
  elements.submit.textContent = 'Checking response…';
  try {
    const result = await api(`/api/attempts/${attemptId}/responses`, {
      method: 'POST',
      body: JSON.stringify({ hazard_id: activeHazard.id, option_id: Number(selected.value), event_id: crypto.randomUUID() }),
    });
    elements.score.textContent = Number(result.score).toLocaleString();
    elements.feedbackLabel.textContent = result.label;
    elements.feedbackHeading.textContent = result.correct ? 'Good observation' : 'Review the safe response';
    elements.feedbackExplanation.textContent = result.explanation;
    elements.feedbackPoints.textContent = `${result.points_delta >= 0 ? '+' : ''}${result.points_delta}`;
    elements.feedback.classList.toggle('incorrect', !result.correct);
    elements.form.hidden = true;
    elements.feedback.hidden = false;
    elements.feedback.focus();
    activeHotspot.classList.add('completed');
    activeHotspot.disabled = true;
    activeHotspot.setAttribute('aria-label', `Completed hazard: ${activeHazard.title}`);
    activeHazard.completed = true;
    payload.progress.all_interactions += 1;
    if (activeHazard.is_assessed) payload.progress.found += 1;
    elements.hazards.textContent = `${payload.progress.found} / ${payload.progress.total}`;
    elements.complete.textContent = payload.progress.all_interactions >= payload.progress.required_interactions ? 'Complete scene →' : 'End scene & review →';
    announce(`${result.label}. ${result.points_delta >= 0 ? '+' : ''}${result.points_delta} points. Score ${result.score}.`);
  } catch (error) {
    elements.error.textContent = error.message;
    elements.error.hidden = false;
  } finally {
    elements.submit.disabled = false;
    elements.submit.textContent = 'Submit answer';
  }
});

elements.continue?.addEventListener('click', () => {
  modal?.hide();
  elements.modal.addEventListener('hidden.bs.modal', () => elements.panorama.focus(), { once: true });
});

elements.complete?.addEventListener('click', async () => {
  const original = elements.complete.textContent;
  elements.complete.disabled = true;
  elements.complete.textContent = 'Saving scene…';
  try {
    const result = await api(`/api/attempts/${attemptId}/scene/complete`, { method: 'POST', body: '{}' });
    window.clearInterval(timerInterval);
    if (result.complete) {
      window.location.assign(`/results/${attemptId}`);
      return;
    }
    elements.loading.hidden = false;
    payload = await api(`/api/attempts/${attemptId}/scene`);
    renderScene();
  } catch (error) {
    announce(error.message);
    elements.complete.textContent = original;
  } finally {
    elements.complete.disabled = false;
  }
});

async function boot() {
  if (!attemptId) return;
  try {
    payload = await api(`/api/attempts/${attemptId}/scene`, { method: 'GET' });
    if (document.readyState === 'complete') renderScene();
    else window.addEventListener('load', renderScene, { once: true });
  } catch (error) {
    elements.loading.innerHTML = '';
    const heading = document.createElement('h1');
    heading.textContent = 'The training scene could not load.';
    const message = document.createElement('p');
    message.textContent = error.message;
    const link = document.createElement('a');
    link.className = 'btn btn-light';
    link.href = '/dashboard';
    link.textContent = 'Return to dashboard';
    elements.loading.append(heading, message, link);
  }
}

boot();

