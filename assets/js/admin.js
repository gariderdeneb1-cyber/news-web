/**
 * Admin dashboard interactivity: sidebar toggle, slug auto-generate, tag
 * chip input, image upload/preview, delete confirmations, status/schedule
 * field toggling, color pickers, and the Quill rich-text editor.
 */
document.addEventListener('DOMContentLoaded', function () {
  initAdminSidebar();
  initSlugField();
  initTagInput();
  initImageUploads();
  initDeleteConfirm();
  initStatusField();
  initColorSync();
  initRichTextEditor();
});

function csrfToken() {
  var meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}

function initAdminSidebar() {
  var toggle = document.querySelector('[data-admin-sidebar-toggle]');
  var sidebar = document.querySelector('[data-admin-sidebar]');
  var overlay = document.querySelector('[data-admin-sidebar-overlay]');
  if (!toggle || !sidebar || !overlay) return;
  toggle.addEventListener('click', function () {
    sidebar.classList.add('is-open');
    overlay.classList.add('is-open');
  });
  overlay.addEventListener('click', function () {
    sidebar.classList.remove('is-open');
    overlay.classList.remove('is-open');
  });
}

function slugifyClient(text) {
  return String(text)
    .trim()
    .toLowerCase()
    .replace(/['"«»]/g, '')
    .replace(/[^\p{L}\p{N}]+/gu, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-{2,}/g, '-');
}

function initSlugField() {
  var titleInput = document.querySelector('[data-title-input]');
  var slugInput = document.querySelector('[data-slug-input]');
  if (!titleInput || !slugInput) return;

  var manuallyEdited = slugInput.value.trim() !== '';
  slugInput.addEventListener('input', function () { manuallyEdited = true; });
  titleInput.addEventListener('input', function () {
    if (manuallyEdited) return;
    slugInput.value = slugifyClient(titleInput.value);
  });
}

function initTagInput() {
  var wrap = document.querySelector('[data-tag-input]');
  var hidden = document.querySelector('[data-tags-hidden]');
  var textInput = wrap ? wrap.querySelector('[data-tag-text]') : null;
  if (!wrap || !hidden || !textInput) return;

  var tags = hidden.value.split(',').map(function (t) { return t.trim(); }).filter(Boolean);

  function render() {
    wrap.querySelectorAll('.tag-chip-x').forEach(function (el) { el.remove(); });
    tags.forEach(function (tag, idx) {
      var chip = document.createElement('span');
      chip.className = 'tag-chip-x';
      var label = document.createElement('span');
      label.textContent = tag;
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.setAttribute('aria-label', 'Шошго хасах');
      btn.textContent = '×';
      btn.addEventListener('click', function () {
        tags.splice(idx, 1);
        render();
      });
      chip.appendChild(label);
      chip.appendChild(btn);
      wrap.insertBefore(chip, textInput);
    });
    hidden.value = tags.join(',');
  }

  function addFromInput() {
    var val = textInput.value.replace(/,$/, '').trim();
    if (val && tags.indexOf(val) === -1) {
      tags.push(val);
    }
    textInput.value = '';
    render();
  }

  textInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ',') {
      e.preventDefault();
      addFromInput();
    } else if (e.key === 'Backspace' && textInput.value === '' && tags.length) {
      tags.pop();
      render();
    }
  });
  textInput.addEventListener('blur', function () {
    if (textInput.value.trim()) addFromInput();
  });

  render();
}

function initImageUploads() {
  document.querySelectorAll('[data-image-uploader]').forEach(function (wrap) {
    var fileInput = wrap.querySelector('[data-image-file]');
    var preview = wrap.querySelector('[data-image-preview]');
    var urlInput = wrap.querySelector('[data-image-url]');
    var status = wrap.querySelector('[data-image-status]');
    var uploadUrl = wrap.getAttribute('data-upload-url');
    if (!fileInput || !urlInput || !uploadUrl) return;

    fileInput.addEventListener('change', function () {
      var file = fileInput.files[0];
      if (!file) return;
      var form = new FormData();
      form.append('image', file);
      form.append('csrf_token', csrfToken());
      if (status) status.textContent = 'Байршуулж байна…';

      fetch(uploadUrl, { method: 'POST', body: form })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.ok) {
            urlInput.value = data.url;
            if (preview) preview.innerHTML = '<img src="' + data.url + '" alt="">';
            if (status) status.textContent = '';
          } else {
            if (status) status.textContent = data.error || 'Байршуулахад алдаа гарлаа.';
          }
        })
        .catch(function () {
          if (status) status.textContent = 'Байршуулахад алдаа гарлаа.';
        });
    });
  });
}

function initDeleteConfirm() {
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    var handler = function (e) {
      if (!confirm(el.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    };
    if (el.tagName === 'FORM') {
      el.addEventListener('submit', handler);
    } else {
      el.addEventListener('click', handler);
    }
  });
}

function initStatusField() {
  var statusSelect = document.querySelector('[data-status-select]');
  var scheduleGroup = document.querySelector('[data-schedule-group]');
  if (!statusSelect || !scheduleGroup) return;
  function sync() {
    scheduleGroup.style.display = statusSelect.value === 'scheduled' ? '' : 'none';
  }
  statusSelect.addEventListener('change', sync);
  sync();
}

function initColorSync() {
  document.querySelectorAll('[data-color-pair]').forEach(function (colorInput) {
    var key = colorInput.getAttribute('data-color-pair');
    var textInput = document.querySelector('[data-color-text="' + key + '"]');
    if (!textInput) return;
    colorInput.addEventListener('input', function () { textInput.value = colorInput.value; });
    textInput.addEventListener('input', function () {
      if (/^#[0-9a-fA-F]{6}$/.test(textInput.value)) colorInput.value = textInput.value;
    });
  });
}

function initRichTextEditor() {
  var mount = document.querySelector('[data-quill-mount]');
  var hidden = document.querySelector('[data-quill-hidden]');
  if (!mount || !hidden || typeof Quill === 'undefined') return;

  var quill = new Quill(mount, {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ header: [2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link', 'image'],
        ['clean'],
      ],
    },
  });

  if (hidden.value) {
    quill.clipboard.dangerouslyPasteHTML(hidden.value);
  }

  var form = mount.closest('form');
  if (form) {
    form.addEventListener('submit', function () {
      hidden.value = quill.root.innerHTML;
    });
  }

  var uploadUrl = mount.getAttribute('data-upload-url');
  if (uploadUrl) {
    var toolbar = quill.getModule('toolbar');
    toolbar.addHandler('image', function () {
      var input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.onchange = function () {
        var file = input.files[0];
        if (!file) return;
        var form2 = new FormData();
        form2.append('image', file);
        form2.append('csrf_token', csrfToken());
        fetch(uploadUrl, { method: 'POST', body: form2 })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data.ok) {
              var range = quill.getSelection(true) || { index: quill.getLength() };
              quill.insertEmbed(range.index, 'image', data.url);
              quill.setSelection(range.index + 1);
            } else {
              alert(data.error || 'Зураг байршуулахад алдаа гарлаа.');
            }
          })
          .catch(function () { alert('Зураг байршуулахад алдаа гарлаа.'); });
      };
      input.click();
    });
  }
}
