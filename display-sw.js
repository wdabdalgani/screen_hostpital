/* Service worker: شاشة العرض — تخزين الصفحة والـ API والوسائط للعمل أوفلاين */
(function () {
  'use strict';

  var CACHE = 'display-offline-v2';

  self.addEventListener('install', function () {
    self.skipWaiting();
  });

  self.addEventListener('activate', function (e) {
    e.waitUntil(
      caches
        .keys()
        .then(function (keys) {
          return Promise.all(
            keys.map(function (k) {
              if (k !== CACHE && k.indexOf('display-offline-') === 0) {
                return caches.delete(k);
              }
            })
          );
        })
        .then(function () {
          return self.clients.claim();
        })
    );
  });

  function isDisplayPage(url) {
    return url.pathname.indexOf('display.php') !== -1;
  }

  function isApiDisplay(url) {
    return url.pathname.indexOf('api/display.php') !== -1;
  }

  function isAssetLike(url) {
    return url.pathname.indexOf('/uploads/') !== -1 || url.pathname.indexOf('/assets/') !== -1;
  }

  self.addEventListener('fetch', function (e) {
    var req = e.request;
    if (req.method !== 'GET') return;

    var url;
    try {
      url = new URL(req.url);
    } catch (err) {
      return;
    }
    if (url.origin !== self.location.origin) return;

    if (req.mode === 'navigate' || (req.destination === 'document' && isDisplayPage(url))) {
      e.respondWith(
        fetch(req)
          .then(function (res) {
            if (res.ok && isDisplayPage(url)) {
              var copy = res.clone();
              caches.open(CACHE).then(function (c) {
                c.put(req, copy);
              });
            }
            return res;
          })
          .catch(function () {
            return caches.match(req).then(function (hit) {
              if (hit) return hit;
              return new Response(
                '<!DOCTYPE html><html lang="ar" dir="rtl"><meta charset="utf-8"><title>أوفلاين</title><body style="font-family:sans-serif;text-align:center;padding:2rem;background:#050a12;color:#e8eef5">غير متاح أوفلاين. اتصل بالإنترنت ثم افتح الشاشة واضغط «حفظ للأوفلاين».</body></html>',
                { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
              );
            });
          })
      );
      return;
    }

    if (isApiDisplay(url)) {
      e.respondWith(
        fetch(req)
          .then(function (res) {
            if (res.ok) {
              var copy = res.clone();
              caches.open(CACHE).then(function (c) {
                c.put(req, copy);
              });
            }
            return res;
          })
          .catch(function () {
            return caches.match(req).then(function (hit) {
              return (
                hit ||
                new Response(JSON.stringify({ ok: false, error: 'offline' }), {
                  status: 200,
                  headers: { 'Content-Type': 'application/json; charset=utf-8' },
                })
              );
            });
          })
      );
      return;
    }

    if (isAssetLike(url)) {
      e.respondWith(
        (function () {
          var cacheKey = new Request(req.url, { method: 'GET' });
          return caches.match(cacheKey).then(function (hit) {
            if (hit) return hit;
            return fetch(req)
              .then(function (res) {
                if (res.ok) {
                  var copy = res.clone();
                  caches.open(CACHE).then(function (c) {
                    c.put(cacheKey, copy);
                  });
                }
                return res;
              })
              .catch(function () {
                return caches.match(cacheKey).then(function (h2) {
                  return h2 || new Response('', { status: 503 });
                });
              });
          });
        })()
      );
    }
  });
})();
