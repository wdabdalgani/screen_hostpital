(function () {
  'use strict';

  var grid = document.getElementById('doctorCardsGrid');
  var countEl = document.getElementById('doctorsFilteredCount');
  var searchEl = document.getElementById('doctorsSearch');
  var deptEl = document.getElementById('filterDepartment');
  var statusEl = document.getElementById('filterStatus');
  var screenEl = document.getElementById('filterScreen');

  if (!grid) return;

  var api = grid.getAttribute('data-api') || '';

  function normalize(s) {
    if (!s) return '';
    try {
      return s.toString().toLowerCase();
    } catch (e) {
      return '';
    }
  }

  function applyFilters() {
    var q = normalize(searchEl ? searchEl.value : '');
    var dept = deptEl && deptEl.value ? deptEl.value : '';
    var st = statusEl && statusEl.value ? statusEl.value : '';
    var scr = screenEl && screenEl.value ? screenEl.value : '';
    var cards = grid.querySelectorAll('.doctor-card');
    var visible = 0;
    cards.forEach(function (card) {
      var match = true;
      var blob = normalize(card.getAttribute('data-search') || '');
      if (q && blob.indexOf(q) === -1) {
        match = false;
      }
      if (match && dept && card.getAttribute('data-dept-id') !== dept) {
        match = false;
      }
      if (match && st && card.getAttribute('data-status') !== st) {
        match = false;
      }
      if (match && scr && card.getAttribute('data-screen-id') !== scr) {
        match = false;
      }
      card.hidden = !match;
      if (match) visible++;
    });
    if (countEl) {
      var total = cards.length;
      countEl.textContent =
        total === 0
          ? 'لا يوجد أطباء'
          : visible === total
            ? 'عرض ' + total + ' طبيب'
            : 'عرض ' + visible + ' من ' + total;
    }
  }

  function postJson(body) {
    return fetch(api, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(body),
      credentials: 'same-origin',
    }).then(function (r) {
      return r.json().then(function (data) {
        return { ok: r.ok, data: data };
      });
    });
  }

  function setBadge(card, effective) {
    var badge = card.querySelector('.doctor-card__badge');
    if (!badge) return;
    badge.textContent = effective === 'available' ? 'متاح' : 'غير متاح';
    badge.classList.remove('doctor-card__badge--ok', 'doctor-card__badge--no');
    badge.classList.add(effective === 'available' ? 'doctor-card__badge--ok' : 'doctor-card__badge--no');
    card.setAttribute('data-status', effective);
  }

  function setModeLabel(card, mode) {
    var el = card.querySelector('.doctor-card__mode');
    if (!el) return;
    el.textContent = mode === 'manual' ? 'يدوي' : 'تلقائي';
  }

  grid.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-action]');
    if (!btn || !grid.contains(btn)) return;
    var action = btn.getAttribute('data-action');
    var id = parseInt(btn.getAttribute('data-doctor-id') || '0', 10);
    if (!id) return;

    if (action === 'toggle') {
      btn.disabled = true;
      postJson({ action: 'toggle_status', doctor_id: id })
        .then(function (res) {
          btn.disabled = false;
          if (!res.ok || !res.data || !res.data.ok) {
            alert('تعذر تحديث الحالة.');
            return;
          }
          var card = btn.closest('.doctor-card');
          if (card) {
            setBadge(card, res.data.effective_status);
            setModeLabel(card, res.data.status_mode || 'manual');
          }
        })
        .catch(function () {
          btn.disabled = false;
          alert('تعذر الاتصال بالخادم.');
        });
      return;
    }

    if (action === 'delete') {
      if (!confirm('حذف هذا الطبيب نهائياً؟')) return;
      btn.disabled = true;
      postJson({ action: 'delete', doctor_id: id })
        .then(function (res) {
          if (!res.ok || !res.data || !res.data.ok) {
            btn.disabled = false;
            alert('تعذر الحذف.');
            return;
          }
          var card = btn.closest('.doctor-card');
          if (card) {
            card.classList.add('is-leaving');
            window.setTimeout(function () {
              card.remove();
              applyFilters();
            }, 260);
          }
        })
        .catch(function () {
          btn.disabled = false;
          alert('تعذر الاتصال بالخادم.');
        });
    }
  });

  if (searchEl) searchEl.addEventListener('input', applyFilters);
  if (deptEl) deptEl.addEventListener('change', applyFilters);
  if (statusEl) statusEl.addEventListener('change', applyFilters);
  if (screenEl) screenEl.addEventListener('change', applyFilters);

  applyFilters();
})();
