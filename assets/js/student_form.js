/**
 * AJAX field validation + local image preview for student forms.
 * Server-side validation on submit remains authoritative.
 */
(() => {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('studentForm');
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    const liveRegion = document.getElementById('studentFormLive');
    const endpoint = form.getAttribute('data-ajax-validate');

    let requestId = 0;
    let timer = null;

    const announce = (message) => {
      if (liveRegion) {
        liveRegion.textContent = message;
      }
    };

    const clearFieldError = (field) => {
      const input = form.querySelector(`[data-validate-field="${field}"]`);
      const feedback = form.querySelector(`[data-error-for="${field}"]`);
      if (input) {
        input.classList.remove('is-invalid');
        input.removeAttribute('aria-invalid');
      }
      if (feedback) {
        feedback.textContent = '';
      }
    };

    const showFieldError = (field, message) => {
      const input = form.querySelector(`[data-validate-field="${field}"]`);
      const feedback = form.querySelector(`[data-error-for="${field}"]`);
      if (input) {
        input.classList.add('is-invalid');
        input.setAttribute('aria-invalid', 'true');
      }
      if (feedback) {
        feedback.textContent = message;
        feedback.classList.add('d-block');
      }
    };

    const validateNow = () => {
      if (!endpoint) {
        return;
      }

      const csrfInput = form.querySelector('input[name="_csrf"]');
      const csrf = csrfInput instanceof HTMLInputElement ? csrfInput.value : '';
      const body = new URLSearchParams();
      body.set('_csrf', csrf);

      form.querySelectorAll('[data-validate-field]').forEach((el) => {
        if (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement) {
          body.set(el.name, el.value);
        }
      });

      const thisRequest = ++requestId;

      fetch(endpoint, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        },
        body: body.toString(),
        credentials: 'same-origin',
      })
        .then((res) => res.json().then((data) => ({ status: res.status, data })))
        .then(({ data }) => {
          if (thisRequest !== requestId) {
            return;
          }
          const errors = data && typeof data.errors === 'object' && data.errors ? data.errors : {};
          let firstMessage = '';
          ['name', 'email', 'phone', 'address'].forEach((field) => {
            if (Object.prototype.hasOwnProperty.call(errors, field)) {
              const msg = String(errors[field]);
              showFieldError(field, msg);
              if (!firstMessage) {
                firstMessage = msg;
              }
            } else {
              clearFieldError(field);
            }
          });
          if (firstMessage) {
            announce(firstMessage);
          } else if (data && data.valid === true) {
            announce('All fields look valid.');
          }
        })
        .catch(() => {
          // Graceful degradation: keep server-side submit validation.
        });
    };

    const scheduleValidate = () => {
      if (timer !== null) {
        window.clearTimeout(timer);
      }
      timer = window.setTimeout(validateNow, 350);
    };

    if (endpoint) {
      form.querySelectorAll('[data-validate-field]').forEach((el) => {
        el.addEventListener('blur', scheduleValidate);
        el.addEventListener('input', scheduleValidate);
        el.addEventListener('change', scheduleValidate);
      });
    }

    // Local-only preview; never trusts the file as validated.
    const fileInput = form.querySelector('#student_picture');
    const previewImg = document.getElementById('studentImagePreview');
    const previewHint = document.getElementById('studentImagePreviewHint');
    let objectUrl = null;

    if (fileInput instanceof HTMLInputElement && previewImg instanceof HTMLImageElement) {
      fileInput.addEventListener('change', () => {
        if (objectUrl) {
          URL.revokeObjectURL(objectUrl);
          objectUrl = null;
        }
        const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        if (!file) {
          previewImg.classList.add('d-none');
          previewImg.removeAttribute('src');
          if (previewHint) {
            previewHint.textContent = 'No new image selected.';
          }
          return;
        }
        objectUrl = URL.createObjectURL(file);
        previewImg.src = objectUrl;
        previewImg.classList.remove('d-none');
        previewImg.alt = 'Selected image preview';
        if (previewHint) {
          previewHint.textContent = 'Preview only — the server will validate the file on submit.';
        }
      });
    }
  });
})();
