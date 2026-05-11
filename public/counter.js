/*!
 * CounterSaaS SDK
 * Commercial Web Counter SaaS
 */
(function () {
  'use strict';

  function currentScript() {
    if (document.currentScript) return document.currentScript;
    var scripts = document.getElementsByTagName('script');
    return scripts[scripts.length - 1];
  }

  function cleanUrl(url) {
    var a = document.createElement('a');
    a.href = url || location.href;
    return a.protocol + '//' + a.host + (a.pathname || '/');
  }

  function serverFromScript(script) {
    var manual = script.getAttribute('data-server');
    if (manual) return manual.replace(/\/+$/, '');
    var src = script.getAttribute('src') || '';
    return src.replace(/\/counter\.js(?:\?.*)?$/i, '').replace(/\/+$/, '');
  }

  function attr(script, key, fallback) {
    var value = script.getAttribute('data-' + key);
    return value === null ? fallback : value;
  }

  function boolAttr(script, key, fallback) {
    var value = script.getAttribute('data-' + key);
    if (value === null) return fallback;
    return !/^(0|false|off|no)$/i.test(value);
  }

  function md5Fallback(text) {
    // Lightweight non-cryptographic fallback key if a page_key is not provided.
    var h = 0, i, chr;
    text = String(text);
    if (text.length === 0) return '0';
    for (i = 0; i < text.length; i++) {
      chr = text.charCodeAt(i);
      h = ((h << 5) - h) + chr;
      h |= 0;
    }
    return 'page_' + Math.abs(h);
  }

  function buildUrl(config, endpoint, countMode) {
    var pageUrl = cleanUrl(location.href);
    var pageKey = config.pageKey || md5Fallback(pageUrl);
    var params = [
      'key=' + encodeURIComponent(config.key),
      'page_url=' + encodeURIComponent(pageUrl),
      'page_key=' + encodeURIComponent(pageKey),
      'page_title=' + encodeURIComponent(document.title || ''),
      't=' + Date.now()
    ];
    return config.server + '/api/' + endpoint + '.php?' + params.join('&');
  }

  function request(url, ok, fail) {
    fetch(url, {
      method: 'GET',
      mode: 'cors',
      credentials: 'omit',
      cache: 'no-store'
    })
      .then(function (res) { return res.json(); })
      .then(function (json) {
        if (!json || json.code !== 200) {
          throw new Error((json && json.message) || 'Counter request failed.');
        }
        ok(json.data, json);
      })
      .catch(function (err) {
        if (typeof fail === 'function') fail(err);
        if (window.console && console.warn) console.warn('[CounterSaaS]', err.message || err);
      });
  }

  function styleFor(theme) {
    var base = 'display:inline-flex;align-items:center;gap:4px;border-radius:999px;padding:2px 8px;font-size:12px;line-height:1.8;';
    if (theme === 'dark') return base + 'background:#111827;color:#f9fafb;';
    if (theme === 'primary') return base + 'background:#2563eb;color:#fff;';
    return base + 'background:#f3f4f6;color:#111827;';
  }

  function render(config, data) {
    if (config.mode === 'hidden' || config.mode === 'custom') return;

    var target = config.target ? document.querySelector(config.target) : null;
    var el = document.createElement('span');
    el.className = 'counter-saas counter-saas-' + config.mode;

    var label = config.label || '访问量';
    if (config.mode === 'number') {
      el.textContent = String(data.views);
    } else if (config.mode === 'badge') {
      el.style.cssText = styleFor(config.theme);
      el.textContent = label + ' ' + data.views;
    } else {
      el.textContent = label + '：' + data.views;
    }

    if (config.theme === 'custom' && config.customCss) {
      el.style.cssText += ';' + config.customCss;
    }

    if (target) {
      target.innerHTML = '';
      target.appendChild(el);
    } else if (config.script && config.script.parentNode) {
      config.script.parentNode.insertBefore(el, config.script.nextSibling);
    }
  }

  function Counter(config) {
    this.config = config;
    this.data = null;
  }

  Counter.prototype.fetch = function (callback, errorCallback) {
    var self = this;
    request(buildUrl(this.config, 'collect'), function (data, raw) {
      self.data = data;
      window.CounterSaaS.data = data;
      render(self.config, data);
      window.dispatchEvent(new CustomEvent('counter-saas:loaded', { detail: data }));
      if (typeof callback === 'function') callback(data, raw);
    }, errorCallback);
  };

  Counter.prototype.get = function (callback, errorCallback) {
    var self = this;
    request(buildUrl(this.config, 'get'), function (data, raw) {
      self.data = data;
      window.CounterSaaS.data = data;
      if (typeof callback === 'function') callback(data, raw);
    }, errorCallback);
  };

  var script = currentScript();
  var config = {
    script: script,
    server: serverFromScript(script),
    key: attr(script, 'key', ''),
    pageKey: attr(script, 'page-key', ''),
    target: attr(script, 'target', ''),
    mode: attr(script, 'mode', 'text'),
    theme: attr(script, 'theme', 'light'),
    label: attr(script, 'label', '访问量'),
    customCss: attr(script, 'custom-css', ''),
    auto: boolAttr(script, 'auto', true),
    lazy: boolAttr(script, 'lazy', false)
  };

  var instance = new Counter(config);

  window.CounterSaaS = {
    data: null,
    instance: instance,
    fetch: function (callback, errorCallback) { instance.fetch(callback, errorCallback); },
    get: function (callback, errorCallback) { instance.get(callback, errorCallback); }
  };

  function run() {
    if (!config.key) {
      if (window.console && console.warn) console.warn('[CounterSaaS] Missing data-key.');
      return;
    }
    if (config.auto && config.mode !== 'custom') {
      instance.fetch();
    }
  }

  if (config.lazy) {
    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(run, { timeout: 2000 });
    } else {
      setTimeout(run, 0);
    }
  } else {
    run();
  }
})();
