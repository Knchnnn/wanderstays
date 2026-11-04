// Notifications
function showNotif(msg, type = 'success') {
  const area = document.getElementById('notif-area');
  if (!area) return;
  const el = document.createElement('div');
  el.className = 'notif ' + type;
  el.textContent = msg;
  el.onclick = () => el.remove();
  area.appendChild(el);
  setTimeout(() => el && el.remove(), 4000);
}

// Flash message from URL
(function() {
  const params = new URLSearchParams(location.search);
  const msg = params.get('msg'), type = params.get('type') || 'success';
  if (msg) {
    setTimeout(() => showNotif(decodeURIComponent(msg), type), 200);
    const u = new URL(location.href);
    u.searchParams.delete('msg'); u.searchParams.delete('type');
    history.replaceState({}, '', u);
  }
})();

// Auth tabs
document.querySelectorAll('.auth-tab').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.auth-tab').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    const mode = this.dataset.mode;
    document.querySelectorAll('.auth-section').forEach(s => s.style.display = 'none');
    const sec = document.getElementById('auth-' + mode);
    if (sec) sec.style.display = 'block';
  });
});

// Payment tabs
document.querySelectorAll('.pay-tab').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.pay-tab').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    document.querySelectorAll('.pay-section').forEach(s => s.classList.remove('active'));
    const t = document.getElementById('pay-' + this.dataset.tab);
    if (t) t.classList.add('active');
  });
});

// Card format
const cardInput = document.getElementById('card_number');
if (cardInput) {
  cardInput.addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '').slice(0, 16);
    this.value = v.match(/.{1,4}/g)?.join(' ') || v;
  });
}
const expiryInput = document.getElementById('card_expiry');
if (expiryInput) {
  expiryInput.addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '').slice(0, 4);
    if (v.length > 2) v = v.slice(0, 2) + '/' + v.slice(2);
    this.value = v;
  });
}

// Price sort live (places page)
const priceSort = document.getElementById('price_sort');
if (priceSort) {
  priceSort.addEventListener('change', function() {
    this.closest('form').submit();
  });
}

// Image upload preview
function initUploader(zoneId, inputId, previewId) {
  const zone = document.getElementById(zoneId);
  const input = document.getElementById(inputId);
  const preview = document.getElementById(previewId);
  if (!zone || !input) return;

  zone.addEventListener('click', () => input.click());
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag'));
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('drag');
    handleFiles(e.dataTransfer.files);
  });
  input.addEventListener('change', () => handleFiles(input.files));

  function handleFiles(files) {
    Array.from(files).forEach(file => {
      if (!file.type.startsWith('image/')) return;
      const reader = new FileReader();
      reader.onload = e => {
        const div = document.createElement('div');
        div.className = 'thumb';
        div.innerHTML = `<img src="${e.target.result}"><button type="button" class="thumb-del" onclick="this.parentNode.remove()">×</button>`;
        preview.appendChild(div);
      };
      reader.readAsDataURL(file);
    });
  }
}
initUploader('upload-zone', 'photos', 'photo-preview');

// Photo gallery (main image switcher)
function switchPhoto(src, btn) {
  const main = document.getElementById('main-photo');
  if (main) main.src = src;
  document.querySelectorAll('.thumbstrip img').forEach(i => i.classList.remove('active'));
  if (btn) btn.classList.add('active');
}

// Dot navigation
function switchDot(idx, el, images) {
  const main = document.getElementById('banner-photo');
  if (main && images[idx]) main.src = images[idx];
  document.querySelectorAll('.dot').forEach((d, i) => d.classList.toggle('active', i === idx));
}

// Booking price calculator
const offerInput = document.getElementById('offered_price');
const checkInInput = document.getElementById('check_in');
const checkOutInput = document.getElementById('check_out');
const totalDisplay = document.getElementById('price-total');

function calcTotal() {
  if (!offerInput || !checkInInput || !checkOutInput || !totalDisplay) return;
  const ci = new Date(checkInInput.value), co = new Date(checkOutInput.value);
  const nights = Math.max(0, (co - ci) / 86400000);
  const price = parseFloat(offerInput.value) || 0;
  if (nights > 0 && price > 0) {
    totalDisplay.innerHTML = `₹${price.toLocaleString('en-IN')} × ${nights} nights = <strong>₹${(price * nights).toLocaleString('en-IN')}</strong>`;
    totalDisplay.style.display = 'block';
  } else {
    totalDisplay.style.display = 'none';
  }
}
[offerInput, checkInInput, checkOutInput].forEach(el => el && el.addEventListener('input', calcTotal));

// Star rating selector
document.querySelectorAll('.star-pick').forEach(star => {
  star.addEventListener('click', function() {
    const val = this.dataset.val;
    document.getElementById('rating_val').value = val;
    document.querySelectorAll('.star-pick').forEach((s, i) => {
      s.style.color = i < val ? '#c5a55a' : '#3a3030';
    });
  });
});

// Bargain warning
if (offerInput) {
  const listedPrice = parseFloat(offerInput.dataset.listed || 0);
  offerInput.addEventListener('input', function() {
    const w = document.getElementById('bargain-warn');
    if (w) w.style.display = parseFloat(this.value) < listedPrice ? 'block' : 'none';
  });
}
