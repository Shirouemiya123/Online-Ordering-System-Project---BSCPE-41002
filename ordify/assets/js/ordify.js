(() => {
  'use strict';
  const body = document.body;
  if (!body || !body.classList.contains('enhanced-app')) return;
  const root = document.documentElement;
  const themeButton = document.querySelector('[data-theme-toggle]');
  const themeIcon = document.querySelector('[data-theme-icon]');
  const themeLabel = document.querySelector('.theme-label');
  const storageKey = 'ordify-theme';

  const applyTheme = (theme) => {
    const next = theme === 'dark' ? 'dark' : 'light';
    body.dataset.theme = next;
    try { localStorage.setItem(storageKey, next); } catch (_) {}
    if (themeButton) {
      const dark = next === 'dark';
      themeButton.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
      themeButton.setAttribute('title', dark ? 'Switch to light mode' : 'Switch to dark mode');
      if (themeIcon) themeIcon.textContent = dark ? '☼' : '◐';
      if (themeLabel) themeLabel.textContent = dark ? 'Light mode' : 'Dark mode';
    }
  };
  let storedTheme = 'light';
  try { storedTheme = localStorage.getItem(storageKey) || 'light'; } catch (_) {}
  applyTheme(storedTheme);
  themeButton?.addEventListener('click', () => applyTheme(body.dataset.theme === 'dark' ? 'light' : 'dark'));

  const navToggle = document.querySelector('[data-nav-toggle]');
  const nav = document.getElementById('primary-nav');
  navToggle?.addEventListener('click', () => {
    const open = nav?.classList.toggle('nav-open') || false;
    navToggle.setAttribute('aria-expanded', String(open));
    navToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    const glyph = navToggle.querySelector('span');
    if (glyph) glyph.textContent = open ? '×' : '☰';
  });
  nav?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
    nav.classList.remove('nav-open');
    navToggle?.setAttribute('aria-expanded', 'false');
  }));

  let toastRegion;
  const toast = (message, type = 'success') => {
    if (!message) return;
    if (!toastRegion) {
      toastRegion = document.createElement('div');
      toastRegion.className = 'toast-region';
      toastRegion.setAttribute('aria-live', 'polite');
      toastRegion.setAttribute('aria-atomic', 'false');
      body.appendChild(toastRegion);
    }
    const item = document.createElement('div');
    item.className = `ordify-toast${type === 'error' ? ' toast-error' : ''}`;
    const icon = document.createElement('span'); icon.className = 'ordify-toast-icon'; icon.textContent = type === 'error' ? '!' : '✓';
    const text = document.createElement('span'); text.textContent = message;
    item.append(icon, text); toastRegion.appendChild(item);
    window.setTimeout(() => { item.classList.add('toast-leave'); window.setTimeout(() => item.remove(), 220); }, 3600);
  };
  document.querySelectorAll('.alert').forEach((alert) => {
    alert.setAttribute('role', alert.classList.contains('error') ? 'alert' : 'status');
  });

  // Client-side filtering for cards already returned by the server; no new database calls.
  document.querySelectorAll('[data-filter-target]').forEach((control) => {
    const selector = control.getAttribute('data-filter-target');
    const container = selector ? document.querySelector(selector) : null;
    if (!container) return;
    const items = Array.from(container.querySelectorAll('[data-filterable]'));
    const update = () => {
      const query = (control.value || '').trim().toLocaleLowerCase();
      let visible = 0;
      items.forEach((item) => {
        const match = !query || item.textContent.toLocaleLowerCase().includes(query);
        item.classList.toggle('is-filtered-out', !match);
        if (match) visible++;
      });
      const countTarget = control.getAttribute('data-filter-count');
      const count = countTarget ? document.querySelector(countTarget) : null;
      if (count) count.textContent = `${visible} ${visible === 1 ? 'result' : 'results'}`;
      let empty = container.querySelector('[data-filter-empty]');
      if (!empty && items.length) {
        empty = document.createElement('p');
        empty.className = 'muted filter-empty-state';
        empty.setAttribute('data-filter-empty', '');
        empty.textContent = 'No matching results. Try a different search.';
        container.appendChild(empty);
      }
      if (empty) empty.hidden = visible > 0;
    };
    control.addEventListener('input', update);
    control.addEventListener('change', update);
    update();
  });

  document.querySelectorAll('[data-status-filter]').forEach((control) => {
    const container = document.querySelector(control.getAttribute('data-status-filter'));
    if (!container) return;
    const searchControl = document.querySelector(`[data-filter-target="#${container.id}"]`);
    const update = () => {
      const query = (searchControl?.value || '').trim().toLocaleLowerCase();
      const status = (control.value || '').toLocaleLowerCase();
      const items = Array.from(container.querySelectorAll('[data-filterable]'));
      let visible = 0;
      items.forEach((item) => {
        const textMatch = !query || item.textContent.toLocaleLowerCase().includes(query);
        const statusMatch = !status || (item.dataset.status || '').toLocaleLowerCase() === status;
        const match = textMatch && statusMatch;
        item.classList.toggle('is-filtered-out', !match);
        if (match) visible++;
      });
      const countSelector = control.getAttribute('data-filter-count');
      const count = countSelector ? document.querySelector(countSelector) : null;
      if (count) count.textContent = `${visible} ${visible === 1 ? 'result' : 'results'}`;
    };
    control.addEventListener('change', update);
    searchControl?.addEventListener('input', update);
    update();
  });

  // Confirm only forms/buttons explicitly marked by the page author.
  document.addEventListener('submit', (event) => {
    const submitter = event.submitter;
    const form = event.target;
    const prompt = submitter?.dataset.confirm || form?.dataset.confirm;
    if (prompt && !window.confirm(prompt)) event.preventDefault();
  });
  document.querySelectorAll('[data-toast]').forEach((node) => {
    node.addEventListener('click', () => toast(node.getAttribute('data-toast')));
  });

  document.querySelectorAll('[data-reveal-group]').forEach((group) => {
    Array.from(group.children).forEach((child, index) => child.style.setProperty('--reveal-index', String(Math.min(index, 8))));
  });
  // Smooth same-page anchor navigation without changing normal link behavior.
  document.querySelectorAll('a[href^="#"]').forEach((link) => link.addEventListener('click', (event) => {
    const id = link.getAttribute('href');
    if (!id || id === '#') return;
    const target = document.querySelector(id);
    if (!target) return;
    event.preventDefault();
    target.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
  }));
})();
