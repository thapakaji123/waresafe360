const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function showToast(message, type = 'success') {
  const container = document.querySelector('.toast-container');
  if (!container || typeof bootstrap === 'undefined') return;
  const element = document.createElement('div');
  element.className = `toast border-0 text-bg-${type}`;
  element.setAttribute('role', 'status');
  element.innerHTML = `<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
  element.querySelector('.toast-body').textContent = message;
  container.append(element);
  const toast = bootstrap.Toast.getOrCreateInstance(element, { delay: 4000 });
  element.addEventListener('hidden.bs.toast', () => element.remove());
  toast.show();
}

document.querySelectorAll('.start-module').forEach((button) => {
  button.addEventListener('click', async () => {
    const original = button.textContent;
    button.disabled = true;
    button.textContent = 'Preparing training…';
    try {
      const response = await fetch('/api/attempts', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ module_id: Number(button.dataset.moduleId) }),
      });
      const payload = await response.json();
      if (!response.ok) throw new Error(payload.message || 'Training could not be started.');
      window.location.assign(payload.redirect);
    } catch (error) {
      showToast(error.message, 'danger');
      button.disabled = false;
      button.textContent = original;
    }
  });
});

document.querySelectorAll('.user-status').forEach((button) => {
  button.addEventListener('click', async () => {
    button.disabled = true;
    try {
      const response = await fetch(`/api/admin/users/${button.dataset.userId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ status: button.dataset.nextStatus }),
      });
      const payload = await response.json();
      if (!response.ok) throw new Error(payload.message || 'Account could not be updated.');
      showToast(`Account ${payload.status}.`);
      window.setTimeout(() => window.location.reload(), 500);
    } catch (error) {
      showToast(error.message, 'danger');
      button.disabled = false;
    }
  });
});

