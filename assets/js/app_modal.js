(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  function ensureModal() {
    let modal = document.getElementById('appMessageModal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'appMessageModal';
    modal.style.cssText = 'display:none;position:fixed;inset:0;z-index:20000;background:rgba(0,0,0,.48);align-items:center;justify-content:center;padding:16px;font-family:Poppins,Arial,sans-serif;';
    modal.innerHTML = `
      <div style="width:100%;max-width:430px;background:#fff;border-radius:18px;box-shadow:0 24px 70px rgba(0,0,0,.24);overflow:hidden;">
        <div id="appMessageModalHead" style="background:#1d3a6e;color:#fff;padding:15px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
          <strong id="appMessageModalTitle" style="font-size:14px;">Message</strong>
          <button type="button" id="appMessageModalX" aria-label="Close" style="border:0;background:transparent;color:#fff;font-size:22px;line-height:1;cursor:pointer;">&times;</button>
        </div>
        <div style="padding:20px;">
          <p id="appMessageModalBody" style="margin:0 0 18px;color:#374151;font-size:13px;line-height:1.55;"></p>
          <div id="appMessageModalActions" style="display:flex;justify-content:flex-end;gap:10px;">
            <button type="button" id="appMessageCancel" style="display:none;padding:9px 18px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;">Cancel</button>
            <button type="button" id="appMessageOk" style="padding:9px 20px;border:0;border-radius:10px;background:#1d3a6e;color:#fff;font-size:13px;font-weight:700;cursor:pointer;">OK</button>
          </div>
        </div>
      </div>`;
    document.body.appendChild(modal);
    return modal;
  }

  function openMessage(options) {
    const modal = ensureModal();
    const head = document.getElementById('appMessageModalHead');
    const title = document.getElementById('appMessageModalTitle');
    const body = document.getElementById('appMessageModalBody');
    const ok = document.getElementById('appMessageOk');
    const cancel = document.getElementById('appMessageCancel');
    const x = document.getElementById('appMessageModalX');

    const type = options.type || 'info';
    const colors = {
      info: '#1d3a6e',
      success: '#059669',
      warning: '#d97706',
      danger: '#dc2626'
    };

    head.style.background = colors[type] || colors.info;
    title.textContent = options.title || (type === 'danger' ? 'Confirm Action' : 'Message');
    body.textContent = options.message || '';
    ok.textContent = options.okText || 'OK';
    cancel.textContent = options.cancelText || 'Cancel';
    cancel.style.display = options.confirm ? 'inline-block' : 'none';

    modal.style.display = 'flex';

    function cleanup(result) {
      modal.style.display = 'none';
      ok.onclick = null;
      cancel.onclick = null;
      x.onclick = null;
      modal.onclick = null;
      if (typeof options.onClose === 'function') options.onClose(result);
    }

    ok.onclick = () => cleanup(true);
    cancel.onclick = () => cleanup(false);
    x.onclick = () => cleanup(false);
    modal.onclick = (e) => {
      if (e.target === modal) cleanup(false);
    };
  }

  window.appAlert = function (message, title, type) {
    openMessage({ message: String(message ?? ''), title: title || 'Message', type: type || 'info' });
  };

  window.appConfirm = function (message, onConfirm, options) {
    openMessage({
      message: String(message ?? ''),
      title: (options && options.title) || 'Please confirm',
      type: (options && options.type) || 'warning',
      okText: (options && options.okText) || 'Yes, continue',
      cancelText: (options && options.cancelText) || 'Cancel',
      confirm: true,
      onClose: function (ok) {
        if (ok && typeof onConfirm === 'function') onConfirm();
      }
    });
  };

  // Replace browser alert with a styled modal.
  window.alert = function (message) {
    window.appAlert(message, 'Message', 'info');
  };

  // Do not use native confirm. Forms should use data-confirm-message.
  window.confirm = function (message) {
    window.appAlert(message, 'Confirmation required', 'warning');
    return false;
  };

  ready(function () {
    document.addEventListener('submit', function (e) {
      const form = e.target;
      if (!form || !form.matches || !form.matches('form[data-confirm-message]')) return;
      if (form.dataset.confirmed === '1') return;

      e.preventDefault();
      window.appConfirm(form.dataset.confirmMessage, function () {
        form.dataset.confirmed = '1';
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit();
        } else {
          form.submit();
        }
      }, {
        title: form.dataset.confirmTitle || 'Please confirm',
        type: form.dataset.confirmType || 'danger',
        okText: form.dataset.confirmOk || 'Yes, continue'
      });
    }, true);
  });
})();
