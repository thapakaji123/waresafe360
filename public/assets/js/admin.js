window.addEventListener('load', () => {
  const root = document.querySelector('#adminDashboard');
  const canvas = document.querySelector('#outcomeChart');
  if (!root || !canvas || typeof Chart === 'undefined') return;
  const completion = Number(root.dataset.completion || 0);
  const accuracy = Number(root.dataset.accuracy || 0);
  new Chart(canvas, {
    type: 'bar',
    data: {
      labels: ['Completion', 'Accuracy'],
      datasets: [{ data: [completion, accuracy], backgroundColor: ['#176a70', '#d7f06d'], borderRadius: 10, maxBarThickness: 70 }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: (context) => `${context.raw}%` } } },
      scales: { y: { beginAtZero: true, max: 100, ticks: { callback: (value) => `${value}%` }, grid: { color: '#e7ede9' } }, x: { grid: { display: false } } },
    },
  });
});

