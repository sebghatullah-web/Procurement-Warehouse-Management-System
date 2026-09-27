/* ============================================================
   PWMS - main.js
   ============================================================ */
(function () {
  'use strict';

  var BASE = window.PWMS_BASE || '';

  /* ---------- Mobile sidebar toggle ---------- */
  window.toggleSidebar = function () {
    var sb = document.getElementById('appSidebar');
    if (!sb) return;
    var show = sb.classList.toggle('show');
    document.body.classList.toggle('sidebar-open', show);
  };

  /* ---------- Active nav link ---------- */
  (function () {
    var path = location.pathname.replace(/\/+$/, '');
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(function (a) {
      var href = a.getAttribute('href') || '';
      var target = (href.split('?')[0] || '').replace(/\/+$/, '');
      if (path && target && path === target) { a.classList.add('active'); }
      else if (path && target && path.endsWith('/' + target.replace(new RegExp('^' + BASE.replace(/\//g, '\\/')), ''))) {
        a.classList.add('active');
      }
    });
  })();

  /* ---------- Confirm dialogs ---------- */
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm]');
    if (el) {
      var msg = el.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(msg)) { e.preventDefault(); }
    }
  });

  /* ---------- Print report buttons ---------- */
  document.addEventListener('click', function (e) {
    if (e.target.closest('.btn-print-report, [data-print]')) { e.preventDefault(); window.print(); }
  });

  /* ---------- Date range presets ---------- */
  document.querySelectorAll('[data-preset]').forEach(function (b) {
    b.addEventListener('click', function () {
      var preset = b.getAttribute('data-preset');
      var from = document.getElementById('from_date');
      var to = document.getElementById('to_date');
      if (!from || !to) return;
      var today = new Date(), d;
      function iso(date) { return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0'); }
      switch (preset) {
        case 'today':   d = new Date(); from.value = iso(d); to.value = iso(d); break;
        case 'week':    d = new Date(); d.setDate(d.getDate() - 6); from.value = iso(d); to.value = iso(new Date()); break;
        case 'month':   d = new Date(); d.setDate(1); from.value = iso(d); to.value = iso(new Date()); break;
        case 'year':    d = new Date(); d.setMonth(0); d.setDate(1); from.value = iso(d); to.value = iso(new Date()); break;
        case 'all':     from.value = ''; to.value = ''; break;
      }
    });
  });

  /* ---------- Simple client-side table filter ---------- */
  document.addEventListener('input', function (e) {
    if (e.target.id === 'tableSearch') {
      var q = e.target.value.toLowerCase();
      var tbl = e.target.getAttribute('data-table') || 'mainTable';
      var table = document.getElementById(tbl);
      if (!table) return;
      Array.from(table.tBodies[0].rows).forEach(function (tr) {
        tr.style.display = (q === '' || tr.innerText.toLowerCase().includes(q)) ? '' : 'none';
      });
    }
  });

  /* ---------- Quote select: sync currency from chosen quote ---------- */
  document.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'quoteSelect') {
      var cur = document.getElementById('currency');
      if (!cur) return;
      var chosen = Array.prototype.find.call(e.target.options, function (o) { return o.selected && o.getAttribute('data-cur'); });
      if (chosen && chosen.getAttribute('data-cur')) { cur.value = chosen.getAttribute('data-cur'); }
    }
  });

  /* ---------- Auto-submit filter forms on change ---------- */
  document.querySelectorAll('select[data-autosubmit]').forEach(function (s) {
    s.addEventListener('change', function () {
      var f = s.closest('form');
      if (f) f.submit();
    });
  });
})();