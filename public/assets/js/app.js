document.addEventListener('click', (event) => {
  const menu = event.target.closest('[data-menu]');
  if (menu) {
    document.querySelector('[data-nav]')?.classList.toggle('open');
  }
  const fill = event.target.closest('[data-fill]');
  if (fill) {
    const form = document.querySelector('form[data-login]');
    if (!form) return;
    form.email.value = fill.dataset.email;
    form.password.value = fill.dataset.password || 'meridian';
  }
});

document.querySelectorAll('[data-confirm]').forEach((node) => {
  node.addEventListener('submit', (event) => {
    if (!confirm(node.dataset.confirm)) event.preventDefault();
  });
});

const timer = document.querySelector('[data-timer]');
if (timer) {
  const ends = Number(timer.dataset.ends);
  const tick = () => {
    const left = Math.max(0, ends - Math.floor(Date.now() / 1000));
    const minutes = String(Math.floor(left / 60)).padStart(2, '0');
    const seconds = String(left % 60).padStart(2, '0');
    timer.textContent = minutes + ':' + seconds;
    if (left === 0) timer.classList.add('field-error');
  };
  tick();
  setInterval(tick, 1000);
}

document.querySelectorAll('[data-add-row]').forEach((button) => {
  button.addEventListener('click', () => {
    const row = button.parentElement.querySelector('[data-row]');
    if (!row) return;
    const clone = row.cloneNode(true);
    clone.querySelectorAll('input').forEach((input) => { input.value = ''; });
    button.before(clone);
  });
});
