/**
 * Public site interactivity: theme toggle, mobile menu, reading progress,
 * most-read tabs, "load more" latest news, and the copy-link share button.
 * Progressive enhancement throughout — every feature this file touches
 * still works (minus the enhancement) with JS disabled.
 */
document.addEventListener('DOMContentLoaded', function () {
  initThemeToggle();
  initMobileMenu();
  initReadingProgress();
  initMostReadTabs();
  initLoadMore();
  initCopyLink();
});

function initThemeToggle() {
  var btn = document.querySelector('[data-theme-toggle]');
  if (!btn) return;
  var root = document.documentElement;
  btn.addEventListener('click', function () {
    var current = root.getAttribute('data-theme') ||
      (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    var next = current === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('theme', next); } catch (e) { /* private mode / disabled storage */ }
  });
}

function initMobileMenu() {
  var openBtn = document.querySelector('[data-menu-open]');
  var closeBtn = document.querySelector('[data-menu-close]');
  var overlay = document.querySelector('[data-menu-overlay]');
  var menu = document.querySelector('[data-menu]');
  if (!openBtn || !menu || !overlay) return;

  function open() {
    menu.classList.add('is-open');
    overlay.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }
  function close() {
    menu.classList.remove('is-open');
    overlay.classList.remove('is-open');
    document.body.style.overflow = '';
  }
  openBtn.addEventListener('click', open);
  if (closeBtn) closeBtn.addEventListener('click', close);
  overlay.addEventListener('click', close);
}

function initReadingProgress() {
  var bar = document.querySelector('[data-reading-progress]');
  var article = document.querySelector('[data-article-body]');
  if (!bar || !article) return;

  function update() {
    var rect = article.getBoundingClientRect();
    var total = rect.height - window.innerHeight;
    var scrolled = Math.min(Math.max(-rect.top, 0), Math.max(total, 1));
    var pct = total > 0 ? (scrolled / total) * 100 : 0;
    bar.style.width = pct + '%';
  }
  document.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  update();
}

function initMostReadTabs() {
  var tabs = document.querySelectorAll('[data-tab-btn]');
  if (!tabs.length) return;
  tabs.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = btn.getAttribute('data-tab-btn');
      document.querySelectorAll('[data-tab-btn]').forEach(function (b) {
        b.classList.toggle('is-active', b === btn);
      });
      document.querySelectorAll('[data-tab-panel]').forEach(function (p) {
        p.classList.toggle('is-active', p.getAttribute('data-tab-panel') === target);
      });
    });
  });
}

function initLoadMore() {
  var btn = document.querySelector('[data-load-more]');
  if (!btn) return;

  btn.addEventListener('click', function () {
    var url = btn.getAttribute('data-load-more');
    var container = document.querySelector(btn.getAttribute('data-target'));
    if (!url || !container) return;

    var originalLabel = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Ачааллаж байна…';

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (res) { return res.ok ? res.text() : ''; })
      .then(function (html) {
        html = (html || '').trim();
        if (!html) {
          btn.remove();
          return;
        }
        var wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        var addedCount = wrapper.children.length;
        Array.prototype.forEach.call(wrapper.children, function (child) {
          container.appendChild(child);
        });

        var perPage = parseInt(btn.getAttribute('data-per-page') || '12', 10);
        var nextPage = parseInt(btn.getAttribute('data-page'), 10) + 1;
        btn.setAttribute('data-page', String(nextPage));
        btn.setAttribute('data-load-more', url.replace(/([?&])page=\d+/, '$1page=' + nextPage));

        if (addedCount < perPage) {
          btn.remove();
        } else {
          btn.disabled = false;
          btn.textContent = originalLabel;
        }
      })
      .catch(function () {
        btn.disabled = false;
        btn.textContent = originalLabel;
      });
  });
}

function initCopyLink() {
  var btn = document.querySelector('[data-share-copy]');
  if (!btn) return;
  btn.addEventListener('click', function () {
    var url = window.location.href;
    var markCopied = function () {
      btn.classList.add('is-copied');
      setTimeout(function () { btn.classList.remove('is-copied'); }, 1600);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(markCopied).catch(function () {
        fallbackCopy(url);
        markCopied();
      });
    } else {
      fallbackCopy(url);
      markCopied();
    }
  });
}

function fallbackCopy(text) {
  var ta = document.createElement('textarea');
  ta.value = text;
  ta.style.position = 'fixed';
  ta.style.opacity = '0';
  document.body.appendChild(ta);
  ta.select();
  try { document.execCommand('copy'); } catch (e) { /* no-op */ }
  document.body.removeChild(ta);
}
