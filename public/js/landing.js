/**
 * AR Metal BD - Public Landing Page Interactive Script
 * Database-backed Products, Real-time Multi-Product Cart & Checkout, Meta Pixel Tracking
 */

let cart = [];
let deliveryCharge = (window.STORE_CONFIG && window.STORE_CONFIG.insideCharge) ? window.STORE_CONFIG.insideCharge : 150;
let currentCategory = 'all';

document.addEventListener('DOMContentLoaded', () => {
  // Pre-select first product if available
  const firstInStock = (window.PRODUCTS_DATA || []).find(p => p.stock > 0);
  if (firstInStock) {
    cart.push({ ...firstInStock, qty: 1 });
  }

  renderProducts('all');
  updateOrderSummary();
  initFaqAccordion();
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
          <img src="${esc(product.image)}" alt="${esc(product.name)}" loading="lazy">
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

          <h3 class="card-title">${esc(product.name)}</h3>
          <p class="card-desc">${esc(product.shortDesc)}</p>

          <ul class="card-specs-list">
            ${(product.specs || []).slice(0, 2).map(s => `<li><i class="fa-solid fa-circle-check"></i> ${esc(s)}</li>`).join('')}
          </ul>

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

// "Buy Now" clicked: add product if not present, and scroll to checkout
function buyNowProduct(id) {
  const existing = cart.find(item => item.id === id);
  if (!existing) {
    const prod = window.PRODUCTS_DATA.find(p => p.id === id);
    if (prod && prod.stock > 0) {
      cart.push({ ...prod, qty: 1 });
      trackPixelAddToCart(prod);
    }
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
}

// Quick View Modal
function openQuickView(id) {
  const product = window.PRODUCTS_DATA.find(p => p.id === id);
  if (!product) return;

  const content = document.getElementById('quickViewContent');
  if (!content) return;

  content.innerHTML = `
    <div class="modal-product-grid">
      <div class="modal-img-wrap">
        <img src="${product.image}" alt="${esc(product.name)}">
      </div>
      <div>
        <div class="card-stars mb-2">
          ${'<i class="fa-solid fa-star"></i>'.repeat(Math.floor(product.rating || 5))}
          <span>(${product.reviews || 20} কাস্টমার রিভিউ)</span>
        </div>
        <h2 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 10px; line-height: 1.3;">${esc(product.name)}</h2>
        <div class="card-price-row mb-3" style="margin-bottom: 14px;">
          <span class="price-current" style="font-size: 1.6rem;">${formatBDT(product.price)}</span>
          ${product.oldPrice ? `<span class="price-old">${formatBDT(product.oldPrice)}</span>` : ''}
          ${product.discount ? `<span class="badge badge-discount" style="margin-left:8px;">${esc(product.discount)}</span>` : ''}
        </div>
        <p style="color: var(--gray-dark); margin-bottom: 16px; font-size: 0.95rem; line-height: 1.6;">${esc(product.shortDesc)}</p>
        
        <h4 style="font-weight: 700; margin-bottom: 8px; font-size: 0.95rem;">প্রোডাক্টের বৈশিষ্ট্যসমূহ:</h4>
        <ul class="card-specs-list" style="margin-bottom: 20px;">
          ${(product.specs || []).map(s => `<li><i class="fa-solid fa-check"></i> ${esc(s)}</li>`).join('')}
        </ul>

        <div style="display: flex; gap: 12px;">
          <button class="btn btn-primary" style="flex: 1;" onclick="closeModal('quickViewModal'); buyNowProduct(${product.id});">
            <i class="fa-solid fa-bolt"></i> এখনই অর্ডার করুন
          </button>
        </div>
      </div>
    </div>
  `;

  document.getElementById('quickViewModal').classList.add('open');
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.classList.remove('open');
}

// FAQ Accordion
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

// Meta Pixel tracking helper
function trackPixelAddToCart(product) {
  if (typeof fbq === 'function') {
    fbq('track', 'AddToCart', {
      content_name: product.name,
      content_ids: [product.id],
      content_type: 'product',
      value: product.price,
      currency: 'BDT'
    });
  }
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
    items: cart.map(i => ({ id: i.id, qty: i.qty }))
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

    // Trigger Meta Pixel Purchase Event
    if (typeof fbq === 'function' && result.pixel_purchase_payload) {
      fbq('track', 'Purchase', result.pixel_purchase_payload);
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
