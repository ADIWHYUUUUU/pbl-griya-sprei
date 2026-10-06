/* =========================================
   1. DATA PRODUK
   ========================================= */
const products = [
  { name: "Sprei Katun Asterina Sage",        type: "Sprei Katun MicroSilk Tencel", category: "sprei",    size: "160 × 200 × 30", price: 185000, color: "#9bb87a", badge: "Premium MicroSilk Tencel", desc: "Halus dan lembut seperti sutera, lebar kain 280cm & tebal 130gsm berserat alami." },
  { name: "Sprei Katun Asterina Sand",        type: "Sprei Katun MicroSilk Tencel", category: "sprei",    size: "160 × 200 × 30", price: 185000, color: "#c4a67c", badge: "Premium MicroSilk Tencel", desc: "Tekstur sutera MicroSilk adem dengan nuansa warna natural sand hangat." },
  { name: "Sprei Katun Asterina Burgundy",    type: "Sprei Katun MicroSilk Tencel", category: "sprei",    size: "180 × 200 × 35", price: 195000, color: "#7a1f35", badge: "Premium MicroSilk Tencel", desc: "Warna burgundy elegan mewah dengan motif salur sutera halus anti luntur." },
  { name: "Sprei Katun Asterina Mocha",       type: "Sprei Katun MicroSilk Tencel", category: "sprei",    size: "160 × 200 × 30", price: 185000, color: "#a98466", badge: "Premium MicroSilk Tencel", desc: "Warna mocha hangat yang nyaman, kain lebar ekstra 280cm jahit rapi presisi." },
  { name: "Sprei Katun Asterina Classic Blue",type: "Sprei Katun MicroSilk Tencel", category: "sprei",    size: "160 × 200 × 30", price: 185000, color: "#4a74b5", badge: "Premium MicroSilk Tencel", desc: "Warna classic blue tenang dengan tekstur sutera lembut dan berdaya serap tinggi." },
  { name: "Sprei Beverly Alexander Dark Grey",type: "Katun Premium Beverly",        category: "bedcover", size: "180 × 200 × 30", price: 175000, color: "#5d6f86", badge: "Beverly Home",             desc: "Material katun premium lebar kain 250cm, adem, tidak berbulu dan tidak luntur." },
  { name: "Sprei Beverly Hilton Light Grey",  type: "Katun Modal High Density",     category: "bedcover", size: "160 × 200 × 30", price: 180000, color: "#b9b4ae", badge: "Beverly Home",             desc: "Katun modal kerapatan tinggi dengan teknologi cool touch, eco-friendly dan lembut." },
  { name: "Sprei Beverly Roland Grey & Navy", type: "Katun Modal High Density",     category: "bedcover", size: "200 × 200 × 35", price: 195000, color: "#7c7f86", badge: "Beverly Home",             desc: "Motif modern geometric katun modal dingin, lembut halus mewah terbukti awet." },
  { name: "Bedcover Set Verona Cream",        type: "Bedcover Set Microfiber",      category: "bedcover", size: "180 × 200 × 30", price: 320000, color: "#d9ccb4", badge: "Set Lengkap",              desc: "Bedcover set 5 in 1 bahan microfiber lembut, tebal dan hangat." },
  { name: "Bedcover Set Milano Navy",         type: "Bedcover Set Microfiber",      category: "bedcover", size: "200 × 200 × 35", price: 340000, color: "#2f4469", badge: "Set Lengkap",              desc: "Bedcover set warna navy dengan jahitan quilting rapi dan awet." },
  { name: "Sprei Katun Asterina Rose",        type: "Sprei Katun MicroSilk Tencel", category: "sprei",    size: "160 × 200 × 30", price: 185000, color: "#d6a1a0", badge: "Premium MicroSilk Tencel", desc: "Warna rose lembut yang menenangkan, tekstur halus seperti sutera." },
  { name: "Sprei Katun Asterina Charcoal",    type: "Sprei Katun MicroSilk Tencel", category: "sprei",    size: "180 × 200 × 35", price: 195000, color: "#4b4f55", badge: "Premium MicroSilk Tencel", desc: "Warna charcoal modern dengan kain adem dan tidak mudah kusut." },
];

/* =========================================
   2. STATE & ELEMEN DOM
   ========================================= */
const ITEMS_PER_PAGE = 8;
const state = { search: "", category: "all", size: "all", page: 1 };

const grid        = document.getElementById("productGrid");
const pagination  = document.getElementById("pagination");
const searchInput = document.getElementById("searchInput");
const sizeFilter  = document.getElementById("sizeFilter");
const chips       = document.querySelectorAll(".chip");

/* =========================================
   3. FUNGSI BANTU
   ========================================= */
const formatRupiah = (n) => "Rp " + n.toLocaleString("id-ID");

function getFilteredProducts() {
  const keyword = state.search.toLowerCase();
  return products.filter((p) => {
    const matchText = (p.name + " " + p.desc + " " + p.type).toLowerCase().includes(keyword);
    const matchCategory = state.category === "all" || p.category === state.category;
    const matchSize = state.size === "all" || p.size === state.size;
    return matchText && matchCategory && matchSize;
  });
}

/* =========================================
   4. RENDER
   ========================================= */
function createCard(p) {
  return `
    <article class="card">
      <div class="card__image" style="--img-color: ${p.color}">
        <span class="card__badge">★ ${p.badge}</span>
      </div>
      <div class="card__body">
        <div class="card__meta">
          <span class="card__type">${p.type}</span>
          <span class="card__size">${p.size}</span>
        </div>
        <h3 class="card__title">${p.name}</h3>
        <p class="card__desc">${p.desc}</p>
        <div class="card__footer">
          <div>
            <div class="card__label">Harga mulai</div>
            <div class="card__price">${formatRupiah(p.price)}</div>
          </div>
          <button class="btn" data-name="${p.name}">Lihat Detail</button>
        </div>
      </div>
    </article>`;
}

function renderProducts() {
  const list = getFilteredProducts();
  const totalPages = Math.max(1, Math.ceil(list.length / ITEMS_PER_PAGE));
  state.page = Math.min(state.page, totalPages);

  const start = (state.page - 1) * ITEMS_PER_PAGE;
  const pageItems = list.slice(start, start + ITEMS_PER_PAGE);

  grid.innerHTML = pageItems.length
    ? pageItems.map(createCard).join("")
    : `<p class="empty">Produk tidak ditemukan. Coba kata kunci atau filter lain.</p>`;

  renderPagination(totalPages);
}

function renderPagination(totalPages) {
  let html = `<button class="page-btn" data-page="${state.page - 1}" ${state.page === 1 ? "disabled" : ""} aria-label="Sebelumnya">‹</button>`;
  for (let i = 1; i <= totalPages; i++) {
    html += `<button class="page-btn ${i === state.page ? "page-btn--active" : ""}" data-page="${i}">${i}</button>`;
  }
  html += `<button class="page-btn" data-page="${state.page + 1}" ${state.page === totalPages ? "disabled" : ""} aria-label="Berikutnya">›</button>`;
  pagination.innerHTML = html;
}

/* =========================================
   5. EVENT LISTENER
   ========================================= */
searchInput.addEventListener("input", (e) => {
  state.search = e.target.value;
  state.page = 1;
  renderProducts();
});

chips.forEach((chip) => {
  chip.addEventListener("click", () => {
    chips.forEach((c) => c.classList.remove("chip--active"));
    chip.classList.add("chip--active");
    state.category = chip.dataset.category;
    state.page = 1;
    renderProducts();
  });
});

sizeFilter.addEventListener("change", (e) => {
  state.size = e.target.value;
  state.page = 1;
  renderProducts();
});

pagination.addEventListener("click", (e) => {
  const btn = e.target.closest(".page-btn");
  if (!btn || btn.disabled) return;
  state.page = Number(btn.dataset.page);
  renderProducts();
  window.scrollTo({ top: 0, behavior: "smooth" });
});

grid.addEventListener("click", (e) => {
  const btn = e.target.closest(".btn");
  if (btn) alert("Detail produk: " + btn.dataset.name); // ganti dengan link halaman detail
});

/* =========================================
   6. JALANKAN
   ========================================= */
renderProducts();