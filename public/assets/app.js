// Confirmación para formularios con data-confirm (sin JS inline, compatible con CSP).
document.addEventListener('submit', (event) => {
  const message = event.target.dataset ? event.target.dataset.confirm : null;
  if (message && !window.confirm(message)) {
    event.preventDefault();
  }
});
