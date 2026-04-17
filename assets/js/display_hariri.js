(function () {
  'use strict';

  var root = document.getElementById('root');
  if (!root) return;
  var api = root.getAttribute('data-api');
  var apiAbs = root.getAttribute('data-api-abs') || '';

  var tpl = document.getElementById('hariri-tpl');
  var slideA = document.getElementById('slideA');
  var slideB = document.getElementById('slideB');
  if (!tpl || !slideA || !slideB) return;

  var doctors = [];
  var slideSeconds = 15;
  var refreshSeconds = 20;
  var index = 0;
  var useA = true;
  var slideTimer = null;
  var refreshTimer = null;
  var lastDoctorsStateSig = '';
  var hospitalPhone = '';
  var hospitalLogo = '';

  /** ISO-8601: 1=lundi … 7=dimanche — عرض الجدول بالفرنسية كما في القالب */
  var FR_WEEKDAYS = ['', 'LUNDI', 'MARDI', 'MERCREDI', 'JEUDI', 'VENDREDI', 'SAMEDI', 'DIMANCHE'];

  function esc(s) {
    if (s == null) return '';
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function absUrl(u) {
    if (!u) return '';
    try {
      return new URL(u, location.origin).href;
    } catch (e) {
      return u;
    }
  }

  /**
   * صفوف الجدول = أيام العمل المفعّلة فقط (من weekly_schedule)، مرتبة من الإثنين للأحد.
   * إن لم يوجد جدول أسبوعي: صف واحد بمواعيد work_start/work_end الافتراضية.
   */
  function weeklyRowsForTemplate(d) {
    var ws = (d.weekly_schedule || []).filter(function (x) {
      if (!x) return false;
      var wd = typeof x.weekday === 'number' ? x.weekday : parseInt(x.weekday, 10);
      return !isNaN(wd) && wd >= 1 && wd <= 7;
    });
    ws.sort(function (a, b) {
      var aw = typeof a.weekday === 'number' ? a.weekday : parseInt(a.weekday, 10);
      var bw = typeof b.weekday === 'number' ? b.weekday : parseInt(b.weekday, 10);
      return aw - bw;
    });
    var padS = d.work_start || '08:00';
    var padE = d.work_end || '16:00';
    if (!ws.length) {
      return [
        {
          weekday: 0,
          work_start: String(padS).slice(0, 5),
          work_end: String(padE).slice(0, 5),
          _fallback: true,
        },
      ];
    }
    return ws.map(function (row) {
      var wd = typeof row.weekday === 'number' ? row.weekday : parseInt(row.weekday, 10);
      return {
        weekday: wd,
        work_start: String(row.work_start || '').slice(0, 5),
        work_end: String(row.work_end || '').slice(0, 5),
      };
    });
  }

  function fillSlideFromDoctor(frag, d) {
    var nameEl = frag.querySelector('.name_doctor p');
    if (nameEl) nameEl.textContent = d.name || '';
    var specEl = frag.querySelector('.name_des span');
    if (specEl) specEl.textContent = d.specialty || '';

    var mainImg = frag.querySelector('img.doctor');
    if (mainImg && d.image) {
      mainImg.src = d.image;
      mainImg.alt = d.name || '';
    }
    var partWrap = frag.querySelector('.img_part_doctor');
    var partImg = frag.querySelector('.img_part_doctor img');
    var deptBanner = d.department_banner ? String(d.department_banner).trim() : '';
    if (partWrap) {
      if (deptBanner) {
        partWrap.style.display = 'flex';
        partWrap.setAttribute('aria-hidden', 'false');
        if (partImg) {
          partImg.src = deptBanner;
          partImg.alt = d.department ? String(d.department) : '';
        }
      } else {
        partWrap.style.display = 'none';
        partWrap.setAttribute('aria-hidden', 'true');
        if (partImg) {
          partImg.removeAttribute('src');
          partImg.alt = '';
        }
      }
    }

    var logoEl = frag.querySelector('img.logo');
    if (logoEl && hospitalLogo) {
      logoEl.src = hospitalLogo;
    }

    var rows = weeklyRowsForTemplate(d);
    var table = frag.querySelector('.table');
    if (table) {
      var html = '';
      for (var r = 0; r < rows.length; r++) {
        var row = rows[r];
        var lab = row._fallback ? 'HORAIRES' : FR_WEEKDAYS[row.weekday] || '—';
        var ws = String(row.work_start || '').slice(0, 5);
        var we = String(row.work_end || '').slice(0, 5);
        html +=
          '<div class="row motion-row">' +
          '<h4>' +
          esc(lab) +
          '</h4>' +
          '<div class="LINE"></div>' +
          '<div class="date">' +
          esc(ws) +
          '</div>' +
          '<div class="line" aria-hidden="true">\u2013</div>' +
          '<div class="date-of">' +
          esc(we) +
          '</div>' +
          '</div>';
      }
      table.innerHTML = html;
    }

    var phoneEl = frag.querySelector('.button_data');
    if (phoneEl) {
      phoneEl.textContent = hospitalPhone || '';
    }
    var foot = frag.querySelector('.footer');
    if (foot) {
      foot.innerHTML =
        '<span class="footer__slogan" lang="fr">VOTRE SANTÉ NOTRE PRIORITÉ</span>';
    }
  }

  function cloneFilledSlide(d) {
    var frag = tpl.content.cloneNode(true);
    fillSlideFromDoctor(frag, d);
    var page = frag.querySelector('.page.motion-page') || frag.querySelector('.page');
    return page;
  }

  function showEmpty(msg) {
    if (slideTimer) {
      clearTimeout(slideTimer);
      slideTimer = null;
    }
    slideA.classList.add('slide--active');
    slideB.classList.remove('slide--active');
    slideB.setAttribute('hidden', '');
    slideA.innerHTML = '<div class="cinematic-empty" style="padding:2rem;text-align:center;color:#fff;font-size:1.25rem">' + esc(msg) + '</div>';
  }

  function swap() {
    if (!doctors.length) return;
    index = (index + 1) % doctors.length;
    var cur = useA ? slideA : slideB;
    var next = useA ? slideB : slideA;
    var pageEl = cloneFilledSlide(doctors[index]);
    next.innerHTML = '';
    if (pageEl) next.appendChild(pageEl);
    next.removeAttribute('hidden');
    cur.classList.remove('slide--active');
    next.classList.add('slide--active');
    useA = !useA;
  }

  function scheduleSlide() {
    if (slideTimer) clearTimeout(slideTimer);
    if (doctors.length <= 1) return;
    var ms = Math.max(5, Math.min(60, slideSeconds)) * 1000;
    slideTimer = setTimeout(function () {
      swap();
      scheduleSlide();
    }, ms);
  }

  function applyData(data) {
    if (!data.ok) {
      showEmpty('تعذر تحميل البيانات');
      return;
    }
    var raw = data.doctors || [];
    slideSeconds =
      data.screen && data.screen.slide_seconds ? Number(data.screen.slide_seconds) : 15;
    refreshSeconds = data.screen && data.screen.refresh_seconds ? data.screen.refresh_seconds : 20;

    var h = data.hospital || {};
    hospitalPhone = h.phone ? String(h.phone) : '';
    hospitalLogo = h.logo_url ? String(h.logo_url) : '';

    if (refreshTimer) {
      clearInterval(refreshTimer);
    }
    refreshTimer = setInterval(fetchData, Math.max(10, Math.min(30, refreshSeconds)) * 1000);

    /* كل الأطباء المربوطين بهذه الشاشة (نفس استعلام api/display.php). لا نفلتر هنا بـ
     * «available» حتى لا يُستبعد طبيب يظهر متاحاً في الإدارة بسبب اختلاف التوقيت/الشاشة. */
    var stateSig = raw
      .map(function (x) {
        return String(x.id) + ':' + String(x.status || '');
      })
      .join('|');
    doctors = raw.slice();

    if (!raw.length) {
      lastDoctorsStateSig = '';
      showEmpty('لا يوجد أطباء مربوطين بهذه الشاشة — راجع «الشاشات» وربط الطبيب بالشاشة الصحيحة.');
      return;
    }

    if (stateSig === lastDoctorsStateSig) {
      scheduleSlide();
      return;
    }
    lastDoctorsStateSig = stateSig;

    index = 0;
    useA = true;
    var firstPage = cloneFilledSlide(doctors[0]);
    slideA.innerHTML = '';
    if (firstPage) slideA.appendChild(firstPage);
    slideA.classList.add('slide--active');
    slideB.classList.remove('slide--active');
    slideB.setAttribute('hidden', '');
    slideB.innerHTML = '';
    scheduleSlide();
  }

  function fetchData() {
    var cacheKey = apiAbs || absUrl(api);
    fetch(api, { cache: 'no-store' })
      .then(function (r) {
        return r.json();
      })
      .then(applyData)
      .catch(function () {
        if (!('caches' in window) || !cacheKey) {
          showEmpty('تعذر الاتصال بالخادم');
          return;
        }
        caches.open('display-offline-v2').then(function (c) {
          return c.match(cacheKey);
        }).then(function (r) {
          if (r) {
            return r.json().then(applyData);
          }
          showEmpty('تعذر الاتصال بالخادم');
        });
      });
  }

  fetchData();

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') fetchData();
  });

  function tryLockOrientation() {
    try {
      var o = screen.orientation;
      if (o && typeof o.lock === 'function') {
        o.lock('portrait-primary').catch(function () {});
      }
    } catch (e) {}
  }
  function enterFullscreen() {
    var el = document.documentElement;
    var req =
      el.requestFullscreen ||
      el.webkitRequestFullscreen ||
      el.webkitRequestFullScreen ||
      el.mozRequestFullScreen ||
      el.msRequestFullscreen;
    if (!req) {
      tryLockOrientation();
      return;
    }
    var p = req.call(el);
    if (p && typeof p.then === 'function') {
      p.then(function () {
        tryLockOrientation();
      }).catch(function () {
        tryLockOrientation();
      });
    } else {
      tryLockOrientation();
    }
  }
  function onFirstUserGesture() {
    document.removeEventListener('pointerdown', onFirstUserGesture, true);
    document.removeEventListener('touchstart', onFirstUserGesture, true);
    enterFullscreen();
  }
  document.addEventListener('pointerdown', onFirstUserGesture, { capture: true, passive: true });
  document.addEventListener('touchstart', onFirstUserGesture, { capture: true, passive: true });
})();
