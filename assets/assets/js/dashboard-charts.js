/**
 * Dashboard: Chart.js widgets (data from #dashboard-chart-data) — 13 charts.
 */
(function () {
  var dataEl = document.getElementById('dashboard-chart-data');
  if (!dataEl || typeof Chart === 'undefined') {
    return;
  }

  var raw = dataEl.textContent || '{}';
  var data;
  try {
    data = JSON.parse(raw);
  } catch (e) {
    return;
  }

  var C = {
    g: '#2FAE66',
    b: '#2C7FB8',
    m: '#94a3b8',
    o: '#f59e0b',
    p: '#a855f7',
    r: '#ef4444',
    teal: '#14b8a6',
  };

  var multi = [C.g, C.b, C.o, C.p, C.teal, C.r, C.m, '#6366f1'];

  Chart.defaults.font.family =
    "'Segoe UI', 'Tahoma', 'Noto Sans Arabic', 'Arial', sans-serif";
  Chart.defaults.color = '#64748b';
  if (Chart.defaults.plugins && Chart.defaults.plugins.legend) {
    Chart.defaults.plugins.legend.rtl = true;
  }

  function sumArr(arr) {
    var s = 0;
    for (var i = 0; i < arr.length; i++) {
      s += arr[i] || 0;
    }
    return s;
  }

  function emptyNote(canvas, msg) {
    var p = canvas.parentElement;
    if (!p) return;
    canvas.style.display = 'none';
    var n = document.createElement('p');
    n.className = 'chart-card__empty';
    n.textContent = msg || 'لا توجد بيانات كافية.';
    p.appendChild(n);
  }

  /* 1 — Availability */
  var c1 = document.getElementById('dashChart1');
  if (c1) {
    var av = data.availability;
    if (!av || sumArr(av.values) === 0) {
      emptyNote(c1);
    } else {
      new Chart(c1, {
        type: 'doughnut',
        data: {
          labels: av.labels,
          datasets: [
            {
              data: av.values,
              backgroundColor: [C.g, C.r],
              borderWidth: 0,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
          },
        },
      });
    }
  }

  /* 2 — Departments */
  var c2 = document.getElementById('dashChart2');
  if (c2) {
    var dep = data.departments;
    if (!dep || !dep.labels || dep.labels.length === 0 || sumArr(dep.values) === 0) {
      emptyNote(c2);
    } else {
      new Chart(c2, {
        type: 'bar',
        data: {
          labels: dep.labels,
          datasets: [
            {
              label: 'أطباء',
              data: dep.values,
              backgroundColor: multi,
              borderRadius: 8,
            },
          ],
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
          },
          scales: {
            x: { beginAtZero: true, grid: { color: 'rgba(100,116,139,0.12)' } },
            y: { grid: { display: false } },
          },
        },
      });
    }
  }

  /* 3 — Screen modes */
  var c3 = document.getElementById('dashChart3');
  if (c3) {
    var sm = data.screenModes;
    if (!sm || sumArr(sm.values) === 0) {
      emptyNote(c3);
    } else {
      new Chart(c3, {
        type: 'pie',
        data: {
          labels: sm.labels,
          datasets: [
            {
              data: sm.values,
              backgroundColor: [C.b, C.o],
              borderWidth: 2,
              borderColor: '#fff',
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
          },
        },
      });
    }
  }

  /* 4 — Display styles */
  var c4 = document.getElementById('dashChart4');
  if (c4) {
    var st = data.styles;
    var stLabels = [];
    var stVals = [];
    if (st && st.length) {
      for (var i = 0; i < st.length; i++) {
        stLabels.push(st[i].label || st[i].key);
        stVals.push(st[i].count || 0);
      }
    }
    if (!stLabels.length || sumArr(stVals) === 0) {
      emptyNote(c4);
    } else {
      new Chart(c4, {
        type: 'bar',
        data: {
          labels: stLabels,
          datasets: [
            {
              label: 'شاشات',
              data: stVals,
              backgroundColor: C.b,
              borderRadius: 8,
            },
          ],
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
          },
          scales: {
            x: { beginAtZero: true, ticks: { stepSize: 1 } },
            y: { grid: { display: false } },
          },
        },
      });
    }
  }

  /* 5 — Content types */
  var c5 = document.getElementById('dashChart5');
  if (c5) {
    var ct = data.contentTypes;
    if (!ct || sumArr(ct.values) === 0) {
      emptyNote(c5);
    } else {
      new Chart(c5, {
        type: 'doughnut',
        data: {
          labels: ct.labels,
          datasets: [
            {
              data: ct.values,
              backgroundColor: [C.g, C.b, C.o],
              borderWidth: 0,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
          },
        },
      });
    }
  }

  /* 6 — Doctors per screen buckets */
  var c6 = document.getElementById('dashChart6');
  if (c6) {
    var bk = data.doctorBuckets;
    if (!bk || sumArr(bk.values) === 0) {
      emptyNote(c6);
    } else {
      new Chart(c6, {
        type: 'bar',
        data: {
          labels: bk.labels,
          datasets: [
            {
              label: 'شاشات',
              data: bk.values,
              backgroundColor: C.teal,
              borderRadius: 8,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
          },
          scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { grid: { display: false } },
          },
        },
      });
    }
  }

  /* 7 — Doctor status mode */
  var c7 = document.getElementById('dashChart7');
  if (c7) {
    var dm = data.doctorStatusModes;
    if (!dm || sumArr(dm.values) === 0) {
      emptyNote(c7);
    } else {
      new Chart(c7, {
        type: 'doughnut',
        data: {
          labels: dm.labels,
          datasets: [
            {
              data: dm.values,
              backgroundColor: [C.b, C.o],
              borderWidth: 0,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
          },
        },
      });
    }
  }

  /* 8 — Content groups */
  var c8 = document.getElementById('dashChart8');
  if (c8) {
    var cg = data.contentGroups;
    var cgL = [];
    var cgV = [];
    if (cg && cg.length) {
      for (var j = 0; j < cg.length; j++) {
        cgL.push(cg[j].name);
        cgV.push(cg[j].count || 0);
      }
    }
    if (!cgL.length) {
      emptyNote(c8);
    } else {
      new Chart(c8, {
        type: 'bar',
        data: {
          labels: cgL,
          datasets: [
            {
              label: 'عناصر نشطة',
              data: cgV,
              backgroundColor: C.g,
              borderRadius: 8,
            },
          ],
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
          },
          scales: {
            x: { beginAtZero: true, ticks: { stepSize: 1 } },
            y: { grid: { display: false } },
          },
        },
      });
    }
  }

  /* 9 — Availability trend (48h) */
  var c9 = document.getElementById('dashChart9');
  if (c9) {
    var tr = data.advancedTrend;
    if (!tr || !tr.labels || tr.labels.length < 2) {
      emptyNote(c9, 'تحتاج المنظومة مزيداً من اللقطات الزمنية لعرض الاتجاه.');
    } else {
      new Chart(c9, {
        type: 'line',
        data: {
          labels: tr.labels,
          datasets: [
            {
              label: 'نسبة التوفر %',
              data: tr.availablePct,
              borderColor: C.g,
              backgroundColor: 'rgba(47,174,102,0.14)',
              yAxisID: 'yPct',
              tension: 0.3,
              fill: true,
              pointRadius: 0,
            },
            {
              label: 'متاح',
              data: tr.availableCount,
              borderColor: C.b,
              yAxisID: 'yCount',
              tension: 0.25,
              pointRadius: 0,
            },
            {
              label: 'غير متاح',
              data: tr.unavailableCount,
              borderColor: C.r,
              yAxisID: 'yCount',
              tension: 0.25,
              pointRadius: 0,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: { legend: { position: 'bottom' } },
          scales: {
            x: { ticks: { maxTicksLimit: 10 } },
            yPct: {
              position: 'right',
              beginAtZero: true,
              max: 100,
              grid: { drawOnChartArea: false },
            },
            yCount: {
              position: 'left',
              beginAtZero: true,
              ticks: { stepSize: 1 },
            },
          },
        },
      });
    }
  }

  function heatColor(v) {
    var pct = Math.max(0, Math.min(100, v || 0));
    if (pct >= 85) return 'rgba(47,174,102,0.95)';
    if (pct >= 70) return 'rgba(52,191,116,0.9)';
    if (pct >= 55) return 'rgba(245,158,11,0.9)';
    if (pct >= 40) return 'rgba(251,146,60,0.9)';
    return 'rgba(239,68,68,0.9)';
  }

  /* 10 — Heatmap day x hour */
  var c10 = document.getElementById('dashChart10');
  if (c10) {
    var hm = data.advancedHeatmap;
    if (!hm || !hm.cells || hm.cells.length === 0) {
      emptyNote(c10, 'لا توجد بيانات كافية لرسم Heatmap بعد.');
    } else {
      var hmData = [];
      for (var h = 0; h < hm.cells.length; h++) {
        var cell = hm.cells[h];
        hmData.push({
          x: cell.hour,
          y: cell.dow,
          r: 9,
          v: cell.pct,
        });
      }
      new Chart(c10, {
        type: 'bubble',
        data: {
          datasets: [
            {
              label: 'نسبة التوفر',
              data: hmData,
              radius: 10,
              backgroundColor: function (ctx) {
                var raw = ctx.raw || {};
                return heatColor(raw.v || 0);
              },
              borderWidth: 0,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function (ctx) {
                  var raw = ctx.raw || {};
                  var d = hm.days && hm.days[raw.y - 1] ? hm.days[raw.y - 1] : raw.y;
                  return d + ' - ' + raw.x + ':00 => ' + (raw.v || 0) + '%';
                },
              },
            },
          },
          scales: {
            x: {
              min: -0.5,
              max: 23.5,
              ticks: {
                stepSize: 2,
                callback: function (v) {
                  return v + ':00';
                },
              },
              grid: { color: 'rgba(100,116,139,0.08)' },
            },
            y: {
              min: 0.5,
              max: 7.5,
              ticks: {
                stepSize: 1,
                callback: function (v) {
                  return hm.days && hm.days[v - 1] ? hm.days[v - 1] : v;
                },
              },
              grid: { color: 'rgba(100,116,139,0.08)' },
            },
          },
        },
      });
    }
  }

  /* 11 — Data quality funnel */
  var c11 = document.getElementById('dashChart11');
  if (c11) {
    var fn = data.advancedFunnel;
    if (!fn || !fn.values || sumArr(fn.values) === 0) {
      emptyNote(c11);
    } else {
      new Chart(c11, {
        type: 'bar',
        data: {
          labels: fn.labels,
          datasets: [
            {
              label: 'عدد',
              data: fn.values,
              backgroundColor: ['#2563eb', '#14b8a6', '#2fae66', '#f59e0b', '#ef4444'],
              borderRadius: 7,
            },
          ],
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { beginAtZero: true, ticks: { stepSize: 1 } },
            y: { grid: { display: false } },
          },
        },
      });
    }
  }

  /* 12 — Pareto */
  var c12 = document.getElementById('dashChart12');
  if (c12) {
    var pr = data.advancedPareto;
    if (!pr || !pr.labels || pr.labels.length === 0 || sumArr(pr.unavailable || []) === 0) {
      emptyNote(c12, 'لا توجد حالات عدم توفر كافية لرسم Pareto.');
    } else {
      new Chart(c12, {
        type: 'bar',
        data: {
          labels: pr.labels,
          datasets: [
            {
              type: 'bar',
              label: 'غير متاح',
              data: pr.unavailable,
              yAxisID: 'yCount',
              backgroundColor: 'rgba(239,68,68,0.78)',
              borderRadius: 7,
            },
            {
              type: 'line',
              label: 'تراكمي %',
              data: pr.cumulativePct,
              yAxisID: 'yPct',
              borderColor: C.b,
              backgroundColor: C.b,
              tension: 0.3,
              pointRadius: 2,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom' } },
          scales: {
            x: { grid: { display: false } },
            yCount: { position: 'left', beginAtZero: true, ticks: { stepSize: 1 } },
            yPct: {
              position: 'right',
              beginAtZero: true,
              max: 100,
              grid: { drawOnChartArea: false },
            },
          },
        },
      });
    }
  }

  /* 13 — SLA radar */
  var c13 = document.getElementById('dashChart13');
  if (c13) {
    var sla = data.advancedSla;
    if (!sla || !sla.labels || sla.labels.length === 0) {
      emptyNote(c13);
    } else {
      new Chart(c13, {
        type: 'radar',
        data: {
          labels: sla.labels,
          datasets: [
            {
              label: 'الحالي',
              data: sla.actual,
              borderColor: C.g,
              backgroundColor: 'rgba(47,174,102,0.22)',
              pointBackgroundColor: C.g,
            },
            {
              label: 'الهدف',
              data: sla.target,
              borderColor: C.b,
              backgroundColor: 'rgba(44,127,184,0.13)',
              pointBackgroundColor: C.b,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom' } },
          scales: {
            r: {
              min: 0,
              max: 100,
              ticks: { stepSize: 20, showLabelBackdrop: false },
              angleLines: { color: 'rgba(100,116,139,0.15)' },
              grid: { color: 'rgba(100,116,139,0.15)' },
            },
          },
        },
      });
    }
  }
})();
