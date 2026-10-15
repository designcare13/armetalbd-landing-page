/**
 * AR Metal BD - shared visitor and event helpers (landing page and product pages)
 *
 * One place for the anonymous visitor id, the campaign the visitor came from, the Meta cookie ids,
 * and for sending events to the Meta Pixel and Google Analytics. It loads before the Pixel code, so
 * the visitor id can be handed to Meta right away. Tracking must never break the page: every call is guarded.
 */
(function () {
  var TOKEN_KEY = 'arm_visitor_token';
  var ATTRIBUTION_KEY = 'arm_attribution';
  var ATTRIBUTION_DAYS = 30;
  var CAMPAIGN_PARAMS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
  var GA_EVENTS = { ViewContent: 'view_item', AddToCart: 'add_to_cart', InitiateCheckout: 'begin_checkout', Lead: 'generate_lead', Purchase: 'purchase', Contact: 'contact' };

  function newToken() {
    if (window.crypto && typeof crypto.randomUUID === 'function') return crypto.randomUUID();
    var bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    var hex = Array.prototype.map.call(bytes, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
    return hex.slice(0, 8) + '-' + hex.slice(8, 12) + '-' + hex.slice(12, 16) + '-' + hex.slice(16, 20) + '-' + hex.slice(20);
  }

  // One anonymous id per browser; null when storage is blocked (private mode), in which case nothing is saved.
  function visitorToken() {
    try {
      var token = localStorage.getItem(TOKEN_KEY);
      if (!token) {
        token = newToken();
        localStorage.setItem(TOKEN_KEY, token);
      }
      return token;
    } catch (error) {
      return null;
    }
  }

  function readCookie(name) {
    var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : '';
  }

  // Remember the campaign the visitor arrived from. The newest campaign wins; without one, an outside site is kept as the referrer.
  function captureAttribution() {
    try {
      var params = new URLSearchParams(window.location.search);
      var found = {};
      CAMPAIGN_PARAMS.forEach(function (key) {
        var value = params.get(key);
        if (value) found[key] = value.slice(0, 255);
      });
      if (params.get('fbclid')) found.fbclid = params.get('fbclid').slice(0, 200);

      var referrerHost = '';
      try { referrerHost = document.referrer ? new URL(document.referrer).hostname : ''; } catch (error) { /* ignore */ }
      var external = referrerHost && referrerHost !== window.location.hostname;

      var stored = JSON.parse(localStorage.getItem(ATTRIBUTION_KEY) || 'null');
      var fresh = stored && (Date.now() - stored.at) < ATTRIBUTION_DAYS * 864e5;

      if (Object.keys(found).length) {
        if (external) found.referrer = referrerHost;
        localStorage.setItem(ATTRIBUTION_KEY, JSON.stringify({ at: Date.now(), values: found }));
      } else if (!fresh && external) {
        localStorage.setItem(ATTRIBUTION_KEY, JSON.stringify({ at: Date.now(), values: { referrer: referrerHost } }));
      }
    } catch (error) { /* tracking is optional */ }
  }

  function storedAttribution() {
    try {
      var stored = JSON.parse(localStorage.getItem(ATTRIBUTION_KEY) || 'null');
      if (stored && (Date.now() - stored.at) < ATTRIBUTION_DAYS * 864e5) return stored;
    } catch (error) { /* ignore */ }
    return null;
  }

  function fbp() {
    return readCookie('_fbp');
  }

  // Meta's click id cookie, or one built from the ad click id in the address.
  function fbc() {
    var cookie = readCookie('_fbc');
    if (cookie) return cookie;
    var stored = storedAttribution();
    return stored && stored.values.fbclid ? 'fb.1.' + stored.at + '.' + stored.values.fbclid : '';
  }

  // What the server needs next to every order or phone number it receives.
  function clientPayload() {
    var stored = storedAttribution();
    var values = stored ? stored.values : {};
    var payload = { fbp: fbp(), fbc: fbc() };
    CAMPAIGN_PARAMS.concat(['referrer']).forEach(function (key) { payload[key] = values[key] || ''; });
    Object.keys(payload).forEach(function (key) { if (!payload[key]) delete payload[key]; });
    return payload;
  }

  function gaParams(params, eventId) {
    var out = { currency: params.currency, value: params.value };
    if (params.order_id) out.transaction_id = params.order_id;
    else if (eventId) out.event_id = eventId;
    if (params.contents) {
      out.items = params.contents.map(function (item) { return { item_id: item.id, quantity: item.quantity, price: item.item_price }; });
    } else if (params.content_ids) {
      out.items = params.content_ids.map(function (id) { return { item_id: id }; });
    }
    return out;
  }

  // Sends one event to the Meta Pixel and, when installed, to Google Analytics.
  // The same eventId is used by the server, so Meta counts the event once.
  function track(name, params, eventId) {
    params = params || {};
    try {
      if (typeof window.fbq === 'function') {
        if (eventId) window.fbq('track', name, params, { eventID: eventId });
        else window.fbq('track', name, params);
      }
      if (typeof window.gtag === 'function' && GA_EVENTS[name]) {
        window.gtag('event', GA_EVENTS[name], gaParams(params, eventId));
      }
    } catch (error) { /* tracking must never break the page */ }
  }

  // Calls and WhatsApp chats are conversions too.
  document.addEventListener('click', function (event) {
    var link = event.target.closest ? event.target.closest('a[href^="tel:"], a[href*="api.whatsapp.com"], a[href*="wa.me"]') : null;
    if (link) track('Contact', { content_name: link.getAttribute('href').indexOf('tel:') === 0 ? 'call' : 'whatsapp' });
  }, true);

  captureAttribution();

  window.ArmTracking = { visitorToken: visitorToken, clientPayload: clientPayload, fbp: fbp, fbc: fbc, track: track };
})();