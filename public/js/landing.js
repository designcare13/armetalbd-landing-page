/**
 * AR Metal BD - Public Landing Page Interactive Script
 * Database-backed Products, Real-time Multi-Product Cart & Checkout, Meta Pixel Tracking
 */

// Ad blockers can stop the shared helper script from loading. The page must keep working without it.
window.ArmTracking = window.ArmTracking || {
  visitorToken: () => null,
  clientPayload: () => ({}),
  fbp: () => '',
  fbc: () => '',
  track: () => {},
  trackCustom: () => {},
};

let cart = [];
let deliveryCharge = (window.STORE_CONFIG && window.STORE_CONFIG.insideCharge) ? window.STORE_CONFIG.insideCharge : 150;
let currentCategory = 'all';

document.addEventListener('DOMContentLoaded', () => {
  // Pre-select a product: the one chosen on its product page (?buy=ID), otherwise the first one in stock
  const products = window.PRODUCTS_DATA || [];
  const buyId = parseInt(new URLSearchParams(window.location.search).get('buy'), 10);
  const chosen = products.find(p => p.id === buyId && p.stock > 0);
  const firstInStock = chosen || products.find(p => p.stock > 0);
  if (firstInStock) {
    cart.push({ ...firstInStock, qty: 1 });
  }

  renderProducts('all');
  updateOrderSummary();
  initFaqAccordion();
  initVideoSection();
  initIncompleteOrderCapture();
  initCheckoutTracking();

  if (chosen) {
    trackPixelAddToCart(chosen);
    history.replaceState(null, '', window.location.pathname + '#checkoutSection'); // keep the address clean and shareable
    // Wait for the page to finish loading: images still arriving would otherwise move the form after we scrolled.
    window.addEventListener('load', () => {
      document.getElementById('checkoutSection')?.scrollIntoView({ behavior: 'instant', block: 'start' });
    }, { once: true });
  }
});

// Escape text before injecting it into innerHTML
function esc(value) {
  return String(value ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
}



// ---------- Friendly messages (replaces browser alert boxes) ----------
const FIELD_MAP = {
  customer_name: 'customerName',
  customer_phone: 'customerPhone',
  customer_address: 'customerAddress',
  order_note: 'customerNote',
};

function showToast(message, type = 'error') {
  let wrap = document.getElementById('toastWrap');
  if (!wrap) {
    wrap = document.createElement('div');
    wrap.id = 'toastWrap';
    wrap.className = 'toast-wrap';
    wrap.setAttribute('aria-live', 'polite');
    document.body.appendChild(wrap);
  }

  const icons = { error: 'fa-circle-exclamation', success: 'fa-circle-check', warning: 'fa-triangle-exclamation' };
  const toast = document.createElement('div');
  toast.className = 'toast toast-' + type;
  toast.innerHTML = `<i class="fa-solid ${icons[type] || icons.error}"></i><span>${esc(message)}</span><button type="button" aria-label="বন্ধ করুন">&times;</button>`;

  const remove = () => { toast.classList.add('toast-hide'); setTimeout(() => toast.remove(), 300); };
  toast.querySelector('button').addEventListener('click', remove);
  wrap.appendChild(toast);
  setTimeout(remove, 6000);
}

function clearFieldErrors() {
  document.querySelectorAll('.field-error').forEach(el => el.remove());
  document.querySelectorAll('.form-control.invalid').forEach(el => el.classList.remove('invalid'));
}

function showFieldError(inputId, message) {
  const input = document.getElementById(inputId);
  if (!input) return false;

  input.classList.add('invalid');
  const error = document.createElement('div');
  error.className = 'field-error';
  error.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> ${esc(message)}`;
  input.insertAdjacentElement('afterend', error);
  input.addEventListener('input', () => {
    input.classList.remove('invalid');
    error.remove();
  }, { once: true });

  return true;
}

function focusFirstError() {
  const first = document.querySelector('.form-control.invalid');
  if (first) {
    first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => first.focus({ preventScroll: true }), 350);
  }
}

function normalizePhone(value) {
  const bn = '০১২৩৪৫৬৭৮৯';
  let digits = String(value).replace(/[০-৯]/g, d => bn.indexOf(d)).replace(/\D+/g, '');
  if (digits.startsWith('880') && digits.length === 13) digits = '0' + digits.slice(3);
  return digits;
}

function validateOrderForm(name, phone, address) {
  let valid = true;
  if (!name) { showFieldError('customerName', 'আপনার নাম প্রদান করুন।'); valid = false; }
  if (!phone) {
    showFieldError('customerPhone', 'মোবাইল নম্বর প্রদান করুন।'); valid = false;
  } else if (!/^01[3-9]\d{8}$/.test(normalizePhone(phone))) {
    showFieldError('customerPhone', '১১ ডিজিটের সঠিক মোবাইল নম্বর দিন (যেমন: 01805962220)।'); valid = false;
  }
  if (!address) { showFieldError('customerAddress', 'সম্পূর্ণ ঠিকানা প্রদান করুন।'); valid = false; }
  return valid;
}
// Format BDT
function formatBDT(num) {
  return '৳' + Number(num || 0).toLocaleString('en-IN');
}

// Render Products Grid
function renderProducts(filter = 'all') {
  currentCategory = filter;
  const grid = document.getElementById('productsGrid');
  if (!grid || !window.PRODUCTS_DATA) return;

  const filtered = filter === 'all' 
    ? window.PRODUCTS_DATA 
    : window.PRODUCTS_DATA.filter(p => p.category === filter);

  grid.innerHTML = filtered.map(product => {
    const isAdded = cart.some(item => item.id === product.id);
    const soldOut = !(product.stock > 0);

    return `
      <div class="product-card" id="product-${product.id}">
        <div class="product-badge-wrap">
          ${soldOut ? '<span class="badge badge-discount">স্টক শেষ</span>' : ''}
          ${product.badge ? `<span class="badge badge-featured"><i class="fa-solid fa-fire"></i> ${esc(product.badge)}</span>` : ''}
          ${product.discount ? `<span class="badge badge-discount">${esc(product.discount)}</span>` : ''}
        </div>

        <div class="card-img-wrap">
          <a href="${esc(product.url)}" tabindex="-1" aria-hidden="true"><img src="${esc(product.image)}" alt="${esc(product.name)}" loading="lazy"></a>
          <div class="card-quick-actions">
            <button class="quick-btn" title="কুইক ভিউ" onclick="openQuickView(${product.id})">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="card-body">
          <div class="card-stars">
            ${'<i class="fa-solid fa-star"></i>'.repeat(Math.floor(product.rating || 5))}
            <span>(${product.reviews || 20} রিভিউ)</span>
          </div>

          <h3 class="card-title"><a href="${esc(product.url)}">${esc(product.name)}</a></h3>

          <div class="card-price-row">
            <span class="price-current">${formatBDT(product.price)}</span>
            ${product.oldPrice ? `<span class="price-old">${formatBDT(product.oldPrice)}</span>` : ''}
          </div>

          <div class="card-actions-row">
            <button class="btn-order-now" ${soldOut ? 'disabled style="opacity:.5;cursor:not-allowed"' : ''} onclick="buyNowProduct(${product.id})">
              <i class="fa-solid fa-bolt"></i> ${soldOut ? 'স্টক শেষ' : 'সরাসরি অর্ডার করুন'}
            </button>
            <button class="btn-cart-toggle ${isAdded ? 'added' : ''}" ${soldOut ? 'disabled style="opacity:.5;cursor:not-allowed"' : ''} 
                    title="${isAdded ? 'অর্ডার লিস্টে যুক্ত আছে' : 'লিস্টে যোগ করুন'}"
                    onclick="toggleCartProduct(${product.id})">
              <i class="fa-solid ${isAdded ? 'fa-check' : 'fa-cart-plus'}"></i>
            </button>
          </div>
        </div>
      </div>
    `;
  }).join('');

  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.category === filter);
  });
}

function filterProducts(cat) {
  renderProducts(cat);
}

// "Buy Now" clicked: add product if not present, and scroll to checkout.
// The quick view popup passes the chosen quantity; the product cards omit it.
function buyNowProduct(id, qty = null) {
  const prod = window.PRODUCTS_DATA.find(p => p.id === id);
  const existing = cart.find(item => item.id === id);
  const wantedQty = prod && qty !== null
    ? Math.max(1, Math.min(qty, prod.stock, QUICK_VIEW_MAX_QTY))
    : 1;

  if (!existing) {
    if (prod && prod.stock > 0) {
      cart.push({ ...prod, qty: wantedQty });
      trackPixelAddToCart(prod);
    }
  } else if (qty !== null) {
    existing.qty = wantedQty;
  }
  updateOrderSummary();
  renderProducts(currentCategory);

  const checkoutSection = document.getElementById('checkoutSection');
  if (checkoutSection) {
    checkoutSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    const card = document.querySelector('.checkout-card');
    if (card) {
      card.style.transition = 'box-shadow 0.4s ease';
      card.style.boxShadow = '0 0 30px rgba(0, 128, 79, 0.4)';
      setTimeout(() => { card.style.boxShadow = ''; }, 1000);
    }
  }
}

// Toggle Cart Add/Remove from Card
function toggleCartProduct(id) {
  const index = cart.findIndex(item => item.id === id);
  if (index > -1) {
    cart.splice(index, 1);
  } else {
    const prod = window.PRODUCTS_DATA.find(p => p.id === id);
    if (prod && prod.stock > 0) {
      cart.push({ ...prod, qty: 1 });
      trackPixelAddToCart(prod);
    }
  }
  updateOrderSummary();
  renderProducts(currentCategory);
}

function updateQuantity(id, delta) {
  const item = cart.find(p => p.id === id);
  if (!item) return;

  item.qty += delta;
  if (item.qty > item.stock) {
    item.qty = item.stock;
    showToast('দুঃখিত, এই প্রোডাক্টের মাত্র ' + item.stock + ' টি স্টকে আছে।', 'warning');
  }
  if (item.qty <= 0) {
    cart = cart.filter(p => p.id !== id);
  }
  updateOrderSummary();
  renderProducts(currentCategory);
}

function removeItem(id) {
  cart = cart.filter(p => p.id !== id);
  updateOrderSummary();
  renderProducts(currentCategory);
}

// Set Delivery Area
function setDelivery(area, charge) {
  deliveryCharge = charge;
  document.querySelectorAll('.delivery-radio-label').forEach(label => {
    label.classList.remove('selected');
  });
  const activeLabel = document.getElementById(`del-${area}`);
  if (activeLabel) activeLabel.classList.add('selected');

  updateOrderSummary();
}

// Update Order Summary
function updateOrderSummary() {
  const listEl = document.getElementById('selectedItemsList');
  const emptyNotice = document.getElementById('emptyCartNotice');
  const subtotalEl = document.getElementById('summarySubtotal');
  const deliveryEl = document.getElementById('summaryDelivery');
  const totalEl = document.getElementById('summaryTotal');
  const cartBadge = document.getElementById('headerCartCount');
  const orderCountText = document.getElementById('orderCountText');

  const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
  const grandTotal = cart.length > 0 ? (subtotal + deliveryCharge) : 0;

  if (cartBadge) cartBadge.textContent = totalQty;
  if (orderCountText) orderCountText.textContent = `${totalQty} টি আইটেম`;

  if (cart.length === 0) {
    if (listEl) listEl.innerHTML = '';
    if (emptyNotice) emptyNotice.style.display = 'block';
  } else {
    if (emptyNotice) emptyNotice.style.display = 'none';
    if (listEl) {
      listEl.innerHTML = cart.map(item => `
        <div class="selected-item-row">
          <div class="item-thumb-info">
            <img src="${esc(item.image)}" alt="${esc(item.name)}" class="item-thumb">
            <div class="item-meta">
              <h5>${esc(item.name)}</h5>
              <span>${formatBDT(item.price)} × ${item.qty} = ${formatBDT(item.price * item.qty)}</span>
            </div>
          </div>
          <div class="qty-counter">
            <button type="button" class="qty-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
            <span class="qty-value">${item.qty}</span>
            <button type="button" class="qty-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
          </div>
          <button type="button" class="btn-remove-item" title="মুছে ফেলুন" onclick="removeItem(${item.id})">
            <i class="fa-solid fa-trash-can"></i>
          </button>
        </div>
      `).join('');
    }
  }

  if (subtotalEl) subtotalEl.textContent = formatBDT(subtotal);
  if (deliveryEl) deliveryEl.textContent = formatBDT(cart.length > 0 ? deliveryCharge : 0);
  if (totalEl) totalEl.textContent = formatBDT(grandTotal);

  scheduleIncompleteCapture();
}

// ---------- Quick View popup ----------
const QUICK_VIEW_MAX_QTY = 50; // same per-item limit the order endpoint enforces

function toBanglaDigits(value) {
  return String(value).replace(/\d/g, d => '০১২৩৪৫৬৭৮৯'[d]);
}

// "- ব্র্যান্ড: AR Metal" becomes a key/value row; lines without a colon stay as a plain note.
function renderQuickViewSpecs(specs) {
  const rows = (specs || []).map(s => String(s).replace(/^[\s\-–—•*]+/, '').trim()).filter(Boolean);
  if (!rows.length) return '';

  const items = rows.map(row => {
    const colon = row.indexOf(':');
    return colon > 0
      ? `<div class="pv-spec-row"><span class="pv-spec-key">${esc(row.slice(0, colon).trim())}</span><span class="pv-spec-val">${esc(row.slice(colon + 1).trim())}</span></div>`
      : `<div class="pv-spec-note"><i class="fa-solid fa-check"></i>${esc(row)}</div>`;
  }).join('');

  return `
    <section class="pv-section">
      <h3><i class="fa-solid fa-list-check"></i> প্রোডাক্টের বৈশিষ্ট্যসমূহ</h3>
      <div class="pv-spec-table">${items}</div>
    </section>`;
}

// Inner zoom: the part of the photo under the pointer is magnified inside the same frame (shared with the product page).
function setupQuickViewZoom(root) {
  if (window.ArmZoom) window.ArmZoom.init(root);
}

function openQuickView(id) {
  const product = window.PRODUCTS_DATA.find(p => p.id === id);
  if (!product) return;

  const content = document.getElementById('quickViewContent');
  if (!content) return;

  const inStock = product.stock > 0;
  const maxQty = Math.max(1, Math.min(product.stock, QUICK_VIEW_MAX_QTY));
  const inCart = cart.find(item => item.id === id);
  let qty = Math.min(inCart ? inCart.qty : 1, maxQty);
  const saving = product.oldPrice && product.oldPrice > product.price ? product.oldPrice - product.price : 0;
  const description = String(product.description || product.shortDesc || '').trim();
  const phone = String((window.STORE_CONFIG && window.STORE_CONFIG.phone) || '').replace(/[^\d+]/g, '');

  content.innerHTML = `
    <div class="pv-media">
      <div class="pv-stage">
        <div class="pv-zoom-frame">
          ${product.badge ? `<span class="pv-badge">${esc(product.badge)}</span>` : ''}
          <img src="${esc(product.image)}" alt="${esc(product.name)}" draggable="false" decoding="async">
          <span class="pv-zoom-level"></span>
          <span class="pv-zoom-hint"><i class="fa-solid fa-magnifying-glass-plus"></i>
            <span class="pv-hint-mouse">জুম দেখতে ছবির উপর মাউস নিন</span><span class="pv-hint-touch">জুম করতে ছবিতে ট্যাপ করুন</span>
          </span>
        </div>
      </div>
    </div>

    <div class="pv-info">
      <div class="pv-scroll">
        <div class="pv-top">
          <div class="card-stars">
            ${'<i class="fa-solid fa-star"></i>'.repeat(Math.floor(product.rating || 5))}
            <span>(${product.reviews || 20} কাস্টমার রিভিউ)</span>
          </div>
          <span class="pv-stock ${inStock ? 'in' : 'out'}"><i class="fa-solid ${inStock ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>${inStock ? 'স্টকে আছে' : 'স্টক শেষ'}</span>
        </div>

        <h2 class="pv-title">${esc(product.name)}</h2>
        <div class="pv-price-row">
          <span class="price-current">${formatBDT(product.price)}</span>
          ${product.oldPrice ? `<span class="price-old">${formatBDT(product.oldPrice)}</span>` : ''}
          ${product.discount ? `<span class="badge badge-discount">${esc(product.discount)}</span>` : ''}
        </div>
        ${saving ? `<div class="pv-saving"><i class="fa-solid fa-tag"></i> আপনার সাশ্রয়: ${formatBDT(saving)}</div>` : ''}
        <a class="pv-detail-link" href="${esc(product.url)}"><i class="fa-solid fa-arrow-up-right-from-square"></i> পূর্ণ বিবরণ ও ছবি আলাদা পেজে দেখুন</a>

        <div class="pv-trust">
          <div class="pv-trust-item"><i class="fa-solid fa-hand-holding-dollar"></i>পণ্য হাতে পেয়ে টাকা দিন (ক্যাশ অন ডেলিভারি)</div>
          <div class="pv-trust-item"><i class="fa-solid fa-truck-fast"></i>ঢাকায় ও সারা দেশে ডেলিভারি</div>
          <div class="pv-trust-item"><i class="fa-solid fa-industry"></i>সরাসরি নিজস্ব কারখানা থেকে</div>
        </div>

        ${description ? `
          <section class="pv-section">
            <h3><i class="fa-solid fa-align-left"></i> প্রোডাক্টের বিবরণ</h3>
            <div class="pv-desc clamped">${esc(description)}</div>
            <button type="button" class="pv-desc-more">আরও দেখুন <i class="fa-solid fa-chevron-down"></i></button>
          </section>` : ''}

        ${renderQuickViewSpecs(product.specs)}
      </div>

      <div class="pv-footer">
        <div class="pv-total"><span class="pv-qty-text"></span><span>মোট: <b class="pv-total-price"></b></span></div>
        <div class="pv-actions">
          <div class="pv-qty">
            <button type="button" class="pv-qty-minus" aria-label="পরিমাণ কমান">−</button>
            <output class="pv-qty-value" aria-live="polite"></output>
            <button type="button" class="pv-qty-plus" aria-label="পরিমাণ বাড়ান">+</button>
          </div>
          <button type="button" class="btn btn-primary pv-order-btn" ${inStock ? '' : 'disabled'}>
            <i class="fa-solid fa-bolt"></i> ${inStock ? 'এখনই অর্ডার করুন' : 'স্টক শেষ'}
          </button>
          ${phone ? `<a class="btn pv-call" href="tel:${esc(phone)}" aria-label="কল করুন"><i class="fa-solid fa-phone"></i></a>` : ''}
        </div>
      </div>
    </div>
  `;

  const qtyValue = content.querySelector('.pv-qty-value');
  const minusBtn = content.querySelector('.pv-qty-minus');
  const plusBtn = content.querySelector('.pv-qty-plus');
  const refreshQuantity = () => {
    qtyValue.textContent = qty;
    content.querySelector('.pv-qty-text').textContent = `${qty} টি, ডেলিভারি চার্জ আলাদা`;
    content.querySelector('.pv-total-price').textContent = formatBDT(product.price * qty);
    minusBtn.disabled = qty <= 1;
    plusBtn.disabled = qty >= maxQty;
  };

  minusBtn.addEventListener('click', () => { qty = Math.max(1, qty - 1); refreshQuantity(); });
  plusBtn.addEventListener('click', () => { qty = Math.min(maxQty, qty + 1); refreshQuantity(); });
  content.querySelector('.pv-order-btn').addEventListener('click', () => {
    closeModal('quickViewModal');
    buyNowProduct(product.id, qty);
  });

  const moreBtn = content.querySelector('.pv-desc-more');
  if (moreBtn) {
    const descEl = content.querySelector('.pv-desc');
    moreBtn.addEventListener('click', () => {
      const collapsed = descEl.classList.toggle('clamped');
      moreBtn.innerHTML = collapsed
        ? 'আরও দেখুন <i class="fa-solid fa-chevron-down"></i>'
        : 'কম দেখুন <i class="fa-solid fa-chevron-up"></i>';
    });
  }

  refreshQuantity();
  setupQuickViewZoom(content);

  const scrollArea = content.querySelector('.pv-scroll');
  if (scrollArea) scrollArea.scrollTop = 0;

  document.getElementById('quickViewModal').classList.add('open');
  document.body.style.overflow = 'hidden';
  ArmTracking.track('ViewContent', productEventData(product));

  // Offer "আরও দেখুন" only when the description really is cut off (needs the popup to be visible to measure).
  const descEl = content.querySelector('.pv-desc');
  if (moreBtn && descEl && descEl.scrollHeight <= descEl.clientHeight + 1) {
    descEl.classList.remove('clamped');
    moreBtn.remove();
  }
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.classList.remove('open');
  if (id === 'quickViewModal') document.body.style.overflow = '';
}

document.addEventListener('keydown', event => {
  if (event.key === 'Escape') closeModal('quickViewModal');
});
// FAQ Accordion
// The video player is loaded only when the visitor taps the cover, so it never slows the page down.
function initVideoSection() {
  const frame = document.getElementById('videoFrame');
  const poster = frame ? frame.querySelector('.video-poster') : null;
  if (!frame || !poster) return;

  poster.addEventListener('click', () => {
    const title = frame.dataset.videoTitle || 'Video';
    const player = document.createElement('iframe');
    player.src = frame.dataset.embedUrl;
    player.title = title;
    player.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
    player.referrerPolicy = 'strict-origin-when-cross-origin';
    player.allowFullscreen = true;

    frame.replaceChildren(player);
    ArmTracking.trackCustom('VideoPlay', { content_name: title, video_provider: 'youtube' }, 'video_start');
  }, { once: true });
}

function initFaqAccordion() {
  document.querySelectorAll('.faq-header').forEach(header => {
    header.addEventListener('click', () => {
      const item = header.parentElement;
      const wasActive = item.classList.contains('active');
      document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
      if (!wasActive) {
        item.classList.add('active');
      }
    });
  });
}

// ---------- Tracking helpers (events go out through the shared ArmTracking script) ----------
function productEventData(product, quantity = 1) {
  return {
    content_name: product.name,
    content_ids: [String(product.id)],
    content_type: 'product',
    contents: [{ id: String(product.id), quantity, item_price: product.price }],
    value: product.price * quantity,
    currency: 'BDT'
  };
}

function cartEventData() {
  return {
    content_ids: cart.map(item => String(item.id)),
    content_type: 'product',
    contents: cart.map(item => ({ id: String(item.id), quantity: item.qty, item_price: item.price })),
    num_items: cart.reduce((sum, item) => sum + item.qty, 0),
    value: cart.reduce((sum, item) => sum + item.price * item.qty, 0),
    currency: 'BDT'
  };
}

function trackPixelAddToCart(product) {
  ArmTracking.track('AddToCart', productEventData(product));
}

// InitiateCheckout fires once, the first time the visitor starts filling the order form.
function initCheckoutTracking() {
  const form = document.getElementById('orderForm');
  if (!form) return;

  const onFirstFocus = () => {
    if (cart.length === 0) return;
    form.removeEventListener('focusin', onFirstFocus);
    ArmTracking.track('InitiateCheckout', cartEventData());
  };
  form.addEventListener('focusin', onFirstFocus);
}

// Lead fires once per browser, the first time a valid phone number is saved. The server sends the same event.
function trackLeadOnce(token) {
  try {
    if (localStorage.getItem('arm_lead_tracked')) return;
    localStorage.setItem('arm_lead_tracked', '1');
  } catch (error) {
    return;
  }
  ArmTracking.track('Lead', cartEventData(), 'lead-' + token);
}

// ---------- Incomplete order capture ----------
// Once a valid phone number is typed, the details so far are saved on the server so the team can
// follow up if the order is never completed. The server removes them as soon as an order is placed.
let incompleteTimer = null;
let lastIncompleteBody = '';

// One anonymous id per browser (kept by arm-core.js); null when storage is blocked, in which case nothing is saved.
function getVisitorToken() {
  return window.ArmTracking ? ArmTracking.visitorToken() : null;
}

function buildIncompletePayload() {
  const phone = normalizePhone(document.getElementById('customerPhone')?.value || '');
  const token = getVisitorToken();
  if (!token || !/^01[3-9]\d{8}$/.test(phone)) return null;

  const areaInput = document.querySelector('input[name="deliveryArea"]:checked');
  return {
    visitor_token: token,
    customer_phone: phone,
    customer_name: (document.getElementById('customerName')?.value || '').trim(),
    customer_address: (document.getElementById('customerAddress')?.value || '').trim(),
    delivery_area: areaInput ? areaInput.value : null,
    items: cart.map(item => ({ id: item.id, qty: item.qty })),
    ...ArmTracking.clientPayload(),
  };
}

function sendIncompleteOrder() {
  incompleteTimer = null;
  const payload = buildIncompletePayload();
  if (!payload || !window.STORE_CONFIG || !window.STORE_CONFIG.incompleteUrl) return;

  const body = JSON.stringify(payload);
  if (body === lastIncompleteBody) return;
  lastIncompleteBody = body;
  trackLeadOnce(payload.visitor_token);

  fetch(window.STORE_CONFIG.incompleteUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': window.STORE_CONFIG.csrfToken,
    },
    body,
    keepalive: true,
  }).catch(() => { lastIncompleteBody = ''; }); // failures are silent; the next change retries
}

function scheduleIncompleteCapture() {
  clearTimeout(incompleteTimer);
  incompleteTimer = setTimeout(sendIncompleteOrder, 1500);
}

function cancelIncompleteCapture() {
  clearTimeout(incompleteTimer);
  incompleteTimer = null;
  lastIncompleteBody = '';
}

function initIncompleteOrderCapture() {
  ['customerName', 'customerPhone', 'customerAddress'].forEach(id => {
    const field = document.getElementById(id);
    if (field) field.addEventListener('input', scheduleIncompleteCapture);
  });
  document.querySelectorAll('input[name="deliveryArea"]').forEach(radio => {
    radio.addEventListener('change', scheduleIncompleteCapture);
  });

  // Save right away if the visitor leaves while a change is still waiting.
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden' && incompleteTimer) {
      clearTimeout(incompleteTimer);
      sendIncompleteOrder();
    }
  });
}

// Handle Order Submission via AJAX
async function handleOrderSubmit(event) {
  event.preventDefault();

  if (cart.length === 0) {
    showToast('অনুগ্রহ করে অন্তত ১টি প্রোডাক্ট সিলেক্ট করুন!', 'warning');
    const prodSection = document.getElementById('productsSection');
    if (prodSection) prodSection.scrollIntoView({ behavior: 'smooth' });
    return;
  }

  const name = document.getElementById('customerName').value.trim();
  const phone = document.getElementById('customerPhone').value.trim();
  const address = document.getElementById('customerAddress').value.trim();
  const note = document.getElementById('customerNote') ? document.getElementById('customerNote').value.trim() : '';

  clearFieldErrors();
  if (!validateOrderForm(name, phone, address)) {
    focusFirstError();
    return;
  }

  const areaInput = document.querySelector('input[name="deliveryArea"]:checked');
  const deliveryArea = areaInput ? areaInput.value : 'inside';

  const submitBtn = document.getElementById('btnSubmitOrder');
  const originalBtnText = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> অর্ডার প্রসেস হচ্ছে...';

  const payload = {
    customer_name: name,
    customer_phone: phone,
    customer_address: address,
    delivery_area: deliveryArea,
    order_note: note,
    visitor_token: getVisitorToken(),
    items: cart.map(i => ({ id: i.id, qty: i.qty })),
    ...ArmTracking.clientPayload()
  };

  try {
    const response = await fetch(window.STORE_CONFIG.orderUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.STORE_CONFIG.csrfToken
      },
      body: JSON.stringify(payload)
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
      let shownInline = false;
      Object.entries(result.errors || {}).forEach(([key, messages]) => {
        if (FIELD_MAP[key] && showFieldError(FIELD_MAP[key], messages[0])) shownInline = true;
      });

      if (shownInline) {
        focusFirstError();
      } else {
        const firstError = Object.values(result.errors || {})[0];
        showToast((firstError && firstError[0]) || result.message || 'অর্ডারে সমস্যা হয়েছে, পুনরায় চেষ্টা করুন।');
      }
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnText;
      return;
    }

    // The server sends this same Purchase to Meta with the same id, so it is counted once.
    if (result.pixel_purchase_payload) {
      ArmTracking.track('Purchase', result.pixel_purchase_payload, result.event_id);
    }

    // Populate Success Modal
    document.getElementById('confirmOrderId').textContent = result.order_number;
    document.getElementById('confirmCustomerName').textContent = result.customer_name;
    document.getElementById('confirmCustomerPhone').textContent = result.customer_phone;
    document.getElementById('confirmAddress').textContent = result.customer_address;
    document.getElementById('confirmDeliveryArea').textContent = result.delivery_area_text;
    document.getElementById('confirmGrandTotal').textContent = formatBDT(result.grand_total);
    document.getElementById('confirmProductsList').textContent = result.items_summary;

    // Show modal and reset cart
    cancelIncompleteCapture();
    document.getElementById('orderSuccessModal').classList.add('open');
    cart = [];
    document.getElementById('orderForm').reset();
    updateOrderSummary();

  } catch (error) {
    console.error('Order submission error:', error);
    showToast('সার্ভার রেসপন্স করতে পারছে না। অনুগ্রহ করে নেটওয়ার্ক চেক করুন।');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalBtnText;
  }
}
