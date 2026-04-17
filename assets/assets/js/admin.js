(function () {
  'use strict';

  var sidebar = document.getElementById('saasSidebar');
  var btn = document.getElementById('saasMenuBtn');
  var overlay = document.getElementById('saasOverlay');

  function closeMenu() {
    if (!sidebar || !btn) return;
    sidebar.classList.remove('is-open');
    btn.setAttribute('aria-expanded', 'false');
    if (overlay) {
      overlay.hidden = true;
      overlay.classList.remove('is-visible');
    }
    document.body.classList.remove('menu-open');
  }

  function openMenu() {
    if (!sidebar || !btn) return;
    sidebar.classList.add('is-open');
    btn.setAttribute('aria-expanded', 'true');
    if (overlay) {
      overlay.hidden = false;
      overlay.classList.add('is-visible');
    }
    document.body.classList.add('menu-open');
  }

  function toggleMenu() {
    if (sidebar && sidebar.classList.contains('is-open')) {
      closeMenu();
    } else {
      openMenu();
    }
  }

  if (btn) {
    btn.addEventListener('click', toggleMenu);
  }
  if (overlay) {
    overlay.addEventListener('click', closeMenu);
  }
  window.addEventListener('resize', function () {
    if (window.innerWidth > 960) {
      closeMenu();
    }
  });

  var slider = document.querySelector('[data-stat-slider]');
  if (slider) {
    var track = slider.querySelector('[data-stat-track]');
    var slides = slider.querySelectorAll('[data-stat-slide]');
    var dotsWrap = slider.querySelector('[data-stat-dots]');
    var prevBtn = slider.querySelector('[data-stat-prev]');
    var nextBtn = slider.querySelector('[data-stat-next]');
    var n = slides.length;
    var index = 0;
    var autoplayMs = parseInt(slider.getAttribute('data-autoplay') || '5000', 10);
    var timer;

    function go(i) {
      if (!track || n === 0) return;
      index = (i + n * 10) % n;
      var pct = (index * 100) / n;
      track.style.transform = 'translate3d(-' + pct + '%, 0, 0)';
      slides.forEach(function (el, j) {
        el.classList.toggle('is-active', j === index);
      });
      if (dotsWrap) {
        var dots = dotsWrap.querySelectorAll('button');
        dots.forEach(function (d, j) {
          d.classList.toggle('is-active', j === index);
          d.setAttribute('aria-current', j === index ? 'true' : 'false');
        });
      }
    }

    function nextSlide() {
      go(index + 1);
    }

    function prevSlide() {
      go(index - 1);
    }

    function resetTimer() {
      if (timer) clearInterval(timer);
      if (n > 1 && autoplayMs > 0) {
        timer = setInterval(nextSlide, autoplayMs);
      }
    }

    if (dotsWrap && n) {
      for (var j = 0; j < n; j++) {
        (function (k) {
          var b = document.createElement('button');
          b.type = 'button';
          b.className = 'saas-stat-dot' + (k === 0 ? ' is-active' : '');
          b.setAttribute('aria-label', 'الشريحة ' + (k + 1));
          b.addEventListener('click', function () {
            go(k);
            resetTimer();
          });
          dotsWrap.appendChild(b);
        })(j);
      }
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        prevSlide();
        resetTimer();
      });
    }
    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        nextSlide();
        resetTimer();
      });
    }

    go(0);
    resetTimer();
  }
})();
