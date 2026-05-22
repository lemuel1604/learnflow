

(function () {
  'use strict';

  /* ── Apply theme CSS vars live ──────────────────────────────────────── */
  function applyThemeLive(theme) {
    if (!theme || !theme.primary_color) return;

    var p   = theme.primary_color;
    var pd  = theme.primary_dark  || theme.primary_color;
    var pl  = theme.primary_light || theme.bg_color;
    var bg  = theme.bg_color;
    var sf  = theme.surface_color;
    var bd  = theme.border_color;
    var tx  = theme.text_color;
    var tx2 = theme.text_secondary || theme.text_color;
    var ac  = theme.accent_color   || theme.primary_color;

    var css = [
      ':root {',
      '  --primary:       hsl(' + p  + ');',
      '  --primary-hsl:   '    + p  + ';',
      '  --primary-dark:  hsl(' + pd + ');',
      '  --primary-light: hsl(' + pl + ');',
      '  --bg:            hsl(' + bg + ');',
      '  --surface:       hsl(' + sf + ');',
      '  --surface-2:     hsl(' + sf + ' / .72);',
      '  --border:        hsl(' + bd + ');',
      '  --text:          hsl(' + tx + ');',
      '  --text-2:        hsl(' + tx2 + ');',
      '  --text-3:        hsl(' + tx2 + ' / .6);',
      '  --accent:        hsl(' + ac + ');',
      '  --shadow-card:   0 4px 24px hsl(' + p + ' / .15);',
      '  --shadow-btn:    0 4px 20px hsl(' + p + ' / .38);',
      '}',
    ].join('\n');

    var tag = document.getElementById('lf-theme-live');
    if (!tag) {
      tag    = document.createElement('style');
      tag.id = 'lf-theme-live';
      document.head.appendChild(tag);
    }
    tag.textContent = css;

    /* Update any visible "active theme" badge in the admin portal */
    var badge = document.getElementById('themeActiveName');
    var swatch = document.getElementById('themeActiveSwatch');
    if (badge)  badge.textContent       = theme.name || '';
    if (swatch) swatch.style.background = 'hsl(' + p + ')';

    /* Dispatch a DOM event so custom portal code can react */
    document.dispatchEvent(new CustomEvent('lf:theme_changed', { detail: theme }));
  }

  /* ── SSE connection ─────────────────────────────────────────────────── */
  var _es        = null;
  var _retryMs   = 3000;   // start at 3 s
  var _maxRetry  = 30000;  // cap at 30 s
  var _retryTimer = null;

  function connect() {
    if (!window.EventSource) return; // browser too old — server-rendered theme still works

    if (_es) { _es.close(); _es = null; }

    var es = new EventSource('api-theme-sse.php');
    _es    = es;

    /* Incoming theme update — hot-swap CSS vars */
    es.addEventListener('theme_update', function (e) {
      try {
        var theme = JSON.parse(e.data);
        applyThemeLive(theme);
        _retryMs = 3000; // reset back-off on successful message
      } catch (err) {
        // ignore malformed data
      }
    });

    /* Server asked us to reconnect (lifetime exceeded) */
    es.addEventListener('reconnect', function () {
      es.close();
      _es = null;
      if (_retryTimer) clearTimeout(_retryTimer);
      _retryTimer = setTimeout(connect, 500); // reconnect almost immediately
    });

    /* Keep-alive heartbeat — no action needed */
    es.addEventListener('heartbeat', function () {});

    /* Error / disconnect — exponential back-off */
    es.onerror = function () {
      es.close();
      _es = null;
      if (_retryTimer) clearTimeout(_retryTimer);
      _retryTimer = setTimeout(connect, _retryMs);
      _retryMs = Math.min(_retryMs * 2, _maxRetry);
    };
  }

  /* ── Public API ─────────────────────────────────────────────────────── */
  window.applyThemeLive = applyThemeLive;

  window.LearnFlowTheme = {
    connect: connect,
    disconnect: function () {
      if (_retryTimer) clearTimeout(_retryTimer);
      if (_es) { _es.close(); _es = null; }
    },
    apply: applyThemeLive,
  };

  /* ── Boot ───────────────────────────────────────────────────────────── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', connect);
  } else {
    connect();
  }

}());
