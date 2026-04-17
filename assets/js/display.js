(function () {
  'use strict';

  var root = document.getElementById('root');
  if (!root) return;
  var api = root.getAttribute('data-api');
  var apiAbs = root.getAttribute('data-api-abs') || '';

  var OFFLINE_CACHE = 'display-offline-v2';

  function offlineContextOk() {
    if (location.protocol === 'https:') return true;
    if (location.hostname === 'localhost' || location.hostname === '127.0.0.1') return true;
    return false;
  }

  function absUrl(u) {
    if (!u) return '';
    try {
      return new URL(u, location.origin).href;
    } catch (e) {
      return u;
    }
  }

  function collectMediaUrlsFromPayload(data) {
    var urls = [];
    var seen = {};
    function add(u) {
      var h = absUrl(u);
      if (!h || seen[h]) return;
      seen[h] = true;
      urls.push(h);
    }
    if (!data || !data.ok) return urls;
    var w = data.welcome;
    if (w) {
      if (w.image_url) add(w.image_url);
      if (w.logo_url) add(w.logo_url);
    }
    (data.doctors || []).forEach(function (d) {
      if (d.image) add(d.image);
      if (d.department_banner) add(d.department_banner);
    });
    (data.contents || []).forEach(function (c) {
      if (c.url) add(c.url);
    });
    return urls;
  }

  function registerOfflineServiceWorker() {
    var swUrl = root.getAttribute('data-sw-url');
    var scope = root.getAttribute('data-sw-scope');
    var autoOffline = root.getAttribute('data-auto-offline-sync') === '1';
    function startAutoSync() {
      if (!autoOffline) return;
      setTimeout(function () {
        runOfflineSync();
      }, 400);
    }
    if (!swUrl || !('serviceWorker' in navigator) || !offlineContextOk()) {
      startAutoSync();
      return;
    }
    navigator.serviceWorker
      .register(swUrl, { scope: scope || undefined })
      .then(function () {
        return navigator.serviceWorker.ready;
      })
      .then(startAutoSync)
      .catch(startAutoSync);
  }

  function setOfflineMsg(t) {
    var el = document.getElementById('displayOfflineMsg');
    if (el) el.textContent = t || '';
  }

  function runOfflineSync() {
    var pageAbs = root.getAttribute('data-display-page-abs') || location.href;
    var apiUrlAbs = apiAbs || absUrl(api);
    var cssU = absUrl(root.getAttribute('data-asset-css') || '');
    var jsU = absUrl(root.getAttribute('data-asset-js') || '');
    var manifestU = absUrl(root.getAttribute('data-manifest-abs') || '');
    var swU = absUrl(root.getAttribute('data-sw-url') || '');

    if (!('caches' in window)) {
      setOfflineMsg('المتصفح لا يدعم التخزين المحلي');
      return;
    }

    setOfflineMsg('جاري التحميل…');

    fetch(api, { cache: 'no-store' })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        var media = collectMediaUrlsFromPayload(data);
        var shell = [pageAbs, apiUrlAbs, cssU, jsU, manifestU, swU].filter(Boolean);
        var total = shell.length + media.length;
        var done = 0;
        function step() {
          done++;
          setOfflineMsg('جاري التحميل… ' + Math.min(done, total) + '/' + total);
        }
        function putUrl(cache, href) {
          if (!href) return Promise.resolve();
          return fetch(href, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (res) {
              if (res.ok) return cache.put(href, res);
            })
            .catch(function () {});
        }
        return caches.open(OFFLINE_CACHE).then(function (cache) {
          var chain = Promise.resolve();
          shell.forEach(function (u) {
            chain = chain.then(function () {
              return putUrl(cache, u).then(step);
            });
          });
          media.forEach(function (u) {
            chain = chain.then(function () {
              return putUrl(cache, u).then(step);
            });
          });
          return chain.then(function () {
            setOfflineMsg('تم الحفظ (' + total + ' ملفاً تقريباً). يمكنك إغلاق هذه النافذة.');
            try {
              if (root.getAttribute('data-auto-offline-sync') === '1') {
                var u = new URL(location.href);
                u.searchParams.delete('offline_sync');
                history.replaceState({}, '', u.pathname + u.search + u.hash);
              }
            } catch (e2) {}
          });
        });
      })
      .catch(function () {
        setOfflineMsg('تعذر التحميل — تحقق من الاتصال واستخدم HTTPS أو localhost');
      });
  }

  registerOfflineServiceWorker();
  var slideA = document.getElementById('slideA');
  var slideB = document.getElementById('slideB');

  var doctors = [];
  var contents = [];
  var displayMode = 'doctors';
  var slideSeconds = 8;
  var refreshSeconds = 20;
  var index = 0;
  var useA = true;
  var slideTimer = null;
  var refreshTimer = null;
  /** توقيع آخر قائمة محتوى/أطباء — يمنع إعادة ضبط الشرائح عند كل تحديث API */
  var lastContentPlaylistSig = '';
  var lastDoctorPlaylistSig = '';
  var welcomeEl = document.getElementById('welcomeOverlay');
  var welcomeBg = document.getElementById('welcomeBg');
  var welcomeLogo = document.getElementById('welcomeLogo');
  var welcomeTitle = document.getElementById('welcomeTitle');
  var welcomeSubtitle = document.getElementById('welcomeSubtitle');
  var displayStyle = 'hero_medical';
  var styleConfig = {};

  var ICONS = {
    spec:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/><circle cx="12" cy="12" r="4"/></svg>',
    clock:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    ok:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>',
    no:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>',
    user:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c1.5-4 4-6 7-6s5.5 2 7 6"/></svg>',
  };

  var DEPT_PATHS = {
    layers:
      '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="M2 12a1 1 0 0 0 .74.97l8.57 3.91a2 2 0 0 0 1.66 0l8.57-3.91A1 1 0 0 0 22 12"/><path d="M2 17a1 1 0 0 0 .74.97l8.57 3.91a2 2 0 0 0 1.66 0l8.57-3.91A1 1 0 0 0 22 17"/>',
    heart:
      '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.32C18.6 15.36 22 12.28 22 8.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2Z"/>',
    activity: '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
    bone:
      '<path d="M17 10c.7-.7 1.2-1.7 1.5-2.5a6.7 6.7 0 0 0 .3-2.5 4 4 0 0 0-4-4 2 2 0 0 0-1.8.9 2 2 0 0 1-3.6 0A2 2 0 0 0 7 1a4 4 0 0 0-4 4c0 .7.1 1.7.4 2.5.3.8.8 1.8 1.5 2.5"/><path d="M7 14c-.7.7-1.2 1.7-1.5 2.5a6.7 6.7 0 0 0-.3 2.5 4 4 0 0 0 4 4 2 2 0 0 0 1.8-.9 2 2 0 0 1 3.6 0 2 2 0 0 0 1.8.9 4 4 0 0 0 4-4c0-.7-.1-1.7-.4-2.5-.3-.8-.8-1.8-1.5-2.5"/>',
    brain:
      '<circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4"/>',
    baby:
      '<path d="M10 16c0-1.5 2-3 4-3"/><path d="M10 8h.01"/><path d="M14 8h.01"/><path d="M18.5 11c.5 1.5 1 3.5 1 5a7 7 0 1 1-14 0c0-1.5.5-3.5 1-5"/><path d="M7.5 11c.5-1.5 1.5-3 2.5-3s2 1.5 2.5 3"/>',
    tooth: '<path d="M7 10c0-2 1-4 5-4s5 2 5 4c0 2-1 4-2 5s-2 2-3 5c-1-3-2-4-3-5s-2-3-2-5z"/>',
    eye: '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
    stethoscope:
      '<path d="M4.8 2.3A.25.25 0 0 1 5 2h14a.25.25 0 0 1 .2.3l-.74 2.45a.17.17 0 0 0 0 .11c.1.39.18.8.18 1.22a4.5 4.5 0 1 1-9 0c0-.41.07-.82.18-1.22a.17.17 0 0 0 0-.11Z"/><path d="M8 15v1a4 4 0 0 0 4 4"/><path d="M12 20v-4"/>',
    pill: '<path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/>',
  };

  function esc(s) {
    if (s == null) return '';
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function doctorTimeLine(d) {
    if (d && d.time_display) return esc(d.time_display);
    return esc(d && d.work_start) + ' – ' + esc(d && d.work_end);
  }

  function ensureStyleNode() {
    var n = document.getElementById('runtimeStyleCss');
    if (!n) {
      n = document.createElement('style');
      n.id = 'runtimeStyleCss';
      document.head.appendChild(n);
    }
    return n;
  }

  function applyRuntimeStyle(screen) {
    var styleObj = screen && screen.style ? screen.style : {};
    styleConfig = styleObj.config || {};
    displayStyle = screen && screen.display_style ? screen.display_style : displayStyle;
    if (root) {
      root.setAttribute('data-display-style', displayStyle || '');
    }
    var colors = styleConfig.colors || {};
    if (colors.primary) root.style.setProperty('--brand-a', colors.primary);
    if (colors.secondary) root.style.setProperty('--brand-b', colors.secondary);
    if (colors.text) root.style.setProperty('--runtime-text', colors.text);
    if (colors.surface) root.style.setProperty('--runtime-surface', colors.surface);

    var anim = styleConfig.animations || {};
    var dur = parseInt(anim.duration_ms || 900, 10);
    if (!isNaN(dur) && dur > 100) {
      root.style.setProperty('--slide-fade-ms', String(dur) + 'ms');
    }

    var presetCss = styleObj.css || '';
    var fullBleedCard =
      '\n/* Full viewport: preset styles often cap card width — keep layout responsive on any screen */\n' +
      '#root .card-display{position:absolute!important;inset:0!important;width:100%!important;height:100%!important;max-width:100%!important;box-sizing:border-box!important;align-items:stretch!important;justify-content:stretch!important;}\n' +
      '#root .card-display .card-display__card{width:100%!important;max-width:100%!important;height:100%!important;min-height:0!important;flex:1 1 auto!important;display:flex!important;flex-direction:column!important;border-radius:0!important;border:none!important;box-shadow:none!important;}\n' +
      '#root .card-display .card-display__body{flex:1 1 auto!important;min-height:0!important;align-items:stretch!important;}\n' +
      '#root .card-display .card-display__hero{min-height:0!important;height:100%!important;}\n' +
      '#root .card-display .card-display__hero-img,#root .card-display .card-display__hero-empty{width:100%!important;height:100%!important;max-height:none!important;}\n';
    ensureStyleNode().textContent = presetCss + fullBleedCard;
  }

  function renderDoctorHero(d) {
    var photoBlock = d.image
      ? '<img class="cinematic-stack__photo" src="' +
        esc(d.image) +
        '" alt="" loading="eager" decoding="async">'
      : '<div class="cinematic-stack__photo cinematic-stack__photo--empty" aria-hidden="true">' +
        ICONS.user +
        '</div>';

    var stClass = d.status === 'available' ? 'cinematic-status-badge--ok' : 'cinematic-status-badge--no';
    var stackClass = d.status === 'available' ? 'cinematic-stack--ok' : 'cinematic-stack--no';
    var stLabel = d.status === 'available' ? 'متاح' : 'غير متاح';
    var depIcon = DEPT_PATHS[d.department_icon] || DEPT_PATHS.layers;

    var ambient = '';
    if (d.department_banner) {
      ambient =
        '<div class="cinematic-stack__ambient" aria-hidden="true">' +
        '<img class="cinematic-stack__ambient-img" src="' +
        esc(d.department_banner) +
        '" alt="" loading="eager" decoding="async">' +
        '<div class="cinematic-stack__ambient-scrim"></div>' +
        '<div class="cinematic-stack__ambient-brand"></div>' +
        '</div>';
    }

    return (
      '<div class="cinematic-stack ' +
      stackClass +
      '">' +
      ambient +
      '<div class="cinematic-stack__bg">' +
      photoBlock +
      '<div class="cinematic-stack__vignette" aria-hidden="true"></div>' +
      '<div class="cinematic-stack__scrim" aria-hidden="true"></div>' +
      '<div class="cinematic-stack__brand" aria-hidden="true"></div>' +
      '</div>' +
      '<div class="cinematic-stack__content">' +
      '<div class="cinematic-stack__text">' +
      '<div class="cinematic-status-line"></div>' +
      '<span class="cinematic-status-badge ' +
      stClass +
      '">' +
      (d.status === 'available' ? ICONS.ok : ICONS.no) +
      '<span>' +
      stLabel +
      '</span></span>' +
      '<h1 class="cinematic-name">' +
      esc(d.name) +
      '</h1>' +
      '<div class="cinematic-rows">' +
      '<div class="cinematic-row">' +
      ICONS.spec +
      '<span>' +
      esc(d.specialty || '—') +
      '</span></div>' +
      '<div class="cinematic-row cinematic-row--time">' +
      ICONS.clock +
      '<span dir="ltr">' +
      doctorTimeLine(d) +
      '</span></div>' +
      '</div>' +
      '</div>' +
      '</div>' +
      '</div>'
    );
  }

  function renderDoctorCard(d) {
    var banner = d.department_banner
      ? '<img class="card-display__banner-img" src="' + esc(d.department_banner) + '" alt="" loading="eager" decoding="async">'
      : '<div class="card-display__banner-fallback" aria-hidden="true"></div>';
    var hero = d.image
      ? '<img class="card-display__hero-img" src="' + esc(d.image) + '" alt="" loading="eager" decoding="async">'
      : '<div class="card-display__hero-empty" aria-hidden="true">' + ICONS.user + '</div>';
    var stClass = d.status === 'available' ? 'card-display__badge--ok' : 'card-display__badge--no';

    return (
      '<div class="card-display">' +
      '<article class="card-display__card">' +
      '<header class="card-display__banner">' +
      banner +
      '<div class="card-display__banner-overlay"></div>' +
      '</header>' +
      '<div class="card-display__body">' +
      '<div class="card-display__hero">' +
      hero +
      '<div class="card-display__hero-fade" aria-hidden="true"></div>' +
      '</div>' +
      '<div class="card-display__info">' +
      '<h2 class="card-display__name">' +
      esc(d.name) +
      '</h2>' +
      '<p class="card-display__spec">' +
      esc(d.specialty || '—') +
      '</p>' +
      '<p class="card-display__time" dir="ltr">' +
      ICONS.clock +
      '<span>' +
      doctorTimeLine(d) +
      '</span></p>' +
      '<span class="card-display__badge ' +
      stClass +
      '">' +
      (d.status === 'available' ? ICONS.ok : ICONS.no) +
      '<span>' +
      (d.status === 'available' ? 'متاح' : 'غير متاح') +
      '</span></span>' +
      '</div>' +
      '</div>' +
      '</article>' +
      '</div>'
    );
  }

  function renderDoctorMinimal(d) {
    var available = d.status === 'available';
    var stClass = available ? 'simple-display__status--ok' : 'simple-display__status--no';
    var stLabel = available ? 'متاح' : 'غير متاح';
    var stHint = available ? 'يمكنك الزيارة خلال أوقات الدوام' : 'غير متاح في هذا الوقت';
    var glyph = available ? ICONS.ok : ICONS.no;
    var photo = d.image
      ? '<img class="simple-display__photo" src="' +
        esc(d.image) +
        '" alt="" loading="eager" decoding="async">'
      : '<div class="simple-display__photo simple-display__photo--empty" aria-hidden="true">' + ICONS.user + '</div>';
    return (
      '<div class="simple-display" role="article">' +
      '<div class="simple-display__photo-wrap">' +
      photo +
      '</div>' +
      '<div class="simple-display__panel">' +
      '<div class="simple-display__status ' +
      stClass +
      '" role="status" aria-label="' +
      esc(stLabel) +
      '">' +
      '<span class="simple-display__status-glyph" aria-hidden="true">' +
      glyph +
      '</span>' +
      '<div class="simple-display__status-texts">' +
      '<span class="simple-display__status-title">' +
      esc(stLabel) +
      '</span>' +
      '<span class="simple-display__status-hint">' +
      esc(stHint) +
      '</span>' +
      '</div></div>' +
      '<h1 class="simple-display__name">' +
      esc(d.name) +
      '</h1>' +
      '<p class="simple-display__spec">' +
      esc(d.specialty || '—') +
      '</p>' +
      '</div></div>'
    );
  }

  function renderDoctor(d) {
    var layout = styleConfig && styleConfig.layout ? styleConfig.layout : '';
    if (!layout) {
      layout = displayStyle === 'card_social' ? 'card' : 'hero';
    }
    if (layout === 'card' || layout === 'split') {
      return renderDoctorCard(d);
    }
    if (layout === 'minimal') {
      return renderDoctorMinimal(d);
    }
    return renderDoctorHero(d);
  }

  function renderContent(item) {
    var media = '';
    if (item.type === 'video') {
      media =
        '<video class="content-screen__video" src="' +
        esc(item.url) +
        '" autoplay muted playsinline preload="metadata"></video>';
    } else {
      media =
        '<img class="content-screen__image" src="' +
        esc(item.url) +
        '" alt="" loading="eager" decoding="async">';
    }
    return (
      '<div class="content-screen content-screen--clean">' +
      media +
      '<div class="content-screen__overlay"></div>' +
      '</div>'
    );
  }

  function showEmpty(msg) {
    slideA.classList.add('slide--active');
    slideB.classList.remove('slide--active');
    slideB.setAttribute('hidden', '');
    slideA.innerHTML =
      '<div class="cinematic-empty">' + ICONS.user + '<p>' + esc(msg) + '</p></div>';
  }

  function setWelcomeOverlay(w) {
    if (!welcomeEl || !welcomeBg) return;
    var on = w && w.active;
    if (!on) {
      welcomeEl.hidden = true;
      welcomeEl.setAttribute('hidden', '');
      welcomeEl.setAttribute('aria-hidden', 'true');
      welcomeEl.classList.remove('welcome-overlay--on');
      welcomeBg.style.backgroundImage = '';
      welcomeBg.classList.add('welcome-overlay__media--empty');
      if (welcomeLogo) {
        welcomeLogo.hidden = true;
        welcomeLogo.removeAttribute('src');
      }
      if (welcomeTitle) welcomeTitle.textContent = '';
      if (welcomeSubtitle) welcomeSubtitle.textContent = '';
      return;
    }
    welcomeEl.hidden = false;
    welcomeEl.removeAttribute('hidden');
    welcomeEl.setAttribute('aria-hidden', 'false');
    welcomeEl.classList.add('welcome-overlay--on');
    if (w.image_url) {
      welcomeBg.classList.remove('welcome-overlay__media--empty');
      welcomeBg.style.backgroundImage = 'url(' + JSON.stringify(String(w.image_url)) + ')';
    } else {
      welcomeBg.style.backgroundImage = '';
      welcomeBg.classList.add('welcome-overlay__media--empty');
    }
    if (welcomeLogo) {
      if (w.logo_url) {
        welcomeLogo.src = String(w.logo_url);
        welcomeLogo.hidden = false;
      } else {
        welcomeLogo.hidden = true;
        welcomeLogo.removeAttribute('src');
      }
    }
    if (welcomeTitle) welcomeTitle.textContent = w.title ? String(w.title) : '';
    if (welcomeSubtitle) welcomeSubtitle.textContent = w.subtitle ? String(w.subtitle) : '';
  }

  function swap() {
    var arr = displayMode === 'content' ? contents : doctors;
    if (!arr.length) return;
    index = (index + 1) % arr.length;
    var html = displayMode === 'content' ? renderContent(arr[index]) : renderDoctor(arr[index]);
    var cur = useA ? slideA : slideB;
    var next = useA ? slideB : slideA;
    next.innerHTML = html;
    next.removeAttribute('hidden');
    cur.classList.remove('slide--active');
    next.classList.add('slide--active');
    useA = !useA;
  }

  function scheduleSlide() {
    if (slideTimer) clearTimeout(slideTimer);
    if (displayMode === 'content') {
      if (contents.length <= 1) return;
      var item = contents[index] || {};
      var dur = parseInt(item.duration_seconds || 8, 10);
      if (isNaN(dur)) dur = 8;
      dur = Math.max(5, Math.min(15, dur));
      slideTimer = setTimeout(function () {
        swap();
        scheduleSlide();
      }, dur * 1000);
      return;
    }
    if (doctors.length <= 1) return;
    slideTimer = setTimeout(function () {
      swap();
      scheduleSlide();
    }, Math.max(5, Math.min(10, slideSeconds)) * 1000);
  }

  function applyData(data) {
    if (!data.ok) {
      setWelcomeOverlay(null);
      showEmpty('تعذر تحميل البيانات');
      return;
    }
    doctors = data.doctors || [];
    contents = data.contents || [];
    displayMode = data.screen && data.screen.display_mode ? data.screen.display_mode : 'doctors';
    slideSeconds = data.screen && data.screen.slide_seconds ? data.screen.slide_seconds : 8;
    refreshSeconds = data.screen && data.screen.refresh_seconds ? data.screen.refresh_seconds : 20;
    displayStyle = data.screen && data.screen.display_style ? data.screen.display_style : 'hero_medical';
    applyRuntimeStyle(data.screen || {});
    if (refreshTimer) {
      clearInterval(refreshTimer);
    }
    refreshTimer = setInterval(fetchData, Math.max(10, Math.min(30, refreshSeconds)) * 1000);

    var welcome = data.welcome || {};
    var welcomeOn = !!(welcome && welcome.active);

    if (welcomeOn) {
      lastContentPlaylistSig = '';
      lastDoctorPlaylistSig = '';
      setWelcomeOverlay(welcome);
      if (slideTimer) {
        clearTimeout(slideTimer);
        slideTimer = null;
      }
      if (displayMode === 'content' && contents.length) {
        index = 0;
        useA = true;
        slideA.innerHTML = renderContent(contents[0]);
        slideA.classList.add('slide--active');
        slideB.classList.remove('slide--active');
        slideB.setAttribute('hidden', '');
        slideB.innerHTML = '';
      } else if (displayMode === 'doctors' && doctors.length) {
        index = 0;
        useA = true;
        slideA.innerHTML = renderDoctor(doctors[0]);
        slideA.classList.add('slide--active');
        slideB.classList.remove('slide--active');
        slideB.setAttribute('hidden', '');
        slideB.innerHTML = '';
      } else {
        slideA.innerHTML = '';
        slideA.classList.add('slide--active');
        slideB.classList.remove('slide--active');
        slideB.setAttribute('hidden', '');
        slideB.innerHTML = '';
      }
      return;
    }

    setWelcomeOverlay(null);

    if (displayMode !== 'content') {
      lastContentPlaylistSig = '';
    }
    if (displayMode !== 'doctors') {
      lastDoctorPlaylistSig = '';
    }

    if (slideTimer) {
      clearTimeout(slideTimer);
      slideTimer = null;
    }

    if (displayMode === 'content') {
      if (!contents.length) {
        lastContentPlaylistSig = '';
        showEmpty('لا يوجد محتوى لهذه الشاشة');
        return;
      }
      var contentSig = contents
        .map(function (c) {
          return String(c.id);
        })
        .join(',');
      if (contentSig !== '' && contentSig === lastContentPlaylistSig) {
        lastContentPlaylistSig = contentSig;
        scheduleSlide();
        return;
      }
      lastContentPlaylistSig = contentSig;
      index = 0;
      useA = true;
      slideA.innerHTML = renderContent(contents[0]);
      slideA.classList.add('slide--active');
      slideB.classList.remove('slide--active');
      slideB.setAttribute('hidden', '');
      slideB.innerHTML = '';
      scheduleSlide();
      return;
    }

    if (!doctors.length) {
      lastDoctorPlaylistSig = '';
      showEmpty('لا يوجد أطباء لهذه الشاشة');
      return;
    }

    var doctorSig = doctors
      .map(function (d) {
        return String(d.id);
      })
      .join(',');
    if (doctorSig !== '' && doctorSig === lastDoctorPlaylistSig) {
      lastDoctorPlaylistSig = doctorSig;
      scheduleSlide();
      return;
    }
    lastDoctorPlaylistSig = doctorSig;
    index = 0;
    useA = true;
    slideA.innerHTML = renderDoctor(doctors[0]);
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
        caches.open(OFFLINE_CACHE).then(function (c) {
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

  /* أول لمسة/نقرة: طلب ملء الشاشة + محاولة قفل اتجاه مناسب لنمط العرض */
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
