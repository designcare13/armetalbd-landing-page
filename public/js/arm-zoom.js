/**
 * AR Metal BD - hover zoom for product photos (quick view popup and product page)
 *
 * Put the photo in a ".pv-zoom-frame" with an <img> and a ".pv-zoom-level" label. With a mouse, moving over the
 * photo magnifies the part under the pointer inside the same frame. Touch screens have no hover, so a tap
 * toggles the zoom and moving a finger pans it.
 */
(function () {
  var DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

  function toBangla(text) {
    return String(text).replace(/\d/g, function (digit) { return DIGITS[digit]; });
  }

  function attach(frame) {
    var img = frame.querySelector('img');
    var levelEl = frame.querySelector('.pv-zoom-level');
    if (!img || frame.dataset.zoomReady) return;
    frame.dataset.zoomReady = '1';

    var active = false;
    var scale = 2;
    var lastPointerType = 'mouse';

    // Never magnify far beyond the real pixels, otherwise the photo turns blurry.
    function pickScale() {
      var ratio = img.naturalWidth / Math.max(1, img.clientWidth);
      scale = Math.min(3, Math.max(1.8, ratio));
      if (levelEl) levelEl.textContent = toBangla(scale.toFixed(1)) + '× জুম' + (ratio < 1.4 ? ' · ছবির মান কম' : '');
    }

    function moveOrigin(event) {
      var rect = frame.getBoundingClientRect();
      var x = Math.min(100, Math.max(0, (event.clientX - rect.left) / rect.width * 100));
      var y = Math.min(100, Math.max(0, (event.clientY - rect.top) / rect.height * 100));
      img.style.transformOrigin = x + '% ' + y + '%';
    }

    function zoomIn(event) {
      pickScale();
      active = true;
      frame.classList.add('is-zooming');
      moveOrigin(event);
      img.style.transform = 'scale(' + scale + ')';
    }

    function zoomOut() {
      active = false;
      frame.classList.remove('is-zooming');
      img.style.transform = '';
    }

    frame.addEventListener('pointerdown', function (e) { lastPointerType = e.pointerType; });
    frame.addEventListener('pointerenter', function (e) { if (e.pointerType === 'mouse') zoomIn(e); });
    frame.addEventListener('pointermove', function (e) { if (active) moveOrigin(e); });
    frame.addEventListener('pointerleave', function (e) { if (e.pointerType === 'mouse') zoomOut(); });
    frame.addEventListener('click', function (e) { if (lastPointerType !== 'mouse') { active ? zoomOut() : zoomIn(e); } });
  }

  // Makes every zoom frame inside root (a whole page or just a freshly drawn popup) work.
  function init(root) {
    (root || document).querySelectorAll('.pv-zoom-frame').forEach(attach);
  }

  window.ArmZoom = { init: init, attach: attach };
})();
