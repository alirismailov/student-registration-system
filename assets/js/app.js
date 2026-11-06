/**
 * Global client-side helpers (password toggle, submit guards).
 */
(() => {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('js-ready');

    const yearTargets = document.querySelectorAll('[data-current-year]');
    const year = String(new Date().getFullYear());
    yearTargets.forEach((el) => {
      el.textContent = year;
    });

    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-toggle-password');
        if (!targetId) {
          return;
        }
        const input = document.getElementById(targetId);
        if (!(input instanceof HTMLInputElement)) {
          return;
        }
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        btn.textContent = showing ? 'إظهار' : 'إخفاء';
        btn.setAttribute('aria-pressed', showing ? 'false' : 'true');
        btn.setAttribute('aria-label', showing ? 'إظهار كلمة المرور' : 'إخفاء كلمة المرور');
      });
    });

    document.querySelectorAll('form[data-submit-once]').forEach((form) => {
      if (!(form instanceof HTMLFormElement)) {
        return;
      }
      form.addEventListener('submit', () => {
        const submitters = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        submitters.forEach((el) => {
          if (el instanceof HTMLButtonElement || el instanceof HTMLInputElement) {
            el.disabled = true;
            if (el instanceof HTMLButtonElement && !el.dataset.originalLabel) {
              el.dataset.originalLabel = el.textContent || '';
              el.textContent = el.dataset.loadingLabel || 'يرجى الانتظار…';
            }
          }
        });
      });
    });
  });
})();
